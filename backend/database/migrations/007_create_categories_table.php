<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE categories (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                parent_id INT UNSIGNED NULL,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(150) NOT NULL,
                description VARCHAR(255) NULL,
                image VARCHAR(255) NULL,
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_categories_slug (slug),
                INDEX idx_categories_parent (parent_id),
                CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS categories');
    }
};
