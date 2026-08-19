<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface SupplierRepositoryInterface
{
    public function list(bool $includeInactive = false): array;

    public function find(int $id): ?array;

    public function exists(int $id): bool;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
