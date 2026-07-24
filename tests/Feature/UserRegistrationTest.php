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

        $response = $this->postJson('/api/users/register', [
            'name' => 'Maria Silva',
            'email' => 'MARIA.SILVA@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'organization_company' => 'Ministerio da Justica',
            'department' => 'Privacidade',
            'registration_number' => 'MJ-123',
            'profile_type_id' => $profileType->PK_TIPO_PERFIL,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cadastro realizado com sucesso. Sua conta foi criada, mas ainda precisa ser ativada por um administrador do sistema para permitir o acesso.')
            ->assertJsonPath('data.name', 'Maria Silva')
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.active_status', 2)
            ->assertJsonPath('data.account_status', 'pending_activation');

        $this->assertDatabaseHas('TB_USUARIO', [
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'NO_ORGAO_EMPRESA' => 'Ministerio da Justica',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MJ-123',
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'ST_ATIVO' => 2,
        ]);

        $user = Usuario::query()->where('DS_EMAIL', 'maria.silva@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->DS_SENHA));
    }

    public function test_it_accepts_maximum_length_email_and_persists_full_password_hash(): void
    {
        $email = str_repeat('a', 64).'@'.str_repeat('b', 32).'.io';

        $this->postJson('/api/users/register', $this->validPayload([
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]))
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', $email);

        $user = Usuario::query()->where('DS_EMAIL', $email)->firstOrFail();

        $this->assertSame(100, strlen($user->DS_EMAIL));
        $this->assertGreaterThan(50, strlen($user->DS_SENHA));
        $this->assertTrue(Hash::check('secret123', $user->DS_SENHA));
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'DS_EMAIL' => $email,
            'ST_ATIVO' => 2,
        ]);
    }

    public function test_it_rejects_name_without_last_name(): void
    {
        $this->postJson('/api/users/register', $this->validPayload(['name' => 'Maria']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.name.0', 'Informe nome e sobrenome.');
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $this->postJson('/api/users/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'email',
                    'password',
                    'organization_company',
                    'department',
                ],
            ]);
    }

    public function test_it_rejects_invalid_payload(): void
    {
        $this->postJson('/api/users/register', $this->validPayload([
            'email' => 'email-invalido',
            'password' => 'short',
            'password_confirmation' => 'different',
            'profile_type_id' => 999,
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                    'password',
                    'profile_type_id',
                ],
            ]);
    }

    public function test_it_rejects_existing_account(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

        $this->postJson('/api/users/register', $this->validPayload([
            'email' => 'MARIA.SILVA@example.com',
        ]))
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'USER_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Ja existe uma conta cadastrada com este e-mail.');
    }

    public function test_it_rejects_existing_account_with_different_email_case(): void
    {
        Usuario::create([
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'usuario@email.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

        $this->postJson('/api/users/register', $this->validPayload([
            'email' => 'Usuario@Email.com',
        ]))
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'USER_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Ja existe uma conta cadastrada com este e-mail.');
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

        $this->postJson('/api/users/register', $this->validPayload())
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'USER_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Ja existe uma conta cadastrada com este e-mail.');
    }

    public function test_it_rate_limits_registration_attempts_by_ip(): void
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->fromIp('203.0.113.20')->postJson('/api/users/register', [])
                ->assertUnprocessable()
                ->assertJsonPath('code', 'VALIDATION_ERROR');
        }

        $this->fromIp('203.0.113.20')->postJson('/api/users/register', [])
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
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'organization_company' => 'Ministerio da Justica',
            'department' => 'Privacidade',
            'registration_number' => 'MJ-123',
        ], $overrides);
    }

    private function fromIp(string $ip): self
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }
}
