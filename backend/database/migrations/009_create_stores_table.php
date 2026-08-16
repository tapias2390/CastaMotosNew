<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE stores (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(180) NOT NULL,
                logo VARCHAR(255) NULL,
                description TEXT NULL,
                phone VARCHAR(30) NULL,
                email VARCHAR(150) NULL,
                address VARCHAR(255) NULL,
                rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00,
                rating_count INT UNSIGNED NOT NULL DEFAULT 0,
                status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_stores_slug (slug),
                INDEX idx_stores_user (user_id),
                CONSTRAINT fk_stores_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS stores');
    }
};
