<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

/**
 * Cliente SMTP mínimo (EHLO/STARTTLS/AUTH LOGIN/DATA) por sockets puros, sin
 * dependencias nuevas de Composer (mismo criterio que Swagger UI vía CDN —
 * ver README: "sin dependencias nuevas"). Soporta lo que necesita cualquier
 * proveedor real estándar (Gmail, SendGrid, Mailgun, el SMTP del propio
 * hosting cPanel): puerto 587 con STARTTLS, o 465 con TLS implícito desde
 * la conexión. `Mailer::send()` es el único que la usa.
 */
final class SmtpMailer
{
    private $socket;

    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password
    ) {
    }

    /**
     * @throws \RuntimeException si el servidor rechaza cualquier paso (código
     * SMTP fuera del rango esperado) o falla la conexión/TLS.
     */
    public function send(string $fromAddress, string $fromName, string $to, string $subject, string $html): void
    {
        $this->connect();

        try {
            $this->ehlo();

            if ($this->port !== 465) {
                $this->command("STARTTLS\r\n", 220);
                $this->enableCrypto();
                $this->ehlo();
            }

            if ($this->username !== '') {
                $this->command("AUTH LOGIN\r\n", 334);
                $this->command(base64_encode($this->username) . "\r\n", 334);
                $this->command(base64_encode($this->password) . "\r\n", 235);
            }

            $this->command('MAIL FROM:<' . $fromAddress . ">\r\n", 250);
            $this->command('RCPT TO:<' . $to . ">\r\n", [250, 251]);
            $this->command("DATA\r\n", 354);
            $this->command($this->buildMessage($fromAddress, $fromName, $to, $subject, $html) . "\r\n.\r\n", 250);
            $this->command("QUIT\r\n", 221);
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
        }
    }

    private function connect(): void
    {
        // Puerto 465 (SMTPS): TLS implícito desde el primer byte de la conexión,
        // a diferencia de 587/25 donde se empieza en texto plano y se sube a TLS
        // recién con STARTTLS (ver send()).
        $transport = $this->port === 465 ? 'ssl' : 'tcp';
        $errno = 0;
        $errstr = '';

        $this->socket = @stream_socket_client(
            "{$transport}://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if ($this->socket === false) {
            throw new \RuntimeException("No fue posible conectar a {$this->host}:{$this->port} ({$errstr}).");
        }

        stream_set_timeout($this->socket, 15);
        $this->readResponse(220);
    }

    private function ehlo(): void
    {
        // El nombre de dominio en EHLO es informativo para el servidor (algunos
        // lo validan contra reverse DNS, la mayoría no) — "localhost" es
        // aceptado por Gmail y los proveedores estándar.
        $this->command("EHLO localhost\r\n", 250);
    }

    private function enableCrypto(): void
    {
        $method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (@stream_socket_enable_crypto($this->socket, true, $method) !== true) {
            throw new \RuntimeException('No fue posible negociar TLS con el servidor SMTP (STARTTLS).');
        }
    }

    /** @param int|int[] $expectedCode */
    private function command(string $line, $expectedCode): string
    {
        if (fwrite($this->socket, $line) === false) {
            throw new \RuntimeException('Fallo al escribir en el socket SMTP.');
        }

        return $this->readResponse($expectedCode);
    }

    /** @param int|int[] $expectedCode */
    private function readResponse($expectedCode): string
    {
        $expected = is_array($expectedCode) ? $expectedCode : [$expectedCode];
        $full = '';
        $code = 0;

        // Una respuesta SMTP puede venir en varias líneas: "250-algo" en las
        // intermedias y "250 algo" (espacio, no guion) en la última.
        do {
            $line = fgets($this->socket, 515);
            if ($line === false) {
                throw new \RuntimeException('El servidor SMTP cerró la conexión inesperadamente.');
            }
            $full .= $line;
            $code = (int) substr($line, 0, 3);
        } while (isset($line[3]) && $line[3] === '-');

        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException("El servidor SMTP respondió con un error: " . trim($full));
        }

        return $full;
    }

    private function buildMessage(string $fromAddress, string $fromName, string $to, string $subject, string $html): string
    {
        // RFC 2047: el asunto puede traer tildes/ñ (español) — se codifica en
        // UTF-8/base64 para que los clientes de correo lo muestren bien.
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        $headers = [
            'Date: ' . date('r'),
            "From: {$encodedFromName} <{$fromAddress}>",
            "To: <{$to}>",
            "Subject: {$encodedSubject}",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        // Dot-stuffing (RFC 5321): una línea que empieza con "." se duplica,
        // porque una línea con un solo "." es la marca de fin de DATA.
        $body = preg_replace('/^\./m', '..', $html);

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }
}
