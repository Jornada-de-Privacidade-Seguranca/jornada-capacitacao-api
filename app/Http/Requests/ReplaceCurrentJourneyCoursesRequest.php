<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ReplaceCurrentJourneyCoursesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_ids' => ['present', 'array'],
            'course_ids.*' => ['integer', 'exists:TB_CURSO_SUPERIOR,PK_CURSO_SUPERIOR'],
        ];
    }

    public function messages(): array
    {
        return [
            'course_ids.present' => 'Informe a lista de cursos.',
            'course_ids.array' => 'Os cursos devem ser informados em uma lista.',
            'course_ids.*.integer' => 'O identificador do curso deve ser um numero inteiro.',
            'course_ids.*.exists' => 'O curso informado nao existe.',
        ];
    }

    public function courseIds(): array
    {
        return array_values(array_unique($this->validated()['course_ids']));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Dados invalidos.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
