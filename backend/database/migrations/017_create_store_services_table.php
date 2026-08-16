<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE store_services (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                store_id INT UNSIGNED NOT NULL,
                service_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_store_services (store_id, service_id),
                CONSTRAINT fk_store_services_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
                CONSTRAINT fk_store_services_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS store_services');
    }
};
