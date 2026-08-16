<?php

declare(strict_types=1);

namespace App\Application\UseCases\Search;

use App\Application\Support\DidYouMeanFinder;
use App\Domain\Repositories\BrandRepositoryInterface;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;

/**
 * Vista previa de búsqueda "global" (sección 14): pocos resultados por tipo
 * (productos, servicios, categorías, marcas). Para explorar un tipo a fondo
 * con todos sus filtros se usan los listados propios (/api/products, etc.).
 */
final class GlobalSearchUseCase
{
    private const PREVIEW_LIMIT = 5;

    public function __construct(
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services,
        private CategoryRepositoryInterface $categories,
        private BrandRepositoryInterface $brands
    ) {
    }

    public function handle(string $term): array
    {
        $products = $this->products->paginate(['search' => $term, 'per_page' => self::PREVIEW_LIMIT])['data'];
        $services = $this->services->paginate(['search' => $term, 'per_page' => self::PREVIEW_LIMIT])['data'];

        $categories = $this->matchByName($this->flattenCategories($this->categories->tree()), $term);
        $brands = $this->matchByName($this->brands->list(), $term);

        $result = [
            'products' => $products,
            'services' => $services,
            'categories' => $categories,
            'brands' => $brands,
        ];

        $totalResults = count($products) + count($services) + count($categories) + count($brands);

        if ($totalResults === 0) {
            $result['did_you_mean'] = DidYouMeanFinder::find($term, $this->candidateNames());
        }

        return $result;
    }

    private function matchByName(array $items, string $term): array
    {
        $needle = mb_strtolower($term);

        $matches = array_values(array_filter(
            $items,
            fn (array $item) => str_contains(mb_strtolower($item['name']), $needle)
        ));

        return array_map(
            fn (array $item) => ['id' => (int) $item['id'], 'name' => $item['name'], 'slug' => $item['slug']],
            array_slice($matches, 0, self::PREVIEW_LIMIT)
        );
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

    /**
     * Pool de PALABRAS (no nombres completos) para la corrección de términos:
     * comparar "kasko" contra "Casco Integral Deportivo" completo nunca da una
     * distancia de edición pequeña, pero contra la palabra suelta "Casco" sí.
     * Se limita a un muestreo razonable, no todo el catálogo.
     */
    private function candidateNames(): array
    {
        $productNames = array_column($this->products->paginate(['per_page' => 100])['data'], 'name');
        $serviceNames = array_column($this->services->paginate(['per_page' => 100])['data'], 'name');
        $categoryNames = array_column($this->flattenCategories($this->categories->tree()), 'name');
        $brandNames = array_column($this->brands->list(), 'name');

        $allNames = array_merge($productNames, $serviceNames, $categoryNames, $brandNames);

        $words = [];
        foreach ($allNames as $name) {
            foreach (preg_split('/\s+/', trim($name)) ?: [] as $word) {
                if (mb_strlen($word) >= 3) {
                    $words[] = $word;
                }
            }
        }

        return array_values(array_unique($words));
    }
}
