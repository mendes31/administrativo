# Gestão de Pessoas — Visão executiva

## Objetivo

Evoluir os blocos atuais para uma plataforma integrada de Organização,
Talentos, Jornada, Desenvolvimento, Portal e People Analytics, com
cobertura progressiva das funcionalidades de mercado (referência Feedz,
MarQ, LG etc.), sem importar backlog automaticamente — cada item passa
pelo gate do [Plano Diretor](../../00_PLANO_DIRETOR/README.md).

## Estado atual (jul/2026)

- Recrutamento (Fases 1–3): histórico, etapas, entrevistas, comunicação,
  portal público, oferta e conversão — operacionais no núcleo;
- Jornada (Fase 4): onboarding, experiência, movimentações, offboarding,
  identidade Pessoa/Vínculo/Lotação (Expand) e integração LNT/outbox;
- Desenvolvimento (Fase 5 em andamento):
  - ciclos, metas, avaliações↔ciclo, calibração, Nine Box por ciclo;
  - PDI completo (planos, ações, competências, treinamentos, metas, feedbacks, aprovação);
  - talent pool / HiPo por ciclo;
  - sucessão (cargos críticos + sucessores + readiness);
  - carreira (trilhas, níveis e promoções);
  - avaliações em massa a partir do ciclo;
  - feedback contínuo global (regras de escopo/público/anônimo);
  - calibração avançada (notas na sessão + lock de avaliações);
  - **faltam:** itens avançados da Fase 6 (custo R$ de turnover; IA);
  - **Fase 6 (núcleo):** pulse/eNPS; quadro; indicadores; retenção; automação de lembretes (IA generativa adiada);
  - **ainda adiado:** custo R$ de turnover; IA;
  - matching Nine Box→PDI (1º incremento: PDI rascunho a partir do quadrante);
- Treinamentos e Folha Digital permanecem entre os blocos mais maduros;
- Portal e analytics ainda parciais;
- `adms_users` ainda concentra conta + vínculo; Contract de identidade pendente.

## Benefícios esperados

- jornada integrada da requisição ao desligamento;
- dados organizacionais consistentes;
- autorização por papel, relação e escopo;
- histórico e auditoria confiáveis;
- menor recadastro e retrabalho;
- indicadores baseados em fatos;
- experiência coerente para RH, gestores e colaboradores.

## Estrutura-alvo

```text
Gestão de Pessoas
├── Organização
├── Talentos
├── Jornada do Colaborador
├── Desenvolvimento
├── Portal do Colaborador
└── People Analytics
```

Departamento Pessoal e SST permanecem domínios irmãos, compartilhando Pessoa,
Vínculo e Lotação por contratos e permissões específicas.

## Prioridades

1. segurança, LGPD, integridade e testes;
2. modelo de domínio, autorização e eventos;
3. histórico e etapas de recrutamento;
4. requisição, entrevistas e comunicação;
5. portal público e pré-admissão;
6. núcleo de Pessoa, Vínculo e Lotação;
7. desempenho, PDI completo, carreira e sucessão (paridade de mercado);
8. planejamento, clima e analytics.

## Paridade de mercado — Desenvolvimento (fila)

Ordem acordada após os 1ºs incrementos:

1. ~~PDI completo (metas, feedbacks, aprovação, progresso);~~
2. ~~Sucessão (cargos críticos + sucessores + readiness);~~
3. ~~Carreira (trilhas / níveis / promoções);~~
4. ~~Avaliações em massa (a partir do ciclo);~~
5. ~~Feedback contínuo global (hardening);~~
6. ~~Calibração avançada (notas na sessão + lock);~~
7. ~~Matching Nine Box→PDI (1º incremento: rascunho na matriz);~~
8. ~~Pulse/eNPS (1º incremento Fase 6);~~
9. ~~Planejamento de quadro (1º incremento);~~
10. ~~Indicadores integrados (1º incremento no People Analytics);~~
11. ~~Retenção (1º incremento; custo financeiro adiado);~~
12. ~~Automação de lembretes (1º incremento; IA adiada);~~
13. Avançados adiados: custo R$; IA generativa; matching Nine Box→PDI em massa/ações auto.

Fase 6 continua progressiva após maturidade dos dados de desenvolvimento.

## Indicadores executivos propostos

- riscos críticos abertos;
- cobertura de autorização dos recursos sensíveis;
- fluxos críticos com testes;
- tempo de contratação;
- conversão do funil;
- conformidade de treinamentos;
- tempo e conclusão de onboarding;
- turnover e retenção;
- conclusão de avaliações e PDI.

Indicadores só serão publicados quando fontes e fórmulas estiverem validadas.
