# Cancelar jornada em andamento não atribuída

- Taiga ID: 9645333
- Taiga URL: https://tree.taiga.io/project/jotaf-dashboard/us/121
- Status: in-progress

## Critérios de aceite

- Criar `POST /api/journeys/{journey}/cancel` autenticado e protegido por CSRF.
- Permitir que o titular cancele somente jornada em andamento cuja `TP_JORNADA_ATRIBUIDA` seja `2` (iniciada por conta própria).
- Recusar jornadas atribuídas por administrador (`1`), com origem nula ou desconhecida, concluídas, já canceladas ou pertencentes a outra pessoa.
- Persistir estado/data de cancelamento próprios, sem marcar a jornada como concluída.
- Após cancelar, permitir iniciar nova jornada, preservando o limite de uma jornada em andamento.
- Documentar contrato e endpoint em OpenAPI.
- Cobrir autorização, origens, estados terminais e reinício com testes de API e banco.

## Comments

- Dependências confirmadas integradas à `main`: PR #79 grava `TP_JORNADA_ATRIBUIDA` na criação; PR #42 preserva a regra de uma jornada em andamento por usuário.
