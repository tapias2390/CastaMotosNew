<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Reseñas polimórficas: reviewable_type distingue "product" | "service" | "store".
 * La regla de "solo quien compró puede reseñar" (sección 26) se valida en la
 * capa de aplicación, no en el esquema.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            "CREATE TABLE reviews (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                reviewable_type ENUM('product', 'service', 'store') NOT NULL,
                reviewable_id INT UNSIGNED NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                comment TEXT NULL,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_reviews_reviewable (reviewable_type, reviewable_id),
                INDEX idx_reviews_user (user_id),
                CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS reviews');
    }
};
