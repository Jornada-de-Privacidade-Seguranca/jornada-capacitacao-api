# Ticket 01: Implementar endpoints de gestão de usuários e perfis de acesso

Taiga: #136
Status: ready-for-human

## Escopo implementado

1. Migration idempotente `2026_11_12_000001_add_deleted_at_to_tb_usuario.php` adicionando `DH_EXCLUIDO_EM` em `TB_USUARIO`, scripts SQL atualizados e `SoftDeletes` em `Usuario` (mantendo `withTrashed()` em relacionamentos de `Journey` e `ProfileTypeChange`).
2. `UserRepository` e `AccessProfileRepository` (registrado em `RepositoryServiceProvider.php`).
3. `UserManagementService` com transações, locks e regras de negócio (autoalteração, autoexclusão e proteção do último administrador ativo `LAST_ADMIN_REQUIRED`).
4. `UserManagementController`, `AccessProfileController`, Requests e Resources seguindo `ApiResponse`.
5. Rotas registradas em `routes/api.php` com middlewares de sessão, CSRF e permissões (`users.view`, `users.manage`, `users.change_profile`).
6. `docs/openapi.yaml` e `tests/Feature/UserAccessProfileManagementTest.php`.
