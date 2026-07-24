<?php

namespace Tests\Feature;

use App\Models\ProfileType;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_activate_a_user_temporarily(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        $admin = Usuario::create([
            'FK_TIPO_USUARIO' => 1,
            'NO_USUARIO' => 'Admin Sistema',
            'DS_EMAIL' => 'admin@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);
        $user = Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

        $this->actingAs($admin);

        $this->patchJson("/api/users/{$user->PK_USUARIO}/activate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Usuario ativado com sucesso.')
            ->assertJsonPath('data.id', $user->PK_USUARIO)
            ->assertJsonPath('data.email', 'maria.silva@example.com')
            ->assertJsonPath('data.profile_type_id', $profileType->PK_TIPO_PERFIL)
            ->assertJsonPath('data.profile_type_name', 'Aluno')
            ->assertJsonPath('data.active_status', 1)
            ->assertJsonPath('data.account_status', 'active');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'ST_ATIVO' => 1,
        ]);
    }

    public function test_it_returns_not_found_when_admin_activates_missing_user(): void
    {
        $admin = Usuario::create([
            'FK_TIPO_USUARIO' => 1,
            'NO_USUARIO' => 'Admin Sistema',
            'DS_EMAIL' => 'admin@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);

        $this->actingAs($admin);

        $this->patchJson('/api/users/999/activate')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao encontrado.')
            ->assertJsonPath('code', 'USER_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_activation_without_authenticated_user(): void
    {
        $this->patchJson('/api/users/999/activate')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_common_user_activation_even_when_payload_claims_admin_type(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        $commonUser = Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 1,
        ]);
        $targetUser = Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Joao Souza',
            'DS_EMAIL' => 'joao.souza@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

        $this->actingAs($commonUser);

        $this->patchJson("/api/users/{$targetUser->PK_USUARIO}/activate", [
            'FK_TIPO_USUARIO' => 1,
            'fk_tipo_usuario' => 1,
            'is_admin' => true,
        ])
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Permissao administrativa obrigatoria.')
            ->assertJsonPath('code', 'ADMIN_PERMISSION_REQUIRED')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $targetUser->PK_USUARIO,
            'ST_ATIVO' => 2,
        ]);
    }
}
