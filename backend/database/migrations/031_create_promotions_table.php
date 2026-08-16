<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE promotions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                type ENUM('product', 'category') NOT NULL,
                target_id INT UNSIGNED NOT NULL,
                discount_percentage DECIMAL(5,2) NULL,
                discount_fixed DECIMAL(12,2) NULL,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_promotions_target (type, target_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS promotions');
    }
};
