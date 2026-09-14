<?php

namespace Tests\Feature;

use App\Models\Criterion;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\Subcriterion;
use App\Models\Usuario;
use App\Services\JourneyCourseOptionsService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class JourneyCourseOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_course_options_for_user_with_ongoing_journey(): void
    {
        $user = $this->activeUser('Maria Silva');
        $this->ongoingJourney($user);
        $bachelor = $this->subcriterio('Graduação', 'Bachelor Degree');
        $this->higherEducationCourse('Direito', 'Law', $bachelor->PK_SUBCRITERIOS);
        $this->higherEducationCourse('Ciencia da Computacao', 'Computer Science', $bachelor->PK_SUBCRITERIOS);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cursos disponiveis encontrados com sucesso.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name_pt', 'Ciencia da Computacao')
            ->assertJsonPath('data.0.type.id', $bachelor->PK_SUBCRITERIOS)
            ->assertJsonPath('data.0.type.name_pt', 'Graduação')
            ->assertJsonPath('data.1.name_pt', 'Direito');
    }

    public function test_it_returns_null_type_when_course_has_no_matching_subcriterio(): void
    {
        $user = $this->activeUser('Maria Silva');
        $this->ongoingJourney($user);
        HigherEducationCourse::create([
            'NO_CURSO_SUPERIOR_PT' => 'Curso Sem Tipo',
            'NO_CURSO_SUPERIOR_EN' => 'Course Without Type',
            'FK_SUBCRITERIOS' => null,
        ]);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses/options')
            ->assertOk()
            ->assertJsonPath('data.0.type', null);
    }

    public function test_it_filters_course_options_by_type_id(): void
    {
        $user = $this->activeUser('Maria Silva');
        $this->ongoingJourney($user);
        $bachelor = $this->subcriterio('Graduação', 'Bachelor Degree');
        $technologist = $this->subcriterio('Tecnólogo', 'Technologist Degree');
        $this->higherEducationCourse('Direito', 'Law', $bachelor->PK_SUBCRITERIOS);
        $this->higherEducationCourse('Analise de Sistemas', 'Systems Analysis', $technologist->PK_SUBCRITERIOS);

        $this->actingAs($user)
            ->getJson("/api/journeys/current/courses/options?type_id={$technologist->PK_SUBCRITERIOS}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name_pt', 'Analise de Sistemas')
            ->assertJsonPath('data.0.type.id', $technologist->PK_SUBCRITERIOS);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->getJson('/api/journeys/current/courses/options')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Usuario nao autenticado.')
            ->assertJsonPath('code', 'UNAUTHENTICATED')
            ->assertJsonPath('errors', []);
    }

    public function test_it_returns_not_found_when_user_has_no_ongoing_journey(): void
    {
        $user = $this->activeUser('Maria Silva');
        Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-07-01 09:00:00',
            'DH_FIM' => '2026-07-01 10:00:00',
        ]);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses/options')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao existe jornada em andamento para este usuario.')
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND')
            ->assertJsonPath('errors', []);
    }

    public function test_it_rejects_invalid_type_filter(): void
    {
        $user = $this->activeUser('Maria Silva');
        $this->ongoingJourney($user);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses/options?type_id=999999')
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tipo de curso informado e invalido.')
            ->assertJsonPath('code', 'INVALID_COURSE_TYPE')
            ->assertJsonPath('errors.type_id.0', 'Tipo de curso informado e invalido.');
    }

    public function test_it_rejects_type_filter_for_a_non_academic_subcriterion(): void
    {
        $user = $this->activeUser('Maria Silva');
        $this->ongoingJourney($user);
        $criterion = Criterion::create([
            'NO_CRITERIO_PT' => 'Experiência Profissional',
            'NO_CRITERIO_EN' => 'Professional Experience',
        ]);
        $nonAcademicSubcriterion = Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => 'Tempo de Experiência Profissional',
            'NO_SUBCRITERIO_EN' => 'Years of Professional Experience',
        ]);

        $this->actingAs($user)
            ->getJson("/api/journeys/current/courses/options?type_id={$nonAcademicSubcriterion->PK_SUBCRITERIOS}")
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Tipo de curso informado e invalido.')
            ->assertJsonPath('code', 'INVALID_COURSE_TYPE')
            ->assertJsonPath('errors.type_id.0', 'Tipo de curso informado e invalido.');
    }

    public function test_it_returns_standard_error_when_course_search_fails(): void
    {
        $user = $this->activeUser('Maria Silva');
        $service = Mockery::mock(JourneyCourseOptionsService::class);
        $service->shouldReceive('listAvailableForCurrentJourney')->once()->andThrow(new Exception('Unexpected failure.'));
        $this->app->instance(JourneyCourseOptionsService::class, $service);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses/options')
            ->assertInternalServerError()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Nao foi possivel buscar os cursos disponiveis no momento.')
            ->assertJsonPath('code', 'COURSE_OPTIONS_SEARCH_FAILED')
            ->assertJsonPath('errors', []);
    }

    private function activeUser(string $name): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => 2,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function ongoingJourney(Usuario $user): Journey
    {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'DH_CRIADO_EM' => '2026-08-01 09:00:00',
            'DH_FIM' => null,
        ]);
    }

    private function subcriterio(string $namePt, string $nameEn): Subcriterion
    {
        $academicCriterion = Criterion::query()
            ->where('NO_CRITERIO_PT', 'Formação Acadêmica')
            ->firstOrFail();

        return Subcriterion::create([
            'FK_CRITERIOS' => $academicCriterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => $namePt,
            'NO_SUBCRITERIO_EN' => $nameEn,
        ]);
    }

    private function higherEducationCourse(string $namePt, string $nameEn, int $typeId): HigherEducationCourse
    {
        return HigherEducationCourse::create([
            'NO_CURSO_SUPERIOR_PT' => $namePt,
            'NO_CURSO_SUPERIOR_EN' => $nameEn,
            'FK_SUBCRITERIOS' => $typeId,
        ]);
    }
}
