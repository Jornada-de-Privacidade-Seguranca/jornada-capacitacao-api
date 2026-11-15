# Gestão de usuários e perfis de acesso

- Taiga: [#136 - [BackEnd] Endpoints de gestão de usuários e perfil de acesso](https://tree.taiga.io/project/jotaf-dashboard/us/136)
- Status: ready-for-human

## Escopo

- `GET /users` (`users.view`): listagem paginada com busca por nome, e-mail ou matrícula e filtros por perfil, setor e situação (`active`, `pending_activation`, `inactive`).
- `GET /users/{user}` (`users.view`): detalhes do usuário incluindo `registration_number`.
- `PATCH /users/{user}` (`users.manage` + CSRF): atualização cadastral com validação de e-mail único.
- `PUT /users/{user}/access-profile` (`users.change_profile` + CSRF): alteração de perfil com gravação em `TH_ALTERACAO_PERFIS`, bloqueio de autoalteração (`SELF_PROFILE_CHANGE_FORBIDDEN`) e proteção do último administrador ativo (`LAST_ADMIN_REQUIRED`).
- `GET /users/{user}/access-profile-history` (`users.view`): histórico ordenado do mais recente para o mais antigo.
- `GET /access-profiles` (`users.view`): listagem de perfis e permissões (PT/EN).
- `DELETE /users/{user}` (`users.manage` + CSRF): exclusão lógica (`DH_EXCLUIDO_EM` + `ST_ATIVO = 0`), preservando jornadas e histórico, com bloqueio de autoexclusão e proteção do último administrador ativo (`LAST_ADMIN_REQUIRED`).
