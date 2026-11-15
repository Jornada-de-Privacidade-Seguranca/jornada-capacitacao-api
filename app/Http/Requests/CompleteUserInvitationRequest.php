<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class CompleteUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }

        foreach (['name', 'organization', 'department', 'registration_number'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:100'],
            'token' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'name' => ['required', 'string', 'max:50', 'regex:/^\S+\s+\S+(?:.*)?$/u'],
            'organization' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'registration_number' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail valido.',
            'email.max' => 'O e-mail deve ter no maximo 100 caracteres.',
            'token.required' => 'Informe o codigo do convite.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.max' => 'A senha deve ter no maximo 100 caracteres.',
            'password.confirmed' => 'A confirmacao da senha nao confere.',
            'name.required' => 'Informe o nome completo.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no maximo 50 caracteres.',
            'name.regex' => 'Informe nome e sobrenome.',
            'organization.required' => 'Informe o orgao ou empresa.',
            'organization.string' => 'O orgao ou empresa deve ser um texto.',
            'organization.max' => 'O orgao ou empresa deve ter no maximo 100 caracteres.',
            'department.required' => 'Informe o setor.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor deve ter no maximo 100 caracteres.',
            'registration_number.required' => 'Informe a matricula.',
            'registration_number.string' => 'A matricula deve ser um texto.',
            'registration_number.max' => 'A matricula deve ter no maximo 50 caracteres.',
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
