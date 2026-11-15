# Convite de usuário e definição de senha inicial

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/122 (`9645341`)

Administradores convidam novos usuários pelo e-mail. A conta nasce inativa como usuário comum e mantém `TP_ATIVACAO = 2` para registrar criação administrativa. O convite permite validar um token único, expirável e armazenado somente como hash; ao definir a senha inicial, a conta é ativada sem segunda aprovação administrativa.

O token usa a tabela existente `password_reset_tokens`, que indexa os registros por `email`, armazena `token` e `created_at`. O schema de `TB_USUARIO` da migration Laravel será alinhado às colunas `TP_ATIVACAO` e `DH_ATIVACAO` já presentes nos scripts SQL oficiais. A ativação por convite preenche `DH_ATIVACAO` diretamente, pois o trigger legado só a define quando `TP_ATIVACAO` muda para `1`, enquanto a origem administrativa deve permanecer `2`.

O frontend que recebe o link de definição de senha permanece fora do escopo.
