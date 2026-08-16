<?php

declare(strict_types=1);

namespace App\Application\UseCases\Cart;

use App\Domain\Repositories\CartRepositoryInterface;
use App\Exceptions\NotFoundException;

final class RemoveCartItemUseCase
{
    public function __construct(private CartRepositoryInterface $carts)
    {
    }

    public function handle(int $cartId, int $itemId): void
    {
        if (!$this->carts->itemBelongsToCart($itemId, $cartId)) {
            throw new NotFoundException('Ítem de carrito no encontrado.');
        }

        $this->carts->removeItem($itemId);
    }
}
