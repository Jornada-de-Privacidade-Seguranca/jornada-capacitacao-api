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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^\S+\s+\S+(?:.*)?$/u'],
            'email' => ['required', 'email:rfc', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            'organization' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'registration_number' => ['required', 'string', 'max:50'],
            // Temporarily nullable until the database script provides the required profile types.
            // Make this field required again when that setup exists.
            'profile_type_id' => ['nullable', 'integer', Rule::exists('TB_TIPO_PERFIL', 'PK_TIPO_PERFIL')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no maximo 50 caracteres.',
            'name.regex' => 'Informe nome e sobrenome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail valido.',
            'email.max' => 'O e-mail deve ter no maximo 50 caracteres.',
            'password.required' => 'Informe a senha.',
            'password.string' => 'A senha deve ser um texto.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no maximo 72 caracteres.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
            'organization.required' => 'Informe o orgao ou empresa.',
            'organization.string' => 'O orgao ou empresa deve ser um texto.',
            'organization.max' => 'O orgao ou empresa deve ter no maximo 100 caracteres.',
            'department.required' => 'Informe o setor.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor deve ter no maximo 100 caracteres.',
            'registration_number.required' => 'Informe a matricula.',
            'registration_number.string' => 'A matricula deve ser um texto.',
            'registration_number.max' => 'A matricula deve ter no maximo 50 caracteres.',
            'profile_type_id.integer' => 'O tipo de perfil deve ser um numero inteiro.',
            'profile_type_id.exists' => 'Informe um tipo de perfil valido.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Validation error.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'VALIDATION_ERROR',
            errors: $validator->errors()->toArray(),
        ));
    }
}
