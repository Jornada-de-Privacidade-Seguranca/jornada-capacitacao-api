<?php

namespace Database\Seeders;

use App\Models\AcquiredKnowledge;
use App\Models\Competency;
use App\Models\Difficulty;
use App\Models\EnglishProficiency;
use App\Models\HigherEducationCourse;
use App\Models\Journey;
use App\Models\JourneyProfile;
use App\Models\KnowledgeArea;
use App\Models\KnowledgeDomain;
use App\Models\PostgraduateCourse;
use App\Models\ProfessionalCertification;
use App\Models\ProfessionalExperience;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use RuntimeException;

class ManualSwaggerDashboardSeeder extends Seeder
{
    // TO-DO: Soon as we have routes to create a journey, we should remove this seeder and create the journey through the API.
    private const USER_EMAIL = 'user@jornada.local';

    public function run(): void
    {
        $user = Usuario::query()
            ->where('DS_EMAIL', self::USER_EMAIL)
            ->first();

        if ($user === null) {
            throw new RuntimeException('Usuario user@jornada.local nao encontrado.');
        }

        $journeyProfile = JourneyProfile::query()
            ->where('NO_PERFIS_PT', 'Encarregado pelo Tratamento de Dados Pessoais')
            ->first();

        if ($journeyProfile === null) {
            throw new RuntimeException('Perfil fixo de jornada nao encontrado.');
        }

        $professionalExperience = $this->experience('Entre 4 e 6 anos');
        $profileExperience = $this->experience('Entre 1 e 3 anos');
        $englishProficiency = $this->englishProficiency('Intermediário - Leitura e Escrita');
        $higherEducationCourse = $this->higherEducationCourse();
        $postgraduateCourse = $this->postgraduateCourse();

        $completedJourney = Journey::query()->updateOrCreate(
            [
                'FK_USUARIO' => $user->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
                'DH_CRIADO_EM' => '2026-07-23 09:00:00',
            ],
            [
                'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperience->PK_EXPERIENCIA_ATUACAO,
                'FK_ATUACAO_PERFIL' => $profileExperience->PK_EXPERIENCIA_ATUACAO,
                'FK_PROFICIENCIA_INGLES' => $englishProficiency->PK_PROFICIENCIA_INGLES,
                'FK_CURSO_SUPERIOR' => $higherEducationCourse->PK_CURSO_SUPERIOR,
                'FK_POS_GRADUACAO' => $postgraduateCourse->PK_POS_GRADUACAO,
                'DH_FIM' => '2026-07-23 10:00:00',
            ],
        );

        $this->seedCompetencies($journeyProfile, $completedJourney);
        $this->seedCertification($completedJourney);

        Journey::query()->firstOrCreate(
            [
                'FK_USUARIO' => $user->PK_USUARIO,
                'FK_PERFIS_JORNADA' => $journeyProfile->PK_PERFIS_JORNADA,
                'DH_CRIADO_EM' => '2026-07-24 09:00:00',
            ],
            ['DH_FIM' => null],
        );
    }

    private function seedCompetencies(JourneyProfile $journeyProfile, Journey $journey): void
    {
        $privacyDomain = KnowledgeDomain::query()
            ->where('NO_DOMINIO_CONHECIMENTO_PT', 'Privacidade')
            ->first();

        if ($privacyDomain === null) {
            throw new RuntimeException('Dominio de conhecimento Privacidade nao encontrado.');
        }

        $knowledgeArea = KnowledgeArea::query()->firstOrCreate(
            ['NO_AREA_CONHECIMENTO_PT' => 'Gestão de Privacidade'],
            ['NO_AREA_CONHECIMENTO_EN' => 'Privacy Management'],
        );
        $difficulty = Difficulty::query()->firstOrCreate(
            ['NO_DIFICULDADE_PT' => 'Básico'],
            ['NO_DIFICULDADE_EN' => 'Basic'],
        );

        $competencies = [
            [
                'name_pt' => 'Fundamentos da LGPD',
                'name_en' => 'LGPD fundamentals',
                'description_pt' => 'Conhece os conceitos fundamentais da Lei Geral de Protecao de Dados.',
                'description_en' => 'Understands the fundamentals of Brazilian data protection law.',
                'acquired' => 1,
            ],
            [
                'name_pt' => 'Atendimento a titulares de dados',
                'name_en' => 'Data subject request handling',
                'description_pt' => 'Conhece os fluxos de atendimento a solicitacoes de titulares.',
                'description_en' => 'Understands workflows for data subject requests.',
                'acquired' => 0,
            ],
            [
                'name_pt' => 'Programa de governanca em privacidade',
                'name_en' => 'Privacy governance program',
                'description_pt' => 'Conhece praticas de governanca aplicadas a privacidade.',
                'description_en' => 'Understands governance practices applied to privacy.',
                'acquired' => 1,
            ],
        ];

        foreach ($competencies as $competencyData) {
            $competency = Competency::query()->updateOrCreate(
                ['NO_COMPETENCIAS_PT' => $competencyData['name_pt']],
                [
                    'FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO,
                    'FK_AREA_CONHECIMENTO' => $knowledgeArea->PK_AREA_CONHECIMENTO,
                    'FK_DIFICULDADE' => $difficulty->PK_DIFICULDADE,
                    'NO_ORIGEM_COMPETENCIAS_PT' => 'Swagger',
                    'NO_ORIGEM_COMPETENCIAS_EN' => 'Swagger',
                    'NO_COMPETENCIAS_EN' => $competencyData['name_en'],
                    'DS_COMPETENCIAS_PT' => $competencyData['description_pt'],
                    'DS_COMPETENCIAS_EN' => $competencyData['description_en'],
                ],
            );

            $journeyProfile->competencies()->syncWithoutDetaching([$competency->PK_COMPETENCIAS]);

            AcquiredKnowledge::query()->updateOrCreate(
                [
                    'FK_JORNADA' => $journey->PK_JORNADA,
                    'FK_COMPETENCIAS' => $competency->PK_COMPETENCIAS,
                ],
                ['TP_ADQUIRIDO' => $competencyData['acquired']],
            );
        }
    }

    private function seedCertification(Journey $journey): void
    {
        $privacyDomain = KnowledgeDomain::query()
            ->where('NO_DOMINIO_CONHECIMENTO_PT', 'Privacidade')
            ->first();

        if ($privacyDomain === null) {
            throw new RuntimeException('Dominio de conhecimento Privacidade nao encontrado.');
        }

        $certification = ProfessionalCertification::query()->updateOrCreate(
            ['NO_CERTIFICACAO_PROFISSIONAL' => 'Certificacao Swagger de Privacidade'],
            ['FK_DOMINIO_CONHECIMENTO' => $privacyDomain->PK_DOMINIO_CONHECIMENTO],
        );

        $journey->professionalCertifications()->syncWithoutDetaching([
            $certification->PK_CERTIFICACAO_PROFISSIONAL,
        ]);
    }

    private function experience(string $description): ProfessionalExperience
    {
        $experience = ProfessionalExperience::query()
            ->where('DS_EXPERIENCIA_ATUACAO_PT', $description)
            ->first();

        if ($experience === null) {
            throw new RuntimeException("Experiencia {$description} nao encontrada.");
        }

        return $experience;
    }

    private function englishProficiency(string $name): EnglishProficiency
    {
        $englishProficiency = EnglishProficiency::query()
            ->where('NO_PROFICIENCIA_INGLES_PT', $name)
            ->first();

        if ($englishProficiency === null) {
            throw new RuntimeException("Proficiencia {$name} nao encontrada.");
        }

        return $englishProficiency;
    }

    private function higherEducationCourse(): HigherEducationCourse
    {
        return HigherEducationCourse::query()->firstOrCreate(
            ['NO_CURSO_SUPERIOR_PT' => 'Direito'],
            [
                'NO_CURSO_SUPERIOR_EN' => 'Law',
                'TP_CURSO_SUPERIOR' => 1,
            ],
        );
    }

    private function postgraduateCourse(): PostgraduateCourse
    {
        return PostgraduateCourse::query()->firstOrCreate(
            ['NO_POS_GRADUACAO_PT' => 'Privacidade e Protecao de Dados'],
            [
                'NO_POS_GRADUACAO_EN' => 'Privacy and Data Protection',
                'TP_POS_GRADUACAO' => 1,
            ],
        );
    }
}
