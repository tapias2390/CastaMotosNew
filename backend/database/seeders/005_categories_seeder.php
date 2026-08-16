<?php

declare(strict_types=1);

use App\Application\Support\SlugGenerator;
use App\Infrastructure\Database\Seeder;

/**
 * Árbol de categorías de ejemplo (sección 13 del prompt maestro). Es
 * taxonomía base del negocio, no datos de demostración descartables — por
 * eso vive en un seeder y no se crea vía API durante pruebas manuales.
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $rootId = $this->upsert($connection, null, 'Motocicletas', 0);

        $children = [
            'Repuestos', 'Accesorios', 'Cascos', 'Guantes', 'Chaquetas',
            'Llantas', 'Lubricantes', 'Electrónica', 'Herramientas', 'Servicios',
        ];

        foreach ($children as $index => $name) {
            $this->upsert($connection, $rootId, $name, $index + 1);
        }
    }

    private function upsert(PDO $connection, ?int $parentId, string $name, int $sortOrder): int
    {
        $slug = SlugGenerator::slugify($name);

        $existing = $connection->prepare('SELECT id FROM categories WHERE slug = :slug');
        $existing->execute(['slug' => $slug]);
        $id = $existing->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        $stmt = $connection->prepare(
            'INSERT INTO categories (parent_id, name, slug, status, sort_order)
             VALUES (:parent_id, :name, :slug, :status, :sort_order)'
        );
        $stmt->execute([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'sort_order' => $sortOrder,
        ]);

        return (int) $connection->lastInsertId();
    }
};
