<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const USER_EMAIL = 'user@jornada.local';
    private const DOMAIN_PT = 'Swagger - Privacidade e Seguranca';
    private const DOMAIN_EN = 'Swagger - Privacy and Security';
    private const PROFILE_PT = 'Perfil temporario para teste visual de jornadas no Swagger';
    private const PROFILE_EN = 'Temporary profile for visual journey testing in Swagger';
    private const EXPERIENCE_PT = '1 a 3 anos';
    private const EXPERIENCE_EN = '1 to 3 years';
    private const PROFILE_EXPERIENCE_PT = 'Intermediario';
    private const PROFILE_EXPERIENCE_EN = 'Intermediate';
    private const ENGLISH_PT = 'Intermediario';
    private const ENGLISH_EN = 'Intermediate';
    private const COURSE_PT = 'Seguranca da Informacao';
    private const COURSE_EN = 'Information Security';
    private const POSTGRADUATE_PT = 'Privacidade e Protecao de Dados';
    private const POSTGRADUATE_EN = 'Privacy and Data Protection';
    private const AREA_PT = 'Governanca';
    private const AREA_EN = 'Governance';
    private const DIFFICULTY_PT = 'Media';
    private const DIFFICULTY_EN = 'Medium';
    private const CERTIFICATION = 'Swagger Privacy Foundation';

    private const COMPETENCIES = [
        [
            'name_pt' => 'Fundamentos de LGPD',
            'name_en' => 'LGPD fundamentals',
            'description_pt' => 'Compreender conceitos essenciais de protecao de dados pessoais.',
            'description_en' => 'Understand essential personal data protection concepts.',
        ],
        [
            'name_pt' => 'Gestao de riscos de privacidade',
            'name_en' => 'Privacy risk management',
            'description_pt' => 'Identificar e priorizar riscos de privacidade em processos organizacionais.',
            'description_en' => 'Identify and prioritize privacy risks in organizational processes.',
        ],
    ];

    // TODO: Remover esta migration temporaria quando existirem endpoints oficiais
    // para criacao de jornadas e o Swagger puder ser testado criando dados pelo fluxo da aplicacao.
    public function up(): void
    {
        $user = DB::table('TB_USUARIO')
            ->where('DS_EMAIL', self::USER_EMAIL)
            ->first();

        if ($user === null) {
            return;
        }

        $domainId = $this->firstOrCreateId('TB_DOMINIO_CONHECIMENTO', 'PK_DOMINIO_CONHECIMENTO', [
            'NO_DOMINIO_CONHECIMENTO_PT' => self::DOMAIN_PT,
        ], [
            'NO_DOMINIO_CONHECIMENTO_EN' => self::DOMAIN_EN,
        ]);

        $profileId = $this->firstOrCreateId('TB_PERFIS_JORNADA', 'PK_PERFIS_JORNADA', [
            'DS_PERFIS_PT' => self::PROFILE_PT,
        ], [
            'FK_DOMINIO_CONHECIMENTO' => $domainId,
            'DS_PERFIS_EN' => self::PROFILE_EN,
        ]);

        $professionalExperienceId = $this->firstOrCreateId('TB_EXPERIENCIA_ATUACAO', 'PK_EXPERIENCIA_ATUACAO', [
            'DS_EXPERIENCIA_ATUACAO_PT' => self::EXPERIENCE_PT,
        ], [
            'DS_EXPERIENCIA_ATUACAO_EN' => self::EXPERIENCE_EN,
        ]);

        $profileExperienceId = $this->firstOrCreateId('TB_EXPERIENCIA_ATUACAO', 'PK_EXPERIENCIA_ATUACAO', [
            'DS_EXPERIENCIA_ATUACAO_PT' => self::PROFILE_EXPERIENCE_PT,
        ], [
            'DS_EXPERIENCIA_ATUACAO_EN' => self::PROFILE_EXPERIENCE_EN,
        ]);

        $englishProficiencyId = $this->firstOrCreateId('TB_PROFICIENCIA_INGLES', 'PK_PROFICIENCIA_INGLES', [
            'NO_PROFICIENCIA_INGLES_PT' => self::ENGLISH_PT,
        ], [
            'NO_PROFICIENCIA_INGLES_EN' => self::ENGLISH_EN,
        ]);

        $higherEducationCourseId = $this->firstOrCreateId('TB_CURSO_SUPERIOR', 'PK_CURSO_SUPERIOR', [
            'NO_CURSO_SUPERIOR_PT' => self::COURSE_PT,
        ], [
            'NO_CURSO_SUPERIOR_EN' => self::COURSE_EN,
            'TP_CURSO_SUPERIOR' => 1,
        ]);

        $postgraduateCourseId = $this->firstOrCreateId('TB_POS_GRADUACAO', 'PK_POS_GRADUACAO', [
            'NO_POS_GRADUACAO_PT' => self::POSTGRADUATE_PT,
        ], [
            'NO_POS_GRADUACAO_EN' => self::POSTGRADUATE_EN,
            'TP_POS_GRADUACAO' => 1,
        ]);

        $areaId = $this->firstOrCreateId('TB_AREA_CONHECIMENTO', 'PK_AREA_CONHECIMENTO', [
            'NO_AREA_CONHECIMENTO_PT' => self::AREA_PT,
        ], [
            'NO_AREA_CONHECIMENTO_EN' => self::AREA_EN,
        ]);

        $difficultyId = $this->firstOrCreateId('TB_DIFICULDADE', 'PK_DIFICULDADE', [
            'NO_DIFICULDADE_PT' => self::DIFFICULTY_PT,
        ], [
            'NO_DIFICULDADE_EN' => self::DIFFICULTY_EN,
        ]);

        $certificationId = $this->firstOrCreateId('TB_CERTIFICACAO_PROFISSIONAL', 'PK_CERTIFICACAO_PROFISSIONAL', [
            'NO_CERTIFICACAO_PROFISSIONAL' => self::CERTIFICATION,
        ], [
            'FK_DOMINIO_CONHECIMENTO' => $domainId,
        ]);

        $competencyIds = [];

        foreach (self::COMPETENCIES as $competency) {
            $competencyId = $this->firstOrCreateId('TB_COMPETENCIAS', 'PK_COMPETENCIAS', [
                'NO_COMPETENCIAS_PT' => $competency['name_pt'],
            ], [
                'FK_DOMINIO_CONHECIMENTO' => $domainId,
                'FK_AREA_CONHECIMENTO' => $areaId,
                'FK_DIFICULDADE' => $difficultyId,
                'NO_ORIGEM_COMPETENCIAS_PT' => 'Swagger',
                'NO_ORIGEM_COMPETENCIAS_EN' => 'Swagger',
                'NO_COMPETENCIAS_EN' => $competency['name_en'],
                'DS_COMPETENCIAS_PT' => $competency['description_pt'],
                'DS_COMPETENCIAS_EN' => $competency['description_en'],
            ]);

            $competencyIds[] = $competencyId;

            $this->firstOrCreateId('RL_PERFIS_COMPETENCIAS', 'PK_PERFIS_COMPETENCIAS', [
                'FK_PERFIS_JORNADA' => $profileId,
                'FK_COMPETENCIAS' => $competencyId,
            ]);
        }

        $completedJourneyId = $this->firstOrCreateId('TB_JORNADA', 'PK_JORNADA', [
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profileId,
            'DH_CRIADO_EM' => '2026-07-30 09:00:00',
        ], [
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperienceId,
            'FK_ATUACAO_PERFIL' => $profileExperienceId,
            'FK_PROFICIENCIA_INGLES' => $englishProficiencyId,
            'FK_CURSO_SUPERIOR' => $higherEducationCourseId,
            'FK_POS_GRADUACAO' => $postgraduateCourseId,
            'DH_FIM' => '2026-07-30 10:30:00',
        ]);

        $ongoingJourneyId = $this->firstOrCreateId('TB_JORNADA', 'PK_JORNADA', [
            'FK_USUARIO' => $user->PK_USUARIO,
            'FK_PERFIS_JORNADA' => $profileId,
            'DH_CRIADO_EM' => '2026-08-01 14:00:00',
        ], [
            'FK_EXPERIENCIA_PROFISSIONAL' => $professionalExperienceId,
            'FK_ATUACAO_PERFIL' => $profileExperienceId,
            'FK_PROFICIENCIA_INGLES' => $englishProficiencyId,
            'FK_CURSO_SUPERIOR' => $higherEducationCourseId,
            'FK_POS_GRADUACAO' => $postgraduateCourseId,
            'DH_FIM' => null,
        ]);

        foreach ([$completedJourneyId, $ongoingJourneyId] as $journeyId) {
            foreach ($competencyIds as $competencyId) {
                $this->firstOrCreateId('TB_CONHECIMENTOS_ADQUIRIDOS', 'PK_CONHECIMENTOS_ADQUIRIDOS', [
                    'FK_JORNADA' => $journeyId,
                    'FK_COMPETENCIAS' => $competencyId,
                ], [
                    'TP_ADQUIRIDO' => 1,
                ]);
            }
        }

        $this->firstOrCreateId('RL_CERTIFICACAO_JORNADA', 'PK_CERTIFICACAO_JORNADA', [
            'FK_JORNADA' => $completedJourneyId,
            'FK_CERTIFICACAO_PROFISSIONAL' => $certificationId,
        ]);
    }

    public function down(): void
    {
        $user = DB::table('TB_USUARIO')
            ->where('DS_EMAIL', self::USER_EMAIL)
            ->first();
        $profileIds = DB::table('TB_PERFIS_JORNADA')
            ->where('DS_PERFIS_PT', self::PROFILE_PT)
            ->pluck('PK_PERFIS_JORNADA')
            ->all();

        if ($user !== null && $profileIds !== []) {
            $journeyIds = DB::table('TB_JORNADA')
                ->where('FK_USUARIO', $user->PK_USUARIO)
                ->whereIn('FK_PERFIS_JORNADA', $profileIds)
                ->pluck('PK_JORNADA')
                ->all();

            DB::table('RL_CERTIFICACAO_JORNADA')->whereIn('FK_JORNADA', $journeyIds)->delete();
            DB::table('TB_CONHECIMENTOS_ADQUIRIDOS')->whereIn('FK_JORNADA', $journeyIds)->delete();
            DB::table('TB_JORNADA')->whereIn('PK_JORNADA', $journeyIds)->delete();
        }

        $competencyIds = DB::table('TB_COMPETENCIAS')
            ->whereIn('NO_COMPETENCIAS_PT', array_column(self::COMPETENCIES, 'name_pt'))
            ->pluck('PK_COMPETENCIAS')
            ->all();

        DB::table('RL_PERFIS_COMPETENCIAS')->whereIn('FK_COMPETENCIAS', $competencyIds)->delete();
        DB::table('TB_COMPETENCIAS')->whereIn('PK_COMPETENCIAS', $competencyIds)->delete();
        DB::table('TB_CERTIFICACAO_PROFISSIONAL')->where('NO_CERTIFICACAO_PROFISSIONAL', self::CERTIFICATION)->delete();
        DB::table('TB_PERFIS_JORNADA')->where('DS_PERFIS_PT', self::PROFILE_PT)->delete();
        DB::table('TB_EXPERIENCIA_ATUACAO')->whereIn('DS_EXPERIENCIA_ATUACAO_PT', [self::EXPERIENCE_PT, self::PROFILE_EXPERIENCE_PT])->delete();
        DB::table('TB_PROFICIENCIA_INGLES')->where('NO_PROFICIENCIA_INGLES_PT', self::ENGLISH_PT)->delete();
        DB::table('TB_CURSO_SUPERIOR')->where('NO_CURSO_SUPERIOR_PT', self::COURSE_PT)->delete();
        DB::table('TB_POS_GRADUACAO')->where('NO_POS_GRADUACAO_PT', self::POSTGRADUATE_PT)->delete();
        DB::table('TB_AREA_CONHECIMENTO')->where('NO_AREA_CONHECIMENTO_PT', self::AREA_PT)->delete();
        DB::table('TB_DIFICULDADE')->where('NO_DIFICULDADE_PT', self::DIFFICULTY_PT)->delete();
        DB::table('TB_DOMINIO_CONHECIMENTO')->where('NO_DOMINIO_CONHECIMENTO_PT', self::DOMAIN_PT)->delete();
    }

    private function firstOrCreateId(string $table, string $primaryKey, array $attributes, array $values = []): int
    {
        $existing = DB::table($table)->where($attributes)->first();

        if ($existing !== null) {
            return (int) $existing->{$primaryKey};
        }

        return (int) DB::table($table)->insertGetId(array_merge($attributes, $values), $primaryKey);
    }
};
