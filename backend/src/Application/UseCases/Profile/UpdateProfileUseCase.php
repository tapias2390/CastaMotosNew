<?php

declare(strict_types=1);

namespace App\Application\UseCases\Profile;

use App\Domain\Repositories\UserRepositoryInterface;

final class UpdateProfileUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    public function handle(int $userId, array $data): void
    {
        $this->users->updateProfile($userId, $data);
    }
}
