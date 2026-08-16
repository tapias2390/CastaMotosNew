<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE orders (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(30) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                address_id INT UNSIGNED NULL,
                store_id INT UNSIGNED NULL,
                payment_method_id INT UNSIGNED NULL,
                status ENUM(
                    'PENDIENTE', 'CONFIRMADO', 'PAGO_PENDIENTE', 'PAGO_CONFIRMADO',
                    'PREPARANDO', 'EN_CAMINO', 'ENTREGADO', 'CANCELADO', 'DEVUELTO'
                ) NOT NULL DEFAULT 'PENDIENTE',
                subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                discount_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                shipping_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                tax_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                notes VARCHAR(500) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_orders_number (order_number),
                INDEX idx_orders_user (user_id),
                INDEX idx_orders_status (status),
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
                CONSTRAINT fk_orders_address FOREIGN KEY (address_id) REFERENCES addresses (id) ON DELETE SET NULL,
                CONSTRAINT fk_orders_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL,
                CONSTRAINT fk_orders_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS orders');
    }
};
