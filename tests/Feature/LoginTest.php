<?php

namespace Tests\Feature;

use App\Models\ProfileType;
use App\Models\Usuario;
use App\Services\LoginService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_an_active_common_user(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->postJson('/api/auth/login', [
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
            ->assertJsonMissingPath('data.DS_SENHA');
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

        $this->postJson('/api/auth/login', [
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

        $this->postJson('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario e/ou senha invalidos.')
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.email.0', 'Informe o e-mail.')
            ->assertJsonPath('errors.password.0', 'Informe a senha.');
    }

    public function test_it_rejects_invalid_payload_format(): void
    {
        $this->postJson('/api/auth/login', [
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

        $this->postJson('/api/auth/login', [
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

        $this->postJson('/api/auth/login', [
            'email' => 'maria.silva@example.com',
            'password' => 'secret123',
        ])
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Erro interno do servidor.')
            ->assertJsonPath('code', 'INTERNAL_SERVER_ERROR')
            ->assertJsonPath('errors', []);
    }
}
