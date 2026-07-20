<?php

namespace Tests\Feature;

use App\Models\Competency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_competency(): void
    {
        $response = $this->postJson('/api/competencies', [
            'name' => 'Privacidade de dados',
            'description' => 'Conceitos essenciais de protecao de dados pessoais.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Privacidade de dados');

        $this->assertDatabaseHas('TB_COMPETENCIAS', [
            'NO_COMPETENCIAS_PT' => 'Privacidade de dados',
        ]);
    }

    public function test_it_lists_competencies(): void
    {
        Competency::factory()->count(2)->create();

        $this->getJson('/api/competencies')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_it_rejects_invalid_competency_payload_with_standard_contract(): void
    {
        $this->postJson('/api/competencies', [
            'name' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['errors' => ['name']]);
    }
}
