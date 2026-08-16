<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

/**
 * Logger minimalista a archivo (sin dependencias externas).
 * Escribe un archivo por día en /storage/logs, formato legible para diagnóstico rápido.
 *
 * No se registra información sensible (contraseñas, tokens, tarjetas) — ver sección 36
 * del prompt maestro: "No registrar información extremadamente sensible".
 */
final class Logger
{
    private static ?string $logPath = null;

    public static function boot(string $basePath): void
    {
        self::$logPath = $basePath . '/storage/logs';
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0775, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$logPath === null) {
            // Si no se inicializó explícitamente, usar la ruta por defecto del proyecto.
            self::boot(dirname(__DIR__, 3));
        }

        $file = self::$logPath . '/' . date('Y-m-d') . '.log';
        $line = sprintf(
            '[%s] %s: %s %s%s',
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '',
            PHP_EOL
        );

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
