# Busca, filtro por domínio e métricas por domínio nos resultados do usuário

Taiga: [#149 - [BackEnd] Busca, filtro por domínio e métricas por domínio nos resultados do usuário](https://tree.taiga.io/project/jotaf-dashboard/us/149)

Status: in-progress

## Objetivo

Permitir que a consulta de resultados e jornadas do usuário no backend ofereça:
1. **Busca textual (`search`)**: Filtrar os resultados por termos relevantes (nome do perfil de jornada em português ou inglês, descrição do perfil, ou identificador numérico da jornada).
2. **Filtro por domínio (`knowledge_domain_id` ou `domains`)**: Filtrar os registros de jornadas pelo domínio de conhecimento associado ao perfil da jornada (`TB_DOMINIO_CONHECIMENTO`).
3. **Métricas por domínio**: Fornecer métricas agregadas de desempenho e evolução divididas por domínio de conhecimento para o usuário avaliado, disponíveis tanto como metadados agregados na listagem (`meta.domain_metrics`) quanto em endpoint dedicado (`GET /api/users/{user}/journeys/metrics`).

## Critérios de Aceitação

- `GET /api/users/{user}/journeys`:
  - Aceita `search` (string, max 255) e filtra jornadas que correspondam ao ID ou ao perfil (nome/descrição em PT/EN) sem diferenciar caixa ou acentos.
  - Aceita `knowledge_domain_id` (inteiro existente) e/ou `domains` (array de inteiros existentes) e filtra apenas jornadas pertencentes ao(s) domínio(s) informado(s).
  - Mantém paginação correta com contagem de total ajustada aos filtros combinados.
  - Retorna em `meta.domain_metrics` as métricas agregadas por domínio do usuário (total de jornadas, concluídas, em andamento, pontuação média, pontuação máxima, pontuação mais recente, competências adquiridas e taxa de aquisição).
- `GET /api/users/{user}/journeys/metrics`:
  - Endpoint dedicado para retornar o resumo de métricas gerais e divididas por domínio de conhecimento do usuário.
  - Respeita regras de autorização: o próprio usuário ou administradores podem consultar; usuários comuns não podem consultar métricas de outros usuários (403); usuários não autenticados recebem 401.
- Documentação OpenAPI 3.1 atualizada em `docs/openapi.yaml`.
- Cobertura completa de testes automatizados unitários e de integração (PHPUnit) sobre banco em memória.
- Conformidade total com verificações de qualidade (`pint`, `phpstan`, `phpmd`).
