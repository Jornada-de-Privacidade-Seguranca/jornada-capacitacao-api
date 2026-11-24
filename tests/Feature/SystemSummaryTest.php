<?php

namespace Tests\Feature;

use App\Models\Competency;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_summary_endpoint_uses_standard_api_contract(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);

        $this->actingAs($admin)
            ->getJson('/api/system-summary')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'System summary retrieved successfully.')
            ->assertJsonPath('data.environment', 'testing')
            ->assertJsonPath('data.database.connected', true);
    }

    public function test_system_summary_returns_competencies_count_from_database(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);
        Competency::factory()->count(3)->create();
        $expectedCount = Competency::query()->count();

        $this->actingAs($admin)
            ->getJson('/api/system-summary')
            ->assertOk()
            ->assertJsonPath('data.database.competencies_count', $expectedCount);
    }

    public function test_system_summary_handles_include_database_status_parameter(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);
        $expectedCount = Competency::query()->count();

        $this->actingAs($admin)
            ->getJson('/api/system-summary?include_database_status=false')
            ->assertOk()
            ->assertJsonMissingPath('data.database.connected')
            ->assertJsonMissingPath('data.database.driver')
            ->assertJsonPath('data.database.competencies_count', $expectedCount);
    }

    public function test_system_summary_supports_custom_tag(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);

        $this->actingAs($admin)
            ->getJson('/api/system-summary?tag=monitoring')
            ->assertOk()
            ->assertJsonPath('data.tag', 'monitoring');
    }

    public function test_system_summary_validation_rejects_invalid_boolean(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);

        $this->actingAs($admin)
            ->getJson('/api/system-summary?include_database_status=invalid-bool')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'include_database_status',
                ],
                'code',
            ]);
    }

    public function test_system_summary_validation_rejects_tag_greater_than_fifty_characters(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);
        $tag = str_repeat('a', 51);

        $this->actingAs($admin)
            ->getJson("/api/system-summary?tag={$tag}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'tag',
                ],
                'code',
            ]);
    }

    public function test_system_summary_allows_swagger_origin(): void
    {
        $admin = $this->createUser('Administrator', 1, 1);

        $this->actingAs($admin)
            ->withHeader('Origin', 'http://localhost:8031')
            ->getJson('/api/system-summary')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8031');
    }

    public function test_system_summary_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/system-summary')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_system_summary_rejects_common_user(): void
    {
        $commonUser = $this->createUser('Common User', 1, 2, 3);

        $this->actingAs($commonUser)
            ->getJson('/api/system-summary')
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');
    }

    public function test_system_summary_allows_administrator_profile_even_with_null_user_type(): void
    {
        $admin = $this->createUser('Profile Admin', 1, null, 1);

        $this->actingAs($admin)
            ->getJson('/api/system-summary')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_system_summary_rejects_legacy_admin_user_type_with_manager_or_user_profile(): void
    {
        $legacyTypeWithManagerProfile = $this->createUser('Legacy Manager', 1, 1, 2);
        $legacyTypeWithUserProfile = $this->createUser('Legacy User', 1, 1, 3);

        $this->actingAs($legacyTypeWithManagerProfile)
            ->getJson('/api/system-summary')
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');

        $this->actingAs($legacyTypeWithUserProfile)
            ->getJson('/api/system-summary')
            ->assertForbidden()
            ->assertJsonPath('code', 'PERMISSION_DENIED');
    }

    private function createUser(string $name, int $status, ?int $userType, ?int $profileType = null): Usuario
    {
        return Usuario::query()->create([
            'FK_TIPO_PERFIL' => $profileType ?? ($userType === 1 ? 1 : 3),
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
