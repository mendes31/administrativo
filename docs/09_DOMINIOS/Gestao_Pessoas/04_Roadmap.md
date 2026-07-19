# Gestão de Pessoas — Roadmap

## Fase -1 — Governança mínima

- modelo conceitual;
- glossário;
- limites dos domínios;
- catálogo de capacidades;
- ADRs iniciais.

Saída: conceitos e ownership registrados; conflitos conhecidos explicitados.

## Fase 0 — Segurança, LGPD, integridade e testes

- [x] storage privado e download autorizado de currículos;
- [x] MIME, tamanho, CSRF e proteção de uploads (currículos);
- [x] retenção, anonimização e exclusão física (currículos);
- [x] movimentação de pipeline atômica + autorização/CSRF de vagas/entrevistas;
- [x] sincronização em massa atômica de vínculos + auth nas telas;
- [x] testes de caracterização de currículos e pipeline.

Saída: riscos críticos contidos e regressões principais protegidas.

Diagnósticos:

- [Currículos](SEG_CURRICULOS_DIAGNOSTICO.md)
- [Pipeline / integridade](SEG_PIPELINE_INTEGRIDADE.md)

## Fase 0.5 — Autorização e eventos

- [x] matriz de autorização de Talentos ([MATRIZ_AUTORIZACAO_TALENTOS.md](MATRIZ_AUTORIZACAO_TALENTOS.md));
- [x] catálogo de eventos de Talentos ([CATALOGO_EVENTOS_TALENTOS.md](CATALOGO_EVENTOS_TALENTOS.md));
- [ ] policies/escopos de listagem (filtrar por objeto);
- [ ] outbox + emissão real dos eventos;
- [ ] ADRs das correções estruturais de Fase 0 (opcional formalizar).

Saída: recursos críticos possuem regra explícita e eventos têm proprietário.

## Fase 1 — Recrutamento confiável

- [x] histórico imutável (`rh_candidaturas_historico` + dual-write nas transições);
- [ ] etapas configuráveis;
- [ ] service transacional unificado de movimentação (entrevista + vínculo + histórico);
- [ ] motivos estruturados;
- [ ] status geral como projeção documentada/consumida pelo histórico;
- [ ] requisição de pessoal e aprovação.

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
