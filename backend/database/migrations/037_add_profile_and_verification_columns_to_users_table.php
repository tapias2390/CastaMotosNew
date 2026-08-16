<?php

declare(strict_types=1);

use App\Infrastructure\Database\Migration;

/**
 * Columnas necesarias para el perfil (foto) y la verificación de correo
 * (Fase 2). El token se guarda hasheado, nunca en texto plano.
 */
return new class extends Migration {
    public function up(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE users
                ADD COLUMN avatar VARCHAR(255) NULL AFTER phone,
                ADD COLUMN email_verification_token VARCHAR(64) NULL AFTER email_verified_at,
                ADD COLUMN email_verification_expires_at DATETIME NULL AFTER email_verification_token'
        );
    }

    public function down(PDO $connection): void
    {
        $connection->exec(
            'ALTER TABLE users
                DROP COLUMN avatar,
                DROP COLUMN email_verification_token,
                DROP COLUMN email_verification_expires_at'
        );
    }
};
