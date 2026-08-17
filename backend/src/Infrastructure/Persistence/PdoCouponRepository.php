<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\CouponRepositoryInterface;
use PDO;

final class PdoCouponRepository implements CouponRepositoryInterface
{
    private const MAX_PER_PAGE = 100;

    public function __construct(private PDO $connection)
    {
    }

    public function findByCode(string $code): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM coupons WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->connection->prepare('SELECT * FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function paginateForAdmin(array $filters): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = 'code LIKE :search';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->connection->prepare("SELECT COUNT(*) FROM coupons {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->connection->prepare(
            "SELECT * FROM coupons {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function create(array $data): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO coupons (code, type, value, min_purchase, usage_limit, starts_at, ends_at, status)
             VALUES (:code, :type, :value, :min_purchase, :usage_limit, :starts_at, :ends_at, :status)'
        );
        $stmt->execute($this->bindings($data));

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE coupons SET code = :code, type = :type, value = :value, min_purchase = :min_purchase,
                usage_limit = :usage_limit, starts_at = :starts_at, ends_at = :ends_at, status = :status
             WHERE id = :id'
        );
        $stmt->execute($this->bindings($data) + ['id' => $id]);
    }

    private function bindings(array $data): array
    {
        return [
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'min_purchase' => $data['min_purchase'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];
    }

    public function delete(int $id): void
    {
        $stmt = $this->connection->prepare('DELETE FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function existsByCode(string $code, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM coupons WHERE code = :code';
        $params = ['code' => strtoupper($code)];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function incrementUsage(int $id): void
    {
        $stmt = $this->connection->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
