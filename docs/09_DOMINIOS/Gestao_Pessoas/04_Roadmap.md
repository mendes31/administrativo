# Gestão de Pessoas — Roadmap

## Fase -1 — Governança mínima

- modelo conceitual;
- glossário;
- limites dos domínios;
- catálogo de capacidades;
- ADRs iniciais.

Saída: conceitos e ownership registrados; conflitos conhecidos explicitados.

## Fase 0 — Segurança, LGPD, integridade e testes

- storage privado e download autorizado de currículos;
- MIME, tamanho, CSRF e proteção de uploads;
- retenção, anonimização e exclusão física;
- FKs, constraints, estados e transações críticas;
- testes de candidatos, vagas, pipeline, entrevistas e autorização.

Saída: riscos críticos contidos e regressões principais protegidas.

## Fase 0.5 — Autorização e eventos

- matrizes de autorização;
- policies e escopos;
- catálogo de eventos do domínio;
- convenções de auditoria;
- ADRs das mudanças estruturais.

Saída: recursos críticos possuem regra explícita e eventos têm proprietário.

## Fase 1 — Recrutamento confiável

- etapas configuráveis;
- histórico imutável;
- service transacional de movimentação;
- motivos estruturados;
- status geral como projeção;
- requisição de pessoal e aprovação.

## Fase 2 — Entrevistas e comunicação

- scorecards;
- critérios e pesos;
- múltiplos avaliadores;
- agenda e reagendamento;
- templates, outbox e histórico de entrega.

## Fase 3 — Portal público e pré-admissão

- publicação de vagas;
- candidatura e consentimento;
- CAPTCHA e deduplicação;
- oferta, aceite e documentos;
- conversão auditável para Pessoa/Vínculo.

## Fase 4 — Núcleo de pessoas e jornada

- Pessoa, Conta, Vínculo e Lotação por Expand/Contract;
- onboarding e experiência;
- movimentações;
- offboarding;
- integração progressiva com DP e SST.

## Fase 5 — Desenvolvimento

- ciclos e metas;
- avaliações 180° e 360°;
- calibração;
- PDI integrado a competências e treinamentos;
- carreira, sucessão e Nine Box.

## Fase 6 — Estratégia e analytics

- planejamento de quadro;
- clima, pulse e eNPS;
- indicadores integrados;
- custos e retenção;
- automação e IA após maturidade dos dados.

## Dependências

Fase 0 protege todas as demais. Fase 1 precede indicadores de funil. Fase 4
precede jornadas e analytics organizacionais confiáveis. Fases avançadas não
devem criar atalhos sobre fontes inconsistentes.
