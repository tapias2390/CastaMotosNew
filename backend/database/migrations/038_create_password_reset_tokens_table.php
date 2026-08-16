<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Tabla independiente de "users" para tokens de recuperación de contraseña
 * (patrón estándar): permite múltiples solicitudes y expiración sin tocar
 * la tabla principal. El token se guarda hasheado (sha256), nunca en claro.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_tokens_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS password_reset_tokens');
    }
};
