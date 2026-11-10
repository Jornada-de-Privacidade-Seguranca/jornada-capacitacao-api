<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AccountAlreadyExistsException;
use App\Exceptions\InvalidInvitationTokenException;
use App\Http\Requests\CompleteUserInvitationRequest;
use App\Http\Requests\CreateUserInvitationRequest;
use App\Http\Requests\ValidateUserInvitationRequest;
use App\Http\Resources\InvitedUserResource;
use App\Services\UserInvitationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

class UserInvitationController
{
    public function __construct(private readonly UserInvitationService $service) {}

    public function store(CreateUserInvitationRequest $request): JsonResponse
    {
        try {
            $user = $this->service->invite($request->validated('email'));
        } catch (AccountAlreadyExistsException) {
            return ApiResponse::error(
                message: 'Ja existe uma conta cadastrada com este e-mail.',
                status: Response::HTTP_CONFLICT,
                code: 'ACCOUNT_ALREADY_EXISTS',
                errors: ['email' => ['Informe outro e-mail.']],
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel enviar o convite no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'USER_INVITATION_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new InvitedUserResource($user))->resolve(),
            message: 'Convite enviado com sucesso.',
            status: Response::HTTP_CREATED,
        );
    }

    public function validate(ValidateUserInvitationRequest $request): JsonResponse
    {
        try {
            $isValid = $this->service->validateToken(
                $request->validated('email'),
                $request->validated('token'),
            );
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel validar o convite no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'INVITATION_VALIDATION_FAILED',
            );
        }

        if (! $isValid) {
            return $this->invalidTokenResponse();
        }

        return ApiResponse::success(
            data: ['email' => $request->validated('email'), 'is_valid' => true],
            message: 'Convite valido.',
        );
    }

    public function complete(CompleteUserInvitationRequest $request): JsonResponse
    {
        try {
            $user = $this->service->complete(
                $request->validated('email'),
                $request->validated('token'),
                $request->validated('password'),
            );
        } catch (InvalidInvitationTokenException) {
            return $this->invalidTokenResponse();
        } catch (Throwable) {
            return ApiResponse::error(
                message: 'Nao foi possivel definir a senha no momento.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
                code: 'INITIAL_PASSWORD_FAILED',
            );
        }

        return ApiResponse::success(
            data: (new InvitedUserResource($user))->resolve(),
            message: 'Senha inicial definida e conta ativada com sucesso.',
        );
    }

    private function invalidTokenResponse(): JsonResponse
    {
        return ApiResponse::error(
            message: 'O convite e invalido, expirou ou ja foi utilizado.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_INVITATION_TOKEN',
        );
    }
}
