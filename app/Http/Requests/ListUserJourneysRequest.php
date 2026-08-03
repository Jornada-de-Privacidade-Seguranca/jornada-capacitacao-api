<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ListUserJourneysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->query('page', 1),
            'per_page' => $this->query('per_page', 10),
        ]);
    }

    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['completed', 'ongoing'])],
            'journey_profile_id' => ['sometimes', 'integer', 'exists:TB_PERFIS_JORNADA,PK_PERFIS_JORNADA'],
            'started_from' => ['sometimes', 'date_format:Y-m-d'],
            'started_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:started_from'],
            'ended_from' => ['sometimes', 'date_format:Y-m-d'],
            'ended_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:ended_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'A pagina atual deve ser um numero inteiro.',
            'page.min' => 'A pagina atual deve ser maior ou igual a 1.',
            'per_page.integer' => 'A quantidade de registros por pagina deve ser um numero inteiro.',
            'per_page.min' => 'A quantidade de registros por pagina deve ser maior ou igual a 1.',
            'per_page.max' => 'A quantidade de registros por pagina nao pode ser maior que 100.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Parametros de paginacao invalidos.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_PAGINATION_PARAMETERS',
            errors: $validator->errors()->toArray(),
        ));
    }
}
