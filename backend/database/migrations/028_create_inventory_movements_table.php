<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE inventory_movements (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                type ENUM('in', 'out', 'adjustment', 'reservation', 'release') NOT NULL,
                quantity INT NOT NULL,
                reason VARCHAR(255) NULL,
                reference_type VARCHAR(50) NULL,
                reference_id INT UNSIGNED NULL,
                created_by_user_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_inventory_movements_product (product_id),
                INDEX idx_inventory_movements_reference (reference_type, reference_id),
                CONSTRAINT fk_inventory_movements_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
                CONSTRAINT fk_inventory_movements_user FOREIGN KEY (created_by_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS inventory_movements');
    }
};
