<?php

declare(strict_types=1);

use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Mail\Mailer;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

// Los tests (ej. AuthFlowIntegrationTest) registran usuarios falsos de
// verdad contra la base local y disparan Mailer::send() real — sin esto,
// en cuanto backend/.env tiene credenciales SMTP reales cargadas (para
// probar envío real en desarrollo), cada corrida de tests manda correos
// de verdad a direcciones inventadas (@castamoto.local), que Gmail rebota
// a la bandeja real. Se fuerza acá el driver "log" (sin salida real) ANTES
// de Config::boot(): Dotenv::createImmutable() nunca pisa una variable que
// ya esté seteada, así que estos putenv() ganan pase lo que pase en .env.
putenv('MAIL_HOST=');
putenv('MAIL_USERNAME=');
putenv('MAIL_PASSWORD=');
$_ENV['MAIL_HOST'] = '';
$_ENV['MAIL_USERNAME'] = '';
$_ENV['MAIL_PASSWORD'] = '';

Config::boot($basePath);
Logger::boot($basePath);
Mailer::boot($basePath);
