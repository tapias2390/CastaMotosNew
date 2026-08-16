<?php

declare(strict_types=1);

namespace App\Application\UseCases\Cart;

use App\Domain\Repositories\CartRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class UpdateCartItemUseCase
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products
    ) {
    }

    public function handle(int $cartId, int $itemId, int $quantity): void
    {
        if (!$this->carts->itemBelongsToCart($itemId, $cartId)) {
            throw new NotFoundException('Ítem de carrito no encontrado.');
        }

        $item = $this->carts->findItem($itemId);

        if ($item['product_id'] !== null) {
            $product = $this->products->find((int) $item['product_id']);

            if ($product === null || $product['status'] !== 'active') {
                throw new NotFoundException('Producto no encontrado.');
            }

            if ($quantity > (int) $product['stock']) {
                throw new ValidationException('No fue posible actualizar la cantidad.', [
                    'quantity' => ['La cantidad solicitada supera el stock disponible.'],
                ]);
            }
        }

        $this->carts->updateItemQuantity($itemId, $quantity);
    }
}
