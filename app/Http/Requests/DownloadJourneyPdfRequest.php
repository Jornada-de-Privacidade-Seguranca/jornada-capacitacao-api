<?php

namespace App\Http\Requests;

use App\Services\JourneyResultReportService;
use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class DownloadJourneyPdfRequest extends ShowJourneyRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'locale' => ['nullable', 'string', Rule::in(JourneyResultReportService::SUPPORTED_LOCALES)],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'locale.string' => 'O idioma deve ser pt ou en.',
            'locale.in' => 'O idioma deve ser pt ou en.',
        ];
    }

    public function validatedLocale(): string
    {
        return $this->validated()['locale'] ?? JourneyResultReportService::DEFAULT_LOCALE;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('journey')) {
            parent::failedValidation($validator);
        }

        throw new HttpResponseException(ApiResponse::error(
            message: 'Idioma do PDF invalido.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            code: 'INVALID_LOCALE',
            errors: $validator->errors()->toArray(),
        ));
    }
}
