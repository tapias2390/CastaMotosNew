<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;

/**
 * Evita repetir en cada controller la misma comprobación "¿el usuario
 * autenticado (si lo hay) tiene este permiso?", usada por las rutas públicas
 * de catálogo que muestran más datos a quien puede gestionarlo.
 */
final class AccessChecker
{
    public static function can(?User $user, string $permission, UserRepositoryInterface $users): bool
    {
        if ($user === null) {
            return false;
        }

        return in_array($permission, $users->permissionsForUser($user->id), true);
    }
}
