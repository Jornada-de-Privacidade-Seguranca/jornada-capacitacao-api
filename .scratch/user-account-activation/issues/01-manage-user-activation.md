# Gerenciar ativação e desativação de usuários

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/120

Taiga story ID: `9645278`
Status: ready-for-human

## Objetivo

Permitir que administradores consultem cadastros pendentes, ativem contas pendentes como usuários comuns e desativem contas comuns ativas.

## Critérios de aceite

- Listar de forma paginada apenas contas com `ST_ATIVO = 2` e `FK_TIPO_USUARIO IS NULL`.
- Ativar uma conta pendente definindo `ST_ATIVO = 1` e `FK_TIPO_USUARIO = 2`.
- Desativar somente usuário comum ativo definindo `ST_ATIVO = 2` e mantendo `FK_TIPO_USUARIO = 2`.
- Rejeitar requisições sem autenticação, por usuários comuns, para contas ausentes ou em estado incompatível, sem alteração da conta em caso de erro.
- Manter a resposta no contrato da API, paginação padrão, proteção CSRF e documentação OpenAPI.

## Comments

- Implementadas rotas administrativas para listar contas pendentes, ativar usuário comum e desativar usuário comum ativo. A listagem filtra `ST_ATIVO = 2` com `FK_TIPO_USUARIO IS NULL`, usa paginação padrão; as mutações exigem sessão autenticada e CSRF. Erros de acesso, usuário ausente e estado incompatível seguem o contrato da API.
- Testes: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=UserAccountAdministrationTest` — 6 testes passaram, 49 assertions.
- Qualidade: `composer quality` passou (Pint, PHPStan, PHPMD). `git diff --check` passou.
- OpenAPI: `npx @redocly/cli lint docs/openapi.yaml --skip-rule security-defined --format summary` validou o documento; 18 avisos preexistentes permanecem. Sem `--skip-rule`, o lint também aponta quatro erros de operações antigas sem `security` definida; as três operações novas têm `sessionAuth` configurado.
- Verificação HTTP em servidor local e SQLite isolado de teste: registro de usuário via `POST /auth/register` (201), listagem autenticada `GET /admin/users/pending-activation` (200), ativação `POST /admin/users/{id}/activate` (200), desativação `POST /admin/users/{id}/deactivate` (200) e confirmação de que usuário desativado não reaparece na fila. Os dados ficaram somente no banco isolado de teste, removido após a verificação.
- Estado: ready-for-human.
