# Jornada de Capacitacao API

Backend REST em Laravel para a Jornada de Capacitacao para Privacidade e Seguranca.

## Requisitos

- Docker com Docker Compose.
- Portas locais livres: `8030` para API e `5432` para PostgreSQL.
- Porta local livre `8031` para a documentacao Swagger.

## Setup local

```bash
cd api
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

A API ficara disponivel em `http://localhost:8030`.
A documentacao Swagger ficara disponivel em `http://localhost:8031`.

## Teste inicial

```bash
curl http://localhost:8030/api/auth/csrf-token
```

Resposta esperada:

```json
{
  "success": true,
  "message": "Token CSRF gerado com sucesso.",
  "data": {
    "csrf_token": "..."
  }
}
```

## Testes automatizados

```bash
docker compose exec app php artisan test
```

Os testes usam SQLite em memoria por meio do `phpunit.xml`.

## Documentacao Swagger

A especificacao OpenAPI fica em `docs/openapi.yaml`.

Com os containers ativos, acesse:

```text
http://localhost:8031
```

O Swagger UI carrega a documentacao dos endpoints disponiveis da API.

## Frontend

No frontend Next, configure:

```env
NEXT_PUBLIC_API_URL=http://localhost:8030/api
```

Use os endpoints documentados no Swagger como contrato de integracao.

## Arquitetura

O projeto usa MVC com Services e Repositories:

- Controllers recebem requests, validam entrada e delegam para Services.
- Services concentram regras de negocio e orquestracao.
- Repositories encapsulam acesso a dados.
- Interfaces ficam em `app/Repositories/Contracts`.
- Implementacoes Eloquent ficam em `app/Repositories/Eloquent`.
- Bindings ficam em `app/Providers/RepositoryServiceProvider.php`.

Ao adicionar uma entidade, crie migration, model, repository interface, repository implementation, service, controller, form requests quando houver entrada de dados e testes basicos.

## Endpoints iniciais

- `POST /api/auth/register`
- `GET /api/auth/csrf-token`
- `POST /api/auth/login`
- `GET /api/me`
- `GET /api/dashboard`
- `POST /api/auth/logout`
- `GET /api/journey-profiles`
- `GET /api/journey-profiles/{id}`
- `GET /api/users/{user}/journeys`
