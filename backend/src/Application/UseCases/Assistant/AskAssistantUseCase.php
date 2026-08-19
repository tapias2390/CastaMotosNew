<?php

declare(strict_types=1);

namespace App\Application\UseCases\Assistant;

use App\Application\Ai\AiProviderFactory;
use App\Domain\Repositories\AiConversationRepositoryInterface;
use App\Domain\Repositories\PaymentMethodRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Infrastructure\Config\Config;
use App\Infrastructure\Persistence\PdoSiteSettingsRepository;

/**
 * Fase 11: el bot responde preguntas sobre productos/servicios/la
 * plataforma. Nunca inventa catálogo — busca en products/services de
 * verdad (mismo buscador del sitio) antes de responder, sea cual sea el
 * camino de abajo.
 *
 * Dos caminos, en este orden:
 *  1. Si hay un proveedor de IA configurado (AI_PROVIDER/AI_API_KEY), se le
 *     pasa el catálogo encontrado como contexto obligatorio.
 *  2. Si NO hay proveedor (o falló la llamada), responde con reglas fijas
 *     por palabras clave — sin IA, sin costo, sin credenciales de terceros.
 *     Nunca deja al usuario sin respuesta solo porque no hay IA conectada.
 */
final class AskAssistantUseCase
{
    private const HISTORY_LIMIT = 12;
    private const CATALOG_MATCHES_PER_TYPE = 4;

    public function __construct(
        private AiConversationRepositoryInterface $conversations,
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services,
        private PaymentMethodRepositoryInterface $paymentMethods,
        private PdoSiteSettingsRepository $siteSettings
    ) {
    }

    /** @return array{conversation_id: int, reply: string} */
    public function handle(?int $userId, ?int $conversationId, string $message): array
    {
        $conversationId = ($conversationId !== null && $this->conversations->belongsTo($conversationId, $userId))
            ? $conversationId
            : $this->conversations->create($userId);

        $this->conversations->appendMessage($conversationId, 'user', $message);

        $catalog = $this->searchCatalog($message);

        try {
            $history = $this->conversations->recentMessages($conversationId, self::HISTORY_LIMIT);
            $reply = AiProviderFactory::make()->reply($this->buildSystemPrompt($catalog), $history);
        } catch (\Throwable $e) {
            // Sin proveedor configurado, o la llamada falló (red, límite del
            // proveedor, etc.): se responde igual con reglas fijas en vez de
            // dejar al usuario sin nada — ver buildRuleBasedReply().
            $reply = $this->buildRuleBasedReply($message, $catalog);
        }

        $this->conversations->appendMessage($conversationId, 'assistant', $reply);

        return ['conversation_id' => $conversationId, 'reply' => $reply];
    }

    /** @return array{products: array, services: array} */
    private function searchCatalog(string $query): array
    {
        return [
            'products' => $this->products->paginate(['search' => $query, 'per_page' => self::CATALOG_MATCHES_PER_TYPE])['data'] ?? [],
            'services' => $this->services->paginate(['search' => $query, 'per_page' => self::CATALOG_MATCHES_PER_TYPE])['data'] ?? [],
        ];
    }

    private function buildSystemPrompt(array $catalog): string
    {
        $shippingFlat = (float) Config::get('app.shipping.flat_rate', 12000);
        $shippingFree = (float) Config::get('app.shipping.free_threshold', 300000);
        $catalogText = $this->formatCatalogForPrompt($catalog);

        return <<<PROMPT
        Eres el asistente virtual de CASTAMOTO, un marketplace colombiano de repuestos, accesorios y servicios para motocicletas.

        Reglas estrictas:
        - Respondé SOLO sobre productos/servicios de CASTAMOTO, cómo comprar, envíos, pagos o la plataforma en general.
        - Si te preguntan algo sin relación con esto, decí amablemente que solo podés ayudar con temas de CASTAMOTO.
        - NUNCA inventes productos, precios, stock ni políticas que no aparezcan en el "Catálogo relevante" de abajo o en esta información. Si no tenés el dato, decilo — no lo inventes.
        - Sé breve y concreto (2-4 oraciones), en español, tono cercano pero profesional.
        - No des consejos médicos, legales ni de otro tema ajeno al negocio.

        Información general:
        - Envío a domicilio: tarifa plana de \${$this->formatCop($shippingFlat)}, GRATIS en compras desde \${$this->formatCop($shippingFree)}.
        - También hay opción de recogida en tienda (envío \$0).
        - El pedido se puede pagar con los métodos habilitados que se muestran en el checkout.

        Catálogo relevante para esta pregunta:
        {$catalogText}
        PROMPT;
    }

    private function formatCatalogForPrompt(array $catalog): string
    {
        if (empty($catalog['products']) && empty($catalog['services'])) {
            return '(Sin coincidencias directas en el catálogo para esta pregunta — si el usuario pregunta por algo puntual que no aparece acá, decile que no encontrás ese producto/servicio ahora mismo, en vez de inventar uno.)';
        }

        $lines = [];
        foreach ($catalog['products'] as $product) {
            $lines[] = "- [Producto] {$product['name']} — \${$this->formatCop((float) $product['price'])} — stock: {$product['stock']}";
        }
        foreach ($catalog['services'] as $service) {
            $lines[] = "- [Servicio] {$service['name']} — \${$this->formatCop((float) $service['price'])}";
        }

        return implode("\n", $lines);
    }

    /**
     * Sin IA: reglas fijas por palabras clave, siempre con datos REALES
     * (catálogo ya buscado arriba, medios de pago habilitados, términos
     * guardados en la BD) — nunca un texto inventado. Cubre los casos que
     * pidió el negocio: políticas, consultas generales de CASTAMOTO,
     * productos/servicios y lavados.
     */
    private function buildRuleBasedReply(string $message, array $catalog): string
    {
        $normalized = $this->normalize($message);

        // Intenciones específicas PRIMERO — si la pregunta menciona lavado,
        // políticas, envío o pago, esa es la respuesta correcta aunque el
        // buscador del catálogo también haya encontrado algo suelto por una
        // palabra en común (ej. "política de devolución" no debe listar
        // productos solo porque "de" aparece en alguna descripción).

        // 0) Saludo — corta y evita caer directo en "no encontré información",
        // que queda feo como primera respuesta a un simple "Hola".
        if ($this->isGreeting($normalized)) {
            return '¡Hola! Preguntame por un producto o servicio puntual (ej. "cascos", "cambio de aceite"), por el lavado de motos/cascos, envíos, medios de pago o nuestras políticas.';
        }

        // 1) Lavado de motos/cascos — página dedicada con reserva paso a paso.
        if ($this->containsAny($normalized, ['lavado', 'lavar', 'lavada'])) {
            return 'Tenemos Lavado de Moto y Lavado de Casco con reserva de fecha y hora — entrá a la sección "Lavado de Motos y Cascos" desde el inicio (o a /lavado) para ver el precio actual y reservar.';
        }

        // 2) Políticas / garantías / devoluciones — términos reales guardados en la BD.
        if ($this->containsAny($normalized, ['politica', 'garantia', 'devolucion', 'retracto', 'terminos', 'condiciones'])) {
            $terms = $this->siteSettings->get('terms_and_conditions');
            if ($terms) {
                $excerpt = mb_substr(trim($terms), 0, 320);
                return "{$excerpt}…\n\nPodés ver el texto completo en /terminos.";
            }
            return 'Todavía no publicamos el texto completo de términos y condiciones — escribinos por WhatsApp si tenés una duda puntual.';
        }

        // 3) Envío.
        if ($this->containsAny($normalized, ['envio', 'domicilio', 'entrega'])) {
            $flat = $this->formatCop((float) Config::get('app.shipping.flat_rate', 12000));
            $free = $this->formatCop((float) Config::get('app.shipping.free_threshold', 300000));
            return "El envío a domicilio tiene una tarifa plana de \${$flat}, y es GRATIS en compras desde \${$free}. También podés elegir recogida en tienda (envío \$0) al finalizar tu compra.";
        }

        // 4) Pago — medios REALMENTE habilitados ahora mismo (nunca una lista fija que podría mentir).
        if ($this->containsAny($normalized, ['pago', 'pagar', 'tarjeta', 'efectivo', 'transferencia'])) {
            $methods = $this->paymentMethods->listEnabled();
            if (empty($methods)) {
                return 'Ahora mismo no hay medios de pago habilitados — escribinos por WhatsApp para coordinar.';
            }
            $names = implode(', ', array_map(static fn (array $m) => $m['name'], $methods));
            return "Los medios de pago disponibles ahora son: {$names}. Los elegís al finalizar tu compra en el checkout.";
        }

        // 5) Ninguna intención específica: si el catálogo encontró algo relacionado, mostrarlo.
        // El buscador (LIKE/FULLTEXT del sitio) puede matchear una frase larga
        // por una palabra suelta en común con una descripción — se limita a
        // preguntas cortas (estilo "tienen X"), que es como la gente busca un
        // producto de verdad; una frase larga de varias palabras probablemente
        // no es una búsqueda de catálogo, va directo al fallback honesto de abajo.
        $wordCount = count(array_filter(explode(' ', trim($normalized))));
        if ($wordCount <= 6 && (!empty($catalog['products']) || !empty($catalog['services']))) {
            $lines = ['Esto encontré en el catálogo:'];
            foreach (array_slice($catalog['products'], 0, 3) as $product) {
                $lines[] = "🔧 {$product['name']} — \${$this->formatCop((float) $product['price'])}";
            }
            foreach (array_slice($catalog['services'], 0, 3) as $service) {
                $lines[] = "🛠️ {$service['name']} — \${$this->formatCop((float) $service['price'])}";
            }
            $lines[] = 'Podés ver el detalle y comprar/reservar desde el catálogo del sitio.';

            return implode("\n", $lines);
        }

        // 6) Fallback honesto: nunca inventar, siempre dar un camino real.
        return 'No encontré información puntual sobre eso. Podés mirar todo el catálogo en /productos y /servicios, reservar un lavado en /lavado, o escribirnos por el botón de WhatsApp para hablar con alguien del equipo.';
    }

    /** Un saludo suelto (mensaje corto que ES básicamente el saludo, no que lo menciona de paso). */
    private function isGreeting(string $normalized): bool
    {
        $greetings = ['hola', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'hey', 'que tal', 'hi'];
        $trimmed = trim($normalized, " ¡!¿?.");

        return in_array($trimmed, $greetings, true);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /** Minúsculas y sin tildes, para que "envío"/"envio"/"ENVÍO" matcheen igual. */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $replacements = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'];

        return strtr($text, $replacements);
    }

    private function formatCop(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
