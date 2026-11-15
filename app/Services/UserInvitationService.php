<?php

namespace App\Services;

use App\Exceptions\AccountAlreadyExistsException;
use App\Exceptions\InvalidInvitationTokenException;
use App\Mail\UserInvitationMail;
use App\Models\ProfileType;
use App\Models\Usuario;
use App\Repositories\Contracts\PasswordResetTokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserInvitationService
{
    private const COMMON_USER_TYPE = 2;

    private const PENDING_STATUS = 2;

    private const ACTIVE_STATUS = 1;

    private const ADMIN_CREATED = 2;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordResetTokenRepositoryInterface $tokenRepository,
    ) {}

    public function invite(Usuario $inviter, string $email, int $profileTypeId = ProfileType::USER_ID): Usuario
    {
        if ($profileTypeId !== ProfileType::USER_ID && ! $inviter->hasPermission('users.change_profile')) {
            throw new AccessDeniedHttpException;
        }

        $mailer = (string) config('mail.default');
        if (config("mail.mailers.{$mailer}.transport") === 'log') {
            throw new RuntimeException('Invitation links cannot be sent through the log mailer.');
        }

        $email = mb_strtolower(trim($email));
        $token = Str::random(64);

        try {
            $user = DB::transaction(function () use ($email, $token, $profileTypeId): Usuario {
                $existing = $this->userRepository->findByEmailForUpdate($email);

                if ($existing !== null && ! $this->isPendingInvitation($existing)) {
                    throw new AccountAlreadyExistsException;
                }

                if ($existing !== null) {
                    $this->userRepository->update($existing, ['FK_TIPO_PERFIL' => $profileTypeId]);
                }

                $user = $existing ?? $this->userRepository->create([
                    'FK_TIPO_PERFIL' => $profileTypeId,
                    'FK_TIPO_USUARIO' => self::COMMON_USER_TYPE,
                    'NO_USUARIO' => null,
                    'NO_ORGAO_EMPRESA' => null,
                    'NO_SETOR' => null,
                    'NU_MATRICULA' => null,
                    'DS_EMAIL' => $email,
                    'DS_SENHA' => null,
                    'ST_ATIVO' => self::PENDING_STATUS,
                    'TP_ATIVACAO' => self::ADMIN_CREATED,
                    'DH_ATIVACAO' => null,
                ]);

                $this->tokenRepository->replace($email, Hash::make($token));

                return $user;
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueEmailViolation($exception)) {
                throw new AccountAlreadyExistsException(previous: $exception);
            }

            throw $exception;
        }

        $invitationUrl = rtrim((string) config('app.frontend_url'), '/')
            .'/definir-senha-inicial?'.http_build_query([
                'email' => $email,
                'token' => $token,
            ]);

        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);
        Mail::to($email)->send(new UserInvitationMail($email, $invitationUrl, $expiresInMinutes));

        return $user;
    }

    public function validateToken(string $email, string $token): bool
    {
        $user = $this->userRepository->findByEmail(mb_strtolower(trim($email)));

        if ($user === null || ! $this->isPendingInvitation($user)) {
            return false;
        }

        $tokenRecord = $this->tokenRepository->find(mb_strtolower(trim($email)));

        return $tokenRecord !== null && $this->isValidTokenRecord($tokenRecord, $token);
    }

    public function complete(string $email, string $token, string $password, array $profileData): Usuario
    {
        $email = mb_strtolower(trim($email));

        return DB::transaction(function () use ($email, $token, $password, $profileData): Usuario {
            $user = $this->userRepository->findByEmailForUpdate($email);
            $tokenRecord = $this->tokenRepository->findForUpdate($email);

            if ($user === null
                || ! $this->isPendingInvitation($user)
                || $tokenRecord === null
                || ! $this->isValidTokenRecord($tokenRecord, $token)) {
                throw new InvalidInvitationTokenException;
            }

            $this->tokenRepository->delete($email);

            return $this->userRepository->update($user, [
                'NO_USUARIO' => $profileData['name'],
                'NO_ORGAO_EMPRESA' => $profileData['organization'],
                'NO_SETOR' => $profileData['department'],
                'NU_MATRICULA' => $profileData['registration_number'],
                'DS_SENHA' => Hash::make($password),
                'ST_ATIVO' => self::ACTIVE_STATUS,
                'DH_ATIVACAO' => now(),
            ]);
        });
    }

    private function isPendingInvitation(Usuario $user): bool
    {
        return (int) $user->ST_ATIVO === self::PENDING_STATUS
            && (int) $user->FK_TIPO_USUARIO === self::COMMON_USER_TYPE
            && (int) $user->TP_ATIVACAO === self::ADMIN_CREATED
            && $user->DS_SENHA === null;
    }

    private function isValidTokenRecord(object $record, string $token): bool
    {
        if (! is_string($record->created_at) || $record->created_at === '') {
            return false;
        }

        $createdAt = Carbon::parse($record->created_at);
        $expiresAt = $createdAt->addMinutes((int) config('auth.passwords.users.expire', 60));

        return $expiresAt->isFuture() && Hash::check($token, $record->token);
    }

    private function isUniqueEmailViolation(QueryException $exception): bool
    {
        $code = (string) $exception->getCode();
        $message = strtolower($exception->getMessage());

        return in_array($code, ['23000', '23505'], true)
            || (str_contains($message, 'unique') && str_contains($message, 'email'));
    }
}
