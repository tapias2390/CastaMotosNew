<?php

declare(strict_types=1);

namespace App\Application\UseCases\Assistant;

use App\Application\Ai\AiProviderFactory;
use App\Domain\Repositories\AiConversationRepositoryInterface;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\ServiceRepositoryInterface;
use App\Infrastructure\Config\Config;

/**
 * Fase 11: el bot responde preguntas sobre productos/servicios/la
 * plataforma. Nunca inventa catálogo — antes de llamar al proveedor de IA
 * se busca en products/services de verdad (mismo buscador del sitio) y
 * eso se le pasa como contexto, con instrucciones explícitas de no
 * responder con datos que no estén ahí.
 */
final class AskAssistantUseCase
{
    private const HISTORY_LIMIT = 12;
    private const CATALOG_MATCHES_PER_TYPE = 4;

    public function __construct(
        private AiConversationRepositoryInterface $conversations,
        private ProductRepositoryInterface $products,
        private ServiceRepositoryInterface $services
    ) {
    }

    /** @return array{conversation_id: int, reply: string} */
    public function handle(?int $userId, ?int $conversationId, string $message): array
    {
        $conversationId = ($conversationId !== null && $this->conversations->belongsTo($conversationId, $userId))
            ? $conversationId
            : $this->conversations->create($userId);

        $this->conversations->appendMessage($conversationId, 'user', $message);

        $history = $this->conversations->recentMessages($conversationId, self::HISTORY_LIMIT);
        $systemPrompt = $this->buildSystemPrompt($message);

        // AiProviderFactory::make() lanza ValidationException clara si no hay
        // proveedor configurado — se deja propagar tal cual (el controller no
        // necesita saber de proveedores de IA, solo de si funcionó o no).
        $reply = AiProviderFactory::make()->reply($systemPrompt, $history);

        $this->conversations->appendMessage($conversationId, 'assistant', $reply);

        return ['conversation_id' => $conversationId, 'reply' => $reply];
    }

    private function buildSystemPrompt(string $userMessage): string
    {
        $catalog = $this->searchCatalogContext($userMessage);
        $shippingFlat = (float) Config::get('app.shipping.flat_rate', 12000);
        $shippingFree = (float) Config::get('app.shipping.free_threshold', 300000);

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
        {$catalog}
        PROMPT;
    }

    private function searchCatalogContext(string $query): string
    {
        $products = $this->products->paginate(['search' => $query, 'per_page' => self::CATALOG_MATCHES_PER_TYPE])['data'] ?? [];
        $services = $this->services->paginate(['search' => $query, 'per_page' => self::CATALOG_MATCHES_PER_TYPE])['data'] ?? [];

        if (empty($products) && empty($services)) {
            return '(Sin coincidencias directas en el catálogo para esta pregunta — si el usuario pregunta por algo puntual que no aparece acá, decile que no encontrás ese producto/servicio ahora mismo, en vez de inventar uno.)';
        }

        $lines = [];
        foreach ($products as $product) {
            $lines[] = "- [Producto] {$product['name']} — \${$this->formatCop((float) $product['price'])} — stock: {$product['stock']}";
        }
        foreach ($services as $service) {
            $lines[] = "- [Servicio] {$service['name']} — \${$this->formatCop((float) $service['price'])}";
        }

        return implode("\n", $lines);
    }

    private function formatCop(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
