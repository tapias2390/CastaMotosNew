<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Repositories\PasswordResetRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Exceptions\ValidationException;

final class ResetPasswordUseCase
{
    public function __construct(
        private UserRepositoryInterface $users,
        private PasswordResetRepositoryInterface $passwordResets
    ) {
    }

    public function handle(string $rawToken, string $newPassword): void
    {
        $record = $this->passwordResets->findValidByTokenHash(hash('sha256', $rawToken));

        if ($record === null) {
            throw new ValidationException('No fue posible restablecer la contraseña.', [
                'token' => ['El enlace de recuperación es inválido o expiró.'],
            ]);
        }

        $user = $this->users->findByEmail($record['email']);
        if ($user === null) {
            throw new ValidationException('No fue posible restablecer la contraseña.', [
                'token' => ['El enlace de recuperación es inválido o expiró.'],
            ]);
        }

        $this->users->updatePassword($user->id, password_hash($newPassword, PASSWORD_DEFAULT));
        $this->passwordResets->markUsed($record['id']);
    }
}
