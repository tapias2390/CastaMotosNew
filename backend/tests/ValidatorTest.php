<?php

declare(strict_types=1);

namespace Tests;

use App\Application\Validation\Validator;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_pasa_cuando_los_datos_son_validos(): void
    {
        $data = Validator::make(
            ['email' => 'test@castamoto.local', 'password' => 'secreto123', 'password_confirmation' => 'secreto123'],
            ['email' => 'required|email', 'password' => 'required|min:8|confirmed']
        )->validate();

        $this->assertSame('test@castamoto.local', $data['email']);
    }

    public function test_falla_si_falta_un_campo_requerido(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make([], ['email' => 'required|email'])->validate();
    }

    public function test_falla_si_el_correo_no_es_valido(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make(['email' => 'no-es-un-correo'], ['email' => 'required|email'])->validate();
    }

    public function test_falla_si_la_confirmacion_no_coincide(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make(
            ['password' => 'secreto123', 'password_confirmation' => 'otro'],
            ['password' => 'required|confirmed']
        )->validate();
    }

    public function test_regla_accepted_exige_valor_verdadero(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make(['terms_accepted' => false], ['terms_accepted' => 'accepted'])->validate();
    }

    public function test_regla_numeric_rechaza_texto_no_numerico(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make(['price' => 'gratis'], ['price' => 'numeric'])->validate();
    }

    public function test_regla_gte_rechaza_valores_menores_al_minimo(): void
    {
        $this->expectException(ValidationException::class);

        Validator::make(['stock' => '-1'], ['stock' => 'numeric|gte:0'])->validate();
    }

    public function test_regla_gte_acepta_el_valor_limite(): void
    {
        $data = Validator::make(['stock' => '0'], ['stock' => 'numeric|gte:0'])->validate();

        $this->assertSame('0', $data['stock']);
    }
}
