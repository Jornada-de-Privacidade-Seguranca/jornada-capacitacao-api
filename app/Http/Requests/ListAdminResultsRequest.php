<?php

namespace App\Http\Requests;

class ListAdminResultsRequest extends AdminResultFilterRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'page' => $this->query('page', 1),
            'per_page' => $this->query('per_page', 10),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'page' => ['required', 'integer', 'min:1'],
            'per_page' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'page.integer' => 'A pagina atual deve ser um numero inteiro.',
            'page.min' => 'A pagina atual deve ser maior ou igual a 1.',
            'per_page.integer' => 'A quantidade de registros por pagina deve ser um numero inteiro.',
            'per_page.min' => 'A quantidade de registros por pagina deve ser maior ou igual a 1.',
            'per_page.max' => 'A quantidade de registros por pagina nao pode ser maior que 100.',
        ]);
    }
}
