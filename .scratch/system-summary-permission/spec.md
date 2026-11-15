# Autorizar o resumo do sistema pelas permissões do perfil

- Taiga: [#173 - [BackEnd][Bug] Autorizar o resumo do sistema pelas permissões do perfil](https://tree.taiga.io/project/jotaf-dashboard/t/173)
- Status: ready-for-human

## Escopo

- Criar a permissão `system.view` ("Ver o resumo do sistema" / "View the system summary") via migration idempotente e nos scripts de dados fixos (`database/sql/jornada_fixed_data.sql` e `scripts/Script_Insert_Jornada.sql`), atribuída exclusivamente ao perfil Administrador (1).
- Proteger `GET /api/system-summary` com `->middleware('permission:system.view')`.
- Remover `authorizeAdministrator` e `ADMIN_USER_TYPE` de `SystemSummaryService`.
- Atualizar `docs/openapi.yaml` e suítes de teste (`SystemSummaryTest`, `AccessProfilePermissionsTest`, `LegacyJornadaScriptsConsistencyTest`).
