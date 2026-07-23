<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'regex:/\S+\s+\S+/u'],
            'email' => ['required', 'string', 'email', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'organization_company' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'profile_type_id' => ['nullable', 'integer', Rule::exists('TB_TIPO_PERFIL', 'PK_TIPO_PERFIL')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.string' => 'O nome deve ser um texto valido.',
            'name.max' => 'O nome deve ter no maximo 50 caracteres.',
            'name.regex' => 'Informe nome e sobrenome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail valido.',
            'email.max' => 'O e-mail deve ter no maximo 100 caracteres.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no maximo 100 caracteres.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
            'organization_company.required' => 'Informe o orgao ou empresa.',
            'organization_company.max' => 'O orgao ou empresa deve ter no maximo 100 caracteres.',
            'department.required' => 'Informe o setor.',
            'department.max' => 'O setor deve ter no maximo 100 caracteres.',
            'registration_number.max' => 'A matricula deve ter no maximo 50 caracteres.',
            'profile_type_id.integer' => 'O tipo de perfil deve ser um identificador valido.',
            'profile_type_id.exists' => 'O tipo de perfil informado nao existe.',
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
