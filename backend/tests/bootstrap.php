<?php

declare(strict_types=1);

use App\Infrastructure\Config\Config;
use App\Infrastructure\Logging\Logger;
use App\Infrastructure\Mail\Mailer;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = dirname(__DIR__);

Config::boot($basePath);
Logger::boot($basePath);
Mailer::boot($basePath);
