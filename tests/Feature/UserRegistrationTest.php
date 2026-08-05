<?php

namespace Tests\Feature;

use App\Models\ProfileType;
use App\Models\Usuario;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_user_pending_admin_activation(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);

        $response = $this->postJson('/api/auth/register', [
            'name' => ' Maria Silva ',
            'email' => 'MARIA.SILVA@example.com',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'organization' => ' Secretaria de Governo ',
            'department' => ' Privacidade ',
            'registration_number' => ' MAT-123 ',
            'profile_type_id' => $profileType->PK_TIPO_PERFIL,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cadastro realizado com sucesso. Sua conta foi criada, mas ainda precisa ser ativada por um administrador do sistema para permitir o acesso.')
            ->assertJsonPath('data.name', 'Maria Silva')
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.organization_company', 'Secretaria de Governo')
            ->assertJsonPath('data.department', 'Privacidade')
            ->assertJsonPath('data.registration_number', 'MAT-123')
            ->assertJsonPath('data.profile_type_id', $profileType->PK_TIPO_PERFIL)
            ->assertJsonPath('data.active_status', 2)
            ->assertJsonPath('data.account_status', 'pending_activation')
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('TB_USUARIO', [
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'ST_ATIVO' => 2,
        ]);

        $user = Usuario::query()->where('DS_EMAIL', 'maria.silva@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('SenhaSegura123', $user->DS_SENHA));
    }

    public function test_it_registers_a_pending_user_account_without_profile_type(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile_type_id', null)
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.active_status', 2);

        $this->assertDatabaseHas('TB_USUARIO', [
            'FK_TIPO_PERFIL' => null,
            'DS_EMAIL' => 'maria.silva@example.com',
            'ST_ATIVO' => 2,
        ]);
    }

    public function test_it_accepts_maximum_length_email_and_persists_password_hash(): void
    {
        $email = str_repeat('a', 64).'@'.str_repeat('b', 32).'.io';

        $this->postJson('/api/auth/register', $this->validPayload([
            'email' => $email,
        ]))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $email);

        $user = Usuario::query()->where('DS_EMAIL', $email)->firstOrFail();

        $this->assertSame(100, strlen($user->DS_EMAIL));
        $this->assertGreaterThan(50, strlen($user->DS_SENHA));
        $this->assertTrue(Hash::check('SenhaSegura123', $user->DS_SENHA));
    }

    public function test_it_rejects_name_without_last_name(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload(['name' => 'Maria']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.name.0', 'Informe nome e sobrenome.');
    }

    public function test_it_rejects_invalid_profile_type(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload([
            'profile_type_id' => 999,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.profile_type_id.0', 'Informe um tipo de perfil valido.');

        $this->assertDatabaseMissing('TB_USUARIO', [
            'DS_EMAIL' => 'maria.silva@example.com',
        ]);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/auth/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'email',
                    'password',
                    'organization',
                    'department',
                    'registration_number',
                ],
            ]);
    }

    public function test_it_rejects_invalid_payload(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload([
            'email' => 'email-invalido',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    public function test_it_rejects_existing_account(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('SenhaSegura123'),
            'ST_ATIVO' => 2,
        ]);

        $this->postJson('/api/auth/register', $this->validPayload())
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Informe outro e-mail ou solicite a ativacao da conta existente.');

        $this->assertSame(1, Usuario::query()->where('DS_EMAIL', 'maria.silva@example.com')->count());
    }

    public function test_it_rejects_existing_account_with_different_email_case(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
            'DS_EMAIL' => 'usuario@email.com',
            'DS_SENHA' => Hash::make('SenhaSegura123'),
            'ST_ATIVO' => 2,
        ]);

        $this->postJson('/api/auth/register', $this->validPayload([
            'email' => 'Usuario@Email.com',
        ]))
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Informe outro e-mail ou solicite a ativacao da conta existente.');
    }

    public function test_it_returns_conflict_when_database_unique_email_constraint_is_violated(): void
    {
        $repository = Mockery::mock(UserRepositoryInterface::class);
        $repository->shouldReceive('existsByEmail')->once()->andReturnFalse();
        $repository->shouldReceive('create')->once()->andThrow(new QueryException(
            connectionName: 'testing',
            sql: 'insert into "TB_USUARIO" ("DS_EMAIL") values (?)',
            bindings: ['maria.silva@example.com'],
            previous: new \Exception('UNIQUE constraint failed: TB_USUARIO.DS_EMAIL'),
        ));

        $this->app->instance(UserRepositoryInterface::class, $repository);

        $this->postJson('/api/auth/register', $this->validPayload())
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Informe outro e-mail ou solicite a ativacao da conta existente.');
    }

    public function test_it_rate_limits_registration_attempts_by_ip(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->fromIp('203.0.113.20')->postJson('/api/auth/register', [])
                ->assertUnprocessable()
                ->assertJsonPath('code', 'VALIDATION_ERROR');
        }

        $this->fromIp('203.0.113.20')->postJson('/api/auth/register', [])
            ->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Muitas tentativas. Tente novamente mais tarde.')
            ->assertJsonPath('code', 'TOO_MANY_ATTEMPTS')
            ->assertJsonPath('errors', []);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Silva',
            'email' => 'maria.silva@example.com',
            'password' => 'SenhaSegura123',
            'password_confirmation' => 'SenhaSegura123',
            'organization' => 'Secretaria de Governo',
            'department' => 'Privacidade',
            'registration_number' => 'MAT-123',
        ], $overrides);
    }

    private function fromIp(string $ip): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }
}
