<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalExperience;
use App\Models\Subcriterion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentJourneyGeneralDataUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_general_data_for_current_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $englishLevel = $this->englishSubcriterion('Avancado', 'Advanced');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => $englishLevel->PK_SUBCRITERIOS,
                'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados gerais da jornada salvos com sucesso.')
            ->assertJsonPath('data.journey_id', $journey->PK_JORNADA)
            ->assertJsonPath('data.status', 'ongoing')
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.english_level.id', $englishLevel->PK_SUBCRITERIOS)
            ->assertJsonPath('data.english_level.name_pt', 'Avancado')
            ->assertJsonPath('data.professional_experience.id', $professionalExperience->PK_EXPERIENCIA_ATUACAO)
            ->assertJsonPath('data.profile_experience.id', $profileExperience->PK_EXPERIENCIA_ATUACAO)
            ->assertJsonMissingPath('data.english_level.weight')
            ->assertJsonMissingPath('data.score');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => $englishLevel->PK_SUBCRITERIOS,
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            'NU_PONTUACAO_TOTAL' => null,
        ]);
    }

    public function test_it_accepts_none_as_english_level(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $noneEnglishLevel = Subcriterion::query()
            ->where('NO_SUBCRITERIO_PT', 'Nenhum')
            ->whereHas('criterion', fn ($query) => $query->where('NO_CRITERIO_EN', Criterion::ENGLISH_PROFICIENCY_NAME))
            ->firstOrFail();
        $professionalExperience = $this->professionalExperience('Nenhum', 'None');
        $profileExperience = $this->professionalExperience('Menos de 1 ano', 'Less than 1 year');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => $noneEnglishLevel->PK_SUBCRITERIOS,
                'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertOk()
            ->assertJsonPath('data.english_level.name_pt', 'Nenhum');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => $noneEnglishLevel->PK_SUBCRITERIOS,
        ]);
    }

    public function test_it_rejects_subcriterion_from_another_criterion_as_english_level(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);
        $educationSubcriterion = $this->educationSubcriterion();
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => $educationSubcriterion->PK_SUBCRITERIOS,
                'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.english_level_id.0', 'O nivel de ingles informado nao pertence ao criterio de ingles.');
    }

    public function test_it_returns_not_found_when_user_has_no_current_journey(): void
    {
        $user = $this->activeUser();
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => $englishLevel->PK_SUBCRITERIOS,
                'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Jornada em andamento nao encontrada.')
            ->assertJsonPath('code', 'CURRENT_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation error.')
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.english_level_id.0', 'Informe o nivel de ingles.')
            ->assertJsonPath('errors.professional_experience_id.0', 'Informe a experiencia profissional.')
            ->assertJsonPath('errors.profile_experience_id.0', 'Informe o tempo de atuacao no perfil.');
    }

    public function test_it_rejects_update_without_csrf_token(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJson('/api/journeys/current/general-data', [
                'english_level_id' => $englishLevel->PK_SUBCRITERIOS,
                'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
            ])
            ->assertStatus(419)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Token CSRF invalido.')
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_unauthenticated_update(): void
    {
        $this->withCsrfToken()
            ->putJson('/api/journeys/current/general-data', [
                'english_level_id' => 1,
                'professional_experience_id' => 1,
                'profile_experience_id' => 1,
            ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_nonexistent_ids_without_changing_the_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => 999999,
                'professional_experience_id' => 999999,
                'profile_experience_id' => 999999,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.english_level_id.0', 'O nivel de ingles informado nao existe.')
            ->assertJsonPath('errors.professional_experience_id.0', 'A experiencia profissional informada nao existe.')
            ->assertJsonPath('errors.profile_experience_id.0', 'O tempo de atuacao no perfil informado nao existe.');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => null,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
        ]);
    }

    public function test_it_rejects_non_integer_ids(): void
    {
        $user = $this->activeUser();
        $this->currentJourneyFor($user);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', [
                'english_level_id' => 'abc',
                'professional_experience_id' => 'abc',
                'profile_experience_id' => 'abc',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.english_level_id.0', 'O nivel de ingles deve ser um numero inteiro.')
            ->assertJsonPath('errors.professional_experience_id.0', 'A experiencia profissional deve ser um numero inteiro.')
            ->assertJsonPath('errors.profile_experience_id.0', 'O tempo de atuacao no perfil deve ser um numero inteiro.');
    }

    public function test_it_returns_not_found_and_does_not_update_a_completed_journey(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $journey->update(['DH_FIM' => '2026-09-15 10:00:00']);
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', $this->payload(
                $englishLevel,
                $professionalExperience,
                $profileExperience,
            ))
            ->assertNotFound()
            ->assertJsonPath('code', 'CURRENT_JOURNEY_NOT_FOUND');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => null,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
        ]);
    }

    public function test_it_updates_the_most_recent_current_journey_only(): void
    {
        $user = $this->activeUser();
        $olderJourney = $this->currentJourneyFor($user);
        $olderJourney->update(['DH_CRIADO_EM' => '2026-09-13 10:00:00']);
        $newerJourney = $this->currentJourneyFor($user);
        $newerJourney->update(['DH_CRIADO_EM' => '2026-09-15 10:00:00']);
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');
        $payload = $this->payload($englishLevel, $professionalExperience, $profileExperience);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', $payload)
            ->assertOk()
            ->assertJsonPath('data.journey_id', $newerJourney->PK_JORNADA);

        $this->assertDatabaseHas('TB_JORNADA', array_merge(['PK_JORNADA' => $newerJourney->PK_JORNADA], [
            'FK_SUBCRITERIOS_INGLES' => $payload['english_level_id'],
            'FK_EXPERIENCIA_PROFISSIONAL' => $payload['professional_experience_id'],
            'FK_ATUACAO_PERFIL' => $payload['profile_experience_id'],
        ]));
        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $olderJourney->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => null,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
        ]);
    }

    public function test_it_does_not_update_another_users_journey(): void
    {
        $user = $this->activeUser();
        $otherUser = $this->activeUser('joao.silva@example.com', '456');
        $ownJourney = $this->currentJourneyFor($user);
        $otherJourney = $this->currentJourneyFor($otherUser);
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', $this->payload(
                $englishLevel,
                $professionalExperience,
                $profileExperience,
            ))
            ->assertOk()
            ->assertJsonPath('data.journey_id', $ownJourney->PK_JORNADA);

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $otherJourney->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => null,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
        ]);
    }

    public function test_it_overwrites_general_data_when_the_update_is_repeated(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $firstPayload = $this->payload(
            $this->englishSubcriterion('Basico', 'Basic'),
            $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years'),
            $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years'),
        );
        $secondPayload = $this->payload(
            $this->englishSubcriterion('Avancado', 'Advanced'),
            $this->professionalExperience('Entre 7 e 10 anos', 'Between 7 and 10 years'),
            $this->professionalExperience('Mais de 10 anos', 'More than 10 years'),
        );

        $this->actingAs($user)->putJsonWithCsrf('/api/journeys/current/general-data', $firstPayload)->assertOk();
        $this->actingAs($user)->putJsonWithCsrf('/api/journeys/current/general-data', $secondPayload)->assertOk();

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => $secondPayload['english_level_id'],
            'FK_EXPERIENCIA_PROFISSIONAL' => $secondPayload['professional_experience_id'],
            'FK_ATUACAO_PERFIL' => $secondPayload['profile_experience_id'],
        ]);
    }

    public function test_it_treats_missing_english_configuration_as_internal_error(): void
    {
        $user = $this->activeUser();
        $journey = $this->currentJourneyFor($user);
        $englishLevel = $this->englishSubcriterion('Basico', 'Basic');
        $professionalExperience = $this->professionalExperience('Entre 1 e 3 anos', 'Between 1 and 3 years');
        $profileExperience = $this->professionalExperience('Entre 4 e 6 anos', 'Between 4 and 6 years');

        Criterion::query()
            ->where('NO_CRITERIO_EN', Criterion::ENGLISH_PROFICIENCY_NAME)
            ->update(['NO_CRITERIO_EN' => 'Unavailable English criterion']);

        $this->actingAs($user)
            ->putJsonWithCsrf('/api/journeys/current/general-data', $this->payload(
                $englishLevel,
                $professionalExperience,
                $profileExperience,
            ))
            ->assertInternalServerError()
            ->assertJsonPath('code', 'CURRENT_JOURNEY_GENERAL_DATA_UPDATE_FAILED');

        $this->assertDatabaseHas('TB_JORNADA', [
            'PK_JORNADA' => $journey->PK_JORNADA,
            'FK_SUBCRITERIOS_INGLES' => null,
            'FK_EXPERIENCIA_PROFISSIONAL' => null,
            'FK_ATUACAO_PERFIL' => null,
        ]);
    }

    private function activeUser(
        string $email = 'maria.silva@example.com',
        string $registrationNumber = '123',
    ): Usuario {
        return Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => 'Maria Silva',
            'DS_EMAIL' => $email,
            'NO_ORGAO_EMPRESA' => 'Jornada',
            'NO_SETOR' => 'Capacitacao',
            'NU_MATRICULA' => $registrationNumber,
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

    private function englishSubcriterion(string $namePt, string $nameEn): Subcriterion
    {
        $criterion = Criterion::query()
            ->where('NO_CRITERIO_EN', Criterion::ENGLISH_PROFICIENCY_NAME)
            ->firstOrFail();

        return Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => $namePt,
            'NO_SUBCRITERIO_EN' => $nameEn,
            'NU_PESO_SUBCRITERIO' => 0,
        ]);
    }

    private function educationSubcriterion(): Subcriterion
    {
        $criterion = Criterion::query()
            ->where('NO_CRITERIO_EN', 'Education')
            ->firstOrFail();

        return Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Graduacao',
            'NO_SUBCRITERIO_EN' => 'Graduation',
            'NU_PESO_SUBCRITERIO' => 0,
        ]);
    }

    private function professionalExperience(string $descriptionPt, string $descriptionEn): ProfessionalExperience
    {
        return ProfessionalExperience::create([
            'DS_EXPERIENCIA_ATUACAO_PT' => $descriptionPt,
            'DS_EXPERIENCIA_ATUACAO_EN' => $descriptionEn,
        ]);
    }

    private function payload(
        Subcriterion $englishLevel,
        ProfessionalExperience $professionalExperience,
        ProfessionalExperience $profileExperience,
    ): array {
        return [
            'english_level_id' => $englishLevel->PK_SUBCRITERIOS,
            'professional_experience_id' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'profile_experience_id' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
        ];
    }
}
