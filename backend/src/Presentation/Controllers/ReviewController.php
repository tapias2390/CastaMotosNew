<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Reviews\SubmitReviewUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoReviewRepository;

final class ReviewController
{
    private PdoReviewRepository $reviews;

    public function __construct()
    {
        $this->reviews = new PdoReviewRepository(Connection::get());
    }

    /** Pública (sección 26): cualquiera puede LEER reseñas, solo quien compró puede escribirlas. */
    public function index(Request $request): void
    {
        $data = Validator::make($request->query(), [
            'type' => 'required|in:product,service',
            'id' => 'required|integer',
        ])->validate();

        Response::success($this->reviews->listApprovedForItem($data['type'], (int) $data['id']));
    }

    public function store(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'type' => 'required|in:product,service',
            'id' => 'required|integer',
            'rating' => 'required|integer|gte:1|lte:5',
            'comment' => 'max:1000',
        ])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $reviewId = (new SubmitReviewUseCase($this->reviews))->handle(
            $user->id,
            $data['type'],
            (int) $data['id'],
            (int) $data['rating'],
            $data['comment'] ?? null
        );

        Response::success(['id' => $reviewId], 'Reseña publicada correctamente.', 201);
    }
}
