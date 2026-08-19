<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\SupplierRepositoryInterface;
use PDO;

final class PdoSupplierRepository implements SupplierRepositoryInterface
{
    public function __construct(private PDO $connection)
    {
    }

    public function list(bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM suppliers WHERE deleted_at IS NULL';
        if (!$includeInactive) {
            $sql .= " AND status = 'active'";
        }
        $sql .= ' ORDER BY name ASC';

        return $this->connection->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM suppliers WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->connection->prepare('SELECT 1 FROM suppliers WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO suppliers (name, contact_name, phone, email, tax_id, address, notes, status)
             VALUES (:name, :contact_name, :phone, :email, :tax_id, :address, :notes, :status)'
        );
        $stmt->execute([
            'name' => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE suppliers SET name = :name, contact_name = :contact_name, phone = :phone,
                email = :email, tax_id = :tax_id, address = :address, notes = :notes, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $data['name'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'active',
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->connection->prepare('UPDATE suppliers SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
