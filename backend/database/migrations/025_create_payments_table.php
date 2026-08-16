<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE payments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                payment_method_id INT UNSIGNED NOT NULL,
                amount DECIMAL(12,2) NOT NULL,
                status ENUM('pending', 'confirmed', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
                reference VARCHAR(150) NULL,
                proof_file VARCHAR(255) NULL,
                paid_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_payments_order (order_id),
                CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_payments_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS payments');
    }
};
