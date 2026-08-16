<?php

declare(strict_types=1);

namespace Tests;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Router;
use PHPUnit\Framework\TestCase;

/**
 * Prueba de humo de la Fase 1: confirma que la configuración carga
 * correctamente y que el Router hace match/despacha como se espera,
 * sin depender de una base de datos real.
 */
final class ArchitectureSmokeTest extends TestCase
{
    public function test_config_carga_valores_por_defecto(): void
    {
        $this->assertSame('CASTAMOTO', Config::get('app.name'));
        $this->assertNotNull(Config::get('database.host'));
    }

    public function test_router_hace_match_de_ruta_con_parametro(): void
    {
        $router = new Router();
        $captured = null;

        $router->get('api/products/{id}', function (Request $request, string $id) use (&$captured) {
            $captured = $id;
        });

        $_GET['__route'] = 'api/products/42';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $router->dispatch(Request::fromGlobals());

        $this->assertSame('42', $captured);
    }
}
