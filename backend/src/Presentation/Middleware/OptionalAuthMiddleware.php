<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Middleware;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Persistence\PdoUserRepository;

/**
 * Igual que AuthMiddleware, pero para rutas públicas que cambian su respuesta
 * si hay un usuario autenticado (ej. listados de catálogo que muestran
 * borradores/inactivos a quien tenga el permiso de gestión correspondiente).
 * Nunca lanza 401: si no hay token o es inválido, simplemente continúa sin
 * adjuntar "auth_user".
 */
final class OptionalAuthMiddleware implements Middleware
{
    private UserRepositoryInterface $users;

    public function __construct(?UserRepositoryInterface $users = null)
    {
        $this->users = $users ?? new PdoUserRepository(Connection::get());
    }

    public function handle(Request $request): Request
    {
        $header = (string) $request->header('Authorization', '');

        if (!str_starts_with($header, 'Bearer ')) {
            return $request;
        }

        try {
            $claims = JwtService::verify(substr($header, 7));
            $user = $this->users->findById($claims['sub']);

            if ($user !== null && $user->status === 'active') {
                return $request->withAttribute('auth_user', $user);
            }
        } catch (\Throwable) {
            // Token ausente/expirado/inválido: se ignora, la ruta sigue siendo pública.
        }

        return $request;
    }
}
