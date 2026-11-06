<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Validator as ValidationValidator;

/**
 * Edicao parcial dos dados do proprio usuario. O codigo de funcionario
 * (registration_number) nao e editavel: se vier na requisicao, fica fora dos
 * dados validados e e ignorado.
 */
class UpdateOwnProfileRequest extends FormRequest
{
    private const EDITABLE_FIELDS = ['name', 'email', 'organization', 'department'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trimmed = [];

        foreach (['name', 'organization', 'department'] as $field) {
            if (is_string($this->input($field))) {
                $trimmed[$field] = trim($this->input($field));
            }
        }

        if (is_string($this->input('email'))) {
            $trimmed['email'] = mb_strtolower(trim($this->input('email')));
        }

        $this->merge($trimmed);
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^\S+\s+\S+(?:.*)?$/u'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:100'],
            'organization' => ['sometimes', 'required', 'string', 'max:100'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
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
            'email.max' => 'O e-mail deve ter no maximo 100 caracteres.',
            'organization.required' => 'Informe o orgao ou empresa.',
            'organization.string' => 'O orgao ou empresa deve ser um texto.',
            'organization.max' => 'O orgao ou empresa deve ter no maximo 100 caracteres.',
            'department.required' => 'Informe o setor.',
            'department.string' => 'O setor deve ser um texto.',
            'department.max' => 'O setor deve ter no maximo 100 caracteres.',
        ];
    }

    /**
     * Pelo menos um campo editavel precisa ser enviado. Um campo enviado
     * vazio recebe a mensagem do proprio campo, pela regra required.
     */
    public function after(): array
    {
        return [
            function (ValidationValidator $validator): void {
                if (array_intersect(self::EDITABLE_FIELDS, array_keys($this->all())) !== []) {
                    return;
                }

                foreach (self::EDITABLE_FIELDS as $field) {
                    $validator->errors()->add($field, 'Informe ao menos um dos dados: nome, e-mail, orgao/empresa ou setor.');
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
