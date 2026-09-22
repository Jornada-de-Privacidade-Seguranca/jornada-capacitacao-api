<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class RecordJourneyCompetenciesRequest extends FormRequest
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
            'competencies' => ['required', 'array', 'min:1'],
            'competencies.*.competency_id' => ['required', 'integer', 'exists:TB_COMPETENCIAS,PK_COMPETENCIAS'],
            'competencies.*.is_acquired' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'journey.required' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.regex' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.integer' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'journey.min' => 'O identificador da jornada deve ser um numero inteiro maior ou igual a 1.',
            'competencies.required' => 'Informe ao menos uma competencia.',
            'competencies.array' => 'As competencias devem ser informadas em uma lista.',
            'competencies.min' => 'Informe ao menos uma competencia.',
            'competencies.*.competency_id.required' => 'O identificador da competencia e obrigatorio.',
            'competencies.*.competency_id.integer' => 'O identificador da competencia deve ser um numero inteiro.',
            'competencies.*.competency_id.exists' => 'A competencia informada nao existe.',
            'competencies.*.is_acquired.required' => 'E obrigatorio informar se a competencia foi adquirida.',
            'competencies.*.is_acquired.boolean' => 'O campo is_acquired deve ser verdadeiro ou falso.',
        ];
    }

    public function validatedJourneyId(): int
    {
        $validated = $this->validated();

        return (int) $validated['journey'];
    }

    public function competencies(): array
    {
        return $this->validated()['competencies'];
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
