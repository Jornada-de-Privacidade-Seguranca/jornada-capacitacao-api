# Tests.MD

## Regras gerais

- Toda tarefa que crie ou altere uma funcionalidade deve incluir teste automatizado cobrindo o comportamento novo ou modificado.
- Priorizar testes de Feature/end-to-end de API em `tests/Feature`, exercitando a aplicacao pelo endpoint HTTP publico ou interno correspondente.
- Manter testes pequenos, legiveis e focados em comportamento observavel pelo usuario da API.
- Nao depender de dados existentes no banco local, staging ou producao.
- Nao usar `.env` real nos testes.
- Nao fazer chamadas externas reais em testes. Integre servicos externos com fakes, mocks ou drivers de teste.
- Cada teste deve preparar seus proprios dados com factories, seeders especificos de teste ou payloads explicitos.
- Evitar testar detalhes internos de Controllers, Services ou Repositories quando um teste de API consegue validar o fluxo completo.

## Banco de testes

- Todos os testes devem rodar com `APP_ENV=testing`.
- O banco de testes deve ser isolado do banco de desenvolvimento e de producao.
- Por padrao, usar a configuracao de `phpunit.xml`, atualmente com SQLite em memoria:
  - `DB_CONNECTION=sqlite`
  - `DB_DATABASE=:memory:`
- Testes que acessam banco devem usar `Illuminate\Foundation\Testing\RefreshDatabase`.
- Nunca apontar testes para banco de producao.
- Nunca executar comandos destrutivos contra bancos que nao sejam explicitamente de teste.
- Neste projeto, agentes podem executar testes automatizados sem autorizacao previa quando eles usarem exclusivamente o ambiente `testing` e banco de teste isolado.
- Antes de executar testes, qualquer IA deve procurar containers Docker existentes do projeto, por exemplo com `docker ps` ou `docker compose ps`, e preferir rodar `php artisan test` no container da aplicacao quando ele estiver disponivel.
- Ao rodar testes no container da aplicacao, forcar explicitamente `APP_ENV=testing`, `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:` quando as variaveis do container apontarem para ambiente local ou banco persistente.

## Testes para funcionalidades que alteram banco

- Se a nova funcionalidade cria, atualiza ou remove registros, o teste deve validar a resposta da API e tambem o efeito no banco.
- Para inserts, sempre usar `assertDatabaseHas` para confirmar que o registro foi persistido com os campos relevantes.
- Para updates, validar a resposta e usar `assertDatabaseHas` com os novos valores.
- Para deletes fisicos, validar a resposta e usar `assertDatabaseMissing`.
- Para soft deletes, validar a resposta e usar `assertSoftDeleted`.
- Quando houver relacionamento entre entidades, validar tambem as chaves estrangeiras ou registros de tabela pivo relevantes.
- Quando houver regras de unicidade, criar teste para impedir duplicidade.
- Quando houver validacao de entrada, criar teste cobrindo payload invalido e mensagens/estrutura de erro esperadas pelo contrato da API.

## Testes para funcionalidades sem alteracao de banco

- Se a funcionalidade nao altera banco, o teste deve focar em assertions de retorno.
- Validar status HTTP, estrutura JSON, campos obrigatorios, tipos ou valores relevantes.
- Para listagens, validar filtros, ordenacao, paginacao e formato da colecao quando aplicavel.
- Para endpoints de consulta, validar casos de sucesso e casos de recurso nao encontrado.
- Para regras condicionais, cobrir ao menos o caminho principal e um caminho alternativo significativo.

## Padrao de teste end-to-end de API

- Fazer a requisicao usando helpers como `getJson`, `postJson`, `putJson`, `patchJson` ou `deleteJson`.
- Validar o status HTTP esperado com assertions explicitas como `assertOk`, `assertCreated`, `assertNoContent`, `assertNotFound` ou `assertUnprocessable`.
- Validar que o JSON respeita `docs/api-contract.md` e, quando endpoint publico for criado ou alterado, `docs/openapi.yaml`.
- Usar payloads realistas, com nomes de campos iguais aos aceitos pela API.
- Evitar assertions frageis contra resposta inteira quando a ordem ou campos secundarios nao forem parte do contrato.
- Preferir `assertJsonPath`, `assertJsonStructure`, `assertJsonCount` e assertions de banco para deixar claro o comportamento esperado.
- Testar autenticacao/autorizacao quando o endpoint exigir usuario, permissao ou papel especifico.
- Testar codigos de erro importantes, especialmente validacao, nao encontrado, nao autorizado e proibido.

## Organizacao

- Nomear testes pelo comportamento: `test_it_creates_a_competency`, `test_it_rejects_invalid_payload`.
- Agrupar testes da mesma feature no mesmo arquivo em `tests/Feature`.
- Criar factories para entidades persistidas que aparecam em mais de um teste.
- Evitar compartilhar estado entre testes.
- Nao depender da ordem de execucao dos testes.
- Remover duplicacao excessiva com pequenos helpers privados no proprio arquivo de teste quando isso melhorar a leitura.

## Execucao obrigatoria

- Antes de concluir uma tarefa, executar ao menos os testes afetados pela mudanca.
- Quando a mudanca tocar comportamento compartilhado ou infraestrutura, executar a suite completa com:

```bash
php artisan test
```

- Se nao for possivel executar os testes, registrar claramente o motivo e quais testes deveriam ser executados.
