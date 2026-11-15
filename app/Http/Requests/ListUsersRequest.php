<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile_type_id' => ['sometimes', 'nullable', 'integer', 'exists:TB_TIPO_PERFIL,PK_TIPO_PERFIL'],
            'department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', 'string', Rule::in(['active', 'pending_activation', 'inactive'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'A pagina atual deve ser um numero inteiro.',
            'page.min' => 'A pagina atual deve ser maior ou igual a 1.',
            'per_page.integer' => 'A quantidade de registros por pagina deve ser um numero inteiro.',
            'per_page.min' => 'A quantidade de registros por pagina deve ser maior ou igual a 1.',
            'per_page.max' => 'A quantidade de registros por pagina nao pode ser maior que 100.',
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca nao pode ter mais de 255 caracteres.',
            'profile_type_id.integer' => 'O perfil de acesso deve ser um numero inteiro.',
            'profile_type_id.exists' => 'O perfil de acesso informado nao existe.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor nao pode ter mais de 100 caracteres.',
            'status.in' => 'A situacao do usuario deve ser active, pending_activation ou inactive.',
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
