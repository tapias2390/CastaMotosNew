<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Exceptions\ValidationException;

final class VerifyEmailUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function handle(string $rawToken): void
    {
        $user = $this->users->findByEmailVerificationToken(hash('sha256', $rawToken));

        if ($user === null) {
            throw new ValidationException('No fue posible verificar el correo.', [
                'token' => ['El enlace de verificación es inválido o expiró.'],
            ]);
        }

        $this->users->markEmailVerified($user->id);
    }
}
