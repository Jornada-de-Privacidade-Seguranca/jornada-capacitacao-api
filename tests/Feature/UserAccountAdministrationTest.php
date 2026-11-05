<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_lists_only_pending_accounts_with_pagination(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Pending User', 2, null);
        $this->user('Inactive Common User', 2, 2);
        $this->user('Active Common User', 1, 2);

        $this->actingAs($admin)
            ->getJson('/api/admin/users/pending-activation?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $pending->PK_USUARIO)
            ->assertJsonPath('data.0.name', 'Pending User')
            ->assertJsonPath('data.0.email', 'pending.user@example.com')
            ->assertJsonPath('data.0.organization', 'Secretaria')
            ->assertJsonPath('data.0.department', 'Privacidade')
            ->assertJsonPath('data.0.registration_number', 'MAT-1')
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.pagination.has_next_page', false)
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_administrator_activates_pending_account_as_common_user(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $pending->PK_USUARIO)
            ->assertJsonPath('data.active_status', 1)
            ->assertJsonPath('data.user_type_id', 2);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_USUARIO' => 2,
        ]);
    }

    public function test_administrator_deactivates_active_common_user_without_changing_its_type(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $commonUser = $this->user('Common User', 1, 2);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$commonUser->PK_USUARIO}/deactivate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $commonUser->PK_USUARIO)
            ->assertJsonPath('data.active_status', 2)
            ->assertJsonPath('data.user_type_id', 2);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $commonUser->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => 2,
        ]);
        $this->actingAs($admin)
            ->getJson('/api/admin/users/pending-activation')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_common_user_cannot_list_or_change_accounts(): void
    {
        $commonUser = $this->user('Common User', 1, 2);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($commonUser)
            ->getJson('/api/admin/users/pending-activation')
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_ACCOUNT_ADMINISTRATION_FORBIDDEN');

        $this->actingAs($commonUser)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertForbidden()
            ->assertJsonPath('code', 'USER_ACCOUNT_ADMINISTRATION_FORBIDDEN');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => null,
        ]);
    }

    public function test_account_management_requires_authentication_and_csrf(): void
    {
        $this->getJson('/api/admin/users/pending-activation')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $this->withCsrfToken()
            ->postJson('/api/admin/users/1/activate')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($admin)
            ->withSession(['_token' => 'expected-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'invalid-csrf-token')
            ->postJson("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => null,
        ]);
    }

    public function test_activation_and_deactivation_reject_missing_users_and_invalid_states(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $activeCommon = $this->user('Active Common', 1, 2);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/users/999999/activate')
            ->assertNotFound()
            ->assertJsonPath('code', 'USER_NOT_FOUND');

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$activeCommon->PK_USUARIO}/activate")
            ->assertConflict()
            ->assertJsonPath('code', 'INVALID_USER_ACCOUNT_STATE');

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/deactivate")
            ->assertConflict()
            ->assertJsonPath('code', 'INVALID_USER_ACCOUNT_STATE');

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/users/999999/deactivate')
            ->assertNotFound()
            ->assertJsonPath('code', 'USER_NOT_FOUND');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $activeCommon->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_USUARIO' => 2,
        ]);
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => null,
        ]);
    }

    public function test_invalid_account_identifiers_are_rejected_without_changing_accounts(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Pending User', 2, null);
        $activeCommon = $this->user('Active Common', 1, 2);

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/users/abc/activate')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_USER_IDENTIFIER')
            ->assertJsonPath('errors.user.0', 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.');

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$activeCommon->PK_USUARIO}.5/deactivate")
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_USER_IDENTIFIER');

        $this->actingAs($admin)
            ->postJsonWithCsrf('/api/admin/users/999999999999999999999999999999999999/activate')
            ->assertUnprocessable()
            ->assertJsonPath('code', 'INVALID_USER_IDENTIFIER');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => null,
        ]);
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $activeCommon->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_USUARIO' => 2,
        ]);
    }

    private function user(string $name, int $status, ?int $userType): Usuario
    {
        return Usuario::query()->create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'NO_ORGAO_EMPRESA' => 'Secretaria',
            'NO_SETOR' => 'Privacidade',
            'NU_MATRICULA' => 'MAT-1',
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => $status,
        ]);
    }
}
