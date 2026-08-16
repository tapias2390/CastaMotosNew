<?php

declare(strict_types=1);

namespace App\Application\UseCases\Profile;

use App\Application\Support\FileStorage;
use App\Application\Support\UploadedFileValidator;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Config\Config;

/**
 * Subida segura de foto de perfil (sección 44): valida extensión, MIME real
 * (no el que declara el cliente) y tamaño; guarda fuera del docroot público
 * con un nombre generado (nunca el nombre original) y sin permitir ejecución.
 */
final class UploadAvatarUseCase
{
    public function __construct(private UserRepositoryInterface $users)
    {
    }

    /**
     * @param array $file Entrada estilo $_FILES['avatar'] (name, tmp_name, size, error).
     */
    public function handle(int $userId, array $file): string
    {
        UploadedFileValidator::assertValid(
            $file,
            'avatar',
            (int) Config::get('app.uploads.avatar_max_size_kb', 2048),
            (array) Config::get('app.uploads.avatar_allowed_extensions', []),
            (array) Config::get('app.uploads.avatar_allowed_mimes', [])
        );

        $directory = (string) Config::get('app.base_path') . '/storage/uploads/avatars';
        $filename = FileStorage::store($file, $directory);

        $this->users->updateAvatar($userId, $filename);

        return $filename;
    }
}
