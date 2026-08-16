<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;

/**
 * Endpoint de verificación de salud de la API. Útil para confirmar que
 * el enrutamiento, el Kernel y las respuestas JSON funcionan de punta a punta.
 */
final class HealthController
{
    public function index(Request $request): void
    {
        Response::success([
            'version' => '1.0.0',
            'env' => \App\Infrastructure\Config\Config::get('app.env'),
        ], 'CASTAMOTO API operativa');
    }
}
