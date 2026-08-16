<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Respuesta HTTP en formato JSON, con la envoltura consistente
 * definida en la sección 37 del prompt maestro: success/message/errors.
 */
final class Response
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): void
    {
        self::send($status, [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        self::send($status, [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    private static function send(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
