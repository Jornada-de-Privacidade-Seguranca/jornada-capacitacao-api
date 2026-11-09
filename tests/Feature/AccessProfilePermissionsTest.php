<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\ProfileType;
use App\Models\ProfileTypeChange;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccessProfilePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PROFILE = 1;

    private const MANAGER_PROFILE = 2;

    private const USER_PROFILE = 3;

    private const ALL_PERMISSIONS = [
        'journeys.take',
        'journeys.assign',
        'results.view_own',
        'results.view_all',
        'users.view',
        'users.register',
        'users.deactivate',
        'users.manage',
        'users.change_profile',
        'catalog.manage',
    ];

    public function test_it_creates_permission_and_profile_change_history_tables(): void
    {
        $this->assertTrue(Schema::hasColumns('TB_PERMISSOES', [
            'PK_PERMISSOES',
            'NO_PERMISSOES',
            'DS_PERMISSOES_PT',
            'DS_PERMISSOES_EN',
        ]));
        $this->assertTrue(Schema::hasColumns('RL_PERMISSOES_PERFIS', [
            'PK_PERMISSOES_PERFIS',
            'FK_TIPO_PERFIL',
            'FK_PERMISSOES',
        ]));
        $this->assertTrue(Schema::hasColumns('TH_ALTERACAO_PERFIS', [
            'PK_ALTERACAO_PERFIS',
            'FK_USUARIO_ALTERADO',
            'FK_TIPO_PERFIL_ANTERIOR',
            'FK_TIPO_PERFIL_NOVO',
            'FK_USUARIO_ADM',
            'DH_REGISTRO',
        ]));
    }

    public function test_it_keeps_the_access_profiles_with_the_same_ids(): void
    {
        $this->assertSame(
            [self::ADMIN_PROFILE => 'Administrador', self::MANAGER_PROFILE => 'Gestor', self::USER_PROFILE => 'Usuário'],
            ProfileType::query()->orderBy('PK_TIPO_PERFIL')->pluck('NO_TIPO_PERFIL', 'PK_TIPO_PERFIL')->all(),
        );
    }

    public function test_it_inserts_the_permissions_with_portuguese_and_english_descriptions(): void
    {
        $this->assertSame(self::ALL_PERMISSIONS, Permission::query()->orderBy('PK_PERMISSOES')->pluck('NO_PERMISSOES')->all());
        $this->assertDatabaseHas('TB_PERMISSOES', [
            'NO_PERMISSOES' => 'users.change_profile',
            'DS_PERMISSOES_PT' => 'Alterar o perfil de acesso de usuários',
            'DS_PERMISSOES_EN' => "Change users' access profile",
        ]);
        $this->assertSame(0, Permission::query()->whereNull('DS_PERMISSOES_PT')->orWhereNull('DS_PERMISSOES_EN')->count());
    }

    public function test_it_links_each_access_profile_to_its_permissions(): void
    {
        $this->assertSame(self::ALL_PERMISSIONS, $this->permissionCodes(self::ADMIN_PROFILE));
        $this->assertSame(
            ['journeys.take', 'journeys.assign', 'results.view_own', 'results.view_all', 'users.view'],
            $this->permissionCodes(self::MANAGER_PROFILE),
        );
        $this->assertSame(['journeys.take', 'results.view_own'], $this->permissionCodes(self::USER_PROFILE));
    }

    public function test_permission_lists_the_access_profiles_that_have_it(): void
    {
        $permission = Permission::query()->where('NO_PERMISSOES', 'users.view')->firstOrFail();

        $this->assertSame(
            [self::ADMIN_PROFILE, self::MANAGER_PROFILE],
            $permission->profileTypes()->orderBy('PK_TIPO_PERFIL')->pluck('PK_TIPO_PERFIL')->all(),
        );
    }

    public function test_user_relates_to_access_profile_and_profile_change_history(): void
    {
        $admin = $this->createUser(['FK_TIPO_USUARIO' => 1, 'FK_TIPO_PERFIL' => self::ADMIN_PROFILE]);
        $user = $this->createUser(['FK_TIPO_PERFIL' => self::USER_PROFILE]);

        $firstChange = ProfileTypeChange::create([
            'FK_USUARIO_ALTERADO' => $user->PK_USUARIO,
            'FK_TIPO_PERFIL_ANTERIOR' => null,
            'FK_TIPO_PERFIL_NOVO' => self::USER_PROFILE,
            'FK_USUARIO_ADM' => $admin->PK_USUARIO,
        ]);
        ProfileTypeChange::create([
            'FK_USUARIO_ALTERADO' => $user->PK_USUARIO,
            'FK_TIPO_PERFIL_ANTERIOR' => self::USER_PROFILE,
            'FK_TIPO_PERFIL_NOVO' => self::MANAGER_PROFILE,
            'FK_USUARIO_ADM' => $admin->PK_USUARIO,
        ]);

        $this->assertSame('Usuário', $user->profileType->NO_TIPO_PERFIL);
        $this->assertSame(['journeys.take', 'results.view_own'], $user->profileType->permissions->pluck('NO_PERMISSOES')->all());
        $this->assertCount(2, $user->profileTypeChanges);

        $change = $firstChange->fresh();
        $this->assertNull($change->previousProfileType);
        $this->assertSame('Usuário', $change->newProfileType->NO_TIPO_PERFIL);
        $this->assertTrue($change->user->is($user));
        $this->assertTrue($change->changedBy->is($admin));
        $this->assertNotNull($change->DH_REGISTRO, 'A data da alteracao deve ser preenchida pelo banco.');
    }

    public function test_permission_code_is_unique(): void
    {
        $this->expectException(QueryException::class);

        DB::table('TB_PERMISSOES')->insert(['NO_PERMISSOES' => 'users.manage']);
    }

    public function test_permission_code_is_required(): void
    {
        $this->expectException(QueryException::class);

        DB::table('TB_PERMISSOES')->insert(['NO_PERMISSOES' => null, 'DS_PERMISSOES_PT' => 'Sem código']);
    }

    public function test_profile_permission_pair_is_unique(): void
    {
        $permissionId = $this->permissionId('catalog.manage');

        $this->expectException(QueryException::class);

        DB::table('RL_PERMISSOES_PERFIS')->insert([
            'FK_TIPO_PERFIL' => self::ADMIN_PROFILE,
            'FK_PERMISSOES' => $permissionId,
        ]);
    }

    public function test_profile_permission_requires_profile(): void
    {
        $permissionId = $this->permissionId('catalog.manage');

        $this->expectException(QueryException::class);

        DB::table('RL_PERMISSOES_PERFIS')->insert(['FK_TIPO_PERFIL' => null, 'FK_PERMISSOES' => $permissionId]);
    }

    public function test_profile_permission_requires_permission(): void
    {
        $this->expectException(QueryException::class);

        DB::table('RL_PERMISSOES_PERFIS')->insert(['FK_TIPO_PERFIL' => self::USER_PROFILE, 'FK_PERMISSOES' => null]);
    }

    public function test_profile_change_requires_new_profile_and_author(): void
    {
        $user = $this->createUser();

        foreach (['FK_TIPO_PERFIL_NOVO', 'FK_USUARIO_ADM', 'FK_USUARIO_ALTERADO'] as $requiredColumn) {
            $row = [
                'FK_USUARIO_ALTERADO' => $user->PK_USUARIO,
                'FK_TIPO_PERFIL_NOVO' => self::USER_PROFILE,
                'FK_USUARIO_ADM' => $user->PK_USUARIO,
                $requiredColumn => null,
            ];

            $this->assertQueryFails(
                fn () => DB::table('TH_ALTERACAO_PERFIS')->insert($row),
                "{$requiredColumn} deveria ser obrigatorio.",
            );
        }

        $this->assertDatabaseCount('TH_ALTERACAO_PERFIS', 0);
    }

    public function test_profile_change_history_is_not_deleted_in_cascade(): void
    {
        $admin = $this->createUser(['FK_TIPO_USUARIO' => 1, 'FK_TIPO_PERFIL' => self::ADMIN_PROFILE]);
        $user = $this->createUser();
        ProfileTypeChange::create([
            'FK_USUARIO_ALTERADO' => $user->PK_USUARIO,
            'FK_TIPO_PERFIL_NOVO' => self::USER_PROFILE,
            'FK_USUARIO_ADM' => $admin->PK_USUARIO,
        ]);

        $this->assertQueryFails(
            fn () => DB::table('TB_USUARIO')->where('PK_USUARIO', $user->PK_USUARIO)->delete(),
            'A exclusao fisica de um usuario com historico deveria ser recusada.',
        );

        $this->assertDatabaseCount('TH_ALTERACAO_PERFIS', 1);
    }

    public function test_migration_assigns_profiles_from_user_type_and_can_be_rolled_back(): void
    {
        $admin = $this->createUser(['FK_TIPO_USUARIO' => 1, 'FK_TIPO_PERFIL' => self::USER_PROFILE]);
        $manager = $this->createUser(['FK_TIPO_USUARIO' => 2, 'FK_TIPO_PERFIL' => self::MANAGER_PROFILE]);
        $administratorChoice = $this->createUser(['FK_TIPO_USUARIO' => 2, 'FK_TIPO_PERFIL' => self::ADMIN_PROFILE]);
        $withoutType = $this->createUser(['FK_TIPO_USUARIO' => null, 'FK_TIPO_PERFIL' => null]);

        $migration = require database_path('migrations/2026_11_06_000000_create_access_profile_permissions.php');

        $migration->down();

        $this->assertFalse(Schema::hasTable('TH_ALTERACAO_PERFIS'));
        $this->assertFalse(Schema::hasTable('RL_PERMISSOES_PERFIS'));
        $this->assertFalse(Schema::hasTable('TB_PERMISSOES'));

        $migration->up();

        $this->assertSame(self::ADMIN_PROFILE, (int) $admin->fresh()->FK_TIPO_PERFIL);
        $this->assertSame(self::USER_PROFILE, (int) $manager->fresh()->FK_TIPO_PERFIL);
        $this->assertSame(self::USER_PROFILE, (int) $administratorChoice->fresh()->FK_TIPO_PERFIL);
        $this->assertSame(self::USER_PROFILE, (int) $withoutType->fresh()->FK_TIPO_PERFIL);
        $this->assertDatabaseHas('TB_USUARIO', ['DS_EMAIL' => 'admin@jornada.local', 'FK_TIPO_PERFIL' => self::ADMIN_PROFILE]);
        $this->assertDatabaseHas('TB_USUARIO', ['DS_EMAIL' => 'user@jornada.local', 'FK_TIPO_PERFIL' => self::USER_PROFILE]);
        $this->assertDatabaseCount('TB_PERMISSOES', count(self::ALL_PERMISSIONS));
        $this->assertDatabaseCount('RL_PERMISSOES_PERFIS', 17);
    }

    /**
     * Roda a consulta num savepoint: no Postgres, um erro de restricao aborta a
     * transacao aberta pelo RefreshDatabase, e as consultas seguintes do teste falhariam.
     */
    private function assertQueryFails(callable $query, string $message): void
    {
        try {
            DB::transaction($query);
            $this->fail($message);
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @return list<string>
     */
    private function permissionCodes(int $profileTypeId): array
    {
        return ProfileType::query()
            ->findOrFail($profileTypeId)
            ->permissions()
            ->orderBy('PK_PERMISSOES')
            ->pluck('NO_PERMISSOES')
            ->all();
    }

    private function permissionId(string $code): int
    {
        return (int) Permission::query()->where('NO_PERMISSOES', $code)->value('PK_PERMISSOES');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createUser(array $attributes = []): Usuario
    {
        static $sequence = 0;
        $sequence++;

        return Usuario::create(array_merge([
            'FK_TIPO_PERFIL' => self::USER_PROFILE,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => "Usuario {$sequence}",
            'DS_EMAIL' => "perfil{$sequence}@example.com",
            'DS_SENHA' => 'secret',
            'ST_ATIVO' => 1,
        ], $attributes));
    }
}
