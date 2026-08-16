<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Domain\Repositories\ProductRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class SyncProductVariantsUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function handle(int $productId, array $variants): void
    {
        if (!$this->products->exists($productId)) {
            throw new NotFoundException('Producto no encontrado.');
        }

        foreach ($variants as $index => $variant) {
            if (empty($variant['name'])) {
                throw new ValidationException('Los datos enviados no son válidos.', [
                    "variants.{$index}.name" => ['El nombre de la variante es obligatorio.'],
                ]);
            }
        }

        $this->products->replaceVariants($productId, $variants);
    }
}
