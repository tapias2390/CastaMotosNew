<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE service_images (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                service_id INT UNSIGNED NOT NULL,
                url VARCHAR(255) NOT NULL,
                sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_service_images_service (service_id),
                CONSTRAINT fk_service_images_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS service_images');
    }
};
