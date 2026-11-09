<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_administrator_creates_a_pending_common_user_and_emails_a_hashed_one_time_token(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => 'NEW.USER@example.com'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'new.user@example.com')
            ->assertJsonPath('data.user_type_id', 2)
            ->assertJsonPath('data.active_status', 2)
            ->assertJsonPath('data.account_status', 'pending_initial_password')
            ->assertJsonMissingPath('data.token');

        $user = Usuario::query()->where('DS_EMAIL', 'new.user@example.com')->firstOrFail();
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'FK_TIPO_USUARIO' => 2,
            'ST_ATIVO' => 2,
            'TP_ATIVACAO' => 2,
            'DS_SENHA' => null,
            'DH_ATIVACAO' => null,
        ]);

        $record = DB::table('password_reset_tokens')->where('email', 'new.user@example.com')->first();
        $this->assertNotNull($record);
        $this->assertNotSame('plain-token', $record->token);

        $invitationToken = $this->sentInvitationToken();
        $this->assertNotSame('', $invitationToken);
        $this->assertTrue(Hash::check($invitationToken, $record->token));
        Mail::assertSent(UserInvitationMail::class, fn (UserInvitationMail $mail): bool => $mail->email === 'new.user@example.com');
    }

    public function test_only_an_authenticated_administrator_can_create_an_invitation(): void
    {
        $this->withCsrfToken()->postJson('/api/admin/user-invitations', ['email' => 'new.user@example.com'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $commonUser = $this->user('Common User', 'common@example.com', 1, 2);
        $this->actingAs($commonUser)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => 'new.user@example.com'])
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_INVITATION_FORBIDDEN');

        $this->assertDatabaseMissing('TB_USUARIO', ['DS_EMAIL' => 'new.user@example.com']);
        Mail::assertNothingSent();
    }

    public function test_invitation_requires_csrf_and_valid_email_and_rejects_existing_accounts(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        $existing = $this->user('Existing User', 'existing@example.com', 1, 2);

        $this->actingAs($admin)
            ->postJson('/api/admin/user-invitations', ['email' => 'another@example.com'])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => 'invalid-email'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.email.0', 'Informe um e-mail valido.');

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => $existing->DS_EMAIL])
            ->assertConflict()
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS');

        Mail::assertNothingSent();
    }

    public function test_resending_an_invitation_invalidates_the_previous_token(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        $this->invite($admin, 'new.user@example.com');
        $previousToken = $this->sentInvitationToken();

        Mail::fake();
        $this->invite($admin, 'NEW.USER@example.com');
        $currentToken = $this->sentInvitationToken();

        $this->assertNotSame($previousToken, $currentToken);
        $this->assertSame(1, Usuario::query()->where('DS_EMAIL', 'new.user@example.com')->count());
        $this->assertTrue(Hash::check(
            $currentToken,
            DB::table('password_reset_tokens')->where('email', 'new.user@example.com')->value('token'),
        ));

        $this->postJson('/api/auth/invitations/validate', [
            'email' => 'new.user@example.com',
            'token' => $previousToken,
        ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');

        $this->postJson('/api/auth/invitations/validate', [
            'email' => 'new.user@example.com',
            'token' => $currentToken,
        ])->assertOk()->assertJsonPath('data.is_valid', true);
    }

    public function test_invitation_token_expires_after_the_configured_period(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        $this->invite($admin, 'new.user@example.com');
        $token = $this->sentInvitationToken();
        DB::table('password_reset_tokens')->where('email', 'new.user@example.com')
            ->update(['created_at' => now()->subMinutes(61)]);

        $this->postJson('/api/auth/invitations/validate', [
            'email' => 'new.user@example.com',
            'token' => $token,
        ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');

        $this->postJson('/api/auth/invitations/complete', $this->completionPayload($token))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');

        $this->assertDatabaseHas('TB_USUARIO', [
            'DS_EMAIL' => 'new.user@example.com',
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => 2,
        ]);
    }

    public function test_valid_invitation_sets_the_initial_password_activates_user_and_consumes_token(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        $this->invite($admin, 'new.user@example.com');
        $token = $this->sentInvitationToken();

        $this->postJson('/api/auth/invitations/complete', $this->completionPayload($token))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'new.user@example.com')
            ->assertJsonPath('data.user_type_id', 2)
            ->assertJsonPath('data.active_status', 1)
            ->assertJsonPath('data.account_status', 'active')
            ->assertJsonMissingPath('data.password');

        $user = Usuario::query()->where('DS_EMAIL', 'new.user@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('SenhaSegura123', $user->DS_SENHA));
        $this->assertSame(2, (int) $user->FK_TIPO_USUARIO);
        $this->assertSame(2, (int) $user->TP_ATIVACAO);
        $this->assertSame(1, (int) $user->ST_ATIVO);
        $this->assertNotNull($user->DH_ATIVACAO);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'new.user@example.com']);

        $this->postJson('/api/auth/invitations/validate', [
            'email' => 'new.user@example.com',
            'token' => $token,
        ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');
    }

    public function test_invalid_token_and_password_confirmation_do_not_activate_account(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        $this->invite($admin, 'new.user@example.com');

        $this->postJson('/api/auth/invitations/complete', $this->completionPayload('wrong-token'))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');

        $this->postJson('/api/auth/invitations/complete', $this->completionPayload(
            $this->sentInvitationToken(),
            ['password_confirmation' => 'SenhasDiferentes123'],
        ))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['password']]);

        $this->assertDatabaseHas('TB_USUARIO', [
            'DS_EMAIL' => 'new.user@example.com',
            'ST_ATIVO' => 2,
            'TP_ATIVACAO' => 2,
            'DS_SENHA' => null,
        ]);
    }

    public function test_invitation_validation_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->fromIp('203.0.113.25')->postJson('/api/auth/invitations/validate', [
                'email' => 'unknown@example.com',
                'token' => 'invalid-token',
            ])->assertUnprocessable()->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');
        }

        $this->fromIp('203.0.113.25')->postJson('/api/auth/invitations/validate', [
            'email' => 'unknown@example.com',
            'token' => 'invalid-token',
        ])->assertTooManyRequests()->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
    }

    public function test_invitation_completion_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->fromIp('203.0.113.26')->postJson('/api/auth/invitations/complete', $this->completionPayload('invalid-token'))
                ->assertUnprocessable()
                ->assertJsonPath('code', 'INVALID_INVITATION_TOKEN');
        }

        $this->fromIp('203.0.113.26')->postJson('/api/auth/invitations/complete', $this->completionPayload('invalid-token'))
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'TOO_MANY_ATTEMPTS');
    }

    public function test_invitation_is_not_created_when_mail_transport_would_log_the_token(): void
    {
        $admin = $this->user('Administrator', 'admin@example.com', 1, 1);
        config([
            'mail.default' => 'log',
            'mail.mailers.log.transport' => 'log',
        ]);

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => 'new.user@example.com'])
            ->assertInternalServerError()
            ->assertJsonPath('code', 'USER_INVITATION_FAILED');

        $this->assertDatabaseMissing('TB_USUARIO', ['DS_EMAIL' => 'new.user@example.com']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'new.user@example.com']);
        Mail::assertNothingSent();
    }

    private function invite(Usuario $admin, string $email): void
    {
        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/user-invitations', ['email' => $email])
            ->assertCreated();
    }

    private function sentInvitationToken(): string
    {
        $token = '';
        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use (&$token): bool {
            parse_str((string) parse_url($mail->invitationUrl, PHP_URL_QUERY), $query);
            $token = (string) ($query['token'] ?? '');

            return true;
        });

        return $token;
    }

    private function completionPayload(string $token, array $overrides = []): array
    {
        return array_merge([
            'email' => 'new.user@example.com',
            'token' => $token,
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
        ], $overrides);
    }

    private function user(string $name, string $email, int $status, int $userType): Usuario
    {
        return Usuario::query()->create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => $email,
            'DS_SENHA' => Hash::make('SenhaSegura123'),
            'ST_ATIVO' => $status,
        ]);
    }

    private function fromIp(string $ip): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }
}
