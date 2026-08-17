<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoDashboardRepository;

/**
 * Resumen del negocio para la pestaña "Resumen" del panel admin (sección 28).
 * Un solo endpoint agregado en vez de varios: el dashboard necesita todos
 * estos números juntos en la misma carga de página.
 */
final class DashboardController
{
    private PdoDashboardRepository $dashboard;

    public function __construct()
    {
        $this->dashboard = new PdoDashboardRepository(Connection::get());
    }

    public function summary(Request $request): void
    {
        Response::success($this->dashboard->summary());
    }
}
