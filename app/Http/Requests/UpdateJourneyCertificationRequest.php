<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UpdateJourneyCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certification_ids' => ['present', 'array', 'max:100'],
            'certification_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'certification_ids.present' => 'Informe a lista de certificacoes profissionais.',
            'certification_ids.array' => 'A lista de certificacoes profissionais deve ser um array.',
            'certification_ids.max' => 'A lista de certificacoes profissionais nao pode ter mais de 100 itens.',
            'certification_ids.*.integer' => 'A certificacao profissional deve ser um numero inteiro.',
            'certification_ids.*.min' => 'A certificacao profissional deve ser um numero inteiro positivo.',
            'certification_ids.*.distinct' => 'A lista de certificacoes profissionais nao pode conter itens duplicados.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function certificationIds(): array
    {
        return array_map('intval', $this->validated('certification_ids'));
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
