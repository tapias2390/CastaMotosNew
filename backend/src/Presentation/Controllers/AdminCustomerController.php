<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Exceptions\ValidationException;
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
    /** Roles fijos del sistema (sección 7/28) — no son un catálogo editable. */
    private const VALID_ROLES = ['cliente', 'vendedor', 'administrador', 'superadministrador'];

    private PdoUserRepository $users;

    public function __construct()
    {
        $this->users = new PdoUserRepository(Connection::get());
    }

    public function index(Request $request): void
    {
        Response::success($this->users->paginateForAdmin($request->query()));
    }

    /** Cambiar el rol de un usuario (sección 28, permiso manage-roles —
     * separado de manage-users a propósito: no todo el que ve clientes puede
     * ascenderlos a administrador). */
    public function updateRole(Request $request, string $id): void
    {
        $data = Validator::make($request->input(), [
            'role' => 'required|in:' . implode(',', self::VALID_ROLES),
        ])->validate();

        /** @var User $actingUser */
        $actingUser = $request->attribute('auth_user');
        if ($actingUser->id === (int) $id) {
            throw new ValidationException('No fue posible cambiar el rol.', [
                'role' => ['No puedes cambiar tu propio rol.'],
            ]);
        }

        $this->users->setRole((int) $id, $data['role']);

        Response::success(null, 'Rol actualizado correctamente.');
    }
}
