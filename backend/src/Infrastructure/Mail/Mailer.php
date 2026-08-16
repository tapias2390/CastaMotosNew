<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Logging\Logger;

/**
 * Envío de correo con driver intercambiable (mismo patrón que AI_PROVIDER /
 * PAYMENT_PROVIDER de la Fase 1): si MAIL_HOST está vacío (entorno local),
 * el correo se escribe en /storage/mails/ en vez de enviarse de verdad, para
 * poder probar los flujos de verificación/recuperación sin un SMTP real.
 * Cambiar a un proveedor real (PHPMailer, Symfony Mailer, etc.) más adelante
 * no debería requerir tocar los casos de uso que llaman a Mailer::send().
 */
final class Mailer
{
    private static ?string $basePath = null;

    public static function boot(string $basePath): void
    {
        self::$basePath = $basePath;
    }

    public static function send(string $to, string $subject, string $html, string $template, ?int $userId = null): bool
    {
        $host = (string) Config::get('app.mail.host');
        $status = 'sent';
        $errorMessage = null;

        try {
            if ($host === '') {
                self::writeToLogDriver($to, $subject, $html);
            } else {
                self::sendViaNativeMail($to, $subject, $html);
            }
        } catch (\Throwable $e) {
            $status = 'failed';
            $errorMessage = $e->getMessage();
            Logger::error('Fallo al enviar correo', ['to' => $to, 'template' => $template, 'error' => $errorMessage]);
        }

        self::logAttempt($userId, $to, $subject, $template, $status, $errorMessage);

        return $status === 'sent';
    }

    private static function writeToLogDriver(string $to, string $subject, string $html): void
    {
        $dir = (self::$basePath ?? dirname(__DIR__, 3)) . '/storage/mails';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = sprintf('%s_%s.html', date('Y-m-d_His'), preg_replace('/[^a-z0-9]+/i', '-', $to));
        file_put_contents($dir . '/' . $filename, "<!-- Para: {$to} | Asunto: {$subject} -->\n" . $html);
    }

    private static function sendViaNativeMail(string $to, string $subject, string $html): void
    {
        $fromAddress = (string) Config::get('app.mail.from_address');
        $fromName = (string) Config::get('app.mail.from_name');

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$fromName} <{$fromAddress}>\r\n";

        $sent = mail($to, $subject, $html, $headers);

        if (!$sent) {
            throw new \RuntimeException('mail() devolvió false al intentar enviar el correo.');
        }
    }

    private static function logAttempt(
        ?int $userId,
        string $to,
        string $subject,
        string $template,
        string $status,
        ?string $errorMessage
    ): void {
        $stmt = Connection::get()->prepare(
            'INSERT INTO email_logs (user_id, to_email, subject, template, status, error_message)
             VALUES (:user_id, :to_email, :subject, :template, :status, :error_message)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'to_email' => $to,
            'subject' => $subject,
            'template' => $template,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
