<?php

declare(strict_types=1);

use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Seeder;

/**
 * Crea (o actualiza) el usuario superadministrador inicial, usando las
 * credenciales definidas en .env (ADMIN_EMAIL / ADMIN_PASSWORD).
 * La contraseña por defecto solo es válida para entorno local: debe
 * cambiarse antes de cualquier despliegue real.
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $email = (string) Config::get('app.admin.email', 'admin@castamoto.local');
        $password = (string) Config::get('app.admin.password', 'CambiarEsteClave123!');
        $name = (string) Config::get('app.admin.name', 'Administrador');

        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $connection->prepare(
            'INSERT INTO users (name, last_name, email, password, status, email_verified_at)
             VALUES (:name, :last_name, :email, :password, :status, NOW())
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );
        $stmt->execute([
            'name' => $name,
            'last_name' => 'CASTAMOTO',
            'email' => $email,
            'password' => $hashed,
            'status' => 'active',
        ]);

        $link = $connection->prepare(
            'INSERT INTO user_roles (user_id, role_id)
             SELECT u.id, r.id FROM users u, roles r
             WHERE u.email = :email AND r.name = :role
             ON DUPLICATE KEY UPDATE user_id = user_id'
        );
        $link->execute(['email' => $email, 'role' => 'superadministrador']);
    }
};
