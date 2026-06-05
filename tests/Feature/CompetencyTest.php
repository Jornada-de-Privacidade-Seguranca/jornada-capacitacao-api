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
            ->assertJsonPath('data.name', 'Privacidade de dados');

        $this->assertDatabaseHas('competencies', [
            'name' => 'Privacidade de dados',
        ]);
    }

    public function test_it_lists_competencies(): void
    {
        Competency::factory()->count(2)->create();

        $this->getJson('/api/competencies')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
