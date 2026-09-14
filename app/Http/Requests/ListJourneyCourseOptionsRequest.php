<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ListJourneyCourseOptionsRequest extends FormRequest
{
    private const ACADEMIC_CRITERION_ID = 2;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_id' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::exists('TB_SUBCRITERIOS', 'PK_SUBCRITERIOS')->where('FK_CRITERIOS', self::ACADEMIC_CRITERION_ID),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type_id.integer' => 'Tipo de curso informado e invalido.',
            'type_id.min' => 'Tipo de curso informado e invalido.',
            'type_id.exists' => 'Tipo de curso informado e invalido.',
        ];
    }

    public function typeId(): ?int
    {
        $typeId = $this->validated('type_id');

        return $typeId === null ? null : (int) $typeId;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiResponse::error(
            message: 'Tipo de curso informado e invalido.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_COURSE_TYPE',
            errors: $validator->errors()->toArray(),
        ));
    }
}
