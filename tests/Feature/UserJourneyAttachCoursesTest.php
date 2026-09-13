<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\CreatesJourneyFixtures;
use Tests\TestCase;

class UserJourneyAttachCoursesTest extends TestCase
{
    use CreatesJourneyFixtures;
    use RefreshDatabase;

    public function test_owner_attaches_courses_to_their_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $graduationCourse = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');
        $postgraduateCourse = $this->higherEducationCourse('Pos-Graduacao', 'MBA em Seguranca da Informacao');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", [
                'course_ids' => [$graduationCourse->PK_CURSO_SUPERIOR, $postgraduateCourse->PK_CURSO_SUPERIOR],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cursos vinculados a jornada com sucesso.')
            ->assertJsonCount(2, 'data.answers.higher_education_courses');

        $this->assertDatabaseHas('RL_CURSOS_SUPERIORES_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CURSO_SUPERIOR' => $graduationCourse->PK_CURSO_SUPERIOR,
        ]);

        $this->assertDatabaseHas('RL_CURSOS_SUPERIORES_JORNADA', [
            'FK_JORNADA' => $journey->PK_JORNADA,
            'FK_CURSO_SUPERIOR' => $postgraduateCourse->PK_CURSO_SUPERIOR,
        ]);
    }

    public function test_it_ignores_a_course_already_attached_to_the_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => [$course->PK_CURSO_SUPERIOR]])
            ->assertOk();

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => [$course->PK_CURSO_SUPERIOR]])
            ->assertOk()
            ->assertJsonCount(1, 'data.answers.higher_education_courses');

        $this->assertSame(1, DB::table('RL_CURSOS_SUPERIORES_JORNADA')
            ->where('FK_JORNADA', $journey->PK_JORNADA)
            ->where('FK_CURSO_SUPERIOR', $course->PK_CURSO_SUPERIOR)
            ->count());
    }

    public function test_common_user_cannot_attach_courses_to_another_users_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $otherUser = $this->activeUser('Joao Souza', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($otherUser, $profile, '2026-08-01 09:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => [$course->PK_CURSO_SUPERIOR]])
            ->assertForbidden()
            ->assertJsonPath('code', 'JOURNEY_FORBIDDEN');
    }

    public function test_it_returns_not_found_for_unknown_journey(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $course = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');

        $this->actingAs($user)->withCsrfToken()
            ->postJson('/api/journeys/999999/courses', ['course_ids' => [$course->PK_CURSO_SUPERIOR]])
            ->assertNotFound()
            ->assertJsonPath('code', 'JOURNEY_NOT_FOUND');
    }

    public function test_it_rejects_attach_when_journey_is_already_finished(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00', '2026-08-01 10:00:00');
        $course = $this->higherEducationCourse('Graduacao', 'Ciencia da Computacao');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => [$course->PK_CURSO_SUPERIOR]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'JOURNEY_ALREADY_FINISHED');
    }

    public function test_it_rejects_empty_course_list(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => []])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonPath('errors.course_ids.0', 'Informe ao menos um curso.');
    }

    public function test_it_rejects_unknown_course_id(): void
    {
        $user = $this->activeUser('Maria Silva', 2);
        $profile = $this->journeyProfile('Privacidade');
        $journey = $this->journey($user, $profile, '2026-08-01 09:00:00');

        $response = $this->actingAs($user)->withCsrfToken()
            ->postJson("/api/journeys/{$journey->PK_JORNADA}/courses", ['course_ids' => [999999]])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertSame('O curso informado nao existe.', $response->json('errors')['course_ids.0'][0]);
    }

    public function test_it_rejects_unauthenticated_user(): void
    {
        $this->withCsrfToken()
            ->postJson('/api/journeys/1/courses', ['course_ids' => [1]])
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
