<?php

declare(strict_types=1);

use App\Infrastructure\Http\Router;
use App\Presentation\Controllers\HealthController;

/**
 * Registro central de rutas de la API. Las rutas de negocio (auth, productos,
 * pedidos, etc.) se irán agregando aquí en las siguientes fases.
 *
 * @var Router $router
 */

$router->get('api/health', [HealthController::class, 'index']);
