<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Exceptions\ValidationException;

/**
 * Validación de subida de archivos (sección 44): extensión, tamaño y MIME
 * real (no el que declara el cliente). Compartida por avatares, imágenes de
 * producto y de servicio para no repetir la misma lógica en cada UseCase.
 */
final class UploadedFileValidator
{
    /**
     * @param array $file Entrada estilo $_FILES['campo'].
     * @param string[] $allowedExtensions
     * @param string[] $allowedMimes
     */
    public static function assertValid(
        array $file,
        string $field,
        int $maxSizeKb,
        array $allowedExtensions,
        array $allowedMimes
    ): void {
        $errors = [];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $errors[] = 'No se recibió un archivo válido.';
        }

        if (($file['size'] ?? 0) > $maxSizeKb * 1024) {
            $errors[] = 'El archivo supera el tamaño máximo permitido.';
        }

        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'Extensión de archivo no permitida.';
        }

        if (empty($errors) && is_file($file['tmp_name'] ?? '')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($realMime, $allowedMimes, true)) {
                $errors[] = 'El contenido del archivo no corresponde a una imagen permitida.';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('No fue posible subir el archivo.', [$field => $errors]);
        }
    }
}
