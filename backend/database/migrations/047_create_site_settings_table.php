<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Configuración general del sitio en clave/valor (README, "Próximas fases":
 * "Falta un editor de configuración general del sitio" — este es el primer
 * uso real: términos y condiciones, ver seeder 004). Pensada para crecer con
 * más claves (política de privacidad, sobre nosotros, etc.) sin otra
 * migración por cada una.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE site_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                value LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS site_settings');
    }
};
