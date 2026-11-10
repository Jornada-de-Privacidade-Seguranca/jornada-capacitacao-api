# Cancelar jornada em andamento não atribuída

- Taiga ID: 9645333
- Taiga URL: https://tree.taiga.io/project/jotaf-dashboard/us/121
- Status: ready-for-human
- Pull request: https://github.com/Jornada-de-Privacidade-Seguranca/jornada-capacitacao-api/pull/80
- Reviewer: Gabriel Cesário (`Cezari0o`), requested

## Critérios de aceite

- Criar `POST /api/journeys/{journey}/cancel` autenticado e protegido por CSRF.
- Permitir que o titular cancele somente jornada em andamento cuja `TP_JORNADA_ATRIBUIDA` seja `2` (iniciada por conta própria).
- Recusar jornadas atribuídas por administrador (`1`), com origem nula ou desconhecida, concluídas, já canceladas ou pertencentes a outra pessoa.
- Persistir estado/data de cancelamento próprios, sem marcar a jornada como concluída.
- Após cancelar, permitir iniciar nova jornada, preservando o limite de uma jornada em andamento.
- Expor `origin` na listagem, detalhe e dashboard: `self_started` para TP=2, `assigned` para TP=1 e `null` para origem nula/desconhecida.
- Documentar contrato e endpoint em OpenAPI.
- Cobrir autorização, origens, estados terminais e reinício com testes de API e banco.

## Comments

- Dependências confirmadas integradas à `main`: PR #79 grava `TP_JORNADA_ATRIBUIDA` na criação; PR #42 preserva a regra de uma jornada em andamento por usuário.
- Implementado e aberto para revisão no PR #80; Taiga está em Needs Review e permanece desbloqueada.
- Validação: suíte completa com 2.397 assertions e sem falhas (379 warnings ambientais porque `.env` não existe); Pint, PHPStan, PHPMD, `git diff --check` e parse de `docs/openapi.yaml` passaram.
- Testes afetados: 48 testes / 359 assertions, sem falhas.
- Comentário do reviewer resolvido no PR: `origin` agora aparece nos três recursos e tem testes para `self_started`, `assigned` e `null`.
- Verificação HTTP manual não executada: Docker não está disponível nesta sessão; as rotas foram exercitadas pela suíte Feature usando SQLite em memória.
