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

## Testes e qualidade de codigo

Instale as dependencias do projeto antes de executar as validacoes locais:

```bash
composer install
```

Quando estiver usando Docker, execute os comandos dentro do container da aplicacao:

```bash
docker compose exec app composer check
```

### Testes

Execute os testes automatizados com:

```bash
composer test
```

Comando equivalente:

```bash
php artisan test
```

Os testes usam `APP_ENV=testing` e SQLite em memoria conforme configurado em `phpunit.xml`.

### Formatacao

Verifique a formatacao sem modificar arquivos:

```bash
composer quality:pint
```

Comando equivalente:

```bash
vendor/bin/pint --test
```

Para corrigir a formatacao localmente, use:

```bash
composer format
```

Comando equivalente:

```bash
vendor/bin/pint
```

Esse comando modifica arquivos e nao faz parte da validacao completa automatica.

### Analise estatica

Execute PHPStan com Larastan:

```bash
composer quality:phpstan
```

Comando equivalente:

```bash
vendor/bin/phpstan analyse
```

### Code smells

Execute PHPMD:

```bash
composer quality:phpmd
```

Comando equivalente:

```bash
vendor/bin/phpmd app text phpmd.xml
```

### Qualidade

Execute todas as verificacoes de qualidade:

```bash
composer quality
```

Esse comando executa Laravel Pint, PHPStan/Larastan e PHPMD.

### Validacao completa

Antes de abrir ou atualizar um Pull Request, execute:

```bash
composer check
```

Esse comando executa Laravel Pint, PHPStan/Larastan, PHPMD e os testes automatizados. Caso qualquer etapa falhe, o comando sera finalizado com erro e o problema devera ser corrigido antes do envio do codigo.

### Integracao continua

Todos os Pull Requests destinados a branch `main` executam automaticamente as verificacoes de qualidade e os testes pelo GitHub Actions.

As mesmas verificacoes podem ser executadas localmente antes do envio utilizando:

```bash
composer check
```

Relacao entre comandos locais e CI:

| Local | CI |
| --- | --- |
| `composer quality` | `Quality` |
| `composer test` | `Tests` |
| `composer check` | `Quality` + `Tests` |

O check `Quality` executa Laravel Pint, PHPStan/Larastan e PHPMD. O check `Tests` executa PHPUnit / Laravel Tests.

Os checks `Quality` e `Tests` devem estar configurados como obrigatorios para merge na branch `main`.

## Auditoria de dependencias

O GitHub Actions executa o check `Dependency Audit` em Pull Requests para `main`, em pushes para `main`, semanalmente e quando iniciado manualmente pela aba **Actions**. A auditoria usa `composer audit` sobre o `composer.lock`; dependencias de desenvolvimento tambem sao verificadas. Vulnerabilidades conhecidas fazem o check falhar.

Para executar localmente, instale o Composer 2 e PHP 8.3 ou superior e rode:

```bash
composer audit --locked --no-interaction
```

Para corrigir uma falha, atualize as dependencias afetadas dentro das restricoes de `composer.json` e atualize o lockfile:

```bash
composer update nome/do-pacote --with-all-dependencies
composer audit --locked --no-interaction
```

Se a atualizacao de um pacote exigir mudancas incompatíveis nas restricoes atuais, avalie a menor versao corrigida compativel antes de alterar o `composer.json`. Nao ignore avisos de seguranca para fazer o check passar. Dependabot verifica alertas e propoe atualizacoes semanais para dependencias Composer e GitHub Actions.

O check `Dependency Audit` deve ser obrigatorio para merge em `main`, junto de `Quality` e `Tests`.

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
