# Expor a duração configurada da sessão

- Taiga ID: 9652593
- Taiga URL: https://tree.taiga.io/project/jotaf-dashboard/us/129
- Status: ready-for-human

## Critérios de aceite

- `GET /me` e `POST /auth/login` devolvem `meta.session.lifetime_minutes` igual a `config('session.lifetime')` sem alterar `data`.
- Uma requisição autenticada renova `sessions.last_activity`.
- OpenAPI documenta a metadata em ambas as rotas.
- Testes cobrem lifetime configurado diferente do valor padrão.

## Comments

Implementado em `feature/taiga-129-session-lifetime`. Ambos os endpoints adicionam `meta.session.lifetime_minutes`; o `data` permanece igual. O teste de login e /me verifica valor configurado 47 minutos. Um teste com session driver de banco confirma que GET `/api/me` atualiza `sessions.last_activity`.

Validação: `composer check` passou: 290 testes, 1794 assertions; Pint, PHPStan e PHPMD passaram. Teste focado de renovação também passou com 7 assertions.

Verificação HTTP manual em servidor local com SQLite isolado e `SESSION_LIFETIME=31`: `GET /api/auth/csrf-token` retornou 200; `POST /api/auth/login` com CSRF retornou sucesso e metadata 31; `GET /api/me` autenticado retornou sucesso e metadata 31. Só foram criadas sessões no banco temporário, que foi removido após a verificação.

