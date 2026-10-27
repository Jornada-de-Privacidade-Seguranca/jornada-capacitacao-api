# Implementar busca de perfis de jornada

Taiga: [#123 - Permitir busca por nome na listagem de perfis de jornada](https://tree.taiga.io/project/jotaf-dashboard/us/123) (ID interno 9649412)

Status: ready-for-human

## Escopo

- Adicionar busca por nome e descrição PT/EN, insensível a caixa e acentos.
- Manter filtro em banco e paginação compatível com `page`/`per_page`.
- Atualizar documentação OpenAPI e testes feature.

## Comments

- Implementada busca no banco por `NO_PERFIS_PT`, `NO_PERFIS_EN`, `DS_PERFIS_PT` e `DS_PERFIS_EN`, insensível a maiúsculas/minúsculas e acentos, preservando paginação e filtros existentes.
- OpenAPI e testes feature atualizados. `php artisan test --filter=JourneyProfileTest`: passou (16 testes; PHPunit reportou avisos de leitura de `.env` ausente no worktree, sem falhas). `composer quality`: passou (Pint, PHPStan e PHPMD).
- Verificação HTTP local de `GET /api/journey-profiles?search=protecao` sem sessão: `401 UNAUTHENTICATED`, conforme esperado. Verificação autenticada pela rota real não foi possível sem sessão e dados locais; os cenários de busca foram exercitados via testes feature HTTP com SQLite em memória.
