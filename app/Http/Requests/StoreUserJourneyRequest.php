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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user' => $this->route('user'),
        ]);
    }

    public function rules(): array
    {
        return [
            'user' => ['bail', 'required', 'regex:/^[0-9]+$/', 'integer', 'min:1'],
            'journey_profile_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'user.required' => 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.',
            'user.regex' => 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.',
            'user.integer' => 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.',
            'user.min' => 'O identificador do usuario deve ser um numero inteiro maior ou igual a 1.',
            'journey_profile_id.required' => 'O perfil de jornada e obrigatorio.',
            'journey_profile_id.integer' => 'O perfil de jornada deve ser um numero inteiro.',
        ];
    }

    public function validatedUserId(): int
    {
        $validated = $this->validated();

        return (int) $validated['user'];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('user')) {
            throw new HttpResponseException(ApiResponse::error(
                message: 'Identificador do usuario invalido.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
                code: 'INVALID_USER_IDENTIFIER',
                errors: $validator->errors()->toArray(),
            ));
        }

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
