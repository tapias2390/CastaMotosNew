<?php

declare(strict_types=1);

namespace App\Application\UseCases\Cart;

use App\Domain\Repositories\CartRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class AddCartItemUseCase
{
    public function __construct(
        private CartRepositoryInterface $carts,
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services
    ) {
    }

    public function handle(int $cartId, ?int $productId, ?int $serviceId, int $quantity): void
    {
        if ($productId !== null) {
            $this->addProduct($cartId, $productId, $quantity);
            return;
        }

        $this->addService($cartId, $serviceId, $quantity);
    }

    private function addProduct(int $cartId, int $productId, int $quantity): void
    {
        $product = $this->products->find($productId);
        if ($product === null || $product['status'] !== 'active') {
            throw new NotFoundException('Producto no encontrado.');
        }

        $existing = $this->carts->findExistingItem($cartId, $productId, null);
        $newQuantity = $quantity + ($existing !== null ? (int) $existing['quantity'] : 0);

        if ($newQuantity > (int) $product['stock']) {
            throw new ValidationException('No fue posible agregar el producto.', [
                'quantity' => ['La cantidad solicitada supera el stock disponible.'],
            ]);
        }

        if ($existing !== null) {
            $this->carts->updateItemQuantity((int) $existing['id'], $newQuantity);
        } else {
            $this->carts->addItem($cartId, $productId, null, $quantity, (float) $product['price']);
        }
    }

    private function addService(int $cartId, int $serviceId, int $quantity): void
    {
        $service = $this->services->find($serviceId);
        if ($service === null || $service['status'] !== 'active') {
            throw new NotFoundException('Servicio no encontrado.');
        }

        $existing = $this->carts->findExistingItem($cartId, null, $serviceId);

        if ($existing !== null) {
            $this->carts->updateItemQuantity((int) $existing['id'], (int) $existing['quantity'] + $quantity);
        } else {
            $this->carts->addItem($cartId, null, $serviceId, $quantity, (float) $service['price']);
        }
    }
}
