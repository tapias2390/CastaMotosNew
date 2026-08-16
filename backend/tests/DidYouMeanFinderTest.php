<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Support\DidYouMeanFinder;
use PHPUnit\Framework\TestCase;

final class DidYouMeanFinderTest extends TestCase
{
    public function test_sugiere_el_candidato_mas_cercano(): void
    {
        $candidates = ['Casco Integral', 'Guantes de Cuero', 'Llanta Trasera'];

        $this->assertSame('Casco Integral', DidYouMeanFinder::find('Casko Integral', $candidates));
    }

    public function test_no_sugiere_nada_si_hay_coincidencia_exacta(): void
    {
        $candidates = ['Casco Integral', 'Guantes de Cuero'];

        $this->assertNull(DidYouMeanFinder::find('Casco Integral', $candidates));
    }

    public function test_no_sugiere_nada_si_esta_demasiado_lejos(): void
    {
        $candidates = ['Casco Integral', 'Guantes de Cuero'];

        $this->assertNull(DidYouMeanFinder::find('xyz completamente distinto', $candidates));
    }

    public function test_devuelve_null_con_lista_vacia(): void
    {
        $this->assertNull(DidYouMeanFinder::find('casco', []));
    }
}
