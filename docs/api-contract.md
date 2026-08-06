# Contrato de resposta da API

> [!IMPORTANT]
> Todo agente de IA que atuar neste projeto deve seguir obrigatoriamente o contrato padrão de resposta da API.

Esta regra vale para qualquer criação, alteração ou refatoração de:

- endpoints
- controllers
- services
- resources
- exception handlers
- request validations
- qualquer código que produza resposta HTTP da API

---

## 1. Regra principal

Nenhum endpoint deve retornar JSON manualmente com formatos próprios.

Todo retorno da API deve passar pela camada padrão de resposta do projeto, usando métodos como:

```php
ApiResponse::success(...);
ApiResponse::error(...);
ApiResponse::paginated(...);
```

Os `Resources` devem ser usados apenas para formatar os dados da entidade. Eles não devem montar o contrato completo da resposta.

---

## 2. Respostas de sucesso

Toda resposta de sucesso deve seguir este formato:

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {},
  "meta": {}
}
```

### Campos

| Campo | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `success` | `boolean` | Sim | Deve ser sempre `true` em respostas de sucesso. |
| `message` | `string` | Sim | Mensagem objetiva e legível. |
| `data` | `object`, `array` ou `null` | Sim | Conteúdo principal da resposta. |
| `meta` | `object` | Não | Informações adicionais, como paginação. |

### Regras

- `success` deve ser sempre `true`.
- `message` deve explicar o resultado da operação.
- `data` deve conter o recurso, uma lista de recursos ou `null`.
- `meta` deve ser usado apenas quando houver informações adicionais relevantes.
- Em respostas paginadas, a paginação deve ficar em `meta.pagination`.

---

## 3. Respostas de erro

Toda resposta de erro deve seguir este formato:

```json
{
  "success": false,
  "message": "An error occurred.",
  "errors": {},
  "code": "ERROR_CODE"
}
```

### Campos

| Campo | Tipo | Obrigatório | Descrição |
| --- | --- | --- | --- |
| `success` | `boolean` | Sim | Deve ser sempre `false` em respostas de erro. |
| `message` | `string` | Sim | Mensagem objetiva, segura e legível. |
| `errors` | `object` ou `array` | Sim | Detalhes de validação ou lista vazia. |
| `code` | `string` | Sim | Código estável do erro em `UPPER_SNAKE_CASE`. |

### Regras

- `success` deve ser sempre `false`.
- `message` deve ser objetiva e segura.
- `errors` deve conter os erros de validação ou um array vazio.
- `code` deve ser estável e escrito em `UPPER_SNAKE_CASE`.
- Nunca expor stack trace, SQL, nomes de classes, arquivos internos ou detalhes sensíveis.

---

## 4. Uso correto de Resources

`Resources` devem transformar apenas os dados da entidade.

### Correto

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

### Incorreto

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => [
                'id' => $this->id,
                'name' => $this->name,
            ],
        ];
    }
}
```

> O contrato completo da resposta deve ser responsabilidade da classe central de resposta, não do `Resource`.

---

## 5. Uso correto em Controllers

Controllers não devem montar respostas JSON manualmente.

### Incorreto

```php
return response()->json([
    'status' => true,
    'user' => $user,
]);
```

### Sucesso

```php
return ApiResponse::success(
    data: new UserResource($user),
    message: 'User retrieved successfully.'
);
```

### Erro

```php
return ApiResponse::error(
    message: 'User not found.',
    status: 404,
    code: 'USER_NOT_FOUND'
);
```

### Paginação

```php
return ApiResponse::paginated(
    resource: UserResource::collection($users),
    message: 'Users retrieved successfully.'
);
```

---

## 6. Status HTTP

Os agentes devem manter o contrato padronizado e também usar status HTTP corretos.

| Status | Uso recomendado |
| --- | --- |
| `200 OK` | Busca, listagem, atualização ou exclusão com resposta padronizada. |
| `201 Created` | Criação de recurso. |
| `400 Bad Request` | Erro genérico de requisição. |
| `401 Unauthorized` | Usuário não autenticado. |
| `403 Forbidden` | Usuário autenticado, mas sem permissão. |
| `404 Not Found` | Recurso não encontrado. |
| `409 Conflict` | Conflito de regra de negócio ou recurso duplicado. |
| `422 Unprocessable Entity` | Erro de validação. |
| `500 Internal Server Error` | Erro inesperado no servidor. |

### Regras

- Não retornar sempre `200`.
- Evitar `204 No Content` quando isso quebrar o contrato esperado pelo front-end.
- Para exclusões, preferir resposta padronizada com `data: null`.

Exemplo para exclusão:

```json
{
  "success": true,
  "message": "Resource deleted successfully.",
  "data": null
}
```

---

## 7. Validação

Erros de validação devem retornar status HTTP `422`.

Formato obrigatório:

```json
{
  "success": false,
  "message": "Validation error.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  },
  "code": "VALIDATION_ERROR"
}
```

---

## 8. Paginação

Endpoints paginados devem retornar os dados principais em `data` e as informações de paginação em `meta.pagination`.

Formato obrigatório:

```json
{
  "success": true,
  "message": "Resources retrieved successfully.",
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 100,
      "last_page": 10,
      "has_next_page": true
    }
  }
}
```

### Campos de paginação

| Campo | Descrição |
| --- | --- |
| `current_page` | Página atual. |
| `per_page` | Quantidade de itens por página. |
| `total` | Total de registros encontrados. |
| `last_page` | Última página disponível. |
| `has_next_page` | Indica se existe uma próxima página disponível. |

---

## 9. Nomes de campos

Todos os campos retornados pela API devem usar `snake_case`.

### Correto

```json
{
  "created_at": "2026-06-16 10:30:00",
  "full_name": "João Silva"
}
```

### Incorreto

```json
{
  "createdAt": "2026-06-16 10:30:00",
  "fullName": "João Silva"
}
```

---

## 10. Datas

Datas devem seguir o padrão:

```text
YYYY-MM-DD HH:mm:ss
```

Quando for apenas data, usar:

```text
YYYY-MM-DD
```

Exemplos:

```json
{
  "created_at": "2026-06-16 10:30:00",
  "birth_date": "1998-05-20"
}
```

---

## 11. Regras obrigatórias para agentes de IA

Ao implementar ou alterar endpoints, o agente deve obrigatoriamente:

1. Verificar se a rota retorna o contrato padrão.
2. Usar `ApiResponse::success`, `ApiResponse::error` ou `ApiResponse::paginated`.
3. Criar ou atualizar `Resource` quando houver entidade retornada.
4. Garantir que o `Resource` não monte o contrato completo da API.
5. Evitar `response()->json(...)` diretamente no controller.
6. Usar status HTTP adequado.
7. Garantir que erros tenham `success`, `message`, `errors` e `code`.
8. Garantir que sucessos tenham `success`, `message` e `data`.
9. Usar `meta.pagination` para respostas paginadas.
10. Não expor detalhes internos do sistema.
11. Manter campos em `snake_case`.
12. Manter datas no formato definido.
13. Atualizar ou criar testes quando a alteração afetar o contrato de resposta.
14. Não criar novo formato de resposta sem autorização explícita.

---

## 12. Critério de aceite

Uma tarefa só pode ser considerada concluída quando todos os endpoints criados ou alterados seguirem este contrato.

Se o agente encontrar endpoints antigos fora do padrão, ele não deve alterar tudo automaticamente sem solicitação explícita.

Porém, se o endpoint antigo estiver sendo modificado na tarefa atual, ele deve ser ajustado para o contrato padrão.

---

## 13. Checklist para Pull Requests

Antes de finalizar qualquer implementação, o agente deve revisar o diff e confirmar:

- [ ] Nenhum controller novo monta JSON manualmente.
- [ ] Todos os retornos usam `ApiResponse`.
- [ ] `Resources` formatam apenas dados.
- [ ] Respostas de sucesso seguem o contrato.
- [ ] Respostas de erro seguem o contrato.
- [ ] Paginação usa `meta.pagination`.
- [ ] Status HTTP estão corretos.
- [ ] Nenhum erro interno é exposto.
- [ ] Campos retornados usam `snake_case`.
- [ ] Datas seguem o formato definido.
- [ ] Testes foram atualizados ou criados quando necessário.

Caso qualquer item não seja atendido, a tarefa não deve ser considerada finalizada.

---

## 14. Referência para documentação humana

Antes de alterar qualquer endpoint, o agente deve consultar a documentação completa do contrato da API, quando disponível:

```text
docs/api-contract.md
```

O arquivo `AGENTS.md` define a regra operacional para agentes de IA.

O arquivo `docs/api-contract.md` deve conter a documentação completa para desenvolvedores humanos e para alinhamento com o front-end.
