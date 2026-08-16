<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Exceptions\AppException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Middleware;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Persistence\PdoUserRepository;

/**
 * Exige que el usuario autenticado (debe correr después de AuthMiddleware)
 * tenga el permiso indicado. Queda lista para los paneles admin/vendedor
 * de las fases 9 y 10, aunque en esta fase todavía no se usa en rutas.
 */
final class RequirePermissionMiddleware implements Middleware
{
    private UserRepositoryInterface $users;

    public function __construct(private string $permission, ?UserRepositoryInterface $users = null)
    {
        $this->users = $users ?? new PdoUserRepository(Connection::get());
    }

    public function handle(Request $request): Request
    {
        /** @var User|null $user */
        $user = $request->attribute('auth_user');

        if ($user === null) {
            throw new class ('Debes iniciar sesión para continuar.') extends AppException {
                protected int $statusCode = 401;
            };
        }

        $permissions = $this->users->permissionsForUser($user->id);

        if (!in_array($this->permission, $permissions, true)) {
            throw new class ('No tienes permisos para realizar esta acción.') extends AppException {
                protected int $statusCode = 403;
            };
        }

        return $request;
    }
}
