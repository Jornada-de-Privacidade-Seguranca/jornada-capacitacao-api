<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class AdminResultFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:TB_USUARIO,PK_USUARIO'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'domains' => ['sometimes', 'nullable', 'array'],
            'domains.*' => ['integer', 'distinct', 'exists:TB_DOMINIO_CONHECIMENTO,PK_DOMINIO_CONHECIMENTO'],
            'journey_profile_id' => ['sometimes', 'nullable', 'integer', 'exists:TB_PERFIS_JORNADA,PK_PERFIS_JORNADA'],
            'ended_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'ended_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:ended_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca nao pode ter mais de 255 caracteres.',
            'user_id.integer' => 'O usuario deve ser um numero inteiro.',
            'user_id.exists' => 'O usuario informado nao existe.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor nao pode ter mais de 255 caracteres.',
            'domains.array' => 'Os dominios devem ser enviados como uma lista de ids.',
            'domains.*.integer' => 'Cada dominio deve ser informado pelo id numerico.',
            'domains.*.distinct' => 'Os dominios nao podem se repetir.',
            'domains.*.exists' => 'Um ou mais dominios informados nao existem.',
            'journey_profile_id.integer' => 'O perfil de jornada deve ser um numero inteiro.',
            'journey_profile_id.exists' => 'O perfil de jornada informado nao existe.',
            'ended_from.date_format' => 'A data de finalizacao minima deve estar no formato Y-m-d.',
            'ended_to.date_format' => 'A data de finalizacao maxima deve estar no formato Y-m-d.',
            'ended_to.after_or_equal' => 'A data de finalizacao maxima deve ser maior ou igual a data de finalizacao minima.',
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
