<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\AddressRepositoryInterface;
use PDO;

final class PdoAddressRepository implements AddressRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM addresses WHERE user_id = :user_id AND deleted_at IS NULL ORDER BY is_primary DESC, id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM addresses WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function belongsToUser(int $addressId, int $userId): bool
    {
        $stmt = $this->connection->prepare(
            'SELECT 1 FROM addresses WHERE id = :id AND user_id = :user_id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $addressId, 'user_id' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(int $userId, array $data): int
    {
        // Si es la primera dirección del usuario, se marca como principal automáticamente.
        $countStmt = $this->connection->prepare(
            'SELECT COUNT(*) FROM addresses WHERE user_id = :user_id AND deleted_at IS NULL'
        );
        $countStmt->execute(['user_id' => $userId]);
        $isFirst = ((int) $countStmt->fetchColumn()) === 0;

        $stmt = $this->connection->prepare(
            'INSERT INTO addresses (
                user_id, recipient_name, phone, country, state, city,
                address_line, complement, postal_code, reference, is_primary
            ) VALUES (
                :user_id, :recipient_name, :phone, :country, :state, :city,
                :address_line, :complement, :postal_code, :reference, :is_primary
            )'
        );
        $stmt->execute([
            'user_id' => $userId,
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'state' => $data['state'],
            'city' => $data['city'],
            'address_line' => $data['address_line'],
            'complement' => $data['complement'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'reference' => $data['reference'] ?? null,
            'is_primary' => $isFirst ? 1 : 0,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $addressId, array $data): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE addresses SET
                recipient_name = :recipient_name, phone = :phone, country = :country,
                state = :state, city = :city, address_line = :address_line,
                complement = :complement, postal_code = :postal_code, reference = :reference
             WHERE id = :id'
        );
        $stmt->execute([
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'country' => $data['country'],
            'state' => $data['state'],
            'city' => $data['city'],
            'address_line' => $data['address_line'],
            'complement' => $data['complement'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'reference' => $data['reference'] ?? null,
            'id' => $addressId,
        ]);
    }

    public function delete(int $addressId): void
    {
        $stmt = $this->connection->prepare('UPDATE addresses SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $addressId]);
    }

    public function setPrimary(int $userId, int $addressId): void
    {
        // Transacción: evita que quede más de una dirección principal por una falla a mitad de camino.
        $this->connection->beginTransaction();
        try {
            $unset = $this->connection->prepare(
                'UPDATE addresses SET is_primary = 0 WHERE user_id = :user_id'
            );
            $unset->execute(['user_id' => $userId]);

            $set = $this->connection->prepare(
                'UPDATE addresses SET is_primary = 1 WHERE id = :id AND user_id = :user_id'
            );
            $set->execute(['id' => $addressId, 'user_id' => $userId]);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
