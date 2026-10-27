# Totais de competências e áreas no detalhe do perfil

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/124
Taiga story ID: `9649426`
Status: ready-for-human

## Critérios de aceite

- `GET /journey-profiles/{id}` retorna `competencies_count` e `knowledge_areas_count`.
- Perfis sem competências retornam ambos os totais zerados.
- A listagem e os demais campos do detalhe permanecem inalterados.
- OpenAPI e testes cobrem perfis com competências em áreas repetidas e perfis sem competências.

## Comments

- Implementados no detalhe os totais de competências únicas associadas ao perfil e de áreas de conhecimento distintas; perfis vazios retornam zero. A listagem continua sem os campos novos.
- Testes: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=JourneyProfileTest` — 13 passaram, 160 assertions; PHPUnit mostrou avisos porque o worktree não tem `.env`.
- Qualidade: `composer quality` passou (Pint, PHPStan, PHPMD); `git diff --check` passou.
- OpenAPI: Redocly validou `docs/openapi.yaml` sem erros; 17 avisos preexistentes.
- HTTP manual de sucesso para GET autenticado não realizado: este worktree não tem servidor, credenciais de sessão ou banco de desenvolvimento local. A resposta foi exercitada pela rota Laravel nos testes de feature com SQLite em memória.
