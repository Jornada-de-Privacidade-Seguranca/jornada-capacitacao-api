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
            'certification_ids' => ['present', 'array'],
            'certification_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'certification_ids.present' => 'Informe a lista de certificacoes profissionais.',
            'certification_ids.array' => 'A lista de certificacoes profissionais deve ser um array.',
            'certification_ids.*.integer' => 'A certificacao profissional deve ser um numero inteiro.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function certificationIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated('certification_ids'))));
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
