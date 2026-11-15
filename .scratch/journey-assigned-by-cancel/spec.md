# Registrar quem atribuiu a jornada e permitir que essa pessoa a cancele

- Taiga: [#168 - [BackEnd] Registrar quem atribuiu a jornada e permitir que essa pessoa a cancele](https://tree.taiga.io/project/jotaf-dashboard/us/168)
- Status: ready-for-human

## Objetivo

1. Persistir o autor da atribuição (`FK_USUARIO_ATRIBUIU`) em `TB_JORNADA` quando um administrador/gestor com `journeys.assign` atribui uma jornada a outro usuário (`POST /users/{user}/journeys`), mantendo `null` em jornadas iniciadas pelo próprio titular ou registros legados.
2. Expor `assigned_by` (`{ id, name }` ou `null`) acompanhado de `origin` nos resources de listagem (`GET /users/{user}/journeys`, `POST /users/{user}/journeys`), detalhe (`GET /journeys/{journey}`) e dashboard (`GET /dashboard`), preservando o nome mesmo se o autor for desativado posteriormente.
3. Permitir em `POST /journeys/{journey}/cancel` o cancelamento de jornada atribuída em andamento exclusivamente pelo mesmo usuário que a atribuiu e que ainda possua a permissão `journeys.assign`, retornando `403 PERMISSION_DENIED` para outro administrador/gestor ou autor sem permissão, e `409 JOURNEY_CANCELLATION_NOT_ALLOWED` para titular ou jornada atribuída legada sem `FK_USUARIO_ATRIBUIU`.
