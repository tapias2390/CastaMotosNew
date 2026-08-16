<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE addresses (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                recipient_name VARCHAR(150) NOT NULL,
                phone VARCHAR(30) NOT NULL,
                country VARCHAR(100) NOT NULL,
                state VARCHAR(100) NOT NULL,
                city VARCHAR(100) NOT NULL,
                address_line VARCHAR(255) NOT NULL,
                complement VARCHAR(255) NULL,
                postal_code VARCHAR(20) NULL,
                reference VARCHAR(255) NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX idx_addresses_user (user_id),
                CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS addresses');
    }
};
