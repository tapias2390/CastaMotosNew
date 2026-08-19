<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoSiteSettingsRepository;

/**
 * Configuración pública y segura de exponer al frontend (nunca secretos ni
 * llaves privadas — para eso está el archivo .env, que el frontend nunca lee
 * directamente). Hoy expone el contacto de WhatsApp (.env) y los textos
 * generales del sitio (tabla site_settings, migración 047) — se amplía aquí
 * si el frontend necesita otro dato de configuración no sensible.
 */
final class SettingsController
{
    public function publicSettings(Request $request): void
    {
        Response::success([
            'contact_whatsapp_number' => (string) Config::get('app.contact.whatsapp_number', ''),
        ]);
    }

    public function terms(Request $request): void
    {
        $settings = new PdoSiteSettingsRepository(Connection::get());

        Response::success([
            'content' => $settings->get('terms_and_conditions') ?? '',
        ]);
    }

    /** Edición desde /admin (permiso manage-settings) — el mismo texto que ya muestra /terminos. */
    public function updateTerms(Request $request): void
    {
        $data = \App\Application\Validation\Validator::make($request->input(), [
            'content' => 'required',
        ])->validate();

        $settings = new PdoSiteSettingsRepository(Connection::get());
        $settings->set('terms_and_conditions', $data['content']);

        Response::success(null, 'Términos y condiciones actualizados.');
    }
}
