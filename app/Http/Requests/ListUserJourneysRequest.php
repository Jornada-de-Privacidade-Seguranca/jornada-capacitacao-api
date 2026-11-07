<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ListUserJourneysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->query('page', 1),
            'per_page' => $this->query('per_page', 10),
        ]);
    }

    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::in(['completed', 'ongoing'])],
            'journey_profile_id' => ['sometimes', 'integer', 'exists:TB_PERFIS_JORNADA,PK_PERFIS_JORNADA'],
            'started_from' => ['sometimes', 'date_format:Y-m-d'],
            'started_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:started_from'],
            'ended_from' => ['sometimes', 'date_format:Y-m-d'],
            'ended_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:ended_from'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'knowledge_domain_id' => ['sometimes', 'nullable', 'integer', 'exists:TB_DOMINIO_CONHECIMENTO,PK_DOMINIO_CONHECIMENTO'],
            'domains' => ['sometimes', 'nullable', 'array'],
            'domains.*' => ['integer', 'distinct', 'exists:TB_DOMINIO_CONHECIMENTO,PK_DOMINIO_CONHECIMENTO'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'A pagina atual deve ser um numero inteiro.',
            'page.min' => 'A pagina atual deve ser maior ou igual a 1.',
            'per_page.integer' => 'A quantidade de registros por pagina deve ser um numero inteiro.',
            'per_page.min' => 'A quantidade de registros por pagina deve ser maior ou igual a 1.',
            'per_page.max' => 'A quantidade de registros por pagina nao pode ser maior que 100.',
            'status.in' => 'A situacao da jornada deve ser completed ou ongoing.',
            'journey_profile_id.integer' => 'O perfil de jornada deve ser um numero inteiro.',
            'journey_profile_id.exists' => 'O perfil de jornada informado nao existe.',
            'started_from.date_format' => 'A data inicial minima deve estar no formato Y-m-d.',
            'started_to.date_format' => 'A data inicial maxima deve estar no formato Y-m-d.',
            'started_to.after_or_equal' => 'A data inicial maxima deve ser maior ou igual a data inicial minima.',
            'ended_from.date_format' => 'A data de finalizacao minima deve estar no formato Y-m-d.',
            'ended_to.date_format' => 'A data de finalizacao maxima deve estar no formato Y-m-d.',
            'ended_to.after_or_equal' => 'A data de finalizacao maxima deve ser maior ou igual a data de finalizacao minima.',
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca nao pode ter mais de 255 caracteres.',
            'knowledge_domain_id.integer' => 'O dominio deve ser um numero inteiro.',
            'knowledge_domain_id.exists' => 'O dominio de conhecimento informado nao existe.',
            'domains.array' => 'Os dominios devem ser enviados como uma lista de ids.',
            'domains.*.integer' => 'Cada dominio deve ser informado pelo id numerico.',
            'domains.*.distinct' => 'Os dominios nao podem se repetir.',
            'domains.*.exists' => 'Um ou mais dominios informados nao existem.',
        ];
    }

    public function search(): ?string
    {
        $search = trim((string) $this->validated('search', ''));

        return $search === '' ? null : $search;
    }

    /**
     * @return list<int>|null
     */
    public function domains(): ?array
    {
        /** @var list<int>|null $domains */
        $domains = $this->validated('domains');
        $domainId = $this->validated('knowledge_domain_id');

        if ($domainId !== null) {
            $domains = array_merge($domains ?? [], [(int) $domainId]);
        }

        if ($domains === null) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $domains)));
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
