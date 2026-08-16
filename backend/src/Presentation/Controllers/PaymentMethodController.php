<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoPaymentMethodRepository;

/**
 * Expone los métodos de pago habilitados (sección 21) para que el checkout
 * del frontend sepa qué opciones mostrar. La administración completa
 * (activar/desactivar) es la Fase 7.
 */
final class PaymentMethodController
{
    public function index(Request $request): void
    {
        $repository = new PdoPaymentMethodRepository(Connection::get());

        Response::success($repository->listEnabled());
    }
}
