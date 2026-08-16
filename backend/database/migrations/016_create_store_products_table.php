<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Pivote tienda-producto: permite que en el futuro un mismo producto pueda
 * ser ofrecido por varias tiendas con precio/stock propios (marketplace real).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE store_products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                store_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                price_override DECIMAL(12,2) NULL,
                stock_override INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_store_products (store_id, product_id),
                CONSTRAINT fk_store_products_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
                CONSTRAINT fk_store_products_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS store_products');
    }
};
