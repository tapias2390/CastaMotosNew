<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Sección 24 del prompt maestro: "Preparar arquitectura para notificaciones
 * push... debe existir una tabla o sistema para gestionar dispositivos/
 * tokens de notificación". Un usuario puede tener varios dispositivos
 * suscritos (celular + navegador, por ejemplo) — de ahí la tabla aparte en
 * vez de una sola columna en "users".
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE push_subscriptions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                token VARCHAR(500) NOT NULL,
                platform VARCHAR(20) NOT NULL DEFAULT 'web',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_push_subscriptions_token (token(191)),
                INDEX idx_push_subscriptions_user (user_id),
                CONSTRAINT fk_push_subscriptions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS push_subscriptions');
    }
};
