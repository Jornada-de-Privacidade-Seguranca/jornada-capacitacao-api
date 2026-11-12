# Listagem de todos os resultados para o admin

- Taiga: [#158 - [BackEnd] Listagem de todos os resultados para o admin](https://tree.taiga.io/project/jotaf-dashboard/us/158)
- Status: ready-for-human

## Escopo

- `GET /admin/results`: listagem paginada de jornadas concluídas de todos os usuários ordenadas da mais recente para a mais antiga, com filtros combinados (`search`, `user_id`, `department`, `domains[]`, `journey_profile_id`, `ended_from`, `ended_to`).
- `GET /admin/results/metrics`: métricas agregadas com os mesmos filtros (total concluído, usuários distintos e média por domínio sem média geral).
- `GET /users/departments` e `GET /users/with-results`: endpoints auxiliares para preenchimento dos filtros.
- Todos os endpoints exigem autenticação e permissão `results.view_all`.
