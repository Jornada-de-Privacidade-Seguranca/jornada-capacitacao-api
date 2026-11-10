<?php

namespace App\Http\Resources;

use App\Models\Journey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journey
 */
class DashboardJourneyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->PK_JORNADA,
            'user_id' => $this->FK_USUARIO,
            'journey_profile_id' => $this->FK_PERFIS_JORNADA,
            'status' => $this->DH_FIM === null ? 'in_progress' : 'completed',
            'started_at' => $this->DH_CRIADO_EM?->format('Y-m-d H:i:s'),
            'finished_at' => $this->DH_FIM?->format('Y-m-d H:i:s'),
            'origin' => $this->origin,
            'journey_profile' => [
                'id' => $this->journeyProfile?->PK_PERFIS_JORNADA,
                'name_pt' => $this->journeyProfile?->NO_PERFIS_PT,
                'name_en' => $this->journeyProfile?->NO_PERFIS_EN,
                'description_pt' => $this->journeyProfile?->DS_PERFIS_PT,
                'description_en' => $this->journeyProfile?->DS_PERFIS_EN,
                'knowledge_domain' => [
                    'id' => $this->journeyProfile?->knowledgeDomain?->PK_DOMINIO_CONHECIMENTO,
                    'name_pt' => $this->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_PT,
                    'name_en' => $this->journeyProfile?->knowledgeDomain?->NO_DOMINIO_CONHECIMENTO_EN,
                ],
            ],
            'professional_experience' => [
                'id' => $this->professionalExperience?->PK_EXPERIENCIA_ATUACAO,
                'description_pt' => $this->professionalExperience?->DS_EXPERIENCIA_ATUACAO_PT,
                'description_en' => $this->professionalExperience?->DS_EXPERIENCIA_ATUACAO_EN,
            ],
            'profile_experience' => [
                'id' => $this->profileExperience?->PK_EXPERIENCIA_ATUACAO,
                'description_pt' => $this->profileExperience?->DS_EXPERIENCIA_ATUACAO_PT,
                'description_en' => $this->profileExperience?->DS_EXPERIENCIA_ATUACAO_EN,
            ],
            'english_proficiency' => [
                'id' => $this->englishSubcriterion?->PK_SUBCRITERIOS,
                'name_pt' => $this->englishSubcriterion?->NO_SUBCRITERIO_PT,
                'name_en' => $this->englishSubcriterion?->NO_SUBCRITERIO_EN,
            ],
            'higher_education_courses' => $this->higherEducationCourses->map(fn ($course): array => [
                'id' => $course->PK_CURSO_SUPERIOR,
                'name_pt' => $course->NO_CURSO_SUPERIOR_PT,
                'name_en' => $course->NO_CURSO_SUPERIOR_EN,
                'subcriterion' => [
                    'id' => $course->subcriterion?->PK_SUBCRITERIOS,
                    'name_pt' => $course->subcriterion?->NO_SUBCRITERIO_PT,
                    'name_en' => $course->subcriterion?->NO_SUBCRITERIO_EN,
                ],
            ])->values()->all(),
        ];
    }
}
