<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OwnProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_registration_data(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.organization', 'Secretaria de Governo')
            ->assertJsonPath('data.department', 'Privacidade')
            ->assertJsonPath('data.registration_number', 'MAT-123')
            ->assertJsonPath('data.created_at', '2026-07-23 16:30:00');
    }

    public function test_it_updates_all_editable_fields(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', [
                'name' => ' Maria Souza ',
                'email' => ' MARIA.SOUZA@example.com ',
                'organization' => ' Ministerio da Economia ',
                'department' => ' Seguranca da Informacao ',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados do usuario atualizados com sucesso.')
            ->assertJsonPath('data.id', $user->PK_USUARIO)
            ->assertJsonPath('data.name', 'Maria Souza')
            ->assertJsonPath('data.email', 'maria.souza@example.com')
            ->assertJsonPath('data.organization', 'Ministerio da Economia')
            ->assertJsonPath('data.department', 'Seguranca da Informacao')
            ->assertJsonPath('data.registration_number', 'MAT-123')
            ->assertJsonPath('meta.session.lifetime_minutes', config('session.lifetime'));

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NO_USUARIO' => 'Maria Souza',
            'DS_EMAIL' => 'maria.souza@example.com',
            'NO_ORGAO_EMPRESA' => 'Ministerio da Economia',
            'NO_SETOR' => 'Seguranca da Informacao',
        ]);
    }

    public function test_it_updates_only_the_fields_sent(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', ['department' => 'Seguranca da Informacao'])
            ->assertOk()
            ->assertJsonPath('data.department', 'Seguranca da Informacao');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Seguranca da Informacao',
        ]);
    }

    public function test_it_ignores_registration_number(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', ['name' => 'Maria Souza', 'registration_number' => 'OUTRA-999'])
            ->assertOk()
            ->assertJsonPath('data.registration_number', 'MAT-123');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NO_USUARIO' => 'Maria Souza',
            'NU_MATRICULA' => 'MAT-123',
        ]);
    }

    public function test_it_rejects_a_request_with_only_registration_number(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', ['registration_number' => 'OUTRA-999'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.name.0', 'Informe ao menos um dos dados: nome, e-mail, orgao/empresa ou setor.');

        $this->assertDatabaseHas('TB_USUARIO', ['PK_USUARIO' => $user->PK_USUARIO, 'NU_MATRICULA' => 'MAT-123']);
    }

    public function test_it_rejects_an_empty_request(): void
    {
        $this->actingAs($this->activeUser())->withCsrfToken()
            ->patchJson('/api/me', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['name', 'email', 'organization', 'department']]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_applies_the_registration_validations(array $payload, string $field, string $message): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath("errors.{$field}.0", $message);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
        ]);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'nome sem sobrenome' => [['name' => 'Maria'], 'name', 'Informe nome e sobrenome.'],
            'nome longo' => [['name' => 'Maria '.str_repeat('a', 50)], 'name', 'O nome deve ter no maximo 50 caracteres.'],
            'nome vazio' => [['name' => '   '], 'name', 'Informe o nome completo.'],
            'e-mail invalido' => [['email' => 'email-invalido'], 'email', 'Informe um e-mail valido.'],
            'e-mail longo' => [['email' => str_repeat('a', 92).'@example.com'], 'email', 'O e-mail deve ter no maximo 100 caracteres.'],
            'e-mail nulo' => [['email' => null], 'email', 'Informe o e-mail.'],
            'orgao longo' => [['organization' => str_repeat('a', 101)], 'organization', 'O orgao ou empresa deve ter no maximo 100 caracteres.'],
            'setor vazio' => [['department' => ''], 'department', 'Informe o setor.'],
            'setor longo' => [['department' => str_repeat('a', 101)], 'department', 'O setor deve ter no maximo 100 caracteres.'],
        ];
    }

    public function test_it_rejects_an_email_already_used_by_another_account(): void
    {
        $user = $this->activeUser();
        $this->activeUser(['DS_EMAIL' => 'joao.lima@example.com', 'NU_MATRICULA' => 'MAT-456']);

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', ['email' => 'JOAO.LIMA@example.com'])
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Ja existe uma conta cadastrada com este e-mail.')
            ->assertJsonPath('code', 'EMAIL_ALREADY_IN_USE')
            ->assertJsonPath('errors.email.0', 'Informe outro e-mail.');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'DS_EMAIL' => 'maria.silva@example.com',
        ]);
    }

    public function test_it_accepts_the_users_own_email_in_another_case(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)->withCsrfToken()
            ->patchJson('/api/me', ['email' => 'Maria.Silva@Example.com', 'name' => 'Maria Souza'])
            ->assertOk()
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.name', 'Maria Souza');
    }

    public function test_it_rejects_update_without_csrf_token(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->patchJson('/api/me', ['name' => 'Maria Souza'])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

        $this->assertDatabaseHas('TB_USUARIO', ['PK_USUARIO' => $user->PK_USUARIO, 'NO_USUARIO' => 'Maria Silva']);
    }

    public function test_it_rejects_unauthenticated_update(): void
    {
        $this->withCsrfToken()
            ->patchJson('/api/me', ['name' => 'Maria Souza'])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function activeUser(array $attributes = []): Usuario
    {
        return Usuario::create(array_merge([
            'FK_TIPO_PERFIL' => 3,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'NO_ORGAO_EMPRESA' => 'Secretaria de Governo',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-123',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => 'secret',
            'DH_CRIADO_EM' => '2026-07-23 16:30:00',
            'ST_ATIVO' => 1,
        ], $attributes));
    }
}
