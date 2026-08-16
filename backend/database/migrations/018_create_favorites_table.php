<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Favoritos polimórficos: favoritable_type distingue "product" | "service".
 * No se usa FK directa a products/services porque un mismo registro apunta
 * a una u otra tabla según el tipo (patrón polimórfico simple, sin ORM).
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE favorites (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                favoritable_type ENUM('product', 'service') NOT NULL,
                favoritable_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_favorites (user_id, favoritable_type, favoritable_id),
                INDEX idx_favorites_favoritable (favoritable_type, favoritable_id),
                CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS favorites');
    }
};
