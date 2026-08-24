<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ShowJourneyRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'journey.required' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.regex' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.integer' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.min' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
        ];
    }

    public function validatedJourneyId(): int
    {
        $validated = $this->validated();

        return (int) $validated['journey'];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Identificador da jornada invalido.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_JOURNEY_IDENTIFIER',
            errors: $validator->errors()->toArray(),
        ));
    }
}
