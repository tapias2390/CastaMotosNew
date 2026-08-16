<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Support\OrderStatusTransitions;
use PHPUnit\Framework\TestCase;

final class OrderStatusTransitionsTest extends TestCase
{
    public function test_permite_transiciones_validas(): void
    {
        $this->assertTrue(OrderStatusTransitions::isAllowed('PENDIENTE', 'CONFIRMADO'));
        $this->assertTrue(OrderStatusTransitions::isAllowed('PAGO_CONFIRMADO', 'PREPARANDO'));
        $this->assertTrue(OrderStatusTransitions::isAllowed('EN_CAMINO', 'ENTREGADO'));
    }

    public function test_rechaza_transiciones_invalidas(): void
    {
        $this->assertFalse(OrderStatusTransitions::isAllowed('PENDIENTE', 'ENTREGADO'));
        $this->assertFalse(OrderStatusTransitions::isAllowed('PREPARANDO', 'PENDIENTE'));
    }

    public function test_estados_terminales_no_tienen_salida(): void
    {
        $this->assertFalse(OrderStatusTransitions::isAllowed('CANCELADO', 'PENDIENTE'));
        $this->assertFalse(OrderStatusTransitions::isAllowed('DEVUELTO', 'ENTREGADO'));
        $this->assertTrue(OrderStatusTransitions::isTerminal('CANCELADO'));
        $this->assertTrue(OrderStatusTransitions::isTerminal('ENTREGADO'));
    }

    public function test_solo_cancelado_y_devuelto_restituyen_stock(): void
    {
        $this->assertTrue(OrderStatusTransitions::restoresStock('CANCELADO'));
        $this->assertTrue(OrderStatusTransitions::restoresStock('DEVUELTO'));
        $this->assertFalse(OrderStatusTransitions::restoresStock('ENTREGADO'));
    }
}
