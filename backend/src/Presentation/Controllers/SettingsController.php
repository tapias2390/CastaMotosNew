<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Infrastructure\Config\Config;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;

/**
 * Configuración pública y segura de exponer al frontend (nunca secretos ni
 * llaves privadas — para eso está el archivo .env, que el frontend nunca lee
 * directamente). Hoy solo expone el contacto de WhatsApp; se amplía aquí si
 * el frontend necesita otro dato de configuración no sensible.
 */
final class SettingsController
{
    public function publicSettings(Request $request): void
    {
        Response::success([
            'contact_whatsapp_number' => (string) Config::get('app.contact.whatsapp_number', ''),
        ]);
    }
}
