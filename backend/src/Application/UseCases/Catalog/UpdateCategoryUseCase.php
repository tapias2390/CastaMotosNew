<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\SlugGenerator;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class UpdateCategoryUseCase
{
    public function __construct(private CategoryRepositoryInterface $categories)
    {
    }

    public function handle(int $id, array $data): void
    {
        if (!$this->categories->exists($id)) {
            throw new NotFoundException('Categoría no encontrada.');
        }

        if (!empty($data['parent_id'])) {
            $parentId = (int) $data['parent_id'];

            if (!$this->categories->exists($parentId)) {
                throw new ValidationException('Los datos enviados no son válidos.', [
                    'parent_id' => ['La categoría padre indicada no existe.'],
                ]);
            }

            if ($this->categories->wouldCreateCycle($id, $parentId)) {
                throw new ValidationException('Los datos enviados no son válidos.', [
                    'parent_id' => ['Una categoría no puede ser descendiente de sí misma.'],
                ]);
            }
        }

        $data['slug'] = SlugGenerator::unique(
            $data['name'],
            fn (string $slug) => $this->categories->existsBySlug($slug, $id)
        );

        $this->categories->update($id, $data);
    }
}
