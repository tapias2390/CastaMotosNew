<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Search\GlobalSearchUseCase;
use App\Application\UseCases\Search\SuggestUseCase;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoBrandRepository;
use App\Infrastructure\Persistence\PdoCategoryRepository;
use App\Infrastructure\Persistence\PdoProductRepository;
use App\Infrastructure\Persistence\PdoServiceRepository;

final class SearchController
{
    private PdoProductRepository $products;
    private PdoServiceRepository $services;
    private PdoCategoryRepository $categories;
    private PdoBrandRepository $brands;

    public function __construct()
    {
        $connection = Connection::get();
        $this->products = new PdoProductRepository($connection);
        $this->services = new PdoServiceRepository($connection);
        $this->categories = new PdoCategoryRepository($connection);
        $this->brands = new PdoBrandRepository($connection);
    }

    public function search(Request $request): void
    {
        $term = trim((string) $request->query('q', ''));
        $this->assertTermPresent($term);

        $useCase = new GlobalSearchUseCase($this->products, $this->services, $this->categories, $this->brands);

        Response::success($useCase->handle($term));
    }

    public function suggestions(Request $request): void
    {
        $term = trim((string) $request->query('q', ''));
        $this->assertTermPresent($term);

        $limit = min(20, max(1, (int) $request->query('limit', 10)));

        $useCase = new SuggestUseCase($this->products, $this->services, $this->categories, $this->brands);

        Response::success($useCase->handle($term, $limit));
    }

    private function assertTermPresent(string $term): void
    {
        if ($term === '') {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'q' => ['Debes indicar un término de búsqueda.'],
            ]);
        }
    }
}
