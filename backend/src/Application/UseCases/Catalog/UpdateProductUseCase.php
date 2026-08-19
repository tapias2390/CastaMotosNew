<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\SlugGenerator;
use App\Domain\Repositories\BrandRepositoryInterface;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\SupplierRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private CategoryRepositoryInterface $categories,
        private BrandRepositoryInterface $brands,
        private SupplierRepositoryInterface $suppliers
    ) {
    }

    public function handle(int $id, array $data): void
    {
        $current = $this->products->find($id);
        if ($current === null) {
            throw new NotFoundException('Producto no encontrado.');
        }

        // Un SKU vacío en la edición no debe interpretarse como "generar uno
        // nuevo" (eso solo aplica al crear, sección 10) — cambiar el
        // identificador de un producto ya existente no debe pasar por
        // accidente por dejar el campo en blanco, se conserva el actual.
        if (empty($data['sku'])) {
            $data['sku'] = $current['sku'];
        }

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

        if ($this->products->existsBySku($data['sku'], $id)) {
            $errors['sku'] = ['Ya existe un producto con este SKU.'];
        }

        if (!empty($errors)) {
            throw new ValidationException('Los datos enviados no son válidos.', $errors);
        }

        $data['slug'] = SlugGenerator::unique(
            $data['name'],
            fn (string $slug) => $this->products->existsBySlug($slug, $id)
        );

        $this->products->update($id, $data);
        $this->products->initializeInventory($id, (int) ($data['stock'] ?? 0), (int) ($data['min_stock'] ?? 0));
    }
}
