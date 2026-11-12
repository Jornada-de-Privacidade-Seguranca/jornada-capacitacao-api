# Ticket 01: Implementar listagem administrativa de resultados, métricas e endpoints auxiliares

Taiga: #158
Status: ready-for-human

## Escopo implementado

1. `AdminResultRepositoryInterface` + `AdminResultRepository` (registrado em `RepositoryServiceProvider.php`).
2. `AdminResultService` orquestrando listagem, métricas e listagens auxiliares de filtro.
3. `AdminResultFilterRequest` e `ListAdminResultsRequest` validando paginação e filtros combinados.
4. `AdminResultResource`, `AdminResultMetricsResource` e `UserWithResultsResource`.
5. `AdminResultController` + rotas em `routes/api.php` protegidas por `permission:results.view_all`.
6. Documentação OpenAPI 3.1 atualizada em `docs/openapi.yaml` e suíte `tests/Feature/AdminResultsTest.php`.
