<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Support\OrderNumberGenerator;
use PHPUnit\Framework\TestCase;

final class OrderNumberGeneratorTest extends TestCase
{
    public function test_genera_un_numero_con_el_prefijo_esperado(): void
    {
        $number = OrderNumberGenerator::generate(fn () => false);

        $this->assertStringStartsWith('CM' . date('Ymd') . '-', $number);
    }

    public function test_reintenta_hasta_encontrar_uno_disponible(): void
    {
        $calls = 0;
        $number = OrderNumberGenerator::generate(function () use (&$calls) {
            $calls++;
            return $calls < 3; // los primeros 2 "ya existen", el 3ro no
        });

        $this->assertSame(3, $calls);
        $this->assertNotEmpty($number);
    }
}
