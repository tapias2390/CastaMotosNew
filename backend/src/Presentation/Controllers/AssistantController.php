<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Assistant\AskAssistantUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoAiConversationRepository;
use App\Infrastructure\Persistence\PdoPaymentMethodRepository;
use App\Infrastructure\Persistence\PdoProductRepository;
use App\Infrastructure\Persistence\PdoServiceRepository;
use App\Infrastructure\Persistence\PdoSiteSettingsRepository;

/**
 * Bot de preguntas (Fase 11) — público (funciona para invitados, igual que
 * el carrito) pero si hay sesión la conversación queda asociada al usuario.
 */
final class AssistantController
{
    public function ask(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'message' => 'required|max:1000',
            'conversation_id' => 'integer',
        ])->validate();

        /** @var User|null $user */
        $user = $request->attribute('auth_user');

        $connection = Connection::get();
        $useCase = new AskAssistantUseCase(
            new PdoAiConversationRepository($connection),
            new PdoProductRepository($connection),
            new PdoServiceRepository($connection),
            new PdoPaymentMethodRepository($connection),
            new PdoSiteSettingsRepository($connection)
        );

        $result = $useCase->handle(
            $user?->id,
            isset($data['conversation_id']) ? (int) $data['conversation_id'] : null,
            $data['message']
        );

        Response::success($result);
    }
}
