# Totais de competências e áreas no detalhe do perfil

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/124
Taiga story ID: `9649426`
Status: ready-for-human

## Critérios de aceite

- `GET /journey-profiles/{id}` retorna `competencies_count` e `knowledge_areas_count`.
- `knowledge_areas_count` conta áreas distintas e inclui um grupo adicional quando há competências sem área.
- Perfis sem competências retornam ambos os totais zerados.
- A listagem e os demais campos do detalhe permanecem inalterados.
- OpenAPI e testes cobrem perfis com competências em áreas repetidas e perfis sem competências.

## Comments

- Implementados no detalhe os totais de competências únicas associadas ao perfil e de áreas de conhecimento distintas; perfis vazios retornam zero. A listagem continua sem os campos novos.
- O detalhe usa uma consulta dedicada, sem acrescentar subconsultas aos fluxos de seleção de perfil ou criação de jornada. Os identificadores das subconsultas são envolvidos pela gramática do driver; o total de áreas inclui o grupo de competências sem área quando presente, conforme a documentação OpenAPI.
- Testes: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=JourneyProfileTest` — 13 passaram, 160 assertions; PHPUnit mostrou avisos porque o worktree não tem `.env`.
- Qualidade: `composer quality` passou (Pint, PHPStan, PHPMD); `git diff --check` passou.
- OpenAPI: Redocly validou `docs/openapi.yaml` sem erros; 17 avisos preexistentes.
- HTTP manual de sucesso para GET autenticado não realizado: este worktree não tem servidor, credenciais de sessão ou banco de desenvolvimento local. A resposta foi exercitada pela rota Laravel nos testes de feature com SQLite em memória.
- Revisão do PR aplicada: os contadores agora são carregados apenas no detalhe por uma consulta própria; a gramática Laravel envolve identificadores para PostgreSQL e SQLite; competências sem área contam como um grupo adicional. A resposta da seleção de perfil e da criação de jornada continua sem os campos de contagem.
- Verificação da revisão: 45 testes Feature relacionados passaram em SQLite (364 assertions) e PostgreSQL 17 (364 assertions); suíte completa em PostgreSQL 17: 306 testes executados, 0 falhas, 1936 assertions. `composer quality` e lint OpenAPI passaram. PHPUnit emitiu warnings de leitura de `.env` ausente, sem falhas.
