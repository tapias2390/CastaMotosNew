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

    /**
     * Horarios ya reservados de este servicio en un día puntual (sección 12) —
     * público, para que el selector de fecha/hora del frontend pueda mostrar
     * qué horas ya no están disponibles ANTES de que el usuario intente
     * reservar (createFromCheckout() ya rechaza el choque, esto solo evita
     * mostrarle una opción que de todas formas va a fallar).
     *
     * @return string[] Horas en formato "H:i" (ej. "10:00"), un pedido CANCELADO no cuenta.
     */
    public function bookedTimesForDate(int $serviceId, string $date): array;
}
