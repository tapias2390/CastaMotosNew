<?php

declare(strict_types=1);

/**
 * Configuración general de la aplicación.
 * Los valores sensibles siempre se leen desde el archivo .env, nunca se hardcodean aquí.
 */
return [
    'name' => $_ENV['APP_NAME'] ?? 'CASTAMOTO',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Bogota',

    'cors' => [
        // "*" solo debe usarse en desarrollo. En producción definir dominios específicos en .env.
        'allowed_origins' => $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*',
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? '',
        'ttl' => (int) ($_ENV['JWT_TTL'] ?? 3600),
    ],

    'mail' => [
        'host' => $_ENV['MAIL_HOST'] ?? '',
        'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@castamoto.local',
        'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'CASTAMOTO',
    ],

    'payment' => [
        'provider' => $_ENV['PAYMENT_PROVIDER'] ?? '',
        'public_key' => $_ENV['PAYMENT_PUBLIC_KEY'] ?? '',
        'secret_key' => $_ENV['PAYMENT_SECRET_KEY'] ?? '',
    ],

    'ai' => [
        'provider' => $_ENV['AI_PROVIDER'] ?? '',
        'api_key' => $_ENV['AI_API_KEY'] ?? '',
    ],

    'push' => [
        'provider' => $_ENV['PUSH_PROVIDER'] ?? '',
    ],

    'admin' => [
        'name' => $_ENV['ADMIN_NAME'] ?? 'Administrador',
        'email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@castamoto.local',
        'password' => $_ENV['ADMIN_PASSWORD'] ?? null,
    ],
];
