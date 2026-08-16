<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Repositories\LoginHistoryRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Exceptions\UnauthorizedException;
use App\Infrastructure\Auth\JwtService;
use App\Infrastructure\Config\Config;

/**
 * Login con protección contra fuerza bruta (sección 6): tras
 * auth.max_login_attempts fallos consecutivos, la cuenta se bloquea
 * auth.lockout_minutes. Los mensajes de error son genéricos a propósito
 * para no revelar si el correo existe o no en el sistema.
 */
final class LoginUseCase
{
    private const GENERIC_ERROR = 'Correo o contraseña incorrectos.';

    public function __construct(
        private UserRepositoryInterface $users,
        private LoginHistoryRepositoryInterface $loginHistory
    ) {
    }

    public function handle(string $email, string $password, bool $remember, string $ipAddress, string $userAgent): array
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            $this->loginHistory->record(null, $email, $ipAddress, $userAgent, 'failed');
            throw new UnauthorizedException(self::GENERIC_ERROR);
        }

        if ($user->isLocked()) {
            $this->loginHistory->record($user->id, $email, $ipAddress, $userAgent, 'locked');
            throw new UnauthorizedException('Cuenta bloqueada temporalmente por intentos fallidos. Intenta más tarde.');
        }

        if (!password_verify($password, $user->passwordHash)) {
            $maxAttempts = (int) Config::get('app.auth.max_login_attempts', 5);
            $lockoutMinutes = (int) Config::get('app.auth.lockout_minutes', 15);

            $this->users->registerLoginFailure($user->id, $maxAttempts, $lockoutMinutes);
            $this->loginHistory->record($user->id, $email, $ipAddress, $userAgent, 'failed');
            throw new UnauthorizedException(self::GENERIC_ERROR);
        }

        if ($user->status !== 'active') {
            $this->loginHistory->record($user->id, $email, $ipAddress, $userAgent, 'failed');
            throw new UnauthorizedException('Esta cuenta no está activa. Contacta a soporte.');
        }

        $this->users->registerLoginSuccess($user->id);
        $this->loginHistory->record($user->id, $email, $ipAddress, $userAgent, 'success');

        $token = JwtService::issue($user->id, $user->roles, $remember);

        return ['user' => $user, 'token' => $token];
    }
}
