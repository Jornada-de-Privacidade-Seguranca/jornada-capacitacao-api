# Gestão administrativa de contas de usuários

Taiga: https://tree.taiga.io/project/jotaf-dashboard/us/120 (`9645278`)

Administradores precisam revisar cadastros pendentes, ativar contas como usuários comuns e desativar contas comuns ativas. As rotas devem restringir os dados a contas pendentes (`ST_ATIVO = 2`, sem tipo de usuário), aplicar as mudanças de estado exigidas pela jornada, preservar o tipo comum ao desativar e impedir transições inválidas. A API mantém autenticação por sessão, proteção CSRF para mutações, paginação e o contrato padrão de respostas.
