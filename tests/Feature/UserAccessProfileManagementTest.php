<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Permission;
use App\Models\ProfileType;
use App\Models\ProfileTypeChange;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAccessProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_listing_supports_pagination_search_and_filters(): void
    {
        $admin = $this->createUser('Admin Sistema', 'admin.gestao@example.com', ProfileType::ADMIN_ID, 'TI', 1, 'MAT-001');
        $target = $this->createUser('João Ávila', 'joao.avila@example.com', 2, 'Privacidade', 1, 'MAT-999');
        $this->createUser('Maria Pendente', 'maria.pendente@example.com', null, 'Juridico', 2, 'MAT-002');
        $this->createUser('Carlos Inativo', 'carlos.inativo@example.com', ProfileType::USER_ID, 'TI', 0, 'MAT-003');
        $deleted = $this->createUser('Usuario Excluido', 'excluido@example.com', ProfileType::USER_ID, 'TI', 0, 'MAT-004');
        $deleted->delete();

        $this->actingAs($admin)
            ->getJson('/api/users?page=1&per_page=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // Busca sem acento por nome
        $this->actingAs($admin)
            ->getJson('/api/users?search=avila')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->PK_USUARIO)
            ->assertJsonPath('data.0.access_profile.id', 2)
            ->assertJsonPath('data.0.status', 'active');

        // Busca por matricula
        $this->actingAs($admin)
            ->getJson('/api/users?search=MAT-999')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->PK_USUARIO);

        // Filtro por status pending_activation
        $this->actingAs($admin)
            ->getJson('/api/users?status=pending_activation')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'maria.pendente@example.com');

        // Filtro por status inactive
        $this->actingAs($admin)
            ->getJson('/api/users?status=inactive')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'carlos.inativo@example.com');

        // Filtro por perfil e setor
        $this->actingAs($admin)
            ->getJson('/api/users?profile_type_id=2&department=Privacidade')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->PK_USUARIO);
    }

    public function test_users_listing_and_detail_expose_registration_number_and_completed_journeys_count(): void
    {
        $admin = $this->createUser('Admin Sistema', 'admin.contagem@example.com', ProfileType::ADMIN_ID, 'TI', 1, 'MAT-010');
        $user = $this->createUser('Bruno Contagem', 'bruno.contagem@example.com', ProfileType::USER_ID, 'RH', 1, 'MAT-011');

        $domain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'DPO',
        ]);

        // Duas finalizadas, uma em andamento e uma cancelada: so as finalizadas contam
        foreach ([
            ['DH_FIM' => '2026-11-02 10:00:00', 'DH_CANCELADA_EM' => null],
            ['DH_FIM' => '2026-11-03 10:00:00', 'DH_CANCELADA_EM' => null],
            ['DH_FIM' => null, 'DH_CANCELADA_EM' => null],
            ['DH_FIM' => null, 'DH_CANCELADA_EM' => '2026-11-04 10:00:00'],
        ] as $dates) {
            Journey::create([
                'FK_USUARIO' => $user->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
                'DH_CRIADO_EM' => '2026-11-01 10:00:00',
                ...$dates,
            ]);
        }

        $this->actingAs($admin)
            ->getJson('/api/users?search=bruno')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.registration_number', 'MAT-011')
            ->assertJsonPath('data.0.completed_journeys_count', 2);

        $this->actingAs($admin)
            ->getJson('/api/users?search=admin.contagem')
            ->assertOk()
            ->assertJsonPath('data.0.completed_journeys_count', 0);

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}")
            ->assertOk()
            ->assertJsonPath('data.registration_number', 'MAT-011')
            ->assertJsonPath('data.completed_journeys_count', 2);

        $this->actingAs($admin)
            ->withCsrfToken()
            ->patchJson("/api/users/{$user->PK_USUARIO}", ['department' => 'Juridico'])
            ->assertOk()
            ->assertJsonPath('data.department', 'Juridico')
            ->assertJsonPath('data.completed_journeys_count', 2);

        $this->actingAs($admin)
            ->withCsrfToken()
            ->putJson("/api/users/{$user->PK_USUARIO}/access-profile", ['profile_type_id' => 2])
            ->assertOk()
            ->assertJsonPath('data.access_profile.id', 2)
            ->assertJsonPath('data.completed_journeys_count', 2);
    }

    public function test_show_and_update_user_details_with_unique_email_validation(): void
    {
        $admin = $this->createUser('Admin Sistema', 'admin.edit@example.com', ProfileType::ADMIN_ID, 'TI', 1, 'MAT-100');
        $user = $this->createUser('Maria Silva', 'maria.silva@example.com', ProfileType::USER_ID, 'RH', 1, 'MAT-200');
        $other = $this->createUser('Outro Usuario', 'outro@example.com', ProfileType::USER_ID, 'RH', 1, 'MAT-201');

        $this->actingAs($admin)
            ->getJson("/api/users/{$user->PK_USUARIO}")
            ->assertOk()
            ->assertJsonPath('data.id', $user->PK_USUARIO)
            ->assertJsonPath('data.registration_number', 'MAT-200')
            ->assertJsonPath('data.journey_profile_id', null);

        // Rota com identificador nao numerico nao captura rotas fixas e responde 404
        $this->actingAs($admin)
            ->getJson('/api/users/not-a-number')
            ->assertNotFound();

        // registration_number vazio ou null na edicao e rejeitado com 422
        $this->actingAs($admin)
            ->withCsrfToken()
            ->patchJson("/api/users/{$user->PK_USUARIO}", [
                'registration_number' => '',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['registration_number']);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NU_MATRICULA' => 'MAT-200',
        ]);

        $this->actingAs($admin)
            ->withCsrfToken()
            ->patchJson("/api/users/{$user->PK_USUARIO}", [
                'registration_number' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['registration_number']);

        $this->assertDatabaseHas('TB_USUARIO', [
            'PK_USUARIO' => $user->PK_USUARIO,
            'NU_MATRICULA' => 'MAT-200',
        ]);

        // E-mail duplicado -> 409
        $this->actingAs($admin)
            ->withCsrfToken()
            ->patchJson("/api/users/{$user->PK_USUARIO}", [
                'email' => $other->DS_EMAIL,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'EMAIL_ALREADY_IN_USE');

        // Edicao valida
        $this->actingAs($admin)
            ->withCsrfToken()
            ->patchJson("/api/users/{$user->PK_USUARIO}", [
                'name' => 'Maria Editada',
                'email' => 'maria.editada@example.com',
                'organization' => 'Nova Org',
                'department' => 'Juridico',
                'registration_number' => 'MAT-299',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Maria Editada')
            ->assertJsonPath('data.email', 'maria.editada@example.com')
            ->assertJsonPath('data.organization', 'Nova Org')
            ->assertJsonPath('data.department', 'Juridico')
            ->assertJsonPath('data.registration_number', 'MAT-299');
    }

    public function test_change_access_profile_records_history_and_enforces_security_rules(): void
    {
        $admin1 = $this->createUser('Admin Um', 'admin1@example.com', ProfileType::ADMIN_ID, 'TI', 1);
        $target = $this->createUser('Usuario Alvo', 'alvo@example.com', ProfileType::USER_ID, 'TI', 1);

        // Nao pode alterar o proprio perfil
        $this->actingAs($admin1)
            ->withCsrfToken()
            ->putJson("/api/users/{$admin1->PK_USUARIO}/access-profile", [
                'profile_type_id' => 2,
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'SELF_PROFILE_CHANGE_FORBIDDEN');

        // Promove alvo para Gestor (2) e depois Administrador (1)
        $this->actingAs($admin1)
            ->withCsrfToken()
            ->putJson("/api/users/{$target->PK_USUARIO}/access-profile", [
                'profile_type_id' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.access_profile.id', 2);

        // Para garantir ordem deterministica no historico, ajusta DH_REGISTRO do primeiro registro
        ProfileTypeChange::query()
            ->where('FK_USUARIO_ALTERADO', $target->PK_USUARIO)
            ->update(['DH_REGISTRO' => '2026-11-10 10:00:00']);

        $this->actingAs($admin1)
            ->withCsrfToken()
            ->putJson("/api/users/{$target->PK_USUARIO}/access-profile", [
                'profile_type_id' => ProfileType::ADMIN_ID,
            ])
            ->assertOk()
            ->assertJsonPath('data.access_profile.id', ProfileType::ADMIN_ID);

        // Historico ordenado do mais recente para o mais antigo
        $this->actingAs($admin1)
            ->getJson("/api/users/{$target->PK_USUARIO}/access-profile-history")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.previous_profile.id', 2)
            ->assertJsonPath('data.0.new_profile.id', ProfileType::ADMIN_ID)
            ->assertJsonPath('data.0.changed_by.id', $admin1->PK_USUARIO)
            ->assertJsonPath('data.1.previous_profile.id', ProfileType::USER_ID)
            ->assertJsonPath('data.1.new_profile.id', 2);

        // Se outro admin existir, pode rebaixar o primeiro; quando restar apenas 1 admin ativo, bloqueia
        // Primeiro desativamos todos os demais admins seedados pela migration para isolar o teste
        Usuario::query()
            ->where('FK_TIPO_PERFIL', ProfileType::ADMIN_ID)
            ->whereNotIn('PK_USUARIO', [$admin1->PK_USUARIO, $target->PK_USUARIO])
            ->update(['ST_ATIVO' => 0]);

        $this->actingAs($admin1)
            ->withCsrfToken()
            ->putJson("/api/users/{$target->PK_USUARIO}/access-profile", [
                'profile_type_id' => ProfileType::USER_ID,
            ])
            ->assertOk();

        // Agora $admin1 e o ultimo admin ativo; $target nao pode mais (virou comum), entao criamos $admin2 temporario
        $admin2 = $this->createUser('Admin Dois', 'admin2@example.com', ProfileType::ADMIN_ID, 'TI', 1);
        $this->actingAs($admin2)
            ->withCsrfToken()
            ->putJson("/api/users/{$admin1->PK_USUARIO}/access-profile", [
                'profile_type_id' => ProfileType::USER_ID,
            ])
            ->assertOk();

        // Agora $admin2 e o ultimo admin ativo; $admin1 (com perfil comum) ja nao tem permissao, entao usamos outro usuario com users.change_profile?
        // Ou re-testamos com um perfil/usuario: apenas garantindo que outro admin $admin3 promove e rebaixa:
        $admin1->update(['FK_TIPO_PERFIL' => ProfileType::ADMIN_ID]);
        // Simula cenario onde $admin2 e o unico admin ativo tentando ser rebaixado por um gestor que recebeu users.change_profile
        $admin1->update(['ST_ATIVO' => 0]);

        // Rebaixa $admin2 usando um usuario com profile_type_id = 1? Nao, usamos um perfil com users.change_profile (ex: admin1 inativo nao autentica, entao criamos perfil customizado com users.change_profile)
        $customProfile = ProfileType::create(['NO_TIPO_PERFIL' => 'Auditor Perfis']);
        $permissionId = Permission::query()->where('NO_PERMISSOES', 'users.change_profile')->value('PK_PERMISSOES');
        $customProfile->permissions()->attach($permissionId);
        $delegatedUser = $this->createUser('Delegado Perfis', 'delegado@example.com', $customProfile->PK_TIPO_PERFIL, 'TI', 1);

        $this->actingAs($delegatedUser)
            ->withCsrfToken()
            ->putJson("/api/users/{$admin2->PK_USUARIO}/access-profile", [
                'profile_type_id' => ProfileType::USER_ID,
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'LAST_ADMIN_REQUIRED');
    }

    public function test_soft_delete_hides_user_blocks_login_preserves_journeys_and_enforces_last_admin(): void
    {
        $admin1 = $this->createUser('Admin Um', 'admin.del1@example.com', ProfileType::ADMIN_ID, 'TI', 1);
        $admin2 = $this->createUser('Admin Dois', 'admin.del2@example.com', ProfileType::ADMIN_ID, 'TI', 1);
        $user = $this->createUser('Usuario Deletavel', 'deletavel@example.com', ProfileType::USER_ID, 'RH', 1);

        // Cria jornada e historico para o usuario
        $domain = KnowledgeDomain::create(['NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade']);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'DPO',
        ]);
        $journey = Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => '2026-11-01 10:00:00',
            'DH_FIM' => null,
        ]);

        // Nao pode excluir a propria conta
        $this->actingAs($admin1)
            ->withCsrfToken()
            ->deleteJson("/api/users/{$admin1->PK_USUARIO}")
            ->assertForbidden()
            ->assertJsonPath('code', 'SELF_DELETION_FORBIDDEN');

        // Exclui usuario comum com sucesso
        $this->actingAs($admin1)
            ->withCsrfToken()
            ->deleteJson("/api/users/{$user->PK_USUARIO}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('TB_USUARIO', ['PK_USUARIO' => $user->PK_USUARIO]);
        $this->actingAs($admin1)
            ->getJson("/api/users/{$user->PK_USUARIO}")
            ->assertNotFound()
            ->assertJsonPath('code', 'USER_NOT_FOUND');
        $this->actingAs($admin1)->getJson('/api/users?search=deletavel')->assertOk()->assertJsonCount(0, 'data');

        // Sessao aberta de usuario excluido e invalidada na proxima chamada
        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');

        // Cadastro publico com e-mail de conta excluida e recusado com 409
        $this->withCsrfToken()
            ->postJson('/api/auth/register', [
                'name' => 'Novo Deletavel',
                'email' => 'deletavel@example.com',
                'password' => 'NovaSenhaSegura123',
                'password_confirmation' => 'NovaSenhaSegura123',
                'organization' => 'Org Nova',
                'department' => 'TI',
                'registration_number' => 'MAT-NOVA',
            ])
            ->assertConflict()
            ->assertJsonPath('code', 'ACCOUNT_ALREADY_EXISTS');

        // Login recusado para usuario excluido
        $this->withCsrfToken()->postJson('/api/auth/login', [
            'email' => 'deletavel@example.com',
            'password' => 'SenhaSegura123',
        ])->assertUnauthorized();

        // Jornada preservada e nome do usuario continua disponivel
        $this->assertDatabaseHas('TB_JORNADA', ['PK_JORNADA' => $journey->PK_JORNADA]);
        $this->actingAs($admin1)
            ->getJson("/api/journeys/{$journey->PK_JORNADA}")
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Usuario Deletavel');

        // Isola apenas $admin2 como ultimo admin ativo
        Usuario::query()
            ->where('FK_TIPO_PERFIL', ProfileType::ADMIN_ID)
            ->whereNotIn('PK_USUARIO', [$admin2->PK_USUARIO])
            ->update(['ST_ATIVO' => 0]);

        $manageProfile = ProfileType::create(['NO_TIPO_PERFIL' => 'Gestor Exclusao']);
        $managePermissionId = Permission::query()->where('NO_PERMISSOES', 'users.manage')->value('PK_PERMISSOES');
        $manageProfile->permissions()->attach($managePermissionId);
        $managerWithManage = $this->createUser('Gestor Manage', 'gestor.manage@example.com', $manageProfile->PK_TIPO_PERFIL, 'TI', 1);

        $this->actingAs($managerWithManage)
            ->withCsrfToken()
            ->deleteJson("/api/users/{$admin2->PK_USUARIO}")
            ->assertConflict()
            ->assertJsonPath('code', 'LAST_ADMIN_REQUIRED');
    }

    public function test_access_profiles_endpoint_and_permissions_enforcement(): void
    {
        $admin = $this->createUser('Admin Sistema', 'admin.perm@example.com', ProfileType::ADMIN_ID, 'TI', 1);
        $manager = $this->createUser('Gestor Area', 'gestor.perm@example.com', 2, 'TI', 1);
        $common = $this->createUser('Usuario Comum', 'comum.perm@example.com', ProfileType::USER_ID, 'TI', 1);

        $this->actingAs($admin)
            ->getJson('/api/access-profiles')
            ->assertOk()
            ->assertJsonPath('data.0.id', 1)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'permissions' => [
                            '*' => ['id', 'code', 'description_pt', 'description_en'],
                        ],
                    ],
                ],
            ]);

        // Gestor tem users.view mas nao tem users.manage nem users.change_profile
        $this->actingAs($manager)->getJson('/api/users')->assertOk();
        $this->actingAs($manager)->getJson('/api/access-profiles')->assertOk();
        $this->actingAs($manager)->withCsrfToken()->patchJson("/api/users/{$common->PK_USUARIO}", ['name' => 'Novo Nome'])
            ->assertForbidden()->assertJsonPath('code', 'PERMISSION_DENIED');
        $this->actingAs($manager)->withCsrfToken()->putJson("/api/users/{$common->PK_USUARIO}/access-profile", ['profile_type_id' => 2])
            ->assertForbidden()->assertJsonPath('code', 'PERMISSION_DENIED');
        $this->actingAs($manager)->withCsrfToken()->deleteJson("/api/users/{$common->PK_USUARIO}")
            ->assertForbidden()->assertJsonPath('code', 'PERMISSION_DENIED');

        // Usuario comum nao tem users.view
        $this->actingAs($common)->getJson('/api/users')->assertForbidden()->assertJsonPath('code', 'PERMISSION_DENIED');
        $this->actingAs($common)->getJson('/api/access-profiles')->assertForbidden()->assertJsonPath('code', 'PERMISSION_DENIED');

        // CSRF obrigatorio em rotas mutaveis
        $this->actingAs($admin)
            ->withSession(['_token' => 'expected-csrf-token'])
            ->withHeader('X-CSRF-TOKEN', 'invalid-csrf-token')
            ->patchJson("/api/users/{$common->PK_USUARIO}", ['name' => 'Sem Csrf'])
            ->assertStatus(419)->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }

    public function test_deleted_at_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_11_12_000001_add_deleted_at_to_tb_usuario.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM'));

        // Idempotent down
        $migration->down();
        $this->assertFalse(Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM'));

        $migration->up();
        $this->assertTrue(Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM'));

        // Idempotent up
        $migration->up();
        $this->assertTrue(Schema::hasColumn('TB_USUARIO', 'DH_EXCLUIDO_EM'));
    }

    private function createUser(
        string $name,
        string $email,
        ?int $profileTypeId,
        string $department,
        int $status,
        ?string $registrationNumber = null,
    ): Usuario {
        return Usuario::query()->create([
            'FK_TIPO_PERFIL' => $profileTypeId,
            'FK_TIPO_USUARIO' => $profileTypeId === ProfileType::ADMIN_ID ? 1 : 2,
            'NO_USUARIO' => $name,
            'NO_ORGAO_EMPRESA' => 'Secretaria',
            'NO_SETOR' => $department,
            'NU_MATRICULA' => $registrationNumber,
            'DS_EMAIL' => $email,
            'DS_SENHA' => Hash::make('SenhaSegura123'),
            'ST_ATIVO' => $status,
        ]);
    }
}
