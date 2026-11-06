# Scripts SQL da Jornada

Os arquivos `V1__` a `V6__` formam um pacote PostgreSQL, na ordem indicada pelo número:

1. `V1__Criação_Jornada.sql` cria o schema da Jornada.
2. `V2__Insert_Dominio_Jornada.sql` carrega domínios e demais dados de referência.
3. `V3__Inserts_competencias_privacidade.sql` carrega competências de Privacidade.
4. `V4__Inserts_competencias_seguranca_informacao.sql` carrega competências de Segurança da Informação.
5. `V5__Inserts_rl_perfis_competencias_privacidade.sql` relaciona perfis e competências de Privacidade.
6. `V6__Inserts_rl_perfis_competencias_seguranca_informacao.sql` relaciona perfis e competências de Segurança da Informação.

Execute-os uma única vez e na ordem acima em um banco PostgreSQL preparado para receber um schema novo. A V1 cria objetos sem `IF NOT EXISTS`, e os scripts de carga não são idempotentes. O Laravel não executa esses arquivos: as migrations da aplicação ficam em `database/migrations`.
