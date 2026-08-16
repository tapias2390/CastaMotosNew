<?php

declare(strict_types=1);

namespace App\Database;

use App\Application\Support\SlugGenerator;
use PDO;

/**
 * Datos de MUESTRA (no de sistema) para poder visualizar el catálogo real
 * en el frontend: productos y servicios repartidos en las categorías/marcas
 * ya sembradas por 005_categories_seeder/006_brands_seeder (Fase 3).
 *
 * A propósito NO vive en /database/seeders (esos son datos de sistema que
 * corre `db:seed` siempre) — se ejecuta aparte con `php bin/console demo:seed`,
 * así queda claro que es contenido de demostración, no parte del setup base.
 *
 * Las imágenes son SVG generados en el momento (sin GD disponible en este
 * PHP): un ícono + el nombre de CASTAMOTO sobre la paleta negro/amarillo.
 * MediaController sirve .svg solo para estos archivos generados por el
 * servidor; la subida real de imágenes (UploadedFileValidator) sigue sin
 * aceptar SVG por el riesgo de XSS de un SVG con script embebido subido
 * por un usuario.
 */
final class DemoDataSeeder
{
    private string $basePath;

    public function __construct(private PDO $connection, string $basePath)
    {
        $this->basePath = $basePath;
    }

    public function run(): void
    {
        $categoryIds = $this->categoryIdsBySlug();
        $brandIds = $this->brandIdsBySlug();

        $this->seedProducts($categoryIds, $brandIds);
        $this->seedServices($categoryIds);
    }

    private function categoryIdsBySlug(): array
    {
        $stmt = $this->connection->query('SELECT id, slug FROM categories');
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['slug']] = (int) $row['id'];
        }

        return $map;
    }

    private function brandIdsBySlug(): array
    {
        $stmt = $this->connection->query('SELECT id, slug FROM brands');
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['slug']] = (int) $row['id'];
        }

        return $map;
    }

    private function seedProducts(array $categoryIds, array $brandIds): void
    {
        $products = [
            ['Casco Integral MT Thunder', 'cascos', 'yamaha', 'CASCO-INT-001', 380000, 450000, 15, 12, 3, '🪖', 'Casco integral de fibra de policarbonato, visor antirrayas y ventilación regulable.'],
            ['Casco Abierto Retro Urbano', 'cascos', 'akt', 'CASCO-ABI-002', 210000, null, 0, 8, 2, '🪖', 'Casco abierto estilo clásico, ideal para ciudad. Interior desmontable y lavable.'],
            ['Casco Cross Off-Road Pro', 'cascos', 'ktm', 'CASCO-CRO-003', 320000, null, 0, 3, 3, '🪖', 'Casco cross con visera larga y mentonera reforzada para terrenos exigentes.'],
            ['Guantes de Cuero Racing', 'guantes', null, 'GUANT-RAC-004', 95000, null, 0, 20, 4, '🧤', 'Guantes de cuero genuino con protección en nudillos, para uso deportivo.'],
            ['Guantes Táctiles Urbanos', 'guantes', null, 'GUANT-TAC-005', 65000, null, 0, 0, 3, '🧤', 'Guantes livianos compatibles con pantalla táctil, ideales para el día a día.'],
            ['Chaqueta Impermeable Touring', 'chaquetas', 'honda', 'CHAQ-TOU-006', 480000, 550000, 12, 6, 2, '🧥', 'Chaqueta con membrana impermeable, forro térmico removible y protecciones CE.'],
            ['Chaqueta de Cuero Café Racer', 'chaquetas', null, 'CHAQ-CAF-007', 520000, null, 0, 4, 2, '🧥', 'Chaqueta de cuero estilo café racer, corte clásico y protecciones incluidas.'],
            ['Llanta Deportiva 17" Trasera', 'llantas', 'suzuki', 'LLAN-DEP-008', 380000, null, 0, 10, 3, '🛞', 'Llanta de compuesto deportivo, excelente agarre en seco y mojado.'],
            ['Llanta Todo Terreno 21" Delantera', 'llantas', 'kawasaki', 'LLAN-TT-009', 290000, null, 0, 7, 2, '🛞', 'Llanta mixta para uso en carretera y trocha, banda de rodadura reforzada.'],
            ['Aceite Sintético 10W40 4L', 'lubricantes', null, 'ACEI-SIN-010', 145000, null, 0, 25, 6, '🛢️', 'Aceite 100% sintético para motores de alto rendimiento, presentación 4 litros.'],
            ['Aceite Mineral 20W50 1L', 'lubricantes', null, 'ACEI-MIN-011', 38000, null, 0, 40, 8, '🛢️', 'Aceite mineral de uso general, presentación 1 litro.'],
            ['Kit de Herramientas Básico 46 Piezas', 'herramientas', null, 'HERR-KIT-012', 175000, null, 0, 15, 3, '🔧', 'Set de herramientas con estuche, incluye llaves, destornilladores y puntas.'],
            ['Alarma GPS Antirrobo', 'electronica', null, 'ELEC-GPS-013', 220000, null, 0, 9, 2, '📡', 'Alarma con localización GPS y notificaciones a la app del celular.'],
            ['Baúl Trasero 45L', 'accesorios', 'bajaj', 'ACC-BAU-014', 260000, null, 0, 5, 2, '🎒', 'Baúl rígido de 45 litros con cerradura y kit de instalación universal.'],
            ['Kit de Frenos Delanteros', 'repuestos', 'yamaha', 'REP-FRE-015', 210000, null, 0, 14, 3, '⚙️', 'Kit de pastillas y disco delantero de alto rendimiento.'],
        ];

        foreach ($products as [$name, $categorySlug, $brandSlug, $sku, $price, $previousPrice, $discount, $stock, $minStock, $emoji, $description]) {
            if (!isset($categoryIds[$categorySlug])) {
                continue;
            }

            $existing = $this->connection->prepare('SELECT id FROM products WHERE sku = :sku');
            $existing->execute(['sku' => $sku]);
            if ($existing->fetchColumn() !== false) {
                continue;
            }

            $slug = SlugGenerator::unique($name, function (string $candidate) {
                $stmt = $this->connection->prepare('SELECT 1 FROM products WHERE slug = :slug');
                $stmt->execute(['slug' => $candidate]);
                return (bool) $stmt->fetchColumn();
            });

            $stmt = $this->connection->prepare(
                'INSERT INTO products (
                    category_id, brand_id, name, slug, description, short_description, sku,
                    price, previous_price, discount_percentage, tax_rate, stock, min_stock, status
                ) VALUES (
                    :category_id, :brand_id, :name, :slug, :description, :short_description, :sku,
                    :price, :previous_price, :discount, 19, :stock, :min_stock, \'active\'
                )'
            );
            $stmt->execute([
                'category_id' => $categoryIds[$categorySlug],
                'brand_id' => $brandSlug !== null ? ($brandIds[$brandSlug] ?? null) : null,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'short_description' => $description,
                'sku' => $sku,
                'price' => $price,
                'previous_price' => $previousPrice,
                'discount' => $discount,
                'stock' => $stock,
                'min_stock' => $minStock,
            ]);
            $productId = (int) $this->connection->lastInsertId();

            $this->connection->prepare(
                'INSERT INTO inventory (product_id, stock_current, stock_reserved, stock_minimum)
                 VALUES (:product_id, :stock, 0, :min_stock)'
            )->execute(['product_id' => $productId, 'stock' => $stock, 'min_stock' => $minStock]);

            $filename = $this->savePlaceholderImage('products', $emoji, 'CASTAMOTO');
            $this->connection->prepare(
                'INSERT INTO product_images (product_id, url, is_primary) VALUES (:product_id, :url, 1)'
            )->execute(['product_id' => $productId, 'url' => $filename]);
        }
    }

    private function seedServices(array $categoryIds): void
    {
        if (!isset($categoryIds['servicios'])) {
            return;
        }

        $services = [
            ['Cambio de Aceite', 45000, 30, 'Medellín', '🛢️', 'Cambio de aceite y filtro con producto de tu elección.'],
            ['Mantenimiento Preventivo 5.000 km', 120000, 90, 'Medellín', '🔧', 'Revisión general: frenos, luces, niveles, cadena y ajustes.'],
            ['Revisión de Frenos', 60000, 40, 'Bogotá', '⚙️', 'Diagnóstico y ajuste del sistema de frenos delantero y trasero.'],
            ['Instalación de Accesorios', 35000, 25, 'Bogotá', '🎒', 'Instalación de baúles, parrillas y accesorios en general.'],
            ['Diagnóstico Electrónico', 50000, 30, 'Cali', '📡', 'Escaneo del sistema eléctrico/electrónico con equipo especializado.'],
        ];

        foreach ($services as [$name, $price, $duration, $location, $emoji, $description]) {
            $existing = $this->connection->prepare('SELECT id FROM services WHERE name = :name');
            $existing->execute(['name' => $name]);
            if ($existing->fetchColumn() !== false) {
                continue;
            }

            $slug = SlugGenerator::unique($name, function (string $candidate) {
                $stmt = $this->connection->prepare('SELECT 1 FROM services WHERE slug = :slug');
                $stmt->execute(['slug' => $candidate]);
                return (bool) $stmt->fetchColumn();
            });

            $stmt = $this->connection->prepare(
                "INSERT INTO services (category_id, name, slug, description, price, duration_minutes, location, status)
                 VALUES (:category_id, :name, :slug, :description, :price, :duration, :location, 'active')"
            );
            $stmt->execute([
                'category_id' => $categoryIds['servicios'],
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'price' => $price,
                'duration' => $duration,
                'location' => $location,
            ]);
            $serviceId = (int) $this->connection->lastInsertId();

            $filename = $this->savePlaceholderImage('services', $emoji, 'CASTAMOTO');
            $this->connection->prepare(
                'INSERT INTO service_images (service_id, url) VALUES (:service_id, :url)'
            )->execute(['service_id' => $serviceId, 'url' => $filename]);
        }
    }

    private function savePlaceholderImage(string $subdirectory, string $emoji, string $label): string
    {
        $directory = $this->basePath . '/storage/uploads/' . $subdirectory;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.svg';
        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
          <rect width="600" height="600" rx="24" fill="#1e1e1e"/>
          <rect x="8" y="8" width="584" height="584" rx="20" fill="none" stroke="#f4c430" stroke-width="4"/>
          <text x="300" y="290" font-size="180" text-anchor="middle" dominant-baseline="central">{$emoji}</text>
          <text x="300" y="540" font-size="28" fill="#f4c430" font-family="Arial, sans-serif" font-weight="bold" text-anchor="middle">{$label}</text>
        </svg>
        SVG;

        file_put_contents($directory . '/' . $filename, $svg);

        return $filename;
    }
}
