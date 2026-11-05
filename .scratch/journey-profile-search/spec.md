# Busca nos perfis de jornada

Taiga: [#123 - Permitir busca por nome na listagem de perfis de jornada](https://tree.taiga.io/project/jotaf-dashboard/us/123) (ID interno 9649412)

## Objetivo

Permitir que o catálogo paginado de perfis de jornada filtre por nome ou descrição em português ou inglês, sem diferenciar caixa ou acentos.

## Critérios

- `GET /journey-profiles` aceita `search` e combina o filtro com `page` e `per_page`.
- A paginação informa o total já filtrado.
- Sem `search`, a listagem mantém o comportamento atual.
- OpenAPI e testes cobrem busca com resultados, busca sem resultados e paginação.
