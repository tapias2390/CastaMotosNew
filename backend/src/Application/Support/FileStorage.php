<?php

declare(strict_types=1);

namespace App\Application\Support;

use App\Exceptions\ValidationException;

/**
 * Mueve un archivo subido a su destino final con un nombre generado (nunca
 * el nombre original del cliente) fuera del docroot público (sección 44).
 * Se asume que el archivo ya pasó por UploadedFileValidator::assertValid().
 */
final class FileStorage
{
    public static function store(array $file, string $directory): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $directory . '/' . $filename;

        $moved = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $destination)
            : rename($file['tmp_name'], $destination); // permite pruebas automatizadas sin una subida HTTP real

        if (!$moved) {
            throw new ValidationException('No fue posible guardar el archivo.', [
                'file' => ['Ocurrió un error al procesar el archivo.'],
            ]);
        }

        return $filename;
    }
}
