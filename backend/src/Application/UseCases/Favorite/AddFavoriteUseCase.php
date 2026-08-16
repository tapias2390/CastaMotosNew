<?php

declare(strict_types=1);

namespace App\Application\UseCases\Favorite;

use App\Domain\Repositories\FavoriteRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Exceptions\NotFoundException;

final class AddFavoriteUseCase
{
    public function __construct(
        private FavoriteRepositoryInterface $favorites,
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services
    ) {
    }

    public function handle(int $userId, string $type, int $itemId): void
    {
        $exists = $type === 'product' ? $this->products->exists($itemId) : $this->services->exists($itemId);

        if (!$exists) {
            throw new NotFoundException(
                $type === 'product' ? 'Producto no encontrado.' : 'Servicio no encontrado.'
            );
        }

        $this->favorites->add($userId, $type, $itemId);
    }
}
