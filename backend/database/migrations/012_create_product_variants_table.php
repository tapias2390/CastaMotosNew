<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE product_variants (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                sku VARCHAR(100) NULL,
                price_modifier DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                stock INT NOT NULL DEFAULT 0,
                attributes JSON NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_variants_product (product_id),
                CONSTRAINT fk_product_variants_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS product_variants');
    }
};
