<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE order_status_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                status VARCHAR(30) NOT NULL,
                comment VARCHAR(500) NULL,
                changed_by_user_id INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_order_status_history_order (order_id),
                CONSTRAINT fk_order_status_history_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_status_history_user FOREIGN KEY (changed_by_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS order_status_history');
    }
};
