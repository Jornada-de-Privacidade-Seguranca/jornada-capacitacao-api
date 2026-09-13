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
        ];
    }

    public function messages(): array
    {
        return [
            'journey_profile_id.required' => 'O perfil de jornada e obrigatorio.',
            'journey_profile_id.integer' => 'O perfil de jornada deve ser um numero inteiro.',
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
