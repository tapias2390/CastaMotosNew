<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

interface FavoriteRepositoryInterface
{
    public function add(int $userId, string $type, int $itemId): void;

    public function remove(int $userId, string $type, int $itemId): void;

    public function isFavorite(int $userId, string $type, int $itemId): bool;

    /**
     * Lista los favoritos del usuario con los datos básicos del producto/servicio
     * (join dinámico según el tipo), listos para mostrar en el dashboard (sección 8).
     */
    public function listForUser(int $userId): array;
}
