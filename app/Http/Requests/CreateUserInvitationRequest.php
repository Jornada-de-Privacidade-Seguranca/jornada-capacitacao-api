<?php

namespace App\Http\Requests;

use App\Models\ProfileType;
use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class CreateUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->input('email'))
                ? mb_strtolower(trim($this->input('email')))
                : $this->input('email'),
            'profile_type_id' => $this->input('profile_type_id', ProfileType::USER_ID),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:100'],
            'profile_type_id' => ['required', 'integer', Rule::exists('TB_TIPO_PERFIL', 'PK_TIPO_PERFIL')],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail valido.',
            'email.max' => 'O e-mail deve ter no maximo 100 caracteres.',
            'profile_type_id.integer' => 'O perfil de acesso deve ser um numero inteiro.',
            'profile_type_id.exists' => 'Informe um perfil de acesso valido.',
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
