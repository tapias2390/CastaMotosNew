<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Domain\Repositories\ProductRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class SyncProductAttributesUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function handle(int $productId, array $attributes): void
    {
        if (!$this->products->exists($productId)) {
            throw new NotFoundException('Producto no encontrado.');
        }

        foreach ($attributes as $index => $attribute) {
            if (empty($attribute['name']) || !isset($attribute['value']) || $attribute['value'] === '') {
                throw new ValidationException('Los datos enviados no son válidos.', [
                    "attributes.{$index}" => ['Cada atributo requiere "name" y "value".'],
                ]);
            }
        }

        $this->products->replaceAttributes($productId, $attributes);
    }
}
