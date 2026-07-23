<?php

namespace Tests\Feature;

use App\Models\ProfileType;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_pending_user_account(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);

        $response = $this->postJson('/api/auth/register', $this->validPayload([
            'profile_type_id' => $profileType->PK_TIPO_PERFIL,
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cadastro realizado com sucesso. Sua conta foi criada, mas ainda precisa ser ativada por um administrador do sistema para permitir o acesso.')
            ->assertJsonPath('data.name', 'Maria Silva')
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.active', false)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('TB_USUARIO', [
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'NO_USUARIO' => 'Maria Silva',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
            'DS_EMAIL' => 'maria.silva@example.com',
            'ST_ATIVO' => 0,
        ]);

        $user = Usuario::query()->where('DS_EMAIL', 'maria.silva@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('SenhaSegura123', $user->DS_SENHA));
    }

    public function test_it_rejects_name_without_last_name(): void
    {
        $this->postJson('/api/auth/register', $this->validPayload([
            'name' => 'Maria',
        ]))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.name.0', 'Informe nome e sobrenome.');
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
            'ST_ATIVO' => 0,
        ]);

        $this->postJson('/api/auth/register', $this->validPayload())
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS')
            ->assertJsonPath('errors.email.0', 'Informe outro e-mail ou solicite a ativacao da conta existente.');

        $this->assertSame(1, Usuario::query()->where('DS_EMAIL', 'maria.silva@example.com')->count());
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
            'user_type_id' => 1,
        ], $overrides);
    }
}
