<?php

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ListJourneyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'modality' => ['sometimes', 'string', 'in:Privacidade,Segurança,Privacidade/Segurança'],
            'search' => ['sometimes', 'string', 'max:255'],
            'domains' => ['sometimes', 'array'],
            'domains.*' => ['integer', 'distinct', 'exists:TB_DOMINIO_CONHECIMENTO,PK_DOMINIO_CONHECIMENTO'],
            'user_status' => ['sometimes', 'array'],
            'user_status.*' => ['string', 'distinct', 'in:completed,pending,not_done'],
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
            'modality.string' => 'A modalidade deve ser um texto.',
            'modality.in' => 'A modalidade deve ser Privacidade, Segurança ou Privacidade/Segurança.',
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca nao pode ter mais de 255 caracteres.',
            'domains.array' => 'Os dominios devem ser enviados como uma lista de ids.',
            'domains.*.integer' => 'Cada dominio deve ser informado pelo id numerico.',
            'domains.*.distinct' => 'Os dominios nao podem se repetir.',
            'domains.*.exists' => 'Um ou mais dominios informados nao existem.',
            'user_status.array' => 'A situacao deve ser enviada como uma lista.',
            'user_status.*.string' => 'Cada situacao deve ser um texto.',
            'user_status.*.distinct' => 'As situacoes nao podem se repetir.',
            'user_status.*.in' => 'A situacao deve ser completed, pending ou not_done.',
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

    public function page(): int
    {
        return $this->integer('page', 1);
    }

    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }

    public function modality(): ?string
    {
        return $this->validated('modality');
    }

    public function search(): ?string
    {
        $search = trim((string) $this->validated('search', ''));

        return $search === '' ? null : $search;
    }

    /** @return list<int>|null */
    public function domains(): ?array
    {
        $domains = $this->validated('domains');

        return $domains === null ? null : array_map('intval', $domains);
    }

    /** @return list<string>|null */
    public function userStatuses(): ?array
    {
        return $this->validated('user_status');
    }
}
