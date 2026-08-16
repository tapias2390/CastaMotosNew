<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\SlugGenerator;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Exceptions\ValidationException;

final class CreateCategoryUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function handle(array $data): int
    {
        if (!empty($data['parent_id']) && !$this->categories->exists((int) $data['parent_id'])) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'parent_id' => ['La categoría padre indicada no existe.'],
            ]);
        }

        $data['slug'] = SlugGenerator::unique($data['name'], fn (string $slug) => $this->categories->existsBySlug($slug));

        return $this->categories->create($data);
    }
}
