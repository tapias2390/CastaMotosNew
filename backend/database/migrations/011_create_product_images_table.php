<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE product_images (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                url VARCHAR(255) NOT NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product_images_product (product_id),
                CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS product_images');
    }
};
