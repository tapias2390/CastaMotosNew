<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\FileStorage;
use App\Application\Support\UploadedFileValidator;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Infrastructure\Config\Config;

final class UploadProductImageUseCase
{
    public function __construct(private ProductRepositoryInterface $products)
    {
    }

    public function handle(int $productId, array $file, bool $isPrimary): array
    {
        if (!$this->products->exists($productId)) {
            throw new NotFoundException('Producto no encontrado.');
        }

        // Límite de fotos (nunca solo del lado del frontend: cualquiera que
        // llame directo a la API debe respetar el mismo tope).
        $max = (int) Config::get('app.uploads.max_images_per_catalog_item', 6);
        if ($this->products->countImages($productId) >= $max) {
            throw new ValidationException('No fue posible subir la imagen.', [
                'image' => ["Este producto ya tiene el máximo de {$max} fotos permitidas."],
            ]);
        }

        UploadedFileValidator::assertValid(
            $file,
            'image',
            (int) Config::get('app.uploads.catalog_image_max_size_kb', 4096),
            (array) Config::get('app.uploads.catalog_image_allowed_extensions', []),
            (array) Config::get('app.uploads.catalog_image_allowed_mimes', [])
        );

        $directory = (string) Config::get('app.base_path') . '/storage/uploads/products';
        $filename = FileStorage::store($file, $directory);

        $imageId = $this->products->addImage($productId, $filename, $isPrimary);

        return ['id' => $imageId, 'url' => $filename];
    }
}
