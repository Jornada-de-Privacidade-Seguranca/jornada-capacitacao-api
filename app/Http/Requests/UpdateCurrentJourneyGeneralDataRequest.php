<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateCurrentJourneyGeneralDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'english_level_id' => [
                'required_without_all:professional_experience_id,profile_experience_id',
                'integer',
                'exists:TB_SUBCRITERIOS,PK_SUBCRITERIOS',
            ],
            'professional_experience_id' => [
                'required_without_all:english_level_id,profile_experience_id',
                'integer',
                'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO',
            ],
            'profile_experience_id' => [
                'required_without_all:english_level_id,professional_experience_id',
                'integer',
                'exists:TB_EXPERIENCIA_ATUACAO,PK_EXPERIENCIA_ATUACAO',
            ],
        ];
    }

    public function messages(): array
    {
        $atLeastOneField = 'Informe ao menos um dos dados gerais: nivel de ingles, experiencia profissional ou tempo de atuacao no perfil.';

        return [
            'english_level_id.required_without_all' => $atLeastOneField,
            'english_level_id.integer' => 'O nivel de ingles deve ser um numero inteiro.',
            'english_level_id.exists' => 'O nivel de ingles informado nao existe.',
            'professional_experience_id.required_without_all' => $atLeastOneField,
            'professional_experience_id.integer' => 'A experiencia profissional deve ser um numero inteiro.',
            'professional_experience_id.exists' => 'A experiencia profissional informada nao existe.',
            'profile_experience_id.required_without_all' => $atLeastOneField,
            'profile_experience_id.integer' => 'O tempo de atuacao no perfil deve ser um numero inteiro.',
            'profile_experience_id.exists' => 'O tempo de atuacao no perfil informado nao existe.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Validation error.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
