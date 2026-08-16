<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface PasswordResetRepositoryInterface
{
    public function create(string $email, string $tokenHash, string $expiresAt): void;

    /**
     * @return array{id:int, email:string}|null
     */
    public function findValidByTokenHash(string $tokenHash): ?array;

    public function markUsed(int $id): void;
}
