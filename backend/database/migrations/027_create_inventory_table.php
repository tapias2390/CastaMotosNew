<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Stock disponible = stock_current - stock_reserved (sección 25 del prompt maestro).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE inventory (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                stock_current INT NOT NULL DEFAULT 0,
                stock_reserved INT NOT NULL DEFAULT 0,
                stock_minimum INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_inventory_product (product_id),
                CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS inventory');
    }
};
