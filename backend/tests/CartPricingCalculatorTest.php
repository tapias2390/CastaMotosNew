<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Support\CartPricingCalculator;
use PHPUnit\Framework\TestCase;

final class CartPricingCalculatorTest extends TestCase
{
    public function test_calcula_subtotal_descuento_impuestos_y_envio(): void
    {
        $items = [
            ['unit_price' => 100000.0, 'quantity' => 2, 'discount_percentage' => 10.0, 'tax_rate' => 19.0],
        ];

        // subtotal = 200000; descuento 10% = 20000; base gravable = 180000; iva 19% = 34200
        $result = CartPricingCalculator::calculate($items, 'domicilio', 12000.0, 300000.0);

        $this->assertSame(200000.0, $result['subtotal']);
        $this->assertSame(20000.0, $result['discount_total']);
        $this->assertSame(34200.0, $result['tax_total']);
        $this->assertSame(12000.0, $result['shipping_total']);
        $this->assertSame(226200.0, $result['total']); // 180000 + 34200 + 12000
    }

    public function test_envio_gratis_sobre_el_umbral(): void
    {
        $items = [
            ['unit_price' => 350000.0, 'quantity' => 1, 'discount_percentage' => 0.0, 'tax_rate' => 0.0],
        ];

        $result = CartPricingCalculator::calculate($items, 'domicilio', 12000.0, 300000.0);

        $this->assertSame(0.0, $result['shipping_total']);
    }

    public function test_recogida_en_tienda_no_cobra_envio_aunque_no_llegue_al_umbral(): void
    {
        $items = [
            ['unit_price' => 10000.0, 'quantity' => 1, 'discount_percentage' => 0.0, 'tax_rate' => 0.0],
        ];

        $result = CartPricingCalculator::calculate($items, 'recogida_tienda', 12000.0, 300000.0);

        $this->assertSame(0.0, $result['shipping_total']);
    }

    public function test_carrito_vacio_no_cobra_envio(): void
    {
        $result = CartPricingCalculator::calculate([], 'domicilio', 12000.0, 300000.0);

        $this->assertSame(0.0, $result['shipping_total']);
        $this->assertSame(0.0, $result['total']);
    }
}
