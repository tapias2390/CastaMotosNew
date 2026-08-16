<?php

declare(strict_types=1);

namespace App\Application\UseCases\Favorite;

use App\Domain\Repositories\FavoriteRepositoryInterface;

/**
 * Quitar un favorito es idempotente (si no existía, simplemente no hace
 * nada) — no necesita validar que el producto/servicio exista todavía.
 */
final class RemoveFavoriteUseCase
{
    public function __construct(private FavoriteRepositoryInterface $favorites)
    {
    }

    public function handle(int $userId, string $type, int $itemId): void
    {
        $this->favorites->remove($userId, $type, $itemId);
    }
}
