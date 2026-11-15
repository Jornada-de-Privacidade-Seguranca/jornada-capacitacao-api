<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_creates_default_administrator_user(): void
    {
        $admin = Usuario::query()
            ->where('DS_EMAIL', 'admin@jornada.local')
            ->firstOrFail();

        $this->assertSame(1, (int) $admin->FK_TIPO_USUARIO);
        $this->assertSame(1, (int) $admin->ST_ATIVO);
        $this->assertTrue(Hash::check('admin123', (string) $admin->DS_SENHA));

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'admin@jornada.local',
            'password' => 'admin123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'admin@jornada.local')
            ->assertJsonPath('data.role', 'administrator')
            ->assertJsonPath('data.profile.id', 1)
            ->assertJsonPath('data.profile.name', 'Administrador')
            ->assertJsonPath('data.profile.permissions', [
                'catalog.manage',
                'journeys.assign',
                'journeys.take',
                'results.view_all',
                'results.view_own',
                'system.view',
                'users.change_profile',
                'users.deactivate',
                'users.manage',
                'users.register',
                'users.view',
            ]);
    }

    public function test_migration_creates_default_common_user(): void
    {
        $user = Usuario::query()
            ->where('DS_EMAIL', 'user@jornada.local')
            ->firstOrFail();

        $this->assertSame(2, (int) $user->FK_TIPO_USUARIO);
        $this->assertSame(1, (int) $user->ST_ATIVO);
        $this->assertTrue(Hash::check('usere123', (string) $user->DS_SENHA));

        $this->postJsonWithCsrf('/api/auth/login', [
            'email' => 'user@jornada.local',
            'password' => 'usere123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'user@jornada.local')
            ->assertJsonPath('data.role', 'common_user')
            ->assertJsonPath('data.profile.id', 3)
            ->assertJsonPath('data.profile.name', 'Usuário')
            ->assertJsonPath('data.profile.permissions', ['journeys.take', 'results.view_own']);
    }

    public function test_user_activation_route_is_no_longer_registered(): void
    {
        $this->patchJson('/api/users/1/activate')
            ->assertNotFound();
    }
}
