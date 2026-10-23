<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CurrentJourneyCompetencyOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'modality' => ['sometimes', 'string', Rule::in(['Privacidade', 'Segurança', 'Privacidade/Segurança'])],
        ];
    }

    public function messages(): array
    {
        return [
            'modality.in' => 'Modalidade informada e invalida.',
        ];
    }

    public function modality(): ?string
    {
        return $this->validated('modality');
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Modalidade informada e invalida.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
