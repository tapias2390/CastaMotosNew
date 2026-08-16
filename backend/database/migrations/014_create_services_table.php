<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE services (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                store_id INT UNSIGNED NULL,
                category_id INT UNSIGNED NULL,
                professional_user_id INT UNSIGNED NULL,
                name VARCHAR(200) NOT NULL,
                slug VARCHAR(220) NOT NULL,
                description TEXT NULL,
                price DECIMAL(12,2) NOT NULL,
                duration_minutes INT UNSIGNED NULL,
                location VARCHAR(255) NULL,
                schedule JSON NULL,
                availability JSON NULL,
                cancellation_policy TEXT NULL,
                status ENUM('draft', 'active', 'inactive') NOT NULL DEFAULT 'draft',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_services_slug (slug),
                INDEX idx_services_store (store_id),
                INDEX idx_services_category (category_id),
                INDEX idx_services_professional (professional_user_id),
                CONSTRAINT fk_services_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE SET NULL,
                CONSTRAINT fk_services_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
                CONSTRAINT fk_services_professional FOREIGN KEY (professional_user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS services');
    }
};
