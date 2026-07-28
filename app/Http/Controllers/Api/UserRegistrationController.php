<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AccountAlreadyExistsException;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserRegistrationResource;
use App\Services\UserRegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserRegistrationController
{
    private const SUCCESS_MESSAGE = 'Cadastro realizado com sucesso. Sua conta foi criada, mas ainda precisa ser ativada por um administrador do sistema para permitir o acesso.';

    public function __construct(private readonly UserRegistrationService $userRegistrationService)
    {
    }

    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userRegistrationService->register($request->validated());
        } catch (AccountAlreadyExistsException) {
            return ApiResponse::error(
                message: 'Ja existe uma conta cadastrada com este e-mail.',
                status: Response::HTTP_CONFLICT,
                code: 'ACCOUNT_ALREADY_EXISTS',
                errors: [
                    'email' => ['Informe outro e-mail ou solicite a ativacao da conta existente.'],
                ],
            );
        }

        return ApiResponse::success(
            data: (new UserRegistrationResource($user))->resolve(),
            message: self::SUCCESS_MESSAGE,
            status: Response::HTTP_CREATED,
        );
    }
}
