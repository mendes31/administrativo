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

- [x] publicação de vagas (flag admin `publicada`/`publicado_em` + listagem pública em `vagas-abertas`) — [PUBLICACAO_VAGAS_EXPAND.md](PUBLICACAO_VAGAS_EXPAND.md);
- [x] candidatura pública + consentimento LGPD + CAPTCHA + dedupe (e-mail+vaga) — sem upload de currículo;
- [x] oferta + aceite/recusa (RH) + checklist de pré-admissão — [OFERTA_PREADMISSAO_EXPAND.md](OFERTA_PREADMISSAO_EXPAND.md);
- [x] conversão auditável oferta → colaborador (`adms_users` + `rh_conversoes_admissao`) — [CONVERSAO_ADMISSAO_EXPAND.md](CONVERSAO_ADMISSAO_EXPAND.md).

## Fase 4 — Núcleo de pessoas e jornada

- [x] onboarding pós-conversão (checklist) — [ONBOARDING_EXPAND.md](ONBOARDING_EXPAND.md);
- [x] período de experiência (90 dias + avaliação/prorrogação) — [EXPERIENCIA_EXPAND.md](EXPERIENCIA_EXPAND.md);
- [x] movimentações organizacionais (histórico + aplicação em `adms_users`) — [MOVIMENTACOES_EXPAND.md](MOVIMENTACOES_EXPAND.md);
- [x] offboarding (checklist + desligamento em `adms_users`) — [OFFBOARDING_EXPAND.md](OFFBOARDING_EXPAND.md);
- [x] Pessoa / Vínculo / Lotação (schema sombra + dual-write) — [IDENTIDADE_EXPAND.md](IDENTIDADE_EXPAND.md) / [ADR-0006](../../08_ADR/ADR-0006_MODELO_FISICO_IDENTIDADE.md);
- [x] integração progressiva com DP/SST (LNT + outbox de jornada) — [INTEGRACAO_DP_SST_EXPAND.md](INTEGRACAO_DP_SST_EXPAND.md).

## Fase 5 — Desenvolvimento

- [x] ciclos e metas (ciclo + vínculo opcional) — [DESENVOLVIMENTO_CICLOS_EXPAND.md](DESENVOLVIMENTO_CICLOS_EXPAND.md);
- [x] avaliações 180° e 360° (vínculo ao ciclo) — [DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md](DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md);
- [x] calibração (sessão por ciclo + Nine Box por ciclo) — [DESENVOLVIMENTO_CALIBRACAO_EXPAND.md](DESENVOLVIMENTO_CALIBRACAO_EXPAND.md);
- [x] PDI operacional — planos/ações/competências/treinamentos — [DESENVOLVIMENTO_PDI_EXPAND.md](DESENVOLVIMENTO_PDI_EXPAND.md);
- [x] PDI completo (metas, feedbacks, aprovação, progresso) — [DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md](DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md);
- [x] talent pool / HiPo por ciclo (1º corte carreira/sucessão) — [DESENVOLVIMENTO_TALENT_POOL_EXPAND.md](DESENVOLVIMENTO_TALENT_POOL_EXPAND.md);
- [x] sucessão (cargos críticos + sucessores + readiness) — [DESENVOLVIMENTO_SUCESSAO_EXPAND.md](DESENVOLVIMENTO_SUCESSAO_EXPAND.md);
- [x] carreira (trilhas / níveis / promoções) — [DESENVOLVIMENTO_CARREIRA_EXPAND.md](DESENVOLVIMENTO_CARREIRA_EXPAND.md);
- [x] avaliações em massa a partir do ciclo — [DESENVOLVIMENTO_AVALIACOES_MASSA_EXPAND.md](DESENVOLVIMENTO_AVALIACOES_MASSA_EXPAND.md);
- [x] feedback contínuo global (hardening) — [DESENVOLVIMENTO_FEEDBACK_CONTINUO_EXPAND.md](DESENVOLVIMENTO_FEEDBACK_CONTINUO_EXPAND.md);
- [x] calibração avançada (notas na sessão + lock) — [DESENVOLVIMENTO_CALIBRACAO_AVANCADA_EXPAND.md](DESENVOLVIMENTO_CALIBRACAO_AVANCADA_EXPAND.md);
- [ ] matching Nine Box→PDI (= avançado / adiado).

## Fase 6 — Estratégia e analytics

- [x] clima / pulse / eNPS (1º incremento: campanhas + resposta + score) — [CLIMA_PULSE_ENPS_EXPAND.md](CLIMA_PULSE_ENPS_EXPAND.md);
- [x] planejamento de quadro (1º incremento: plano vs efetivo + gap) — [PLANEJAMENTO_QUADRO_EXPAND.md](PLANEJAMENTO_QUADRO_EXPAND.md);
- [ ] indicadores integrados (ampliar analytics);
- [ ] custos e retenção;
- [ ] automação e IA após maturidade dos dados.

## Dependências

Fase 0 protege todas as demais. Fase 1 precede indicadores de funil. Fase 4
precede jornadas e analytics organizacionais confiáveis. Fases avançadas não
devem criar atalhos sobre fontes inconsistentes.
