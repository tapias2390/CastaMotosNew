<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\PasswordResetRepositoryInterface;
use PDO;

final class PdoPasswordResetRepository implements PasswordResetRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function create(string $email, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO password_reset_tokens (email, token_hash, expires_at) VALUES (:email, :token_hash, :expires_at)'
        );
        $stmt->execute(['email' => $email, 'token_hash' => $tokenHash, 'expires_at' => $expiresAt]);
    }

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, email FROM password_reset_tokens
             WHERE token_hash = :token_hash AND expires_at > NOW() AND used_at IS NULL
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->connection->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
