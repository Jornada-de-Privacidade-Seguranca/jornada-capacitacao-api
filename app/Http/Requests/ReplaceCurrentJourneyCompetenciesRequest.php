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
            'competencies' => ['present', 'array', 'max:500'],
            'competencies.*.competency_id' => ['required', 'integer', 'min:1'],
            'competencies.*.acquired' => ['required', 'boolean'],
        ];
    }

    public function competencies(): array
    {
        $unique = [];

        foreach ($this->validated('competencies') as $competency) {
            $unique[(int) $competency['competency_id']] = [
                'competency_id' => (int) $competency['competency_id'],
                'acquired' => (bool) $competency['acquired'],
            ];
        }

        return array_values($unique);
    }

    public function withValidator(ValidationValidator $validator): void
    {
        $validator->after(function (ValidationValidator $validator): void {
            $answersByCompetency = [];
            $competencies = $this->input('competencies', []);

            if (! is_array($competencies)) {
                return;
            }

            foreach ($competencies as $competency) {
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
