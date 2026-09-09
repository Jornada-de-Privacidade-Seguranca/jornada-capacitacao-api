-- Insert TB_DOMINIO_CONHECIMENTO
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN" ) VALUES ('Privacidade','Privacy');
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN" ) VALUES ('Segurança da Informação','Information Security');
INSERT INTO "TB_DOMINIO_CONHECIMENTO" ("NO_DOMINIO_CONHECIMENTO_PT", "NO_DOMINIO_CONHECIMENTO_EN" ) VALUES ('Conjunta (Privacidade/Segurança)','Joint (Privacy/security)');

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
1.Ministers of State and Secretaries, Presidents, Vice-Presidents, Rectors, Vice-Rectors, Pro-Rectors, Armed Forces Commanders, and the Information Security Committee.'
,'Entende-se que a Alta Administração em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica que representam a administração pública e têm a responsabilidade de orientar a política de privacidade e proteção de dados. Isso inclui:
1.Ministros de Estado e Secretários, Presidentes, Vice-Presidente, Reitores, Vice-Reitores, Pró-Reitores e Comandantes das Forças Armadas, Comitê de Segurança da Informação.'
,'Alta Administração 1', 'Senior Management 1');

INSERT INTO "TB_PERFIS_JORNADA" ("FK_DOMINIO_CONHECIMENTO", "DS_PERFIS_EN", "DS_PERFIS_PT", "NO_PERFIS_PT", "NO_PERFIS_EN") VALUES (3,
'Senior management 2 in public sector bodies is understood to comprise individuals in leadership and strategic decision-making positions who represent the public administration and are responsible for guiding privacy and data protection policy. This includes:
1.Directors, Deputy Directors, Superintendents, Undersecretaries, and General Coordinators.
2.Chiefs of Staff, Deputy Chiefs of Staff, and Advisors.
3.Internal Control Advisor and Ombudsman.'
,'Entende-se que a Alta Administração 2 em órgãos públicos é composta por indivíduos em posições de liderança e decisão estratégica que representam a administração pública e têm a responsabilidade de orientar a política de privacidade e proteção de dados. Isso inclui:
1.Diretores, Vice-Diretores, Superintendentes, Subsecretários, Coordenadores-Gerais.
2.Chefes de Gabinete, Subchefes e Assessores.
3.Assessor de Controle Interno, Ouvidor. '
,'Alta Administração 2', 'Senior Management 2');

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
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Privacidade', 'Privacy Management', 'Conhecimentos relacionados a legislações e obrigações legais referentes a privacidade de dados.', 'Knowledge of legislation and legal obligations regarding data privacy.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Estratégias de Privacidade', 'Privacy Strategies', 'Conhecimentos relacionados a boas práticas, estratégias de implementação e técnicas de privacidade de dados.', 'Knowledge of best practices, implementation strategies, and data privacy techniques.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Documentos Orientativos', 'Guidance Documents', 'Conhecimentos relacionados a documentações oficiais.', 'Knowledge related to official documentation.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Riscos e Conformidade', 'Risk Management and Compliance', 'Conhecimentos relacionados a gestão de riscos de privacidade e conformidade com regulamentações, auditorias e fiscalizações.', 'Knowledge related to privacy risk management and compliance with regulations, audits, and inspections.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Conhecimentos em Tecnologia da Informação', 'Knowledge of Information Technology', 'Conhecimentos relacionados a tecnologia da informação, como programação, armazenamento de arquivos, etc.', 'Knowledge related to information technology, such as programming, file storage, etc.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Privacy By Design', 'Privacy By Design', 'Conhecimentos relacionados aos conceitos e aplicações de privacy by design e privacy by default.', 'Knowledge regarding the concepts and applications of privacy by design and privacy by default.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Segurança da Informação', 'Information Security Management', 'Conhecimentos relacionados a legislações e obrigações legais referentes a segurança da informação.', 'Knowledge related to legislation and legal obligations relating to information security.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Riscos', 'Risk Management', 'Conhecimentos relacionados ao tratamento e gestão de riscos.', 'Knowledge related to risk treatment and management.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão do Uso dos Recursos Operacionais e de Comunicações', 'Management of Operational and Communication Resource Usage', 'Conhecimentos relacionados a gestão do uso de mídias sociais, dispositivos móveis e serviços em nuvem.', 'Knowledge related to the management of social media usage, mobile devices, and cloud services.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Tratamento da Informação', 'Data Handling', 'Conhecimentos relacionados ao tratamento de informação classificada, restrição de acesso, recursos criptográficos, etc.', 'Knowledge related to the handling of classified information, access restrictions, cryptographic resources, etc.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Ativos', 'Asset Management', 'Conhecimentos relacionados ao mapeamento e tratamento de ativos.', 'Knowledge related to asset mapping and treatment.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Incidentes Cibernéticos', 'Cyber ​​Incident Management', 'Conhecimentos relacionados ao tratamento, prevenção e respostas à incidentes ciberneticos, atividades e responsabilidades da da Equipe de Prevenção, Tratamento e Resposta a Incidentes Cibernéticos (ETIR).', 'Knowledge regarding the handling, prevention of, and response to cyber incidents, as well as the activities and responsibilities of the Cyber ​​Incident Prevention, Handling, and Response Team (ETIR).');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Continuidade', 'Continuity Management', 'Conhecimentos relacionados ao processo de continuidade de negócios em segurança da informação.', 'Knowledge related to the information security business continuity process.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Segurança de Controle de Acesso', 'Access Control Security', 'Conhecimentos relacionados ao controle de acesso, privilégios mínimos, senhas, autenticação, etc.', 'Knowledge related to access control, least privilege, passwords, authentication, etc.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Security By Design', 'Security By Design', 'Conhecimentos relacionados aos conceitos e aplicações de security by design.', 'Knowledge regarding the concepts and applications of security by design.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Mudança', 'Change Management', 'Conhecimentos relacionados ao processo da gestão e tipos de classificação sobre a mudança nos aspectos de segurança da informação.', 'Knowledge regarding the management process and classification types concerning changes in information security aspects.');
INSERT INTO "TB_AREA_CONHECIMENTO"("NO_AREA_CONHECIMENTO_PT", "NO_AREA_CONHECIMENTO_EN", "DS_AREA_CONHECIMENTO_PT", "DS_AREA_CONHECIMENTO_EN") VALUES ('Gestão de Conformidade', 'Compliance Management', 'Conhecimentos relacionados ao processo de avaliação de conformidade nos aspectos da segurança da informação.', 'Knowledge related to the conformity assessment process regarding information security aspects.');

-- Insert TB_DIFICULDADE
INSERT INTO "TB_DIFICULDADE"("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Iniciante','Beginner');
INSERT INTO "TB_DIFICULDADE"("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Básico','Basic');
INSERT INTO "TB_DIFICULDADE"("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Intermediário','Intermediary');
INSERT INTO "TB_DIFICULDADE"("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Avançado','Advanced');
INSERT INTO "TB_DIFICULDADE"("NO_DIFICULDADE_PT", "NO_DIFICULDADE_EN") VALUES ('Especialista','Specialist');

-- Insert TB_CRITERIOS
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Experiência Profissional', 'Professional Experience', 23.81);
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Formação Acadêmica', 'Education', 14.7);
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Certificação Profissional', 'Professional Certification', 10.72);
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Nível de Proficiência - Inglês', 'Proficiency Level - English', 4.85);
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Competências', 'Competencies', 45.92);
INSERT INTO "TB_CRITERIOS"("NO_CRITERIO_PT", "NO_CRITERIO_EN", "NU_PESO_CRITERIO") VALUES ('Nenhum', 'None', 0);

-- Insert TB_SUBCRITERIOS
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (2, 'Graduação', 'Graduation', 8.829);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (2, 'Pós-Graduação', 'Postgraduate', 15.751);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (2, 'Mestrado', 'Masters', 27.18);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (2, 'Doutorado', 'Doctorate', 48.241);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (1, 'Tempo de Experiência Profissional', 'Years of Professional Experience', 50);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (1, 'Tempo de Atuação no Perfil','Time Spent on Role', 50);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (4, 'Básico - Leitura', 'Basic - Reading', 10.616);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (4, 'Intermediário - Leitura e Escrita', 'Intermediary - Reading and Writing', 26.05);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (4, 'Avançado - Fluente', 'Advanced - Fluent', 63.335);
INSERT INTO "TB_SUBCRITERIOS"("FK_CRITERIOS", "NO_SUBCRITERIO_PT", "NO_SUBCRITERIO_EN", "NU_PESO_SUBCRITERIO") VALUES (4, 'Nenhum', 'None', 0);

-- Insert TB_PESO_CRIT_COMPETENCIA
--Gestor de Segurança da Informação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,5,13.366);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,7,14.439);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,3,10.901);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,10,3.027);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,12,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,8,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,9,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,11,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,14,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,13,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,16,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,17,6.787);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (6,15,3.975);

--Agente Responsável pela Avaliação de Conformidade
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,5,4.15);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,7,12.09);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,3,11.21);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,10,9.44);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,12,5.91);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,8,7.63);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,9,5.69);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,11,6.59);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,14,5.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,13,6.21);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,16,6.21);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,17,15.51);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (12,15,3.99);

--Usuário
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,5,8.25);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,7,7.45);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,3,6.17);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,10,4.47);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,12,3.86);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,8,3.48);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,9,6.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,11,3.47);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,14,6.46);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,5,6.65);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,6,4.40);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,2,10.40);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,1,12.90);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,3,7.40);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (21,4,8.25);

--Alta Administração 1
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,5,5.79);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,7,6.96);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,3,5.05);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,10,6.07);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,12,3.32);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,8,3.31);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,9,3.27);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,11,3.30);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,14,3.28);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,13,2.84);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,16,2.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,17,2.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,15,1.99);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,5,4.50);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,6,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,2,12);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,1,11.5);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,3,11);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (18,4,5);

--Alta Administração 2
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,5,5.79);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,7,6.96);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,3,5.05);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,10,6.07);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,12,3.32);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,8,3.31);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,9,3.27);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,11,3.30);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,14,3.28);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,13,2.84);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,16,2.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,17,2.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,15,1.99);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,5,4);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,6,5.5);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,2,12.5);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,1,12);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,3,11.5);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (19,4,4.5);

--Responsável pela Unidade de Controle Interno
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,5,4.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,7,6.39);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,3,4.39);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,10,6.14);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,12,3.27);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,8,3.67);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,9,3.23);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,11,3.66);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,14,3.14);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,13,2.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,16,2.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,17,2.74);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,15,2);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,5,4.59);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,6,4.04);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,2,5.81);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,1,10.62);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,3,11.59);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (20,4,13.36);

--Agente Responsável pela Gestão de Continuidade de Negócios em Segurança da Informação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,5,11.33);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,7,12.48);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,3,10.43);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,10,2.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,12,6.82);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,8,10.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,9,5.49);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,11,8.08);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,14,5.22);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,13,14.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,16,5.97);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,17,2.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (10,15,3.97);

--Agente Responsável pela Gestão de Mudança nos Aspectos de Segurança da Informação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,5,11.33);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,7,12.48);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,3,10.43);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,10,2.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,12,6.82);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,8,10.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,9,5.49);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,11,8.08);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,14,5.22);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,13,5.97);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,16,14.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,17,2.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (8,15,3.97);

--Agente Responsável pela Gestão de Riscos de Segurança da Informação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,5,11.41);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,7,11.48);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,3,11.07);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,10,3.09);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,12,6.31);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,8,15.38);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,9,5.69);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,11,11.29);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,14,5.34);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,13,5.95);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,16,5.95);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,17,3.08);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (9,15,3.96);

--Agente Responsável pela Gestão dos Ativos de Informação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,5,11.62);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,7,11.80);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,3,10.80);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,10,3.18);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,12,6.25);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,8,11.78);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,9,5.62);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,11,14.35);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,14,5.35);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,13,6.07);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,16,6.07);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,17,3.14);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (7,15,3.97);

--Agente Responsável pelo uso Seguro de Mídias Sociais
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,5,13.20);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,7,12.02);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,3,10.25);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,10,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,12,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,8,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,9,14.55);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,11,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,14,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,13,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,16,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,17,5.75);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (11,15,3.98);

--Equipe de Administração e de Gestão de Perfis Institucionais em Mídias Sociais
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,5,15.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,7,12.03);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,3,13.79);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,10,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,12,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,8,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,9,15.70);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,11,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,14,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,13,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,16,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,17,4.88);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (13,15,3.91);

--Equipe de TI
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,5,10.305);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,7,6.5);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,3,5.265);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,10,1.475);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,12,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,8,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,9,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,11,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,14,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,13,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,16,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,17,3.06);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,15,1.975);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,5,19.64);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,6,11.31);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,2,9.245);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,1,3.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,3,3.315);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (17,4,2.96);

--ETIR
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,5,18.92);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,7,13.03);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,3,9.37);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,10,2.93);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,12,9.79);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,8,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,9,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,11,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,14,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,13,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,16,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,17,6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (14,15,3.96);

--Gestor de Segurança e Credenciamento
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,5,8.21);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,7,9.48);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,3,7.1);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,10,22.78);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,12,6.82);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,8,6.62);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,9,5.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,11,6.6);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,14,6.02);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,13,5.67);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,16,5.49);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,17,5.32);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (15,15,3.95);

--Gestor de Tecnologia da Informação e Comunicação
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,5,9.19);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,7,6.66);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,3,5.15);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,10,1.54);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,12,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,8,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,9,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,11,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,14,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,13,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,16,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,17,3.185);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,15,1.98);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,5,19.64);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,6,11.31);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,2,9.245);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,1,3.53);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,3,3.315);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (16,4,2.96);

--Encarregado de Dados
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,5,8);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,6,17);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,2,25);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,1,23);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,3,13);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (1,4,14);

--Controlador
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,5,7.81);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,6,11.02);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,2,25.05);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,1,20.04);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,3,13.03);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (2,4,23.05);

--Operador
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,5,10.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,6,11.95);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,2,24.35);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,1,17.92);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,3,12.94);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (3,4,21.9);

--Titular
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,5,13);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,6,7);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,2,20);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,1,35);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,3,15);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (4,4,10);

--Equipe de Apoio ao Encarregado
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,5,12);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,6,18);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,2,21);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,1,20);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,3,13);
INSERT INTO "TB_PESO_CRIT_COMPETENCIA"("FK_PERFIS_JORNADA", "FK_AREA_CONHECIMENTO", "NU_PESO_CRIT_COMPETENCIA") VALUES (5,4,16);

-- Insert TB_EXPERIENCIA_ATUACAO
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Menos de 1 ano', 'Less than 1 year');
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 1 e 3 anos', 'Between 1 and 3 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 4 e 6 anos', 'Between 4 and 6 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Entre 7 e 10 anos', 'Between 7 and 10 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Mais de 10 anos', 'More than 10 years');
INSERT INTO "TB_EXPERIENCIA_ATUACAO"("DS_EXPERIENCIA_ATUACAO_PT", "DS_EXPERIENCIA_ATUACAO_EN") VALUES ('Nenhum', 'None');

-- Insert TB_TIPO_PERFIL
INSERT INTO "TB_TIPO_PERFIL"("NO_TIPO_PERFIL") VALUES ('Administrador');
INSERT INTO "TB_TIPO_PERFIL"("NO_TIPO_PERFIL") VALUES ('Gestor');
INSERT INTO "TB_TIPO_PERFIL"("NO_TIPO_PERFIL") VALUES ('Usuário');
