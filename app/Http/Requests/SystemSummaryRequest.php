<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class SystemSummaryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('include_database_status')) {
            $includeDatabaseStatus = $this->input('include_database_status');

            $this->merge([
                'include_database_status' => match ($includeDatabaseStatus) {
                    'true' => true,
                    'false' => false,
                    default => $includeDatabaseStatus,
                },
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'include_database_status' => ['nullable', 'boolean'],
            'tag' => ['nullable', 'string', 'max:50'],
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
