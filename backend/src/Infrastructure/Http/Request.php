<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Representa la petición HTTP entrante. Centraliza el acceso a método,
 * ruta, query, body y headers para no depender de superglobales dispersas.
 */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;

    public function __construct(string $method, string $path, array $query, array $body, array $headers)
    {
        $this->method = $method;
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // La ruta llega vía ?__route= gracias a la reescritura del .htaccess raíz,
        // así no dependemos de adivinar el "base path" del despliegue (subcarpeta o vhost).
        $path = '/' . trim((string) ($_GET['__route'] ?? ''), '/');

        $query = $_GET;
        unset($query['__route']);

        $body = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '', true);
            $body = is_array($decoded) ? $decoded : [];
        } else {
            $body = $_POST;
        }

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];

        return new self($method, $path, $query, $body, $headers);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->body;
        }

        return $this->body[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        foreach ($this->headers as $name => $value) {
            if (strcasecmp($name, $key) === 0) {
                return $value;
            }
        }

        return $default;
    }
}
