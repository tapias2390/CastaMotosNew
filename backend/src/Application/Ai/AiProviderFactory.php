<?php

declare(strict_types=1);

namespace App\Application\Ai;

use App\Exceptions\ValidationException;
use App\Infrastructure\Config\Config;

/**
 * Resuelve el proveedor de IA a partir de AI_PROVIDER/AI_API_KEY/AI_MODEL
 * (backend/.env) — mismo criterio honesto que PaymentGatewayFactory: si no
 * hay credenciales reales cargadas, el asistente lo dice claro en vez de
 * fingir que puede responder.
 */
final class AiProviderFactory
{
    private const DEFAULT_MODELS = [
        'anthropic' => 'claude-3-5-haiku-latest',
        'openai' => 'gpt-4o-mini',
    ];

    /** @throws ValidationException si no hay proveedor configurado o no se reconoce. */
    public static function make(): AiProviderInterface
    {
        $provider = (string) Config::get('app.ai.provider', '');
        $apiKey = (string) Config::get('app.ai.api_key', '');

        if ($provider === '' || $apiKey === '') {
            throw new ValidationException('El asistente todavía no está disponible.', [
                'assistant' => ['El asistente de preguntas todavía no tiene un proveedor de IA configurado.'],
            ]);
        }

        if (!isset(self::DEFAULT_MODELS[$provider])) {
            throw new ValidationException('El asistente todavía no está disponible.', [
                'assistant' => ["El proveedor de IA \"{$provider}\" no está soportado."],
            ]);
        }

        $model = (string) Config::get('app.ai.model', '') ?: self::DEFAULT_MODELS[$provider];

        return match ($provider) {
            'anthropic' => new AnthropicAiProvider($apiKey, $model),
            'openai' => new OpenAiAiProvider($apiKey, $model),
        };
    }
}
