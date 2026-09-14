<?php

namespace Database\Seeders;

use App\Models\HigherEducationCourse;
use App\Models\Subcriterion;
use Illuminate\Database\Seeder;
use RuntimeException;

class HigherEducationCourseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->courses() as $course) {
            $subcriterion = $this->subcriterion($course['subcriterion']);

            HigherEducationCourse::query()->updateOrCreate(
                [
                    'NO_CURSO_SUPERIOR_PT' => $course['name_pt'],
                    'FK_SUBCRITERIOS' => $subcriterion->PK_SUBCRITERIOS,
                ],
                ['NO_CURSO_SUPERIOR_EN' => $course['name_en']],
            );
        }
    }

    /**
     * @return array<int, array{name_pt: string, name_en: string, subcriterion: string}>
     */
    private function courses(): array
    {
        return [
            ['name_pt' => 'Direito', 'name_en' => 'Law', 'subcriterion' => 'Graduação'],
            ['name_pt' => 'Ciência da Computação', 'name_en' => 'Computer Science', 'subcriterion' => 'Graduação'],
            ['name_pt' => 'Segurança da Informação', 'name_en' => 'Information Security', 'subcriterion' => 'Pós-Graduação'],
            ['name_pt' => 'Privacidade e Proteção de Dados', 'name_en' => 'Privacy and Data Protection', 'subcriterion' => 'Pós-Graduação'],
            ['name_pt' => 'Governança de Privacidade', 'name_en' => 'Privacy Governance', 'subcriterion' => 'Pós-Graduação'],
            ['name_pt' => 'Direito Digital', 'name_en' => 'Digital Law', 'subcriterion' => 'Mestrado'],
            ['name_pt' => 'Segurança da Informação', 'name_en' => 'Information Security', 'subcriterion' => 'Doutorado'],
        ];
    }

    private function subcriterion(string $namePt): Subcriterion
    {
        $subcriterion = Subcriterion::query()
            ->where('NO_SUBCRITERIO_PT', $namePt)
            ->first();

        if ($subcriterion === null) {
            throw new RuntimeException("Subcriterio {$namePt} nao encontrado.");
        }

        return $subcriterion;
    }
}
