<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoOrderRepository;

/**
 * Módulo de reservas de servicios (sección 12, permiso manage-orders — usa el
 * mismo permiso que pedidos porque cada reserva ES un pedido con un servicio
 * agendado, no una entidad separada). El cambio de estado de una reserva
 * reutiliza PUT /api/admin/orders/{orderNumber}/status (AdminOrderController):
 * no hace falta un endpoint aparte para eso.
 */
final class AdminReservationController
{
    private PdoOrderRepository $orders;

    public function __construct()
    {
        $this->orders = new PdoOrderRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->orders->paginateReservationsForAdmin($request->query()));
    }
}
