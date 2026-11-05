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
- Apos realizar qualquer tarefa que crie ou altere uma funcionalidade, criar ou atualizar testes automatizados para cobrir a feature afetada seguindo as regras de `Tests.MD`.
- Ao concluir tarefa que crie ou altere endpoints, quando for tecnicamente possivel e seguro, alem dos testes automatizados, executar testes manuais chamando os endpoints afetados pela rota HTTP real da aplicacao e verificar individualmente todos os endpoints criados ou alterados na tarefa, conforme `Tests.MD`.
- Nao commitar `.env`.
- Nao alterar banco de producao.
- Nao executar comandos destrutivos.
- Neste projeto, a regra pessoal de nao executar testes sem autorizacao previa nao se aplica a testes automatizados que usem exclusivamente o ambiente `testing` e banco de teste isolado, conforme `Tests.MD`.
- Antes de rodar testes neste projeto, qualquer IA deve procurar containers Docker existentes do projeto e preferir executar os testes no container da aplicacao quando ele estiver disponivel.
- Ao rodar testes no container, forcar explicitamente as variaveis de teste quando o container estiver configurado para ambiente local ou banco persistente, conforme `Tests.MD`.
- Sempre que agentes modificarem codigo, alem dos testes aplicaveis, devem executar os criterios de qualidade configurados no projeto, preferencialmente via `composer quality` ou `composer check`.
- Nao remover arquivos do frontend sem autorizacao.
- Nao modificar a arquitetura do frontend; integrar via API somente quando necessario.
- Antes de alterar qualquer endpoint, leia docs/api-contract.md. Todo código de API deve seguir obrigatoriamente o contrato descrito nesse documento.

## Agent skills

### Issue tracker

Taiga é a referência externa; specs e tickets locais ficam em `.scratch/`. Não criar issues no GitHub. Veja `docs/agents/issue-tracker.md`.

### Git workflow

Trabalhar em branch de tarefa. Push é permitido em branches de tarefa, mas nunca em `main`, `master` ou `develop`. Veja `docs/agents/git-workflow.md`.

- Após confirmar que uma branch foi integrada por merge em `main`, remover a branch local e a worktree/pasta local correspondente. Antes da remoção, verificar e preservar qualquer alteração não commitada ou arquivo não rastreado; nunca apagar conteúdo local sem backup ou confirmação.

### Triage labels

Use `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human` e `wontfix`. Veja `docs/agents/triage-labels.md`.

### Domain docs

Layout single-context, com `GLOSSARY.md` na raiz e ADRs em `docs/adr/`. Veja `docs/agents/domain.md`.

## Segurança de autenticação por cookie e CSRF

Quando a aplicação utilizar autenticação baseada em sessão/cookie:

* Rotas autenticadas somente de leitura, como `GET`, não exigem proteção CSRF.
* Antes de criar ou alterar qualquer rota autenticada mutável (`POST`, `PUT`, `PATCH` ou `DELETE`), verificar obrigatoriamente se existe proteção CSRF adequada.
* Não criar rotas mutáveis autenticadas por cookie utilizando apenas `StartSession` ou equivalente sem proteção CSRF.
* Preferir os mecanismos nativos do Laravel/Sanctum ou a solução já adotada pelo projeto, evitando implementações proprietárias de CSRF.
* Caso uma nova rota mutável autenticada por cookie seja necessária e a proteção CSRF ainda não esteja configurada, apresentar o problema e a solução proposta antes de implementar a rota.
* Não remover ou desabilitar proteção CSRF apenas para fazer uma rota funcionar.
* Caso o modelo de autenticação seja alterado de cookie/session para Bearer Token, reavaliar esta regra conforme o novo modelo de autenticação.
