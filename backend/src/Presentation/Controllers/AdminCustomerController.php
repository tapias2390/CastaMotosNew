<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoUserRepository;

/**
 * Listado de clientes para el panel admin (sección 28, permiso manage-users).
 * Este marketplace no compra inventario a proveedores externos (los
 * productos son propios de CASTAMOTO o de una tienda vendedora del propio
 * marketplace) — el equivalente a "proveedor" aquí es una tienda vendedora
 * (tabla stores, Fase 1), cuya gestión completa es la Fase 10 del prompt
 * maestro ("dashboard vendedor + gestión de tiendas"), todavía no construida.
 */
final class AdminCustomerController
{
    private PdoUserRepository $users;

    public function __construct()
    {
        $this->users = new PdoUserRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->users->paginateForAdmin($request->query()));
    }
}
