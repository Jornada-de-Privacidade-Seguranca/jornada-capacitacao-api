# Ticket 01: Implementar busca, filtros e métricas por domínio nas jornadas do usuário

Taiga: #149
Status: ready-for-human

## Escopo

1. **Request & Validation:**
   - Expandir `ListUserJourneysRequest` para validar `search`, `knowledge_domain_id` e `domains`.
2. **Repository Layer:**
   - Atualizar `JourneyRepositoryInterface` e `JourneyRepository::paginatedByUser` para aplicar busca textual acento-insensível e filtro por domínio.
   - Adicionar método `getDomainMetricsForUser(int $userId): array` em `JourneyRepositoryInterface` e `JourneyRepository` com consultas agregadas eficientes.
3. **Service Layer:**
   - Atualizar `JourneyService` para orquestrar a listagem com os novos filtros e fornecer métricas agregadas por domínio.
   - Adicionar método `getDomainMetricsForUser(Usuario $authenticatedUser, int $userId): array` em `JourneyService`.
4. **Controller & Presentation:**
   - Atualizar `UserJourneyController::index` para incluir `domain_metrics` em `meta`.
   - Adicionar `UserJourneyController::metrics` para o endpoint dedicado `GET /api/users/{user}/journeys/metrics`.
   - Criar `UserDomainMetricsResource` para formatar a resposta das métricas.
5. **OpenAPI & Docs:**
   - Documentar os novos parâmetros e endpoints em `docs/openapi.yaml`.
6. **Testes Automatizados:**
   - Expandir `UserJourneyListTest` e criar testes específicos para métricas e filtros.
7. **Qualidade:**
   - Executar `composer check` (Pint, PHPStan, PHPMD, PHPUnit).
