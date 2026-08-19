<?php

declare(strict_types=1);

use App\Infrastructure\Database\Seeder;

/**
 * "Lavado de Moto" y "Lavado de Casco" (sección 52, wizard de lavado) no son
 * datos de demostración descartables: el wizard (frontend/js/pages/lavado.js,
 * WASH_SLUGS) busca estos DOS servicios por slug exacto ("lavado-de-moto" /
 * "lavado-de-casco") y si no existen en el catálogo, el paso 1 del wizard
 * muestra "No fue posible cargar los servicios de lavado". Por eso viven acá
 * como seeder — igual que las categorías — en vez de depender de que alguien
 * los cree a mano desde el admin y tipee el slug exacto correcto.
 *
 * Precio y duración son un punto de partida razonable; se pueden ajustar
 * después desde el admin (Servicios) sin romper el wizard, que solo depende
 * del slug.
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $categoryId = $this->findCategoryId($connection, 'servicios');

        $this->upsertService($connection, [
            'category_id' => $categoryId,
            'name' => 'Lavado de Moto',
            'slug' => 'lavado-de-moto',
            'description' => 'Lavado completo de tu motocicleta: carrocería, llantas, cadena y motor. Reservá el día y la hora que prefieras.',
            'price' => '25000.00',
            'duration_minutes' => 40,
        ]);

        $this->upsertService($connection, [
            'category_id' => $categoryId,
            'name' => 'Lavado de Casco',
            'slug' => 'lavado-de-casco',
            'description' => 'Limpieza y desinfección profunda de tu casco (exterior e interior). Reservá el día y la hora que prefieras.',
            'price' => '12000.00',
            'duration_minutes' => 20,
        ]);
    }

    private function findCategoryId(PDO $connection, string $slug): ?int
    {
        $stmt = $connection->prepare('SELECT id FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private function upsertService(PDO $connection, array $data): void
    {
        $existing = $connection->prepare('SELECT id FROM services WHERE slug = :slug');
        $existing->execute(['slug' => $data['slug']]);
        if ($existing->fetchColumn() !== false) {
            return;
        }

        $stmt = $connection->prepare(
            'INSERT INTO services (category_id, name, slug, description, price, duration_minutes, status)
             VALUES (:category_id, :name, :slug, :description, :price, :duration_minutes, :status)'
        );
        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'price' => $data['price'],
            'duration_minutes' => $data['duration_minutes'],
            'status' => 'active',
        ]);
    }
};
