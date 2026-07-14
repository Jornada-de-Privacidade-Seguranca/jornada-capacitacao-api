<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class TemplateExampleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Normalize aqui pequenas diferencas de entrada HTTP antes da validacao.
        // Exemplo: Swagger envia booleans de query string como "true" ou "false".
        if ($this->has('include_steps')) {
            $includeSteps = $this->input('include_steps');

            $this->merge([
                'include_steps' => match ($includeSteps) {
                    'true' => true,
                    'false' => false,
                    default => $includeSteps,
                },
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Coloque aqui apenas regras de validacao da entrada da rota.
        // Regras de negocio devem ficar na Service, nao no Request.
        return [
            'context' => ['nullable', 'string', 'max:80'],
            'include_steps' => ['nullable', 'boolean'],
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
