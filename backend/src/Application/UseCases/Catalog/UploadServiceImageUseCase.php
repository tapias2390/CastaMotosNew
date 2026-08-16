<?php

declare(strict_types=1);

namespace App\Application\UseCases\Catalog;

use App\Application\Support\FileStorage;
use App\Application\Support\UploadedFileValidator;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Infrastructure\Config\Config;

final class UploadServiceImageUseCase
{
    public function __construct(private ServiceRepositoryInterface $services)
    {
    }

    public function handle(int $serviceId, array $file): array
    {
        if (!$this->services->exists($serviceId)) {
            throw new NotFoundException('Servicio no encontrado.');
        }

        UploadedFileValidator::assertValid(
            $file,
            'image',
            (int) Config::get('app.uploads.catalog_image_max_size_kb', 4096),
            (array) Config::get('app.uploads.catalog_image_allowed_extensions', []),
            (array) Config::get('app.uploads.catalog_image_allowed_mimes', [])
        );

        $directory = (string) Config::get('app.base_path') . '/storage/uploads/services';
        $filename = FileStorage::store($file, $directory);

        $imageId = $this->services->addImage($serviceId, $filename);

        return ['id' => $imageId, 'url' => $filename];
    }
}
