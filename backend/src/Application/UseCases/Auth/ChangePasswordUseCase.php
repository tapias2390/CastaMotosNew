<?php

declare(strict_types=1);

namespace App\Application\UseCases\Auth;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Exceptions\ValidationException;

final class ChangePasswordUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function handle(User $authenticatedUser, string $currentPassword, string $newPassword): void
    {
        if (!password_verify($currentPassword, $authenticatedUser->passwordHash)) {
            throw new ValidationException('No fue posible cambiar la contraseña.', [
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $this->users->updatePassword($authenticatedUser->id, password_hash($newPassword, PASSWORD_DEFAULT));
    }
}
