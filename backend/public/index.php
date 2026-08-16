<?php

declare(strict_types=1);

/**
 * Front controller de la API. Todo request bajo /api/* (reescrito desde el
 * .htaccess de la raíz del proyecto) termina aquí.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Infrastructure\Config\Config;
use App\Infrastructure\Http\Kernel;
use App\Infrastructure\Http\Router;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Mail\Mailer;

$basePath = dirname(__DIR__);

Config::boot($basePath);
Logger::boot($basePath);
Mailer::boot($basePath);

date_default_timezone_set((string) Config::get('app.timezone', 'America/Bogota'));

$router = new Router();
require $basePath . '/routes/api.php';

(new Kernel())->handle($router);
