# Ticket 01: Autorizar resumo do sistema via permissão system.view

Taiga: #173
Status: ready-for-human

## Escopo implementado

1. Migration idempotente `2026_11_12_000002_add_system_view_permission.php` e atualização de `database/sql/jornada_fixed_data.sql` e `scripts/Script_Insert_Jornada.sql`.
2. Rota `GET /api/system-summary` usando `->middleware('permission:system.view')`.
3. Remoção de `authorizeAdministrator()` e `ADMIN_USER_TYPE` de `SystemSummaryService` e do tratamento legado em `SystemSummaryController`.
4. Atualização de `docs/openapi.yaml` e testes (`SystemSummaryTest`, `AccessProfilePermissionsTest`, `LegacyJornadaScriptsConsistencyTest`).
