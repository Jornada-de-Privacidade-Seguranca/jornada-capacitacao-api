# Convidar usuário e definir senha inicial

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/122 (`9645341`)

Status: ready-for-human

## Critérios de aceite

- Somente usuários com a permissão `users.register` podem criar convites; a conta convidada é sempre usuário comum (`FK_TIPO_USUARIO = 2`) e registra `TP_ATIVACAO = 2`.
- A conta fica inativa até o titular usar um convite único e expirável para definir a senha inicial.
- O token é armazenado somente como hash, não aparece em logs ou na resposta de criação, tentativas são limitadas e uma nova emissão invalida a anterior.
- Após a definição da senha, a conta é ativada como usuário comum sem aprovação administrativa adicional.
- A API trata e documenta e-mail já registrado e token inválido, expirado ou consumido; OpenAPI e testes cobrem os endpoints.
- A API não cria telas de frontend.

## Comments

- Escopo verificado contra a história Taiga #122 e coordenação com #120. #119 e #120 estão concluídas.
- O script SQL oficial já inclui `TP_ATIVACAO` e `DH_ATIVACAO`; não é necessário criar coluna nova na fonte de verdade. A migration Laravel de schema será sincronizada.
- Implementados `POST /admin/user-invitations`, `POST /auth/invitations/validate` e `POST /auth/invitations/complete`. O primeiro exige sessão, CSRF e permissão `users.register`; a criação e reemissão mantêm conta comum inativa, `TP_ATIVACAO = 2`, substituem o token anterior e enviam link ao `FRONTEND_URL`. A tabela existente `password_reset_tokens` guarda apenas o hash do token e a data de criação. A senha usa `Hash::make`; o consumo do token e a ativação (`ST_ATIVO = 1`, `DH_ATIVACAO`) ocorrem na mesma transação, preservando `FK_TIPO_USUARIO = 2` e `TP_ATIVACAO = 2`.
- Limites: 10 convites por minuto por usuário autenticado; 5 validações e 5 conclusões por minuto por IP/e-mail. Tokens inválidos, expirados e utilizados retornam a mesma resposta. O mailer `log` é recusado para impedir que o link secreto apareça nos logs. O Compose injeta `MAIL_HOST=mailpit` e `MAIL_PORT=1025` no app e expõe a interface local do Mailpit na porta 8025. A migration Laravel agora espelha `TP_ATIVACAO` e `DH_ATIVACAO` já presentes nos scripts SQL oficiais.
- Integração com a `main` atual: preservadas as permissões `users.view` e `users.deactivate` nas rotas administrativas e aplicado `users.register` ao convite; testes cobrem o acesso para perfil com essa permissão e o bloqueio para quem não a possui.
- Verificação desta correção já sincronizada com a `main`: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --filter=UserInvitationTest` — exit 0, 116 assertions e 12 warnings de `.env` ausente; `php artisan test` — exit 0, 2513 assertions e 391 warnings de `.env` ausente. Nenhum teste falhou. `composer quality` — Pint, PHPStan e PHPMD passaram; PHPMD emitiu aviso de depreciação do formato de CLI. `git diff --check` passou.
- Verificação manual HTTP não executada: o daemon Docker e um servidor SMTP/Mailpit não estavam disponíveis. A criação, reemissão, validação, expiração, consumo único, ativação, autorização, CSRF e limites foram cobertos por testes de feature com SQLite em memória e `Mail::fake()`.
- Aguardando revisão humana; não mesclar até a revisão.
