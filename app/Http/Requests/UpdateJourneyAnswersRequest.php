<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateJourneyAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'journey' => $this->route('journey'),
        ]);
    }

    public function rules(): array
    {
        return [
            'journey' => ['bail', 'required', 'regex:/^[0-9]+$/', 'integer', 'min:1'],
            'professional_experience_id' => ['sometimes', 'integer', 'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO'],
            'profile_experience_id' => ['sometimes', 'integer', 'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO'],
            'english_proficiency_id' => ['sometimes', 'integer', 'exists:TB_SUBCRITERIOS,PK_SUBCRITERIOS'],
        ];
    }

    public function messages(): array
    {
        return [
            'journey.required' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.regex' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.integer' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.min' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'professional_experience_id.integer' => 'A experiencia profissional deve ser um numero inteiro.',
            'professional_experience_id.exists' => 'A experiencia profissional informada nao existe.',
            'profile_experience_id.integer' => 'A atuacao de perfil deve ser um numero inteiro.',
            'profile_experience_id.exists' => 'A atuacao de perfil informada nao existe.',
            'english_proficiency_id.integer' => 'A proficiencia em ingles deve ser um numero inteiro.',
            'english_proficiency_id.exists' => 'A proficiencia em ingles informada nao existe.',
        ];
    }

    public function validatedJourneyId(): int
    {
        $validated = $this->validated();

        return (int) $validated['journey'];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('journey')) {
            throw new HttpResponseException(ApiResponse::error(
                message: 'Identificador da jornada invalido.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'INVALID_JOURNEY_IDENTIFIER',
                errors: $validator->errors()->toArray(),
            ));
        }

        throw new HttpResponseException(ApiResponse::error(
            message: 'Dados invalidos.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
