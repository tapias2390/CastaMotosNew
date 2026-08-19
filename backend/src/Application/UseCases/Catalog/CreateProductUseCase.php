<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\SkuGenerator;
use App\Application\Support\SlugGenerator;
use App\Domain\Repositories\BrandRepositoryInterface;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\SupplierRepositoryInterface;
use App\Exceptions\ValidationException;

final class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private CategoryRepositoryInterface $categories,
        private BrandRepositoryInterface $brands,
        private SupplierRepositoryInterface $suppliers
    ) {
    }

    public function handle(array $data): int
    {
        $this->assertReferencesExist($data);

        $data['slug'] = SlugGenerator::unique($data['name'], fn (string $slug) => $this->products->existsBySlug($slug));

        // SKU dinámico (sección 10): si el vendedor no escribe uno propio, se
        // genera automáticamente a partir de la categoría, garantizado único
        // (mismo criterio que existsBySku() ya usaba para el chequeo manual).
        if (empty($data['sku'])) {
            $category = $this->categories->find((int) $data['category_id']);
            $categoryName = $category['name'] ?? 'Producto';
            $data['sku'] = SkuGenerator::generate($categoryName, fn (string $sku) => $this->products->existsBySku($sku));
        }

        $productId = $this->products->create($data);

        // Inicializa la fila de inventario (sección 25); las reservas reales se
        // conectan en la Fase 6 cuando exista carrito/pedidos.
        $this->products->initializeInventory($productId, (int) ($data['stock'] ?? 0), (int) ($data['min_stock'] ?? 0));

        return $productId;
    }

    private function assertReferencesExist(array $data): void
    {
        $errors = [];

        if (!$this->categories->exists((int) $data['category_id'])) {
            $errors['category_id'] = ['La categoría indicada no existe.'];
        }

        if (!empty($data['brand_id']) && !$this->brands->exists((int) $data['brand_id'])) {
            $errors['brand_id'] = ['La marca indicada no existe.'];
        }

        if (!empty($data['supplier_id']) && !$this->suppliers->exists((int) $data['supplier_id'])) {
            $errors['supplier_id'] = ['El proveedor indicado no existe.'];
        }

        // Si no se escribió SKU, se genera uno automático más abajo (garantizado
        // único por SkuGenerator) — nada que validar todavía en ese caso.
        if (!empty($data['sku']) && $this->products->existsBySku($data['sku'])) {
            $errors['sku'] = ['Ya existe un producto con este SKU.'];
        }

        if (!empty($errors)) {
            throw new ValidationException('Los datos enviados no son válidos.', $errors);
        }
    }
}
