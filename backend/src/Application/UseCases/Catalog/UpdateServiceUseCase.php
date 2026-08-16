<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\SlugGenerator;
use App\Domain\Repositories\CategoryRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;

final class UpdateServiceUseCase
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private CategoryRepositoryInterface $categories
    ) {
    }

    public function handle(int $id, array $data): void
    {
        if (!$this->services->exists($id)) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        if (!empty($data['category_id']) && !$this->categories->exists((int) $data['category_id'])) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'category_id' => ['La categoría indicada no existe.'],
            ]);
        }

        $data['slug'] = SlugGenerator::unique(
            $data['name'],
            fn (string $slug) => $this->services->existsBySlug($slug, $id)
        );

        $this->services->update($id, $data);
    }
}
