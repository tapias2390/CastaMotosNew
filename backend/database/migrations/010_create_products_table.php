<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE products (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                store_id INT UNSIGNED NULL,
                category_id INT UNSIGNED NOT NULL,
                brand_id INT UNSIGNED NULL,
                name VARCHAR(200) NOT NULL,
                slug VARCHAR(220) NOT NULL,
                description TEXT NULL,
                short_description VARCHAR(500) NULL,
                sku VARCHAR(100) NOT NULL,
                internal_code VARCHAR(100) NULL,
                price DECIMAL(12,2) NOT NULL,
                previous_price DECIMAL(12,2) NULL,
                discount_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                stock INT NOT NULL DEFAULT 0,
                min_stock INT NOT NULL DEFAULT 0,
                weight DECIMAL(8,2) NULL,
                dimensions VARCHAR(100) NULL,
                warranty VARCHAR(150) NULL,
                additional_info TEXT NULL,
                status ENUM('draft', 'active', 'inactive', 'out_of_stock') NOT NULL DEFAULT 'draft',
                views INT UNSIGNED NOT NULL DEFAULT 0,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
                rating_count INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_products_slug (slug),
                UNIQUE KEY uq_products_sku (sku),
                INDEX idx_products_store (store_id),
                INDEX idx_products_category (category_id),
                INDEX idx_products_brand (brand_id),
                INDEX idx_products_status (status),
                CONSTRAINT fk_products_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL,
                CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
                CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS products');
    }
};
