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

    public function test_it_activates_a_user_temporarily(): void
    {
        $profileType = ProfileType::create(['NO_TIPO_PERFIL' => 'Aluno']);
        $user = Usuario::create([
            'FK_TIPO_PERFIL' => $profileType->PK_TIPO_PERFIL,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'DS_SENHA' => Hash::make('secret123'),
            'ST_ATIVO' => 2,
        ]);

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

    public function test_it_returns_not_found_when_activating_missing_user(): void
    {
        $this->patchJson('/api/users/999/activate')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao encontrado.')
            ->assertJsonPath('code', 'USER_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }
}
