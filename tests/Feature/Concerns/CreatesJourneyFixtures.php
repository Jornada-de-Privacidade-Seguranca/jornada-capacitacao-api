<?php

namespace Tests\Feature\Concerns;

use App\Models\Competency;
use App\Models\Criterion;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeDomain;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\ProfileCompetency;
use App\Models\Subcriterion;
use App\Models\Usuario;

trait CreatesJourneyFixtures
{
    private function activeUser(string $name, int $userType): Usuario
    {
        return Usuario::create([
            'FK_TIPO_USUARIO' => $userType,
            'NO_USUARIO' => $name,
            'DS_EMAIL' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'ST_ATIVO' => 1,
        ]);
    }

    private function journeyProfile(string $description): JourneyProfile
    {
        $knowledgeDomain = KnowledgeDomain::create([
            'NO_DOMINIO_CONHECIMENTO_PT' => $description,
        ]);

        return JourneyProfile::create([
            'FK_DOMINIO_CONHECIMENTO' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'NO_PERFIS_PT' => $description,
            'DS_PERFIS_PT' => $description,
        ]);
    }

    private function journey(Usuario $user, JourneyProfile $profile, string $startedAt, ?string $finishedAt = null): Journey
    {
        return Journey::create([
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
            'DH_CRIADO_EM' => $startedAt,
            'DH_FIM' => $finishedAt,
        ]);
    }

    private function professionalExperience(string $description = 'Junior'): ProfessionalExperience
    {
        return ProfessionalExperience::create(['DS_EXPERIENCIA_ATUACAO_PT' => $description]);
    }

    private function subcriterion(string $description, string $criterionNamePt = 'Nível de Proficiência - Inglês'): Subcriterion
    {
        $criterion = Criterion::create(['NO_CRITERIO_PT' => $criterionNamePt]);

        return Subcriterion::create([
            'FK_CRITERIOS' => $criterion->PK_CRITERIOS,
            'NO_SUBCRITERIO_PT' => $description,
        ]);
    }

    private function higherEducationCourse(string $subcriterionDescription, string $courseName): HigherEducationCourse
    {
        $subcriterion = $this->subcriterion($subcriterionDescription, 'Formacao Academica');

        return HigherEducationCourse::create([
            'FK_SUBCRITERIOS' => $subcriterion->PK_SUBCRITERIOS,
            'NO_CURSO_SUPERIOR_PT' => $courseName,
        ]);
    }

    private function certification(string $name = 'Certificacao Teste'): ProfessionalCertification
    {
        return ProfessionalCertification::create(['NO_CERTIFICACAO_PROFISSIONAL' => $name]);
    }

    /**
     * Cria uma competencia. Quando um perfil e informado, vincula a competencia a ele
     * em RL_PERFIS_COMPETENCIAS, exigido por Rule::exists em RecordJourneyCompetenciesRequest.
     */
    private function competency(string $namePt = 'Competencia Teste', ?JourneyProfile $profile = null): Competency
    {
        $competency = Competency::create(['NO_COMPETENCIAS_PT' => $namePt]);

        if ($profile !== null) {
            ProfileCompetency::create([
                'FK_PERFIS_JORNADA' => $profile->PK_PERFIS_JORNADA,
                'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
            ]);
        }

        return $competency;
    }
}
