# Scripts SQL da Jornada

Os arquivos `V1__` a `V6__` formam um pacote PostgreSQL, na ordem indicada pelo número:

1. `V1__Criação_Jornada.sql` cria o schema da Jornada.
2. `V2__Insert_Dominio_Jornada.sql` carrega domínios e demais dados de referência.
3. `V3__Inserts_competencias_privacidade.sql` carrega competências de Privacidade.
4. `V4__Inserts_competencias_seguranca_informacao.sql` carrega competências de Segurança da Informação.
5. `V5__Inserts_rl_perfis_competencias_privacidade.sql` relaciona perfis e competências de Privacidade.
6. `V6__Inserts_rl_perfis_competencias_seguranca_informacao.sql` relaciona perfis e competências de Segurança da Informação.

Execute os seis arquivos uma única vez e na ordem acima somente ao criar um banco fora do fluxo do Laravel. A V1 cria objetos sem IF NOT EXISTS, e os scripts de carga não são idempotentes.

No fluxo do Laravel, as migrations criam o schema e os dados de referência. A migration 2026_11_24_000000_populate_fixed_jornada_competencies.php também carrega as competências e os vínculos dos arquivos V3 a V6. Não execute V1 a V6 manualmente em um banco gerenciado por migrations.
