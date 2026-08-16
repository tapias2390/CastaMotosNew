<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE coupons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL,
                type ENUM('percentage', 'fixed') NOT NULL,
                value DECIMAL(12,2) NOT NULL,
                min_purchase DECIMAL(12,2) NULL,
                usage_limit INT UNSIGNED NULL,
                used_count INT UNSIGNED NOT NULL DEFAULT 0,
                starts_at DATETIME NULL,
                ends_at DATETIME NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_coupons_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS coupons');
    }
};
