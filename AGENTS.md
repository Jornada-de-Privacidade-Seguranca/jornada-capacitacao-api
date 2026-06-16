# AGENTS.md

## Regras do backend

- Manter a arquitetura MVC + Services + Repositories.
- Controllers nao devem conter regra de negocio complexa.
- Controllers nao devem acessar banco diretamente.
- Services devem orquestrar regras de negocio e usar repositories.
- Repositories devem encapsular acesso a dados.
- Toda nova entidade deve ter migration, model, repository interface, repository implementation, service, controller, form requests quando houver entrada de dados e testes basicos.
- Registrar bindings de repositories em `app/Providers/RepositoryServiceProvider.php`.
- Manter `docs/openapi.yaml` atualizado quando endpoints publicos forem criados ou alterados.
- Nao commitar `.env`.
- Nao alterar banco de producao.
- Nao executar comandos destrutivos.
- Nao remover arquivos do frontend sem autorizacao.
- Nao modificar a arquitetura do frontend; integrar via API somente quando necessario.
- Antes de alterar qualquer endpoint, leia docs/api-contract.md. Todo código de API deve seguir obrigatoriamente o contrato descrito nesse documento.
