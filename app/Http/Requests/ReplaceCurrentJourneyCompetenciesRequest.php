<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Validator as ValidationValidator;

class ReplaceCurrentJourneyCompetenciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competencies' => ['required', 'array'],
            'competencies.*.competency_id' => ['required', 'integer'],
            'competencies.*.acquired' => ['required', 'boolean'],
        ];
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            $answersByCompetency = [];

            foreach ($this->input('competencies', []) as $competency) {
                if (! is_array($competency) || ! array_key_exists('competency_id', $competency) || ! array_key_exists('acquired', $competency)) {
                    continue;
                }

                $competencyId = (int) $competency['competency_id'];
                $acquired = filter_var($competency['acquired'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($acquired === null) {
                    continue;
                }

                if (array_key_exists($competencyId, $answersByCompetency) && $answersByCompetency[$competencyId] !== $acquired) {
                    $validator->errors()->add(
                        'competencies',
                        'Competencias repetidas com respostas divergentes nao sao permitidas.',
                    );

                    return;
                }

                $answersByCompetency[$competencyId] = $acquired;
            }
        });
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
