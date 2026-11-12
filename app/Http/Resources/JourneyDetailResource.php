<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsAssignedBy;
use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class JourneyDetailResource extends JsonResource
{
    use FormatsAssignedBy;

    public function toArray(Request $request): array
    {
        $isCompleted = $this->DH_FIM !== null;
        $isCancelled = $this->DH_CANCELADA_EM !== null;

        return [
            'id' => $this->PK_JORNADA,
            'user' => $this->user(),
            'journey_profile' => $this->journeyProfile(),
            'started_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
            'finished_at' => $this->DH_FIM?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->DH_CANCELADA_EM?->format('Y-m-d H:i:s'),
            'origin' => $this->origin,
            'assigned_by' => $this->assignedBySummary(),
            'status' => $isCancelled ? 'cancelled' : ($isCompleted ? 'completed' : 'ongoing'),
            'is_completed' => $isCompleted,
            'is_cancelled' => $isCancelled,
            'score' => $this->NU_PONTUACAO_TOTAL,
            'score_breakdown' => $isCompleted ? $this->JS_SCORE_BREAKDOWN : null,
            'answers' => [
                'professional_experience' => $this->professionalExperience(),
                'profile_experience' => $this->profileExperience(),
                'english_proficiency' => $this->englishProficiency(),
                'higher_education_courses' => $this->higherEducationCourses(),
            ],
            'acquired_knowledges' => $this->acquiredKnowledges
                ->map(fn ($acquiredKnowledge): array => [
                    'id' => $acquiredKnowledge->PK_CONHECIMENTOS_ADQUIRIDOS,
                    'type_acquired' => $acquiredKnowledge->TP_ADQUIRIDO,
                    'is_acquired' => (int) $acquiredKnowledge->TP_ADQUIRIDO === 1,
                    'competency' => $this->competency($acquiredKnowledge->competency),
                ])
                ->values()
                ->all(),
            'knowledges_by_domain' => $this->knowledgesByDomain(),
            'professional_certifications' => $this->professionalCertifications
                ->map(fn ($certification): array => [
                    'id' => $certification->PK_CERTIFICACAO_PROFISSIONAL,
                    'name' => $certification->NO_CERTIFICACAO_PROFISSIONAL,
                    'pivot_id' => $certification->pivot?->PK_CERTIFICACAO_JORNADA,
                    'knowledge_domain' => $this->knowledgeDomain($certification->knowledgeDomain),
                ])
                ->values()
                ->all(),
            'summary' => [
                'acquired_knowledges_count' => $this->acquiredKnowledges->count(),
                'professional_certifications_count' => $this->professionalCertifications->count(),
            ],
        ];
    }

    private function user(): array
    {
        return [
            'id' => $this->usuario?->PK_USUARIO,
            'name' => $this->usuario?->NO_USUARIO,
            'email' => $this->usuario?->DS_EMAIL,
            'organization' => $this->usuario?->NO_ORGAO_EMPRESA,
            'sector' => $this->usuario?->NO_SETOR,
            'registration' => $this->usuario?->NU_MATRICULA,
            'profile_type' => [
                'id' => $this->usuario?->profileType?->PK_TIPO_PERFIL,
                'name' => $this->usuario?->profileType?->NO_TIPO_PERFIL,
            ],
            'user_type_id' => $this->usuario?->FK_TIPO_USUARIO,
            'is_active' => (int) $this->usuario?->ST_ATIVO === 1,
        ];
    }

    private function journeyProfile(): array
    {
        return [
            'id' => $this->journeyProfile?->PK_PERFIS_JORNADA,
            'name_pt' => $this->journeyProfile?->NO_PERFIS_PT,
            'name_en' => $this->journeyProfile?->NO_PERFIS_EN,
            'description_pt' => $this->journeyProfile?->DS_PERFIS_PT,
            'description_en' => $this->journeyProfile?->DS_PERFIS_EN,
            'knowledge_domain' => $this->knowledgeDomain($this->journeyProfile?->knowledgeDomain),
            'competencies' => $this->journeyProfile?->competencies
                ?->map(fn ($competency): array => $this->competency($competency))
                ->values()
                ->all() ?? [],
        ];
    }

    private function knowledgesByDomain(): array
    {
        $acquiredKnowledgesByCompetency = $this->acquiredKnowledges
            ->keyBy('FK_COMPETENCIAS');

        $competencies = ($this->journeyProfile?->competencies ?? collect())
            ->merge($this->acquiredKnowledges->pluck('competency')->filter())
            ->keyBy('PK_COMPETENCIAS')
            ->sortKeys();

        return $competencies
            ->groupBy(fn ($competency): string => (string) ($competency->FK_DOMINIO_CONHECIMENTO ?? 'null'))
            ->map(function ($competencies) use ($acquiredKnowledgesByCompetency): array {
                $domain = $competencies->first()?->knowledgeDomain;
                $acquired = [];
                $notAcquired = [];

                foreach ($competencies as $competency) {
                    $answer = $acquiredKnowledgesByCompetency->get($competency->PK_COMPETENCIAS);
                    $item = [
                        'id' => $answer?->PK_CONHECIMENTOS_ADQUIRIDOS,
                        'type_acquired' => $answer?->TP_ADQUIRIDO,
                        'is_acquired' => (int) $answer?->TP_ADQUIRIDO === 1,
                        'competency' => $this->competency($competency),
                    ];

                    if ($item['is_acquired']) {
                        $acquired[] = $item;
                    } else {
                        $notAcquired[] = $item;
                    }
                }

                return [
                    'knowledge_domain' => $this->knowledgeDomain($domain),
                    'acquired_knowledges' => $acquired,
                    'not_acquired_knowledges' => $notAcquired,
                ];
            })
            ->sortBy(fn (array $group): int => $group['knowledge_domain']['id'] ?? PHP_INT_MAX)
            ->values()
            ->all();
    }

    private function professionalExperience(): ?array
    {
        if ($this->professionalExperience === null) {
            return null;
        }

        return [
            'id' => $this->professionalExperience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $this->professionalExperience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $this->professionalExperience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }

    private function profileExperience(): ?array
    {
        if ($this->profileExperience === null) {
            return null;
        }

        return [
            'id' => $this->profileExperience->PK_EXPERIENCIA_ATUACAO,
            'description_pt' => $this->profileExperience->DS_EXPERIENCIA_ATUACAO_PT,
            'description_en' => $this->profileExperience->DS_EXPERIENCIA_ATUACAO_EN,
        ];
    }

    private function englishProficiency(): ?array
    {
        if ($this->englishSubcriterion === null) {
            return null;
        }

        return [
            'id' => $this->englishSubcriterion->PK_SUBCRITERIOS,
            'name_pt' => $this->englishSubcriterion->NO_SUBCRITERIO_PT,
            'name_en' => $this->englishSubcriterion->NO_SUBCRITERIO_EN,
        ];
    }

    private function higherEducationCourses(): array
    {
        return $this->higherEducationCourses
            ->map(fn ($course): array => [
                'id' => $course->PK_CURSO_SUPERIOR,
                'name_pt' => $course->NO_CURSO_SUPERIOR_PT,
                'name_en' => $course->NO_CURSO_SUPERIOR_EN,
                'subcriterion' => $this->subcriterion($course->subcriterion),
            ])
            ->values()
            ->all();
    }

    private function competency(mixed $competency): ?array
    {
        if ($competency === null) {
            return null;
        }

        return [
            'id' => $competency->PK_COMPETENCIAS,
            'origin_pt' => $competency->NO_ORIGEM_COMPETENCIAS_PT,
            'origin_en' => $competency->NO_ORIGEM_COMPETENCIAS_EN,
            'name_pt' => $competency->NO_COMPETENCIAS_PT,
            'name_en' => $competency->NO_COMPETENCIAS_EN,
            'description_pt' => $competency->DS_COMPETENCIAS_PT,
            'description_en' => $competency->DS_COMPETENCIAS_EN,
            'knowledge_domain' => $this->knowledgeDomain($competency->knowledgeDomain),
            'knowledge_area' => $this->knowledgeArea($competency->knowledgeArea),
            'difficulty' => $this->difficulty($competency->difficulty),
        ];
    }

    private function knowledgeDomain(mixed $knowledgeDomain): ?array
    {
        if ($knowledgeDomain === null) {
            return null;
        }

        return [
            'id' => $knowledgeDomain->PK_DOMINIO_CONHECIMENTO,
            'name_pt' => $knowledgeDomain->NO_DOMINIO_CONHECIMENTO_PT,
            'name_en' => $knowledgeDomain->NO_DOMINIO_CONHECIMENTO_EN,
        ];
    }

    private function subcriterion(mixed $subcriterion): ?array
    {
        if ($subcriterion === null) {
            return null;
        }

        return [
            'id' => $subcriterion->PK_SUBCRITERIOS,
            'name_pt' => $subcriterion->NO_SUBCRITERIO_PT,
            'name_en' => $subcriterion->NO_SUBCRITERIO_EN,
        ];
    }

    private function knowledgeArea(mixed $knowledgeArea): ?array
    {
        if ($knowledgeArea === null) {
            return null;
        }

        return [
            'id' => $knowledgeArea->PK_AREA_CONHECIMENTO,
            'name_pt' => $knowledgeArea->NO_AREA_CONHECIMENTO_PT,
            'name_en' => $knowledgeArea->NO_AREA_CONHECIMENTO_EN,
            // Area sem descricao vem nula, para o front mostrar o texto provisorio
            'description_pt' => $knowledgeArea->DS_AREA_CONHECIMENTO_PT ?: null,
            'description_en' => $knowledgeArea->DS_AREA_CONHECIMENTO_EN ?: null,
        ];
    }

    private function difficulty(mixed $difficulty): ?array
    {
        if ($difficulty === null) {
            return null;
        }

        return [
            'id' => $difficulty->PK_DIFICULDADE,
            'name_pt' => $difficulty->NO_DIFICULDADE_PT,
            'name_en' => $difficulty->NO_DIFICULDADE_EN,
        ];
    }
}
