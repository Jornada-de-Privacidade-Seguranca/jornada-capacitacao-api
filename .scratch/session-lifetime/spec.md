# Tempo de vida da sessão disponível para o frontend

- Taiga: [#129 — Expor o tempo de vida da sessão para o aviso de expiração](https://tree.taiga.io/project/jotaf-dashboard/us/129)
- ID interno Taiga: 9652593
- Escopo: adicionar `meta.session.lifetime_minutes` em `GET /me` e `POST /auth/login`, validar renovação de sessão e documentar o contrato.
