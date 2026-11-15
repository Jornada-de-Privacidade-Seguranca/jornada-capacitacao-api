# Ticket 01: Registrar autor da atribuição e permitir cancelamento pelo atribuidor

Taiga: #168
Status: ready-for-human

## Escopo implementado

1. Migration idempotente `2026_11_12_000000_add_assigned_by_to_journeys.php` adicionando `FK_USUARIO_ATRIBUIU` em `TB_JORNADA` (FK para `TB_USUARIO.PK_USUARIO`), acompanhada de atualização em `scripts/Script_Criacao_Jornada.sql` e `scripts/V1__Criação_Jornada.sql`.
2. Model `Journey` com `FK_USUARIO_ATRIBUIU` em `$fillable` e relacionamento `assignedBy()`.
3. `JourneyRepository` e `DashboardRepository` carregando `assignedBy` via eager loading.
4. `JourneyService::startForUser` persistindo `FK_USUARIO_ATRIBUIU` apenas em atribuições para terceiros, e `JourneyService::cancelForUser` aplicando as regras de cancelamento pelo autor da atribuição com permissão `journeys.assign`.
5. `JourneyListResource`, `JourneyDetailResource` e `DashboardJourneyResource` retornando `assigned_by` via trait `FormatsAssignedBy`.
6. Atualização de `docs/openapi.yaml` e suíte de testes em `tests/Feature/JourneyCancellationTest.php`.
