<?php

declare(strict_types=1);

use App\Application\Support\SlugGenerator;
use App\Infrastructure\Database\Seeder;

/**
 * Árbol de categorías de ejemplo (sección 13 del prompt maestro). Es
 * taxonomía base del negocio, no datos de demostración descartables — por
 * eso vive en un seeder y no se crea vía API durante pruebas manuales.
 *
 * name_en (selector de idioma, sección nueva): categorías son vocabulario
 * genérico, no texto de negocio — se traducen acá mismo en vez de pedirle al
 * admin que las traduzca a mano desde un formulario que ni siquiera existe
 * (no hay CRUD de categorías en el admin, solo se gestionan por seeder).
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $rootId = $this->upsert($connection, null, 'Motocicletas', 'Motorcycles', 0);

        $children = [
            'Repuestos' => 'Spare parts',
            'Accesorios' => 'Accessories',
            'Cascos' => 'Helmets',
            'Guantes' => 'Gloves',
            'Chaquetas' => 'Jackets',
            'Llantas' => 'Tires',
            'Lubricantes' => 'Lubricants',
            'Electrónica' => 'Electronics',
            'Herramientas' => 'Tools',
            'Servicios' => 'Services',
        ];

        $index = 0;
        foreach ($children as $name => $nameEn) {
            $this->upsert($connection, $rootId, $name, $nameEn, ++$index);
        }
    }

    private function upsert(PDO $connection, ?int $parentId, string $name, string $nameEn, int $sortOrder): int
    {
        $slug = SlugGenerator::slugify($name);

        $existing = $connection->prepare('SELECT id FROM categories WHERE slug = :slug');
        $existing->execute(['slug' => $slug]);
        $id = $existing->fetchColumn();

        if ($id !== false) {
            // Ya existe (creada antes de que name_en existiera, o en una corrida
            // previa) — solo se completa la traducción, sin tocar el resto.
            $update = $connection->prepare('UPDATE categories SET name_en = :name_en WHERE id = :id');
            $update->execute(['name_en' => $nameEn, 'id' => $id]);

            return (int) $id;
        }

        $stmt = $connection->prepare(
            'INSERT INTO categories (parent_id, name, name_en, slug, status, sort_order)
             VALUES (:parent_id, :name, :name_en, :slug, :status, :sort_order)'
        );
        $stmt->execute([
            'parent_id' => $parentId,
            'name' => $name,
            'name_en' => $nameEn,
            'slug' => $slug,
            'status' => 'active',
            'sort_order' => $sortOrder,
        ]);

        return (int) $connection->lastInsertId();
    }
};
