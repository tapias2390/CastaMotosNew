<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\User;
use App\Domain\Repositories\UserRepositoryInterface;
use PDO;

/**
 * Adaptador PDO del puerto UserRepositoryInterface. Toda sentencia usa
 * parámetros preparados (sección 6: nunca construir SQL con datos del usuario).
 */
final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->connection->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->connection->prepare('SELECT 1 FROM users WHERE email = :email AND deleted_at IS NULL');
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO users (name, last_name, email, phone, password, status)
             VALUES (:name, :last_name, :email, :phone, :password, :status)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
        ]);

        $userId = (int) $this->connection->lastInsertId();

        // Todo usuario nuevo entra con el rol "cliente" por defecto (sección 7).
        $roleStmt = $this->connection->prepare(
            'INSERT INTO user_roles (user_id, role_id) SELECT :user_id, id FROM roles WHERE name = :role'
        );
        $roleStmt->execute(['user_id' => $userId, 'role' => 'cliente']);

        return $userId;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE users SET name = :name, last_name = :last_name, phone = :phone WHERE id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'id' => $userId,
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        // Cambiar la contraseña también limpia el estado de bloqueo por fuerza bruta.
        $stmt = $this->connection->prepare(
            'UPDATE users
             SET password = :password, failed_login_attempts = 0, locked_until = NULL
             WHERE id = :id'
        );
        $stmt->execute(['password' => $passwordHash, 'id' => $userId]);
    }

    public function updateAvatar(int $userId, string $avatarPath): void
    {
        $stmt = $this->connection->prepare('UPDATE users SET avatar = :avatar WHERE id = :id');
        $stmt->execute(['avatar' => $avatarPath, 'id' => $userId]);
    }

    public function markEmailVerified(int $userId): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE users
             SET email_verified_at = NOW(), email_verification_token = NULL, email_verification_expires_at = NULL
             WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
    }

    public function setEmailVerificationToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE users SET email_verification_token = :token, email_verification_expires_at = :expires WHERE id = :id'
        );
        $stmt->execute(['token' => $tokenHash, 'expires' => $expiresAt, 'id' => $userId]);
    }

    public function findByEmailVerificationToken(string $tokenHash): ?User
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM users
             WHERE email_verification_token = :token AND email_verification_expires_at > NOW()
             AND deleted_at IS NULL'
        );
        $stmt->execute(['token' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function registerLoginSuccess(int $userId): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $userId]);
    }

    public function registerLoginFailure(int $userId, int $maxAttempts, int $lockoutMinutes): void
    {
        // Se calcula todo en una sola sentencia atómica para evitar condiciones de carrera
        // entre lecturas y escrituras concurrentes del contador de intentos.
        $stmt = $this->connection->prepare(
            'UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 locked_until = CASE
                     WHEN failed_login_attempts + 1 >= :max THEN DATE_ADD(NOW(), INTERVAL :lockout MINUTE)
                     ELSE locked_until
                 END
             WHERE id = :id'
        );
        $stmt->execute(['max' => $maxAttempts, 'lockout' => $lockoutMinutes, 'id' => $userId]);
    }

    public function permissionsForUser(int $userId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT DISTINCT p.name
             FROM permissions p
             INNER JOIN role_permission rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :id'
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function rolesForUser(int $userId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT r.name FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :id'
        );
        $stmt->execute(['id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            name: $row['name'],
            lastName: $row['last_name'],
            email: $row['email'],
            phone: $row['phone'],
            avatar: $row['avatar'] ?? null,
            passwordHash: $row['password'],
            status: $row['status'],
            emailVerifiedAt: $row['email_verified_at'],
            failedLoginAttempts: (int) $row['failed_login_attempts'],
            lockedUntil: $row['locked_until'],
            roles: $this->rolesForUser((int) $row['id']),
        );
    }
}
