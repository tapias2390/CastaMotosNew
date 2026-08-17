<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

/**
 * Resumen del negocio para el dashboard admin (sección 28: "gráficas que
 * muestran cómo va el negocio"). Todo son agregados de datos reales ya
 * existentes (pedidos, productos, servicios, usuarios) — nada inventado ni
 * calculado del lado del cliente. Es una consulta de reportería pura, por
 * eso vive como una clase de infraestructura directa en vez de un
 * repositorio de una entidad de dominio con CRUD propio.
 */
final class PdoDashboardRepository
{
    /** Pedidos que no representan una venta real concretada. */
    private const EXCLUDED_FROM_REVENUE = ['CANCELADO', 'DEVUELTO'];

    public function __construct(private PDO $connection)
    {
    }

    public function summary(): array
    {
        return [
            'revenue' => $this->revenueSummary(),
            'orders_by_status' => $this->ordersByStatus(),
            'revenue_by_day' => $this->revenueByDay(14),
            'top_products' => $this->topProducts(5),
            'low_stock_count' => $this->lowStockCount(),
            'upcoming_reservations_count' => $this->upcomingReservationsCount(),
            'new_users_last_30_days' => $this->newUsersLast30Days(),
        ];
    }

    private function revenueSummary(): array
    {
        $placeholders = implode(',', array_fill(0, count(self::EXCLUDED_FROM_REVENUE), '?'));

        $stmt = $this->connection->prepare(
            "SELECT
                COALESCE(SUM(total), 0) AS revenue_all_time,
                COALESCE(SUM(CASE WHEN created_at >= CURDATE() - INTERVAL 30 DAY THEN total ELSE 0 END), 0) AS revenue_last_30_days,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END), 0) AS revenue_today,
                COUNT(*) AS orders_count,
                COALESCE(AVG(total), 0) AS average_ticket
             FROM orders
             WHERE deleted_at IS NULL AND status NOT IN ({$placeholders})"
        );
        $stmt->execute(self::EXCLUDED_FROM_REVENUE);
        $row = $stmt->fetch();

        return [
            'all_time' => (float) $row['revenue_all_time'],
            'last_30_days' => (float) $row['revenue_last_30_days'],
            'today' => (float) $row['revenue_today'],
            'orders_count' => (int) $row['orders_count'],
            'average_ticket' => (float) $row['average_ticket'],
        ];
    }

    /** Para el gráfico de barras/dona "pedidos por estado". */
    private function ordersByStatus(): array
    {
        $stmt = $this->connection->query(
            "SELECT status, COUNT(*) AS total FROM orders WHERE deleted_at IS NULL GROUP BY status"
        );

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['total'];
        }

        return $counts;
    }

    /** Para el gráfico de líneas "ventas de los últimos N días" — incluye
     * los días sin ventas en 0, para que el eje X no salte fechas. */
    private function revenueByDay(int $days): array
    {
        // PDO no permite mezclar parámetros nombrados y posicionales en la
        // misma sentencia — cada estado excluido va con su propio nombre.
        $placeholderNames = [];
        $params = ['days' => $days];
        foreach (self::EXCLUDED_FROM_REVENUE as $i => $status) {
            $name = "excluded_{$i}";
            $placeholderNames[] = ":{$name}";
            $params[$name] = $status;
        }
        $placeholders = implode(',', $placeholderNames);

        $stmt = $this->connection->prepare(
            "SELECT DATE(created_at) AS day, COALESCE(SUM(total), 0) AS revenue, COUNT(*) AS orders_count
             FROM orders
             WHERE deleted_at IS NULL AND status NOT IN ({$placeholders})
                AND created_at >= CURDATE() - INTERVAL :days DAY
             GROUP BY DATE(created_at)"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === 'days' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDay[$row['day']] = ['revenue' => (float) $row['revenue'], 'orders_count' => (int) $row['orders_count']];
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date' => $date,
                'revenue' => $byDay[$date]['revenue'] ?? 0.0,
                'orders_count' => $byDay[$date]['orders_count'] ?? 0,
            ];
        }

        return $series;
    }

    /** Top productos por unidades vendidas (pedidos no cancelados/devueltos). */
    private function topProducts(int $limit): array
    {
        $placeholders = implode(',', array_fill(0, count(self::EXCLUDED_FROM_REVENUE), '?'));

        $stmt = $this->connection->prepare(
            "SELECT oi.name_snapshot AS name, SUM(oi.quantity) AS units_sold, SUM(oi.subtotal) AS revenue
             FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id IS NOT NULL AND o.deleted_at IS NULL AND o.status NOT IN ({$placeholders})
             GROUP BY oi.product_id, oi.name_snapshot
             ORDER BY units_sold DESC
             LIMIT " . (int) $limit
        );
        $stmt->execute(self::EXCLUDED_FROM_REVENUE);

        return array_map(static fn (array $row) => [
            'name' => $row['name'],
            'units_sold' => (int) $row['units_sold'],
            'revenue' => (float) $row['revenue'],
        ], $stmt->fetchAll());
    }

    private function lowStockCount(): int
    {
        $stmt = $this->connection->query(
            "SELECT COUNT(*) FROM products WHERE deleted_at IS NULL AND status = 'active' AND stock <= min_stock"
        );

        return (int) $stmt->fetchColumn();
    }

    private function upcomingReservationsCount(): int
    {
        $stmt = $this->connection->query(
            "SELECT COUNT(*) FROM order_items oi
                INNER JOIN orders o ON o.id = oi.order_id
             WHERE oi.service_id IS NOT NULL AND oi.scheduled_at >= NOW()
                AND o.deleted_at IS NULL AND o.status != 'CANCELADO'"
        );

        return (int) $stmt->fetchColumn();
    }

    private function newUsersLast30Days(): int
    {
        $stmt = $this->connection->query(
            "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND created_at >= CURDATE() - INTERVAL 30 DAY"
        );

        return (int) $stmt->fetchColumn();
    }
}
