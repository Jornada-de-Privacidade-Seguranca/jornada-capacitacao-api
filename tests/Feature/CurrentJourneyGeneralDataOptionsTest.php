<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentJourneyGeneralDataOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_general_data_options_for_current_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Opcoes dos dados gerais da jornada retornadas com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.fields.english_level.required', true)
            ->assertJsonPath('data.fields.professional_experience.required', true)
            ->assertJsonPath('data.fields.profile_experience.required', true)
            ->assertJsonFragment([
                'name_pt' => 'Nenhum',
                'name_en' => 'None',
            ])
            ->assertJsonFragment([
                'description_pt' => 'Entre 1 e 3 anos',
                'description_en' => 'Between 1 and 3 years',
            ])
            ->assertJsonMissingPath('data.fields.english_level.options.0.weight')
            ->assertJsonMissingPath('data.fields.english_level.options.0.score')
            ->assertJsonMissingPath('data.fields.professional_experience.options.0.weight')
            ->assertJsonMissingPath('data.fields.profile_experience.options.0.score');
    }

    public function test_it_returns_only_english_subcriteria_as_english_options(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertOk();

        $englishOptionNames = collect($response->json('data.fields.english_level.options'))
            ->pluck('name_pt')
            ->all();

        $this->assertContains('Nenhum', $englishOptionNames);
        $this->assertContains('Básico - Leitura', $englishOptionNames);
        $this->assertNotContains('Graduação', $englishOptionNames);
        $this->assertNotContains('Pós-Graduação', $englishOptionNames);
    }

    public function test_it_returns_not_found_when_user_has_no_current_journey(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada em andamento nao encontrada.')
            ->assertJsonPath('code', 'CURRENT_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/current/general-data/options')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_treats_missing_english_configuration_as_internal_error(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);

        Criterion::query()
            ->where('NO_CRITERIO_EN', Criterion::ENGLISH_PROFICIENCY_NAME)
            ->update(['NO_CRITERIO_EN' => 'Unavailable English criterion']);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'CURRENT_JOURNEY_GENERAL_DATA_OPTIONS_FAILED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_user_only_has_a_completed_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $journey->update(['DH_FIM' => '2026-09-15 10:00:00']);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertNotFound()
            ->assertJsonPath('code', 'CURRENT_JOURNEY_NOT_FOUND');
    }

    public function test_it_orders_all_option_lists_by_primary_key(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/general-data/options')
            ->assertOk();

        foreach (['english_level', 'professional_experience', 'profile_experience'] as $field) {
            $ids = collect($response->json("data.fields.{$field}.options"))->pluck('id')->all();
            $orderedIds = $ids;
            sort($orderedIds);

            $this->assertSame($orderedIds, $ids);
        }
    }

    private function activeUser(): Usuario
    {
        return Usuario::create([
            'FK_TIPO_PERFIL' => 3,
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => 'maria.silva@example.com',
            'NO_ORGAO_EMPRESA' => 'Jornada',
            'NO_SETOR' => 'Capacitacao',
            'NU_MATRICULA' => '123',
            'ST_ATIVO' => 1,
        ]);
    }

    private function currentJourneyFor(Usuario $user): Journey
    {
        $domain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => 'Privacidade',
            'NO_DOMINIO_CONHECIMENTO_EN' => 'Privacy',
        ]);
        $profile = JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $domain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => 'Encarregado',
            'NO_PERFIS_EN' => 'DPO',
        ]);

        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => '2026-09-14 10:00:00',
            'DH_FIM' => null,
        ]);
    }
}
