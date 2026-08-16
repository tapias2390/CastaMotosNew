<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Infrastructure\Config\Config;

/**
 * Configura los headers CORS según los orígenes permitidos en .env.
 * En producción, CORS_ALLOWED_ORIGINS debe listar dominios específicos, no "*".
 */
final class CorsMiddleware
{
    public static function apply(): void
    {
        $allowedOrigins = Config::get('app.cors.allowed_origins', '*');

        header('Access-Control-Allow-Origin: ' . $allowedOrigins);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
    }
}
