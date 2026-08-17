<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface ServiceRepositoryInterface
{
    public function paginate(array $filters, bool $includeAllStatuses = false): array;

    public function findBySlug(string $slug, bool $includeAllStatuses = false): ?array;

    public function find(int $id): ?array;

    public function exists(int $id): bool;

    public function existsBySlug(string $slug, ?int $excludeId = null): bool;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function addImage(int $serviceId, string $path): int;

    public function countImages(int $serviceId): int;

    public function deleteImage(int $imageId): void;

    public function imageBelongsToService(int $imageId, int $serviceId): bool;
}
