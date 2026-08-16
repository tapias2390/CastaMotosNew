<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Tabla pivote no listada explícitamente en la sección 34 del prompt maestro,
 * pero necesaria para que el modelo de roles y permisos (sección 7) funcione.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'CREATE TABLE role_permission (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                role_id INT UNSIGNED NOT NULL,
                permission_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_role_permission (role_id, permission_id),
                CONSTRAINT fk_role_permission_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE,
                CONSTRAINT fk_role_permission_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec('DROP TABLE IF EXISTS role_permission');
    }
};
