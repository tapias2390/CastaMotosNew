<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE email_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                to_email VARCHAR(150) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                template VARCHAR(100) NULL,
                status ENUM('sent', 'failed') NOT NULL,
                error_message VARCHAR(500) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_logs_user (user_id),
                CONSTRAINT fk_email_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS email_logs');
    }
};
