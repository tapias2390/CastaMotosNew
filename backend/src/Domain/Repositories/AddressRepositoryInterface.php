<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface AddressRepositoryInterface
{
    public function listForUser(int $userId): array;

    public function find(int $id): ?array;

    public function belongsToUser(int $addressId, int $userId): bool;

    public function create(int $userId, array $data): int;

    public function update(int $addressId, array $data): void;

    public function delete(int $addressId): void;

    /**
     * Marca la dirección como principal y desmarca cualquier otra del mismo usuario.
     */
    public function setPrimary(int $userId, int $addressId): void;
}
