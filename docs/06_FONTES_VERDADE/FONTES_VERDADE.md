# Fontes de verdade

## Regras

1. toda informação possui domínio proprietário;
2. tabela antiga não é automaticamente fonte canônica;
3. duplicidades são classificadas como legado, projeção, cache ou erro;
4. projeções indicam origem e forma de reconstrução;
5. Analytics não é fonte de fatos operacionais;
6. troca de fonte canônica exige ADR e migração verificável.

## Correspondência entre conceito e implementação legada

| Conceito | Implementação legada | Fonte de verdade atual |
|---|---|---|
| Cargo | `adms_positions` | `adms_positions` |
| Departamento | `adms_departments` | `adms_departments` |
| Conta de acesso | `adms_users` | `adms_users` |

Nomes conceituais do glossário não devem ser confundidos com o nome físico da
tabela. Não usar `adms_jobs` como sinônimo de Cargo sem decisão formal.

## Catálogo inicial

| Informação | Fonte atual | Direção | Proprietário | Estado |
|---|---|---|---|---|
| Conta e credenciais | `adms_users` | Conta | Administração | Confirmada no atual |
| Identidade pessoal | `adms_users` | Pessoa | Organização | Misturada |
| Cargo | `adms_positions` | Cargo | Organização | Confirmada |
| Departamento | `adms_departments` | Departamento | Organização | Confirmada |
| Gestor atual | `immediate_supervisor_id` | Lotação/relação vigente | Organização | Legado funcional |
| Admissão/desligamento | usuário + histórico | Vínculo | Organização/DP | Duplicada |
| Candidato | `rh_candidatos` | Candidato | Talentos | Confirmada |
| Vaga | `rh_vagas` | Vaga autorizada | Talentos | Sem requisição |
| Candidatura | `rh_candidatos_vagas` | Candidatura | Talentos | Confirmada |
| Etapa seletiva | status mutável | histórico + projeção | Talentos | Pendente |
| Status geral do candidato | `status_processo` | projeção | Talentos | Não deve ser primária |
| Entrevista | `rh_entrevistas` | entrevista estruturada | Talentos | Parcial |
| Treinamento corporativo | `adms_trainings` | catálogo de Desenvolvimento | Desenvolvimento | Fronteira SST pendente |
| Documento de folha | payroll + storage privado | documento de DP | DP | Operacional |
| Perfil ocupacional | usuário + SST | vínculo/lotação + SST | Organização/SST | Acoplada |
| Relato de denúncia | whistleblowing | Canal de Denúncias | Canal de Denúncias | Isolada |
| Headcount/turnover | usuário + histórico | projeções de vínculo | Analytics | Pode divergir |

## Consolidação por domínio

1. inventariar tabelas, campos, services e relatórios;
2. agrupar informações equivalentes;
3. definir proprietário e origem;
4. classificar duplicidades;
5. registrar consumidores;
6. planejar backfill e compatibilidade;
7. criar verificação de divergências;
8. migrar consumidores;
9. remover legado após evidência.

## Pendências prioritárias

- identidade, conta, vínculo e lotação;
- emprego atual versus histórico;
- status de candidato versus candidatura;
- treinamento corporativo versus regulatório;
- solicitações do Portal versus Reserva de Salas;
- parceiros em CRM, Compras, Financeiro e SAC;
- indicadores calculados somente sobre estado atual.
