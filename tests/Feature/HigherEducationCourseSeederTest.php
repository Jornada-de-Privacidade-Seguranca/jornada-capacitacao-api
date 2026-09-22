<?php

namespace Tests\Feature;

use App\Models\HigherEducationCourse;
use App\Models\Subcriterion;
use Database\Seeders\HigherEducationCourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HigherEducationCourseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_academic_course_catalog(): void
    {
        $bachelor = $this->subcriterion('Graduação');
        $postgraduate = $this->subcriterion('Pós-Graduação');
        $masters = $this->subcriterion('Mestrado');
        $doctorate = $this->subcriterion('Doutorado');

        $this->seed(HigherEducationCourseSeeder::class);

        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Direito',
            'NO_CURSO_SUPERIOR_EN' => 'Law',
            'FK_SUBCRITERIOS' => $bachelor->PK_SUBCRITERIOS,
        ]);
        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Privacidade e Proteção de Dados',
            'NO_CURSO_SUPERIOR_EN' => 'Privacy and Data Protection',
            'FK_SUBCRITERIOS' => $postgraduate->PK_SUBCRITERIOS,
        ]);
        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Direito Digital',
            'NO_CURSO_SUPERIOR_EN' => 'Digital Law',
            'FK_SUBCRITERIOS' => $masters->PK_SUBCRITERIOS,
        ]);
        $this->assertDatabaseHas('TB_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => 'Segurança da Informação',
            'NO_CURSO_SUPERIOR_EN' => 'Information Security',
            'FK_SUBCRITERIOS' => $doctorate->PK_SUBCRITERIOS,
        ]);
        $this->assertSame(7, HigherEducationCourse::query()->count());
    }

    public function test_it_does_not_duplicate_courses_when_run_more_than_once(): void
    {
        $this->seed(HigherEducationCourseSeeder::class);
        $this->seed(HigherEducationCourseSeeder::class);

        $this->assertSame(7, HigherEducationCourse::query()->count());
    }

    public function test_default_database_seeder_populates_the_academic_course_catalog(): void
    {
        $this->seed();

        $this->assertSame(7, HigherEducationCourse::query()->count());
    }

    private function subcriterion(string $namePt): Subcriterion
    {
        return Subcriterion::query()
            ->where('NO_SUBCRITERIO_PT', $namePt)
            ->firstOrFail();
    }
}
