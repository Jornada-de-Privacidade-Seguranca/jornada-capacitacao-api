<?php

namespace Tests\Feature;

use App\Models\JourneyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_journey_profiles(): void
    {
        JourneyProfile::factory()->count(2)->create();

        $this->getJson('/api/journey-profiles')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profiles retrieved successfully.')
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'description_pt',
                        'description_en',
                        'type',
                    ],
                ],
            ]);
    }

    public function test_it_paginates_journey_profiles(): void
    {
        JourneyProfile::factory()->count(25)->create();

        $this->getJson('/api/journey-profiles?page=2&per_page=8')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(8, 'data')
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 8)
            ->assertJsonPath('meta.pagination.total', 25)
            ->assertJsonPath('meta.pagination.last_page', 4)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'meta' => [
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    public function test_it_shows_a_journey_profile(): void
    {
        $journeyProfile = JourneyProfile::factory()->create([
            'DS_PERFIS_PT' => 'Perfil técnico',
            'DS_PERFIS_EN' => 'Technical profile',
        ]);

        $this->getJson('/api/journey-profiles/' . $journeyProfile->PK_PERFIS_JORNADA)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profile retrieved successfully.')
            ->assertJsonPath('data.id', $journeyProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.description_pt', 'Perfil técnico')
            ->assertJsonPath('data.description_en', 'Technical profile')
            ->assertJsonPath('data.type', null);
    }

    public function test_it_returns_not_found_when_journey_profile_does_not_exist(): void
    {
        $this->getJson('/api/journey-profiles/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Journey profile not found.')
            ->assertJsonPath('code', 'JOURNEY_PROFILE_NOT_FOUND');
    }
}
