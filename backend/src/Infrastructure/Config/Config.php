<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

use Dotenv\Dotenv;

/**
 * Carga el archivo .env y los arrays de /config/*.php, y expone
 * acceso mediante notación de puntos, ej: Config::get('database.host').
 *
 * Es un registro simple en memoria (no un singleton "mágico"): se debe
 * inicializar explícitamente una vez en el punto de entrada de la app.
 */
final class Config
{
    private static array $items = [];
    private static bool $booted = false;

    /**
     * Inicializa la configuración: carga .env y todos los archivos de /config.
     */
    public static function boot(string $basePath): void
    {
        if (self::$booted) {
            return;
        }

        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }

        $configPath = $basePath . '/config';
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }

        // Se expone la ruta raíz del backend (útil para módulos que leen/escriben
        // en /storage, como la subida de avatares o el driver "log" de Mailer).
        self::$items['app']['base_path'] = $basePath;

        self::$booted = true;
    }

    /**
     * Obtiene un valor de configuración usando notación de puntos.
     * Ej: Config::get('database.host', '127.0.0.1')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
