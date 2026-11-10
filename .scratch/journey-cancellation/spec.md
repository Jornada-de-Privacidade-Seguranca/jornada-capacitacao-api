# Cancelamento de jornada não atribuída por administrador

- Taiga: [#121 — Permitir cancelamento de jornada não atribuída por administrador](https://tree.taiga.io/project/jotaf-dashboard/us/121)
- ID interno Taiga: 9645333
- Dependência de criação/origem: [PR #79](https://github.com/Jornada-de-Privacidade-Seguranca/jornada-capacitacao-api/pull/79), integrado à `main`.
- Dependência de fluxo: Taiga #119 / PR #42, integrado à `main`.
- Escopo: permitir ao titular cancelar uma jornada em andamento criada por conta própria, persistindo cancelamento separado da conclusão.
- Os recursos de listagem de jornadas, detalhe de jornada e dashboard retornam `origin`: `self_started` (TP=2), `assigned` (TP=1) ou `null` (origem histórica nula/desconhecida).
- Jornadas com origem nula ou não reconhecida não são canceláveis.
