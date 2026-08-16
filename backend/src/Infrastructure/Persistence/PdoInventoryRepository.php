<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Repositories\InventoryRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use PDO;

final class PdoInventoryRepository implements InventoryRepositoryInterface
{
    private const MAX_PER_PAGE = 100;

    public function __construct(private PDO $connection)
    {
    }

    public function listWithProductInfo(array $filters): array
    {
        $conditions = ['p.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['search'])) {
            // Nombres de parámetro distintos por ocurrencia: con
            // PDO::ATTR_EMULATE_PREPARES=false (Connection.php) MySQL usa prepared
            // statements nativos, que no permiten repetir el mismo parámetro nombrado
            // más de una vez en la misma consulta ("Invalid parameter number") — mismo
            // problema ya resuelto en PdoProductRepository/PdoServiceRepository (Fase 4).
            $conditions[] = '(p.name LIKE :search_name OR p.sku LIKE :search_sku)';
            $likeTerm = '%' . $filters['search'] . '%';
            $params['search_name'] = $likeTerm;
            $params['search_sku'] = $likeTerm;
        }

        if (!empty($filters['low_stock'])) {
            $conditions[] = 'p.stock <= p.min_stock';
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->connection->prepare("SELECT COUNT(*) FROM products p {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT p.id AS product_id, p.name, p.sku, p.stock AS stock_current, p.min_stock,
                    COALESCE(i.stock_reserved, 0) AS stock_reserved,
                    (p.stock - COALESCE(i.stock_reserved, 0)) AS stock_available,
                    c.name AS category_name
                FROM products p
                LEFT JOIN inventory i ON i.product_id = p.id
                LEFT JOIN categories c ON c.id = p.category_id
                {$where}
                ORDER BY p.name ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function adjust(int $productId, string $type, int $quantity, string $reason, int $userId): void
    {
        $this->connection->beginTransaction();

        try {
            $lock = $this->connection->prepare('SELECT stock FROM products WHERE id = :id AND deleted_at IS NULL FOR UPDATE');
            $lock->execute(['id' => $productId]);
            $currentStock = $lock->fetchColumn();

            if ($currentStock === false) {
                throw new NotFoundException('Producto no encontrado.');
            }
            $currentStock = (int) $currentStock;

            $delta = match ($type) {
                'in' => $quantity,
                'out' => -$quantity,
                'adjustment' => $quantity,
                default => throw new ValidationException('Ajuste inválido.', ['type' => ['Tipo de ajuste no reconocido.']]),
            };

            $newStock = $currentStock + $delta;

            if ($newStock < 0) {
                throw new ValidationException('No fue posible aplicar el ajuste.', [
                    'quantity' => ['El ajuste dejaría el stock en un valor negativo.'],
                ]);
            }

            $this->connection->prepare('UPDATE products SET stock = :stock WHERE id = :id')
                ->execute(['stock' => $newStock, 'id' => $productId]);

            $this->connection->prepare(
                'INSERT INTO inventory (product_id, stock_current, stock_reserved, stock_minimum)
                 VALUES (:product_id, :stock, 0, 0)
                 ON DUPLICATE KEY UPDATE stock_current = VALUES(stock_current)'
            )->execute(['product_id' => $productId, 'stock' => $newStock]);

            $this->connection->prepare(
                'INSERT INTO inventory_movements (product_id, type, quantity, reason, created_by_user_id)
                 VALUES (:product_id, :type, :quantity, :reason, :user_id)'
            )->execute([
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $delta,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function movements(array $filters): array
    {
        $conditions = ['1 = 1'];
        $params = [];

        if (!empty($filters['product_id'])) {
            $conditions[] = 'm.product_id = :product_id';
            $params['product_id'] = (int) $filters['product_id'];
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(self::MAX_PER_PAGE, max(1, (int) ($filters['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->connection->prepare("SELECT COUNT(*) FROM inventory_movements m {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT m.*, p.name AS product_name, p.sku AS product_sku
                FROM inventory_movements m
                INNER JOIN products p ON p.id = m.product_id
                {$where}
                ORDER BY m.created_at DESC, m.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->connection->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['data' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
