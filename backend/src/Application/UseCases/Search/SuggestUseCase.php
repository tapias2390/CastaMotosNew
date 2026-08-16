<?php

declare(strict_types=1);

namespace App\Application\UseCases\Search;

use App\Domain\Repositories\BrandRepositoryInterface;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;

/**
 * Autocompletado liviano (sección 14: "Autocompletado", "Sugerencias").
 * Devuelve una lista corta mezclando los cuatro tipos de catálogo.
 */
final class SuggestUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services,
        private CategoryRepositoryInterface $categories,
        private BrandRepositoryInterface $brands
    ) {
    }

    public function handle(string $term, int $limit = 10): array
    {
        $suggestions = [];

        foreach ($this->products->paginate(['search' => $term, 'per_page' => $limit])['data'] as $product) {
            $suggestions[] = ['type' => 'product', 'id' => (int) $product['id'], 'name' => $product['name'], 'slug' => $product['slug']];
        }

        foreach ($this->services->paginate(['search' => $term, 'per_page' => $limit])['data'] as $service) {
            $suggestions[] = ['type' => 'service', 'id' => (int) $service['id'], 'name' => $service['name'], 'slug' => $service['slug']];
        }

        $needle = mb_strtolower($term);

        foreach ($this->flattenCategories($this->categories->tree()) as $category) {
            if (str_contains(mb_strtolower($category['name']), $needle)) {
                $suggestions[] = ['type' => 'category', 'id' => (int) $category['id'], 'name' => $category['name'], 'slug' => $category['slug']];
            }
        }

        foreach ($this->brands->list() as $brand) {
            if (str_contains(mb_strtolower($brand['name']), $needle)) {
                $suggestions[] = ['type' => 'brand', 'id' => (int) $brand['id'], 'name' => $brand['name'], 'slug' => $brand['slug']];
            }
        }

        return array_slice($suggestions, 0, $limit);
    }

    private function flattenCategories(array $tree): array
    {
        $flat = [];
        foreach ($tree as $node) {
            $flat[] = $node;
            $flat = array_merge($flat, $this->flattenCategories($node['children'] ?? []));
        }

        return $flat;
    }
}
