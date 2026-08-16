<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Guarda "snapshots" de nombre/SKU/precio en el momento de la compra:
 * el pedido no debe verse afectado si el producto cambia después (sección 54).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE order_items (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NULL,
                service_id INT UNSIGNED NULL,
                name_snapshot VARCHAR(200) NOT NULL,
                sku_snapshot VARCHAR(100) NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                unit_price DECIMAL(12,2) NOT NULL,
                subtotal DECIMAL(12,2) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_items_order (order_id),
                INDEX idx_order_items_product (product_id),
                INDEX idx_order_items_service (service_id),
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
                CONSTRAINT fk_order_items_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS order_items');
    }
};
