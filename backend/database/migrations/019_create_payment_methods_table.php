<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Métodos de pago configurables desde administración (sección 21 del prompt
 * maestro): el checkout solo debe mostrar los que tengan is_enabled = 1.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE payment_methods (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                is_enabled TINYINT(1) NOT NULL DEFAULT 0,
                config JSON NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_payment_methods_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS payment_methods');
    }
};
