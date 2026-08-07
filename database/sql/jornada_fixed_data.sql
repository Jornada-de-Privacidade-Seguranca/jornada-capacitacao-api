-- Insert TB_DOMINIO_CONHECIMENTO
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN") VALUES ('Privacidade','Privacy');
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN") VALUES ('Segurança da Informação','Information Security');
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN") VALUES ('Conjunta (Privacidade/Segurança)','Joint (Privacy/security)');

-- Insert TB_PERFIS_JORNADA
INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (1,
'Pursuant to Article 41 of the LGPD [Law No. 13.709], the data controller must appoint an officer responsible for personal data processing, referred to as the "Data Protection Officer" (DPO). In carrying out their duties, the DPO can play a significant role in fostering and disseminating a culture of personal data protection within the organization—for instance, by receiving and acting upon requests from data subjects and the National Data Protection Authority (ANPD), or by guiding employees and contractors on the practices to be adopted regarding personal data protection.'
,'Conforme o artigo 41 da LGPD [LEI Nº13.709], o controlador de dados deverá indicar um encarregado pelo tratamento de dados pessoais, denominado encarregado. No exercício de suas atribuições, o encarregado pode desempenhar um importante papel para fomentar e disseminar a cultura da proteção de dados pessoais na organização, como, por exemplo, ao receber solicitações de titulares e da Agência Nacional de Proteção de Dados (ANPD) e adotar providências ou, ainda, ao orientar funcionários e contratados a respeito das práticas a serem tomadas em relação à proteção de dados pessoais.'
,'Encarregado pelo Tratamento de Dados Pessoais', 'Data Protection Officer');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (1,
'The General Personal Data Protection Law [Law No. 13,709] defines the "controller," in Article 5, item VI, as the "natural person or legal entity, of public or private law, responsible for decisions regarding the processing of personal data."'
,'A Lei Geral de Proteção de Dados Pessoais [LEI Nº13.709], define como controlador, em seu Art. 5º, inciso VI, a “pessoa natural ou jurídica, de direito público ou privado, a quem competem as decisões referentes ao tratamento de dados pessoais”.'
,'Controlador', 'Controller');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (1,
'In accordance with the General Personal Data Protection Law [Law No. 13,709], the Processor is defined as: a natural or legal person, under public or private law, that carries out the processing of personal data on behalf of the controller.'
,'De acordo com a Lei Geral de Proteção de Dados Pessoais [LEI Nº13.709], o Operador é definido como: Pessoa natural ou jurídica, de direito público ou privado, que realiza o tratamento de dados pessoais em nome do controlador.'
,'Operador', 'Processor');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (1,
'In accordance with the General Personal Data Protection Law [Law No. 13,709] and the National Data Protection Authority (ANPD), the personal data subject is the natural person to whom the personal data undergoing processing relates. In other words, it is the individual who can be identified, directly or indirectly, through their data.'
,'De acordo com a Lei Geral de Proteção de Dados Pessoais [LEI Nº13.709] e a Agência Nacional de Proteção de Dados (ANPD), o titular dos dados pessoais é a pessoa natural (ou física) a quem se referem os dados pessoais que são objeto de tratamento. Ou seja, é o indivíduo que pode ser identificado, direta ou indiretamente, por meio dos seus dados.'
,'Titular', 'Data Subject');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (1,
'The team mentioned in the Privacy Governance Program Development Guide. Its objective is to support the Data Protection Officer in their activities.'
,'Equipe mencionada no Guia de Elaboração de Programa de Governança de Privacidade. Possui como objetivo o apoio ao Encarregado pelo Tratamento de Dados em suas atividades.'
,'Equipe de Apoio ao Encarregado', 'Data Protection Officer Support Team');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Pursuant to Decree No. 12,572 of August 4, 2025, the Information Security Manager shall be designated by the federal public administration agency or entity within its scope of operations.
Additionally, the Information Security Manager is part of the governance structure of the Privacy and Information Security Program (PPSI), in accordance with Article 6, item II, of Ordinance SGD/MGI No. 852 of March 28, 2023.'
,'Conforme o Decreto N° 12.572, de 4 de Agosto de 2025, o Gestor de Segurança da Informação será designado pelo órgão ou entidade da administração pública federal, em seu âmbito de atuação.
Adicionalmente, o Gestor de Segurança da Informação compõe a estrutura de governança do Programa de Privacidade e Segurança da Informação (PPSI), conforme Art. 6°, inciso II da Portaria SGD/MGI nº 852, de 28 de março de 2023. '
,'Gestor de Segurança da Informação', 'Information Security Manager');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'The agent responsible for managing information assets shall be designated by the Information Security Manager, in accordance with Article 8 of Normative Instruction GSI/PR No. 3, dated May 28, 2021:
“Article 8. It is the responsibility of the information security manager of each agency or entity to coordinate the process of mapping information assets, as well as to designate an agent responsible for managing information assets from among the agency’s or entity’s permanent staff.”'
,'O Agente responsável pela gestão dos ativos de informação será designado pelo Gestor de Segurança da Informação, conforme o Art. 8º da IN GSI/PR N°3, de 28 de maio de 2021: 
“Art. 8º Cabe ao gestor de segurança da informação de cada órgão ou entidade coordenar o processo de mapeamento de ativos de informação, bem como designar um agente responsável pela gestão dos ativos de informação, dentre os servidores efetivos do órgão ou da entidade.”'
,'Agente Responsável pela Gestão dos Ativos de Informação', 'Agent Responsible for Managing Information Assets');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Pursuant to Article 8 of IN GSI/PR No. 3, dated May 28, 2021:
“It is the responsibility of the information security manager of each agency or entity to coordinate the information asset mapping process, as well as to designate an agent responsible for managing information assets from among the agency’s or entity’s permanent staff.”'
,'Conforme o Art. 8° da IN GSI/PR N°3, de 28 de maio de 2021: 
“Cabe ao gestor de segurança da informação de cada órgão ou entidade coordenar o processo de mapeamento de ativos de informação, bem como designar um agente responsável pela gestão dos ativos de informação, dentre os servidores efetivos do órgão ou da entidade.”'
,'Agente Responsável pela Gestão de Mudança nos Aspectos de Segurança da Informação', 'Agent Responsible for Managing Change Regarding Information Security Aspects');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Pursuant to Art. 16 of IN GSI/PR No. 3 of May 28, 2021, the Information Security Manager shall designate the agent responsible for information security risk management.'
,'Conforme o Art. 16 da IN GSI/PR N°3, de 28 de maio de 2021, o Gestor de Segurança da Informação deverá designar o Agente responsável pela gestão de riscos de segurança da informação.'
,'Agente Responsável pela Gestão de Riscos de Segurança da Informação', 'Agent Responsible for Information Security Risk Management');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Profile in accordance with Art. 27 of IN GSI/PR No. 3, dated May 28, 2021.'
,'Perfil conforme Art. 27 da IN GSI/PR N°3, de 28 de maio de 2021. '
,'Agente Responsável pela Gestão de Continuidade de Negócios em Segurança da Informação', 'Agent Responsible for Information Security Business Continuity Management');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'In accordance with Normative Instruction No. 6 of December 23, 2021, the agent responsible for the safe use of social media shall be designated by the Information Security Manager. Additionally, the agent responsible for the safe use of social media shall be part of the team tasked with drafting and revising the normative act regarding the safe use of social media.'
,'De acordo com a IN N°6, de 23 de Dezembro de 2021, o Agente responsável pelo uso seguro de mídias sociais será designado pelo Gestor de Segurança da Informação. Adicionalmente, o agente responsável pelo uso seguro de mídias sociais fará parte da equipe para elaboração e revisões do ato normativo sobre uso seguro de mídias sociais.'
,'Agente Responsável pelo uso Seguro de Mídias Sociais', 'Agent Responsible for the Safe use of Social Media');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Profile in accordance with IN GSI/PR No. 3 of May 28, 2021.'
,'Perfil conforme IN GSI/PR nº 3, de 28 de maio de 2021.'
,'Agente Responsável pela Avaliação de Conformidade', 'Agent Responsible for the Conformity Assessment');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Profile in accordance with Normative Instruction No. 6 of December 23, 2021.'
,'Perfil conforme IN N°6, de 23 de Dezembro de 2021. '
,'Equipe de Administração e de Gestão de Perfis Institucionais em Mídias Sociais', 'Institutional Social Media Profile Administration and Management Team');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'In accordance with Normative Instruction No. 1 of May 27, 2020, a Cyber ​​Incident Prevention, Handling, and Response Team (ETIR) must be established and implemented by agencies and entities of the direct and indirect federal public administration, as indicated in Article 15:
IV - Establish and implement a Cyber ​​Incident Prevention, Handling, and Response Team (ETIR), which shall constitute the network of teams—comprising agencies and entities of the federal public administration—coordinated by the Government Cyber ​​Incident Handling and Response Center of the Institutional Security Office of the Presidency of the Republic.'
,'Conforme a IN Nº 1 de 27 de Maio de 2020, a Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos (ETIR) deverá ser instituída e implementada pelos órgãos e entidades da administração pública federal, direta e indireta, conforme indicado no Art.15: 
IV - Instituir e implementar Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos - ETIR, que constituirá a rede de equipes, integrada pelos órgãos e pelas entidades da administração pública federal, coordenada pelo Centro de Tratamento e Resposta a Incidentes Cibernéticos de Governo do Gabinete de Segurança Institucional da Presidência da República.'
,'Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos (ETIR)', 'Cyber ​​Incident Prevention, Handling, and Response Team (ETIR)');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (2,
'Normative Instruction No. 2 of February 5, 2013, defines the Security and Accreditation Manager as:
“The person responsible for the security of information classified at any level of secrecy within Registration Bodies and Control Posts.”'
,'A Instrução Normativa N° - 2, de 5 de fevereiro de 2013 define o Gestor de Segurança e Credenciamento como: 
“Responsável pela segurança da informação classificada em qualquer grau de sigilo nos Órgãos de Registro e Postos de Controle”.'
,'Gestor de Segurança e Credenciamento', 'Security and Accreditation Manager');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'In accordance with Ordinance SGD/MGI No. 852 of March 28, 2023, the Information and Communication Technology Manager is part of the PPSI governance structure within each agency and entity of the direct, autonomous, and foundational federal public administration.
Additionally, the Privacy and Information Security Program (PPSI) Framework Guide states that the IT Manager constitutes the first line of defense, representing the primary structure responsible for PPSI governance.'
,'De acordo com a Portaria SGD/MGI nº 852, de 28 de março de 2023, o Gestor de Tecnologia da Informação e Comunicação compõe a Estrutura de Governança do PPSI em cada órgão e entidade da administração pública federal direta, autárquica e fundacional.  
Adicionalmente, o Guia do Framework do Programa de Privacidade e Segurança da Informação informa que o Gestor de TI compõe a primeira linha de defesa, representando a estrutura principal responsável pela governança do PPSI.'
,'Gestor de Tecnologia da Informação e Comunicação', 'Information and Communication Technology Manager');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'It is understood that the profile of the Information Technology Team consists of a group of professionals who carry out their activities to support institutional business processes, through the use of information technology, carrying out actions to enable and ensure the availability, integrity, confidentiality and authenticity of information.
Their responsibilities include hardware and software management, data protection, technical support, implementation of new solutions and alignment of technology with institutional strategic objectives.'
,'Entende-se que o perfil da Equipe de Tecnologia da Informação consiste em conjunto de profissionais que exercem suas atividades para apoiar os processos de negócios institucionais, por meio do uso da tecnologia da informação, realizando ações para viabilizar e assegurar a disponibilidade, a integridade, a confidencialidade e a autenticidade das informações.
 As suas responsabilidades incluem a gestão de hardware e software, a proteção de dados, o suporte técnico, a implementação de novas soluções e alinhamento da tecnologia com os objetivos estratégicos institucionais. '
,'Equipe de Tecnologia da Informação', 'Information Technology Team');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'Senior management in public sector bodies is understood to comprise individuals in leadership and strategic decision-making positions who represent the public administration and are responsible for guiding privacy and data protection policy. This includes:
1.Ministers of State and Secretaries, Presidents, Vice-Presidents, Rectors, Vice-Rectors, Pro-Rectors, Armed Forces Commanders, and the Information Security Committee.
2.Directors, Deputy Directors, Superintendents, Undersecretaries, and General Coordinators.
3.Chiefs of Staff, Deputy Chiefs of Staff, and Advisors.
4.Internal Control Advisor and Ombudsman.'
,'Entende-se que a Alta Administração em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica que representam a administração pública e têm a responsabilidade de orientar a política de privacidade e proteção de dados. Isso inclui: 
1.Ministros de Estado e Secretários, Presidentes, Vice-Presidente, Reitores, Vice-Reitores, Pró-Reitores e Comandantes das Forças Armadas, Comitê de Segurança da Informação. 
2.Diretores, Vice-Diretores, Superintendentes, Subsecretários, Coordenadores-Gerais. 
3.Chefes de Gabinete, Subchefes e Assessores. 
4.Assessor de Controle Interno, Ouvidor. '
,'Alta Administração', 'Senior Management');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'According to the Information Security and Privacy Framework Guide, the Head of the Internal Control Unit plays a fundamental role in driving and implementing the Framework, supporting, supervising, and monitoring the activities carried out by the first line of defense established by CGU Normative Instruction No. 3 of June 9, 2017.
Ordinance SGD/MGI No. 852 of March 28, 2023, states that the Head of the Internal Control Unit will support, supervise, and monitor the activities carried out by the first line of defense established by CGU Normative Instruction No. 3 of June 9, 2017.'
,'De acordo com o Guia do Framework de Privacidade e Segurança da Informação, o Responsável pela Unidade de Controle Interno consiste em um dos papéis fundamentais para condução e implementação do Framework, atuando no apoio, supervisão e monitoramento das atividades desenvolvidas pela primeira linha de defesa prevista pela Instrução Normativa CGU nº 3, de 9 de junho de 2017. 
A Portaria SGD/MGI nº 852, de 28 de março de 2023 informa que o Responsável pela Unidade Controle Interno atuará no apoio, supervisão e monitoramento das atividades desenvolvidas pela primeira linha de defesa prevista pela Instrução Normativa CGU nº 3, de 9 de junho de 2017. '
,'Responsável pela Unidade de Controle Interno', 'Head of the Internal Control Unit');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'In accordance with GSI/PR Ordinance No. 93 of October 18, 2021, a User is an individual—whether a public servant or equivalent, an employee, or a service provider—authorized by the administration to access the information assets of a federal public administration agency or entity, an authorization formalized through the signing of a Statement of Responsibility.'
,'Conforme a PORTARIA GSI/PR Nº 93, DE 18 DE OUTUBRO DE 2021, o Usuário consiste em uma pessoa física, seja servidor ou equiparado, empregado ou prestador de serviços, habilitada pela administração para acessar os ativos de informação de um órgão ou entidade da administração pública federal, formalizada por meio da assinatura de Termo de Responsabilidade.'
,'Usuário', 'User');


-- Insert TB_AREA_CONHECIMENTO
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Privacidade', 'Privacy Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Estratégias de Privacidade', 'Privacy Strategies');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Documentos Orientativos', 'Guidance Documents');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Riscos e Conformidade', 'Risk Management and Compliance');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Conhecimentos em Tecnologia da Informação', 'Knowledge of Information Technology');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Privacy By Design', 'Privacy By Design');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Segurança da Informação', 'Information Security Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Riscos', 'Risk Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão do Uso dos Recursos Operacionais e de Comunicações', 'Management of Operational and Communication Resource Usage');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Tratamento da Informação', 'Data Handling');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Ativos', 'Asset Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Incidentes Cibernéticos', 'Cyber ​​Incident Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Continuidade', 'Continuity Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Segurança de Controle de Acesso', 'Access Control Security');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Security By Design', 'Security By Design');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Mudança', 'Change Management');
INSERT INTO "TB_AREA_CONHECIMENTO" ("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Conformidade', 'Compliance Management');

-- Insert TB_DIFICULDADE
INSERT INTO "TB_DIFICULDADE" ("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Iniciante','Beginner');
INSERT INTO "TB_DIFICULDADE" ("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Básico','Basic');
INSERT INTO "TB_DIFICULDADE" ("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Intermediário','Intermediary');
INSERT INTO "TB_DIFICULDADE" ("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Avançado','Advanced');
INSERT INTO "TB_DIFICULDADE" ("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Especialista','Specialist');

-- Insert TB_PROFICIENCIA_INGLES
INSERT INTO "TB_PROFICIENCIA_INGLES" ("NO_PROFICIENCIA_INGLES_PT", "NO_PROFICIENCIA_INGLES_EN") VALUES ('Básico - Leitura', 'Basic - Reading');
INSERT INTO "TB_PROFICIENCIA_INGLES" ("NO_PROFICIENCIA_INGLES_PT", "NO_PROFICIENCIA_INGLES_EN") VALUES ('Intermediário - Leitura e Escrita', 'Intermediary - Reading and Writing');
INSERT INTO "TB_PROFICIENCIA_INGLES" ("NO_PROFICIENCIA_INGLES_PT", "NO_PROFICIENCIA_INGLES_EN") VALUES ('Avançado - Fluente', 'Advanced - Fluent');

-- Insert TB_EXPERIENCIA_ATUACAO
INSERT INTO "TB_EXPERIENCIA_ATUACAO" ("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Menos de 1 ano', 'Less than 1 year');
INSERT INTO "TB_EXPERIENCIA_ATUACAO" ("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 1 e 3 anos', 'Between 1 and 3 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO" ("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 4 e 6 anos', 'Between 4 and 6 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO" ("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 7 e 10 anos', 'Between 7 and 10 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO" ("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Mais de 10 anos', 'More than 10 years');

-- Insert TB_TIPO_PERFIL
INSERT INTO "TB_TIPO_PERFIL" ("NO_TIPO_PERFIL") VALUES ('Administrador');
INSERT INTO "TB_TIPO_PERFIL" ("NO_TIPO_PERFIL") VALUES ('Gestor');
INSERT INTO "TB_TIPO_PERFIL" ("NO_TIPO_PERFIL") VALUES ('Usuário');