<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\UseCases\Profile\UpdateProfileUseCase;
use App\Application\UseCases\Profile\UploadAvatarUseCase;
use App\Application\Validation\Validator;
use App\Domain\Entities\User;
use App\Exceptions\ValidationException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Infrastructure\Persistence\PdoUserRepository;

final class ProfileController
{
    private PdoUserRepository $users;

    public function __construct()
    {
        $this->users = new PdoUserRepository(Connection::get());
    }

    public function show(Request $request): void
    {
        /** @var User $user */
        $user = $request->attribute('auth_user');

        Response::success($user->toArray());
    }

    public function update(Request $request): void
    {
        $data = Validator::make($request->input(), [
            'name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'phone' => 'max:30',
        ])->validate();

        /** @var User $user */
        $user = $request->attribute('auth_user');

        (new UpdateProfileUseCase($this->users))->handle($user->id, $data);

        $updated = $this->users->findById($user->id);

        Response::success($updated->toArray(), 'Perfil actualizado correctamente.');
    }

    public function uploadAvatar(Request $request): void
    {
        $file = $request->file('avatar');
        if ($file === null) {
            throw new ValidationException('No fue posible subir la imagen.', [
                'avatar' => ['Debes adjuntar un archivo con el campo "avatar".'],
            ]);
        }

        /** @var User $user */
        $user = $request->attribute('auth_user');

        $filename = (new UploadAvatarUseCase($this->users))->handle($user->id, $file);

        Response::success(['avatar' => $filename, 'url' => '/api/media/avatars/' . $filename], 'Foto de perfil actualizada.');
    }
}
