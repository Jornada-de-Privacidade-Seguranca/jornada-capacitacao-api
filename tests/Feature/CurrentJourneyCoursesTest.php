<?php

namespace Tests\Feature;

use App\Models\HigherEducationCourse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class CurrentJourneyCoursesTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_get_returns_an_empty_list_when_there_is_no_selection(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cursos selecionados encontrados com sucesso.')
            ->assertJsonCount(0, 'data');
    }

    public function test_get_returns_selected_courses_without_affinity_weight_or_score(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');
        $journey->higherEducationCourses()->attach($course->PK_CURSO_SUPERIOR);

        $response = $this->actingAs($user)
            ->getJson('/api/journeys/current/courses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $course->PK_CURSO_SUPERIOR)
            ->assertJsonPath('data.0.name_pt', 'Ciencia da Computacao')
            ->assertJsonPath('data.0.type.name_pt', 'Graduacao');

        $returnedCourse = $response->json('data.0');

        foreach (['affinity', 'afinidade', 'weight', 'peso', 'score', 'pontuacao'] as $field) {
            $this->assertArrayNotHasKey($field, $returnedCourse);
        }
    }

    public function test_put_replaces_the_previous_selection(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');
        $previousCourse = $this->higherEducationCourse('Graduacao', 'Direito');
        $newCourse = $this->higherEducationCourse('Pos-Graduacao', 'Seguranca da Informacao');
        $journey->higherEducationCourses()->attach($previousCourse->PK_CURSO_SUPERIOR);

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', [
                'course_ids' => [$newCourse->PK_CURSO_SUPERIOR],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Cursos selecionados atualizados com sucesso.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $newCourse->PK_CURSO_SUPERIOR);

        $this->assertDatabaseMissing('RL_CURSOS_SUPERIORES_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CURSO_SUPERIOR' => $previousCourse->PK_CURSO_SUPERIOR,
        ]);
        $this->assertDatabaseHas('RL_CURSOS_SUPERIORES_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CURSO_SUPERIOR' => $newCourse->PK_CURSO_SUPERIOR,
        ]);
    }

    public function test_put_accepts_an_empty_list_and_clears_the_selection(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Direito');
        $journey->higherEducationCourses()->attach($course->PK_CURSO_SUPERIOR);

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', ['course_ids' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseCount('RL_CURSOS_SUPERIORES_JORNADA', 0);
    }

    public function test_put_removes_duplicate_ids_before_synchronizing(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Direito');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', [
                'course_ids' => [$course->PK_CURSO_SUPERIOR, $course->PK_CURSO_SUPERIOR],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame(1, DB::table('RL_CURSOS_SUPERIORES_JORNADA')
            ->where('FK_JORNADA', $journey->PK_JORNADA)
            ->where('FK_CURSO_SUPERIOR', $course->PK_CURSO_SUPERIOR)
            ->count());
    }

    public function test_put_keeps_distinct_courses_of_the_same_type_without_duplicate_links(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $journey = $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');
        $type = $this->subcriterion('Graduacao', 'Formacao Academica');
        $firstCourse = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $type->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
        ]);
        $secondCourse = HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $type->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => 'Administracao',
        ]);

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', [
                'course_ids' => [
                    $firstCourse->PK_CURSO_SUPERIOR,
                    $secondCourse->PK_CURSO_SUPERIOR,
                    $firstCourse->PK_CURSO_SUPERIOR,
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(2, DB::table('RL_CURSOS_SUPERIORES_JORNADA')
            ->where('FK_JORNADA', $journey->PK_JORNADA)
            ->count());
    }

    public function test_put_rejects_an_unknown_course(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');

        $response = $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', ['course_ids' => [999999]])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertSame('O curso informado nao existe.', $response->json('errors')['course_ids.0'][0]);
    }

    public function test_put_rejects_a_nested_course_id_as_invalid_input(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', ['course_ids' => [[1]]])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_endpoints_return_not_found_without_an_ongoing_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);

        $this->actingAs($user)
            ->getJson('/api/journeys/current/courses')
            ->assertNotFound()
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');

        $this->actingAs($user)->withCsrfToken()
            ->putJson('/api/journeys/current/courses', ['course_ids' => []])
            ->assertNotFound()
            ->assertJsonPath('code', 'ONGOING_JOURNEY_NOT_FOUND');
    }

    public function test_put_requires_csrf_token(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $this->journey($user, $this->journeyProfile('Privacidade'), '2026-09-29 09:00:00');

        $this->actingAs($user)
            ->putJson('/api/journeys/current/courses', ['course_ids' => []])
            ->assertStatus(419)
            ->assertJsonPath('code', 'CSRF_TOKEN_MISMATCH');
    }
}
