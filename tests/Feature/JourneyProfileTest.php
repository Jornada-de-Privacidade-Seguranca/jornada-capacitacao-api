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

    public function test_it_shows_a_journey_profile(): void
    {
        $journeyProfile = JourneyProfile::factory()->create([
            'DS_PERFIS_PT' => 'Perfil técnico',
            'DS_PERFIS_EN' => 'Technical profile',
            'TP_PERFIS' => 2,
        ]);

        $this->getJson('/api/journey-profiles/' . $journeyProfile->PK_PERFIS_JORNADA)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Journey profile retrieved successfully.')
            ->assertJsonPath('data.id', $journeyProfile->PK_PERFIS_JORNADA)
            ->assertJsonPath('data.description_pt', 'Perfil técnico')
            ->assertJsonPath('data.description_en', 'Technical profile')
            ->assertJsonPath('data.type', 2);
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