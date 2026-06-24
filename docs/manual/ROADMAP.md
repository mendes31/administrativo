# Roadmap — Manual do sistema

Critério **completo** por tópico: objetivo, passo a passo, campos/regras, integrações, problemas comuns (se prioritário), screenshot (opcional), `manifest.json` + `PAGE_TOPIC_MAP`, âncoras F1 (telas com abas).

Legenda: `esqueleto` → `util` → `completo`

## Resumo por fase

| Fase | Módulos | Tópicos | Status geral |
|------|---------|---------|--------------|
| 0 | SST | 22 | **util** (12 esqueletos elevados em jun/2026) |
| 1 | Dashboard, Administração, Cadastro | 11 | **util** |
| 2 | Comunicação, CRM, Estoque, Financeiro, Parceiros, Qualidade | 24 | **util** |
| 3 | Gestão de Treinamentos, Projetos, Gestão de Pessoas, Salas, SAC | 20 | **util** |
| 4 | LGPD, Planejamento, Relatórios | 8 | **util** |

**Total documentado:** 85 tópicos (+ 2 gerais: index, em-desenvolvimento) em `manifest.json` v1.

## Fase 0 — SST (22 tópicos)

| ID | Título | Status | Notas |
|----|--------|--------|-------|
| sst-visao-geral | Visão geral | util | Fluxos ponta a ponta |
| sst-conceitos | Conceitos | util | Modelo cargo/risco/GHE |
| sst-dashboard | Dashboard | util | Seções F1 |
| sst-riscos | Riscos | util | Referência de qualidade |
| sst-exames | Catálogo exames | util | Elevado jun/2026 |
| sst-asos | ASOs | util | Fluxo admissional/periódico |
| sst-epis | Catálogo EPIs | util | Elevado jun/2026 |
| sst-epi-fichas | Fichas EPI | util | Fluxo entrega |
| sst-necessidades | Necessidades | util | |
| sst-treinamentos | Treinamentos | util | Fluxo sync/aplicar |
| sst-matriz-treinamento | Matriz cargo | util | Elevado jun/2026 |
| sst-ghe | GHE | util | |
| sst-medicos | Médicos | util | Elevado jun/2026 |
| sst-cids | CIDs | util | Elevado jun/2026 |
| sst-acidentes-afastamentos | Acidentes | util | Elevado jun/2026 |
| sst-conformidade | Conformidade | util | Elevado jun/2026 |
| sst-esocial-ppp | eSocial / PPP | util | Elevado jun/2026 |
| sst-equipamentos | Equipamentos | util | Elevado jun/2026 |
| sst-cipa-inspecoes | CIPA / inspeções | util | Elevado jun/2026 |
| sst-relatorios | Relatórios | util | Elevado jun/2026 |
| sst-report-pendencias | Pendências | completo | Referência |
| sst-perfil-colaborador | Perfil colaborador | util | |

## Fase 1 — Dashboard, Administração, Cadastro

| ID | Título | Status |
|----|--------|--------|
| dashboard-visao-geral | Dashboard | util |
| adm-visao-geral | Administração | util |
| adm-configuracoes | Configurações | util |
| adm-logs | Logs | util |
| adm-permissoes | Permissões ACL | util |
| adm-treinamentos-obrigatorios | Trein. obrigatórios | util |
| cad-visao-geral | Cadastro | util |
| cad-estrutura | Estrutura | util |
| cad-usuarios | Usuários | util |
| cad-organograma | Organograma | util |
| cad-niveis-acesso | Níveis de acesso | util |

## Fase 2 — Comercial e operacional

| ID | Módulo | Status |
|----|--------|--------|
| com-* (5) | Comunicação | util |
| crm-* (7) | CRM | util |
| est-* (5) | Estoque | util |
| fin-* (4) | Financeiro | util |
| parceiros-negocio | Parceiros | util |
| qualidade-documentos | Qualidade | util |

## Fase 3 — Pessoas e facilities

| ID | Módulo | Status |
|----|--------|--------|
| rh-trein-* (6) | Gestão de Treinamentos | util |
| proj-projetos | Projetos | util |
| gp-* (8) | Gestão de Pessoas | util |
| salas-* (3) | Reserva de Salas | util |
| sac-* (2) | SAC | util |

## Fase 4 — Conformidade e BI

| ID | Módulo | Status |
|----|--------|--------|
| lgpd-* (5) | LGPD | util |
| pe-estrategico | Planejamento | util |
| rel-* (2) | Relatórios | util |

## Próximos passos

- [ ] Mapear slugs em `ContextHelpHelper::PAGE_TOPIC_MAP` para F1 contextual
- [ ] `data-adms-help-tab` / `data-adms-help-section` nas views com abas
- [ ] Screenshots (opcional, próximo sprint)
- [ ] Elevar tópicos **util** → **completo** com integrações e casos avançados
- [ ] Remover scripts temporários `scripts/generate_manual_batch*.php` após revisão

## Template de novo tópico

1. Criar `docs/manual/content/{modulo}/{id}.html`
2. Registrar em `docs/manual/manifest.json`
3. Mapear slugs em `ContextHelpHelper::PAGE_TOPIC_MAP`
4. `data-adms-help-tab` / `data-adms-help-section` nas views com abas
5. Atualizar esta tabela
