<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Support\SlugGenerator;
use PHPUnit\Framework\TestCase;

final class SlugGeneratorTest extends TestCase
{
    public function test_slugify_normaliza_acentos_y_espacios(): void
    {
        $this->assertSame('casco-integral-xyz', SlugGenerator::slugify('Casco Integral XYZ'));
        $this->assertSame('electronica', SlugGenerator::slugify('Electrónica'));
        $this->assertSame('cambio-de-aceite', SlugGenerator::slugify('  Cambio de Aceite!!  '));
    }

    public function test_unique_agrega_sufijo_si_el_slug_base_ya_existe(): void
    {
        $taken = ['casco-moto', 'casco-moto-2'];
        $exists = fn (string $slug) => in_array($slug, $taken, true);

        $this->assertSame('casco-moto-3', SlugGenerator::unique('Casco Moto', $exists));
    }

    public function test_unique_no_agrega_sufijo_si_el_slug_esta_libre(): void
    {
        $this->assertSame('producto-nuevo', SlugGenerator::unique('Producto Nuevo', fn () => false));
    }
}
