<?php

namespace Tests\Feature;

use App\Models\ProfileType;
use App\Models\Usuario;
use App\Services\LoginService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_an_active_common_user(): void
    {
        config(['session.lifetime' => 47]);

        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'MARIA.SILVA@example.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login realizado com sucesso.')
            ->assertJsonPath('data.name', 'Maria Silva')
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.role', 'common_user')
            ->assertJsonPath('data.profile.role', 'common_user')
            ->assertJsonPath('data.profile.label', 'Usuario comum')
            ->assertJsonPath('data.profile.user_type_id', 2)
            ->assertJsonPath('data.profile.profile_type_id', $profileType->PK_TIPO_PERFIL)
            ->assertJsonPath('data.profile.profile_type_name', 'Aluno')
            ->assertJsonPath('meta.session.lifetime_minutes', 47)
            ->assertJsonMissingPath('data.DS_SENHA');

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario autenticado recuperado com sucesso.')
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.role', 'common_user')
            ->assertJsonPath('meta.session.lifetime_minutes', 47);
    }

    public function test_authenticated_requests_refresh_database_session_activity(): void
    {
        config(['session.driver' => 'database']);

        $user = Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $response = $this->actingAs($user)->getJson('/api/me')->assertOk();

        $session = DB::table('sessions')->first();
        $this->assertNotNull($session);
        $sessionCookie = $response->getCookie(config('session.cookie'), false);
        $this->assertNotNull($sessionCookie);
        $this->assertSame($session->id, $sessionCookie->getValue());

        $previousActivity = $session->last_activity - 60;
        DB::table('sessions')->where('id', $session->id)->update([
            'last_activity' => $previousActivity,
        ]);

        $this->withCredentials()
            ->withUnencryptedCookies([config('session.cookie') => $sessionCookie->getValue()])
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('meta.session.lifetime_minutes', config('session.lifetime'));

        $this->assertGreaterThan(
            $previousActivity,
            DB::table('sessions')->where('id', $session->id)->value('last_activity'),
        );
    }

    public function test_it_rejects_session_when_authenticated_user_becomes_inactive(): void
    {
        $user = Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])->assertOk();

        $this->getJson('/api/me')->assertOk();

        $user->update(['ST_ATIVO' => 2]);

        $this->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sua conta ainda nao foi ativada por um administrador do sistema.')
            ->assertJsonPath('code', 'ACCOUNT_NOT_ACTIVATED')
            ->assertJsonPath('errors', []);

        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_invalidates_session_on_logout(): void
    {
        Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])->assertOk();

        $this->getJson('/api/me')->assertOk();

        $this->postJsonWithCsrf('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout realizado com sucesso.')
            ->assertJsonPath('data', null);

        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_rejects_logout_without_csrf_token(): void
    {
        Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])->assertOk();

        $this->getJson('/api/me')->assertOk();

        $this->withoutHeader('X-CSRF-TOKEN')
            ->postJson('/api/auth/logout')
            ->assertStatus(419)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Token CSRF invalido.')
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH')
            ->assertJsonPath('errors', []);

        $this->getJson('/api/me')->assertOk();
    }

    public function test_it_returns_administrator_profile_for_admin_user(): void
    {
        Usuario::create([
            'FK_TIPO_USUARIO' => 1,
            'NO_USUARIO' => 'Admin Sistema',
            'DS_EMAIL' => 'admin@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ])
            ->assertOk()
            ->assertJsonPath('data.role', 'administrator')
            ->assertJsonPath('data.profile.role', 'administrator')
            ->assertJsonPath('data.profile.label', 'Administrador')
            ->assertJsonPath('data.profile.user_type_id', 1);
    }

    public function test_it_rejects_invalid_credentials_with_generic_message(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario e/ou senha invalidos.')
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('errors', []);

        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_it_rate_limits_login_attempts_by_ip_and_email(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->fromIp('203.0.113.10')->postJsonWithCsrf('/api/auth/login', [
                'email' => 'maria.silva@example.com',
                'password' => 'wrong-password',
            ])
                ->assertUnauthorized()
                ->assertJsonPath('code', 'INVALID_CREDENTIALS');
        }

        $this->fromIp('203.0.113.10')->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'wrong-password',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Muitas tentativas. Tente novamente mais tarde.')
            ->assertJsonPath('code', 'TOO_MANY_ATTEMPTS')
            ->assertJsonPath('errors', []);
    }

    public function test_login_rate_limit_does_not_block_distinct_email_on_same_ip(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->fromIp('203.0.113.11')->postJsonWithCsrf('/api/auth/login', [
                'email' => 'maria.silva@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->fromIp('203.0.113.11')->postJsonWithCsrf('/api/auth/login', [
            'email' => 'joao.souza@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    }

    public function test_it_rejects_authenticated_user_endpoint_without_session(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $this->postJsonWithCsrf('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.email.0', 'Informe o e-mail.')
            ->assertJsonPath('errors.password.0', 'Informe a senha.');
    }

    public function test_it_rejects_invalid_payload_format(): void
    {
        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'email-invalido',
            'password' => 'secret123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.email.0', 'Informe um e-mail valido.');
    }

    public function test_it_rejects_account_pending_admin_activation(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Sua conta ainda nao foi ativada por um administrador do sistema.')
            ->assertJsonPath('code', 'ACCOUNT_NOT_ACTIVATED');
    }

    public function test_it_returns_standard_error_for_internal_server_error(): void
    {
        $loginService = Mockery::mock(LoginService::class);
        $loginService->shouldReceive('authenticate')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(LoginService::class, $loginService);

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Erro interno do servidor.')
            ->assertJsonPath('code', 'INTERNAL_SERVER_ERROR')
            ->assertJsonPath('errors', []);
    }

    private function fromIp(string $ip): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }
}
