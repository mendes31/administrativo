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
- [x] log de download de currículo (`rh_candidato_anexo_access_logs`; UI/exportação posterior);
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
- [x] policies/escopos de listagem — piloto vagas + entrevistas + candidatos ([ESCOPO_LISTAGEM_VAGAS_EXPAND.md](ESCOPO_LISTAGEM_VAGAS_EXPAND.md), [ESCOPO_LISTAGEM_ENTREVISTAS_EXPAND.md](ESCOPO_LISTAGEM_ENTREVISTAS_EXPAND.md), [ESCOPO_LISTAGEM_CANDIDATOS_EXPAND.md](ESCOPO_LISTAGEM_CANDIDATOS_EXPAND.md); Contract ainda pendente);
- [x] outbox + emissão dos eventos de entrevista e consumo pelo worker SMTP —
  [COMUNICACAO_ENTREVISTA_OUTBOX_EXPAND.md](COMUNICACAO_ENTREVISTA_OUTBOX_EXPAND.md);
- [x] ADRs das correções estruturais de Fase 0 / 0.5 ([ADR-0003](../../08_ADR/ADR-0003_STORAGE_PRIVADO_CURRICULOS.md), [ADR-0004](../../08_ADR/ADR-0004_ESCOPO_VIEWALL_ATS.md)).

Saída: recursos críticos possuem regra explícita e eventos têm proprietário.

## Fase 1 — Recrutamento confiável

- [x] histórico imutável (`rh_candidaturas_historico` + dual-write nas transições);
- [x] motivos estruturados (catálogo PHP + obrigatório no pipeline);
- [x] service transacional unificado de movimentação (entrevista + vínculo + histórico);
- [x] etapas configuráveis (Expand: rótulo/ordem/classe dos 5 códigos em `rh_pipeline_stages`);
- [x] status geral como projeção (`RhCandidatoStatusProcessoProjector` + cadastro sem edição livre);
- [x] requisição de pessoal e aprovação (`rh_personnel_requests` → conversão em vaga).

Saída Fase 1: recrutamento com histórico, motivos, etapas catalogadas, projeção de status e requisição aprovável.

## Fase 2 — Entrevistas e comunicação

- [x] scorecards + critérios/pesos na edição da entrevista ([SCORECARD_ENTREVISTA_EXPAND.md](SCORECARD_ENTREVISTA_EXPAND.md));
- [x] painel interno de múltiplos avaliadores ([AVALIADORES_ENTREVISTA_EXPAND.md](AVALIADORES_ENTREVISTA_EXPAND.md));
- [x] agenda e reagendamento auditável ([AGENDA_ENTREVISTA_EXPAND.md](AGENDA_ENTREVISTA_EXPAND.md));
- [x] templates + registro de intenção/outbox de entrevista ([COMUNICACAO_ENTREVISTA_OUTBOX_EXPAND.md](COMUNICACAO_ENTREVISTA_OUTBOX_EXPAND.md));
- [x] preflight CLI `recorded → ready|blocked` (sem SMTP) — script `rh_entrevista_comunicacoes_preflight.php`;
- [x] worker CLI SMTP e histórico de entrega (`sent`/`failed`), com dry-run,
  interruptor específico na Configuração de E-mail, claim atômico e redirecionamento
  para destinatário de teste fora de produção;
- [x] reenvio manual de comunicações `failed`/`blocked` (nova intenção + outbox).

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
