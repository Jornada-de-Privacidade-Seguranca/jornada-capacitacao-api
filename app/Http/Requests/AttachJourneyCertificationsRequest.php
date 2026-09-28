<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class AttachJourneyCertificationsRequest extends FormRequest
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
            'certification_ids' => ['required', 'array', 'min:1'],
            'certification_ids.*' => ['integer', 'distinct', 'exists:TB_CERTIFICACAO_PROFISSIONAL,PK_CERTIFICACAO_PROFISSIONAL'],
        ];
    }

    public function messages(): array
    {
        return [
            'journey.required' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.regex' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.integer' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.min' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'certification_ids.required' => 'Informe ao menos uma certificacao.',
            'certification_ids.array' => 'As certificacoes devem ser informadas em uma lista.',
            'certification_ids.min' => 'Informe ao menos uma certificacao.',
            'certification_ids.*.integer' => 'O identificador da certificacao deve ser um numero inteiro.',
            'certification_ids.*.distinct' => 'O identificador da certificacao esta duplicado.',
            'certification_ids.*.exists' => 'A certificacao informada nao existe.',
        ];
    }

    public function validatedJourneyId(): int
    {
        $validated = $this->validated();

        return (int) $validated['journey'];
    }

    public function certificationIds(): array
    {
        return $this->validated()['certification_ids'];
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
