<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class StoreUserJourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'journey_profile_id' => ['required', 'integer'],
            'professional_experience_id' => ['sometimes', 'integer', 'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO'],
            'profile_experience_id' => ['sometimes', 'integer', 'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO'],
            'english_proficiency_id' => ['sometimes', 'integer', 'exists:TB_PROFICIENCIA_INGLES,PK_PROFICIENCIA_INGLES'],
            'higher_education_course_id' => ['sometimes', 'integer', 'exists:TB_CURSO_SUPERIOR,PK_CURSO_SUPERIOR'],
            'postgraduate_course_id' => ['sometimes', 'integer', 'exists:TB_POS_GRADUACAO,PK_POS_GRADUACAO'],
        ];
    }

    public function messages(): array
    {
        return [
            'journey_profile_id.required' => 'O perfil de jornada e obrigatorio.',
            'journey_profile_id.integer' => 'O perfil de jornada deve ser um numero inteiro.',
            'professional_experience_id.integer' => 'A experiencia profissional deve ser um numero inteiro.',
            'professional_experience_id.exists' => 'A experiencia profissional informada nao existe.',
            'profile_experience_id.integer' => 'A atuacao de perfil deve ser um numero inteiro.',
            'profile_experience_id.exists' => 'A atuacao de perfil informada nao existe.',
            'english_proficiency_id.integer' => 'A proficiencia em ingles deve ser um numero inteiro.',
            'english_proficiency_id.exists' => 'A proficiencia em ingles informada nao existe.',
            'higher_education_course_id.integer' => 'O curso superior deve ser um numero inteiro.',
            'higher_education_course_id.exists' => 'O curso superior informado nao existe.',
            'postgraduate_course_id.integer' => 'A pos-graduacao deve ser um numero inteiro.',
            'postgraduate_course_id.exists' => 'A pos-graduacao informada nao existe.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $isMissingRequiredField = ! $this->filled('journey_profile_id');

        throw new HttpResponseException(ApiResponse::error(
            message: $isMissingRequiredField
                ? 'Dados obrigatorios nao informados.'
                : 'Dados invalidos.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: $isMissingRequiredField
                ? 'REQUIRED_FIELDS_MISSING'
                : 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
