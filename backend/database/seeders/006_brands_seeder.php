<?php

declare(strict_types=1);

use App\Application\Support\SlugGenerator;
use App\Infrastructure\Database\Seeder;

/**
 * Marcas comunes en el mercado de motocicletas, como taxonomía base
 * (igual criterio que el seeder de categorías).
 */
return new class extends Seeder {
    public function run(PDO $connection): void
    {
        $brands = ['Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'Bajaj', 'AKT', 'KTM'];

        $stmt = $connection->prepare(
            'INSERT INTO brands (name, slug, status) VALUES (:name, :slug, :status)
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        );

        foreach ($brands as $name) {
            $stmt->execute(['name' => $name, 'slug' => SlugGenerator::slugify($name), 'status' => 'active']);
        }
    }
};
