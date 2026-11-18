<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\ProfileType;
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
        $pendingWithLegacyUserType = $this->user('Pending Legacy Type', 2, 2);
        $this->user('Inactive Common User', 0, 2);
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
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('meta.pagination.has_next_page', true)
            ->assertJsonMissingPath('data.0.password');

        $this->actingAs($admin)
            ->getJson('/api/admin/users/pending-activation?per_page=10')
            ->assertOk()
            ->assertJsonFragment(['id' => $pendingWithLegacyUserType->PK_USUARIO]);
    }

    public function test_administrator_activates_pending_account_keeping_its_access_profile(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Pending User', 2, null);
        $pending->update(['FK_TIPO_PERFIL' => 2]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $pending->PK_USUARIO)
            ->assertJsonPath('data.active_status', 1)
            ->assertJsonMissingPath('data.user_type_id');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_PERFIL' => 2,
        ]);
    }

    public function test_activation_assigns_user_profile_to_account_without_access_profile(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $pending = $this->user('Legacy Pending User', 2, null);
        $pending->update(['FK_TIPO_PERFIL' => null]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertOk()
            ->assertJsonPath('data.id', $pending->PK_USUARIO)
            ->assertJsonPath('data.active_status', 1);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_PERFIL' => ProfileType::USER_ID,
        ]);
    }

    public function test_administrator_reactivates_inactive_account_keeping_its_access_profile(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $inactive = $this->user('Inactive User', 0, 2);
        $inactive->update(['FK_TIPO_PERFIL' => 2]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$inactive->PK_USUARIO}/activate")
            ->assertOk()
            ->assertJsonPath('data.id', $inactive->PK_USUARIO)
            ->assertJsonPath('data.active_status', 1);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $inactive->PK_USUARIO,
            'ST_ATIVO' => 1,
            'FK_TIPO_PERFIL' => 2,
        ]);
    }

    public function test_invitation_awaiting_initial_password_cannot_be_activated_by_administrator(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $invited = $this->user('Invited User', 2, 2);
        $invited->update(['TP_ATIVACAO' => 2, 'DS_SENHA' => null]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$invited->PK_USUARIO}/activate")
            ->assertConflict()
            ->assertJsonPath('code', 'INVALID_USER_ACCOUNT_STATE');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $invited->PK_USUARIO,
            'ST_ATIVO' => 2,
        ]);
    }

    public function test_administrator_deactivates_active_manager_and_administrator_while_another_admin_remains(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $otherAdmin = $this->user('Other Administrator', 1, 1);
        $manager = $this->user('Manager', 1, 2);
        $manager->update(['FK_TIPO_PERFIL' => 2]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$manager->PK_USUARIO}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.active_status', 0);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$otherAdmin->PK_USUARIO}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.active_status', 0);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $manager->PK_USUARIO,
            'ST_ATIVO' => 0,
            'FK_TIPO_PERFIL' => 2,
        ]);
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $otherAdmin->PK_USUARIO,
            'ST_ATIVO' => 0,
            'FK_TIPO_PERFIL' => 1,
        ]);
    }

    public function test_deactivation_rejects_own_account_and_last_active_administrator(): void
    {
        $admin = $this->user('Administrator', 1, 1);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$admin->PK_USUARIO}/deactivate")
            ->assertForbidden()
            ->assertJsonPath('code', 'SELF_DEACTIVATION_FORBIDDEN');

        // Um perfil customizado com users.deactivate tenta desativar o unico admin ativo
        Usuario::query()
            ->where('FK_TIPO_PERFIL', ProfileType::ADMIN_ID)
            ->whereKeyNot($admin->PK_USUARIO)
            ->update(['ST_ATIVO' => 0]);
        $customProfile = ProfileType::create(['NO_TIPO_PERFIL' => 'Gestor de Contas']);
        $permissionId = Permission::query()->where('NO_PERMISSOES', 'users.deactivate')->value('PK_PERMISSOES');
        $customProfile->permissions()->attach($permissionId);
        $accountManager = $this->user('Account Manager', 1, 2);
        $accountManager->update(['FK_TIPO_PERFIL' => $customProfile->PK_TIPO_PERFIL]);

        $this->actingAs($accountManager)
            ->postJsonWithCsrf("/api/admin/users/{$admin->PK_USUARIO}/deactivate")
            ->assertConflict()
            ->assertJsonPath('code', 'LAST_ADMIN_REQUIRED');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $admin->PK_USUARIO,
            'ST_ATIVO' => 1,
        ]);
    }

    public function test_administrator_deactivates_active_common_user_without_changing_its_access_profile(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $commonUser = $this->user('Common User', 1, 2);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$commonUser->PK_USUARIO}/deactivate")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $commonUser->PK_USUARIO)
            ->assertJsonPath('data.active_status', 0);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $commonUser->PK_USUARIO,
            'ST_ATIVO' => 0,
            'FK_TIPO_PERFIL' => 3,
        ]);
        $this->actingAs($admin)
            ->getJson('/api/admin/users/pending-activation')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_deactivation_uses_the_access_profile_instead_of_legacy_user_type(): void
    {
        $admin = $this->user('Administrator', 1, 1);
        $commonUser = $this->user('Common User with legacy admin type', 1, 1);
        $commonUser->update(['FK_TIPO_PERFIL' => 3]);

        $this->actingAs($admin)
            ->postJsonWithCsrf("/api/admin/users/{$commonUser->PK_USUARIO}/deactivate")
            ->assertOk()
            ->assertJsonPath('data.active_status', 0);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $commonUser->PK_USUARIO,
            'ST_ATIVO' => 0,
            'FK_TIPO_PERFIL' => 3,
            'FK_TIPO_USUARIO' => 1,
        ]);
    }

    public function test_common_user_cannot_list_or_change_accounts(): void
    {
        $commonUser = $this->user('Common User', 1, 2);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($commonUser)
            ->getJson('/api/admin/users/pending-activation')
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->actingAs($commonUser)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
            'FK_TIPO_USUARIO' => null,
        ]);
    }

    public function test_permission_profiles_control_account_access_independently_of_legacy_user_type(): void
    {
        $manager = $this->user('Manager', 1, 1);
        $manager->update(['FK_TIPO_PERFIL' => 2]);
        $pending = $this->user('Pending User', 2, null);

        $this->actingAs($manager)
            ->getJson('/api/admin/users/pending-activation')
            ->assertOk()
            ->assertJsonPath('data.0.id', $pending->PK_USUARIO);

        $this->actingAs($manager)
            ->postJsonWithCsrf("/api/admin/users/{$pending->PK_USUARIO}/activate")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $activeUser = $this->user('Active User', 1, 2);

        $this->actingAs($manager)
            ->postJsonWithCsrf("/api/admin/users/{$activeUser->PK_USUARIO}/deactivate")
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $activeUser->PK_USUARIO,
            'ST_ATIVO' => 1,
        ]);
    }

    public function test_pending_status_migration_reclassifies_existing_inactive_accounts(): void
    {
        $inactive = $this->user('Inactive User', 2, 2);
        $pending = $this->user('Pending User', 2, null);

        $migration = require database_path('migrations/2026_11_06_000001_separate_pending_and_inactive_user_statuses.php');
        $migration->up();

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $inactive->PK_USUARIO,
            'ST_ATIVO' => 0,
        ]);
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
        ]);

        $migration->down();

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $inactive->PK_USUARIO,
            'ST_ATIVO' => 2,
        ]);
        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $pending->PK_USUARIO,
            'ST_ATIVO' => 2,
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
            'FK_TIPO_PERFIL' => $userType === 1 ? 1 : 3,
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
