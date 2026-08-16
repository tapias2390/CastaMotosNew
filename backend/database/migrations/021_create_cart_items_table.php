<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE cart_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                cart_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NULL,
                service_id INT UNSIGNED NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                unit_price_snapshot DECIMAL(12,2) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_cart_items_cart (cart_id),
                INDEX idx_cart_items_product (product_id),
                INDEX idx_cart_items_service (service_id),
                CONSTRAINT fk_cart_items_cart FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE,
                CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
                CONSTRAINT fk_cart_items_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS cart_items');
    }
};
