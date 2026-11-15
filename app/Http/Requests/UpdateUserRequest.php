<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Validator as ValidationValidator;

class UpdateUserRequest extends FormRequest
{
    private const EDITABLE_FIELDS = ['name', 'email', 'organization', 'department', 'registration_number'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach (['name', 'organization', 'department', 'registration_number'] as $field) {
            if (is_string($this->input($field))) {
                $trimmed[$field] = trim($this->input($field));
            }
        }

        if (is_string($this->input('email'))) {
            $trimmed['email'] = mb_strtolower(trim($this->input('email')));
        }

        $this->merge($trimmed);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^\S+\s+\S+(?:.*)?$/u'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:100'],
            'organization' => ['sometimes', 'required', 'string', 'max:100'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'registration_number' => ['sometimes', 'required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome completo.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome deve ter no maximo 50 caracteres.',
            'name.regex' => 'Informe nome e sobrenome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail valido.',
            'email.max' => 'O e-mail deve ter no maximo 100 caracteres.',
            'organization.required' => 'Informe o orgao ou empresa.',
            'organization.string' => 'O orgao ou empresa deve ser um texto.',
            'organization.max' => 'O orgao ou empresa deve ter no maximo 100 caracteres.',
            'department.required' => 'Informe o setor.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor deve ter no maximo 100 caracteres.',
            'registration_number.required' => 'Informe o codigo do funcionario.',
            'registration_number.string' => 'O codigo de funcionario deve ser um texto.',
            'registration_number.max' => 'O codigo de funcionario deve ter no maximo 50 caracteres.',
        ];
    }

    /**
     * @return array<int, callable(ValidationValidator): void>
     */
    public function after(): array
    {
        return [
            function (ValidationValidator $validator): void {
                if (array_intersect(self::EDITABLE_FIELDS, array_keys($this->all())) !== []) {
                    return;
                }

                foreach (self::EDITABLE_FIELDS as $field) {
                    $validator->errors()->add($field, 'Informe ao menos um dado para atualizar o usuario.');
                }
            },
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
