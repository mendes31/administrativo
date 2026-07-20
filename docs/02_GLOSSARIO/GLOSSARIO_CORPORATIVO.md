# Glossário corporativo

## Regras

- termos conceituais independem dos nomes atuais das tabelas;
- nomes legados permanecem registrados até sua substituição;
- “usuário” não deve ser usado como sinônimo de pessoa ou colaborador;
- termos novos indicam o domínio responsável.

## Identidade e organização

| Termo | Definição | Situação atual |
|---|---|---|
| Pessoa | Identidade humana, com ou sem acesso ou vínculo | Misturada em `adms_users` |
| Conta | Credencial para autenticação e ações | Em `adms_users` |
| Usuário | Pessoa ou ator operando por uma conta | Termo ambíguo no legado |
| Colaborador | Pessoa que possui ou possuiu vínculo | Representado principalmente por `adms_users`; colaborador ativo do Portal possui conta 1:1 |
| Vínculo | Relação contratual entre pessoa e empresa, com vigência | Distribuído entre usuário e histórico |
| Lotação | Alocação vigente do vínculo na organização | Não existe como entidade consolidada |
| Empresa | Pessoa jurídica empregadora ou unidade legal | Fonte canônica pendente |
| Filial | Estabelecimento pertencente a uma empresa | Cadastro existe; vínculo é parcial |
| Departamento | Unidade administrativa formal | `adms_departments` |
| Cargo | Conjunto formal de responsabilidades e requisitos | `adms_positions` |
| Função | Atividade efetivamente exercida | Modelagem pendente |
| Gestor | Colaborador responsável por pessoa ou estrutura | Usa `immediate_supervisor_id` |
| Equipe | Agrupamento operacional com objetivo ou liderança comum | Entidade pendente |
| Centro de custo | Unidade de imputação e controle financeiro | Fonte compartilhada a confirmar |

## Talentos e jornada

| Termo | Definição |
|---|---|
| Candidato | Pessoa tratada no contexto de uma oportunidade |
| Vaga | Oportunidade autorizada ou publicada |
| Candidatura | Participação de candidato em vaga específica |
| Etapa seletiva | Estado configurado do fluxo de uma candidatura |
| Requisição de pessoal | Solicitação e aprovação da necessidade de contratação |
| Banco de talentos | Conjunto governado de candidatos para oportunidades futuras |
| Pré-admissão | Período entre aceite da oferta e criação do vínculo |
| Admissão | Formalização e início do vínculo |
| Onboarding | Processo coordenado de integração inicial |
| Movimentação | Alteração de lotação, cargo, gestor, salário ou situação |
| Offboarding | Encerramento controlado de vínculo, ativos e acessos |

## Desenvolvimento

| Termo | Definição |
|---|---|
| Competência | Conhecimento, habilidade ou comportamento avaliável |
| Avaliação de treinamento | Verificação de aprendizagem ligada a conteúdo |
| Avaliação de desempenho | Avaliação do trabalho e resultados em um ciclo |
| Avaliação 180° | Perspectivas definidas, normalmente colaborador e gestor |
| Avaliação 360° | Processo multiperspectiva com consolidação e governança |
| Ciclo de desempenho | Janela temporal que agrupa avaliações, metas e calibração |
| Calibração | Sessão em que gestores alinham notas entre áreas antes do fechamento |
| PDI | Plano individual com objetivos, ações, prazos e evidências |
| OKR / Meta | Objetivo com resultado-chave ou indicador mensurável no período |
| Nine Box / 9BOX | Matriz que cruza desempenho e potencial independentes |
| HiPo | Colaborador de alto potencial, tipicamente priorizado no talent pool |
| Talent pool | Banco interno de talentos nomeados a partir de desempenho/potencial |
| Sucessão | Preparação de sucessores para cargos críticos |
| Readiness | Grau de prontidão do sucessor para assumir o cargo |
| eNPS | Employee Net Promoter Score: recomendação da empresa como empregadora (0–10) |
| Pulse | Pesquisa curta e periódica de clima (Likert/texto), distinta do eNPS |

## Plataforma

| Termo | Definição |
|---|---|
| Domínio | Área de negócio com linguagem, regras e responsabilidades próprias |
| Capacidade | Resultado de negócio que o sistema consegue oferecer |
| Módulo | Organização funcional e técnica que implementa capacidades |
| Fonte de verdade | Origem canônica de uma informação |
| Projeção | Cópia derivada e reconstruível |
| ACL de página | Controle de acesso a rota ou tela |
| Autorização por objeto | Decisão sobre ação em registro específico |
| Evento de domínio | Fato de negócio relevante já ocorrido |
| Auditoria | Evidência de ação, tentativa, decisão ou acesso |
| Outbox | Registro transacional de eventos a publicar |
| ADR | Registro de decisão arquitetural e suas consequências |

## Pendências a resolver no domínio Organização

- empresa, filial, unidade e local de trabalho;
- cargo, função, posição e posto;
- vínculos simultâneos e recontratações;
- gestor administrativo e funcional;
- equipe e estrutura matricial;
- centro de custo canônico;
- fronteira entre Gestão de Pessoas e Departamento Pessoal.
