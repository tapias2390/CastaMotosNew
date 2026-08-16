<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(30) NULL,
                password VARCHAR(255) NOT NULL,
                status ENUM('active', 'inactive', 'banned') NOT NULL DEFAULT 'active',
                email_verified_at DATETIME NULL,
                remember_token VARCHAR(100) NULL,
                failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                UNIQUE KEY uq_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS users');
    }
};
