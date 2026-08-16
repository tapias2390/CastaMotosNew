<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE product_attributes (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                value VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product_attributes_product (product_id),
                CONSTRAINT fk_product_attributes_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS product_attributes');
    }
};
