# Proposta de novos grupos ACL (padrão de nomes)

- Status: **P1 + P2 aplicados** (GP/SST/LGPD + Estoque/CRM) — migrations `20260729120000` e `20260729160000`
- Data: 2026-07-29
- Base: inventário homologação (`adms_pages` / `adms_groups_pages`)
- Contexto / riscos / fases: [PLANO_SEPARACAO_GRUPOS_PAGINAS.md](PLANO_SEPARACAO_GRUPOS_PAGINAS.md)

## Como foi implementado

| Artefato | Papel |
|----------|--------|
| `database/migrations/20260729120000_split_gp_sst_lgpd_page_groups.php` | P1: GP / SST / LGPD |
| `database/migrations/20260729160000_split_estoque_crm_page_groups.php` | P2: Estoque / CRM |
| `database/helpers/AdmsPageGroupSplit.php` | Classificação compartilhada (por **nome** de grupo) |
| `database/seeds/AddAdmsGroupsPages.php` | Lista os novos grupos para installs/seeds |
| `database/seeds/AddAdmsPages.php` | Ao final, reaplica a cisão (idempotente) |

**Não** altera `adms_access_levels_pages`. Menu lateral **não** muda.

## Resposta direta

Sim - o padrão é exatamente esse:

```text
Gestão de Pessoas - Talentos (ATS)
Gestão de Pessoas - Portal / Solicitações
Gestão de Pessoas - Desempenho e Carreira
Gestão de Pessoas - Organização / Políticas
…
SST - …
LGPD - …
```

Cada linha é um **novo registro** em `adms_groups_pages`. As páginas só mudam
`adms_groups_page_id`. **Permissões por `page_id` não mudam.**

Meta operacional (~20–50 páginas/grupo): orientação de UX, **não** limite rígido.

---

## 1) Gestão de Pessoas (hoje: grupo 36 - 178 páginas)

| Novo grupo | ~Páginas | O que entra |
|------------|--------:|-------------|
| **Gestão de Pessoas - Talentos (ATS)** | 51 | Vagas, candidatos, entrevistas, ofertas, onboarding ATS, requisições de pessoal (`Rh*`) |
| **Gestão de Pessoas - Portal / Solicitações** | 40 | Portal do colaborador, minhas solicitações, aprovações, tipos, delegações |
| **Gestão de Pessoas - Desempenho e Carreira** | 60 | Avaliações de desempenho, metas, feedbacks, ciclos, PDI, 9BOX, pulse/eNPS, sucessão, trilhas |
| **Gestão de Pessoas - Organização / Políticas** | 24+ | Políticas internas, categorias, turnos, quadro/headcount, analytics RH |

**Já existem e permanecem:** Treinamentos (24), Avaliações (25) - não misturar de volta no GP.

> Analytics RH (`PeopleAnalytics`, `PeopleReports`) e `UpdateEmploymentHistory` foram para **Gestão de Pessoas - Organização / Políticas** (evita grupo de 2 páginas).

---

## 2) Segurança e Medicina (hoje: grupo 42 - 169 páginas)

| Novo grupo | ~Páginas | O que entra |
|------------|--------:|-------------|
| **SST - Medicina / ASO / Exames** | 38 | ASO, exames, médicos, CIDs, encaminhamentos |
| **SST - Cadastros e vínculos** | 36 | Cadastros SST genéricos / necessidades / vínculos não cobertos abaixo |
| **SST - Treinamentos / GHE / PPP** | 32 | Treinamentos SST, GHE, PPP, matriz |
| **SST - EPI** | 26 | EPIs, estoque EPI, fichas, necessidades de EPI |
| **SST - Equipamentos / Vistoria** | 26 | Equipamentos, vistorias, recargas, não conformidades |
| **SST - Acidentes / Afastamentos** | 6 | Acidentes, afastamentos, CAT |
| **SST - Dashboard / Relatórios** | 5 | Dashboard SST, exports/relatórios transversais |

Alinhado ao menu `Segurança e Medicina` (cadastros, EPI, equipamentos, medicina…).

**Opção de consolidar (se quiser menos grupos):** juntar Acidentes + Dashboard em **SST - Operação / Relatórios** (~11).

---

## 3) LGPD (hoje: grupo 31 - 102 páginas)

| Novo grupo | ~Páginas | O que entra |
|------------|--------:|-------------|
| **LGPD - Taxonomia** | 20 | Finalidades, bases legais, tipos de dados, classificações |
| **LGPD - Inventário / ROPA / Mapping** | 18 | Inventário, ROPA, data mapping, relatório integrado |
| **LGPD - AIPD** | 15 | AIPD + templates |
| **LGPD - TIA** | 14 | TIA + templates/exports |
| **LGPD - Consentimentos** | 11 | Consentimentos |
| **LGPD - Dashboard / Termos / Legal** | 10 | Dashboard LGPD, termos, páginas legais públicas |
| **LGPD - RIPD** | 9 | RIPD + exports |
| **LGPD - Titulares** | 5 | Categorias / titulares |

**Opção de consolidar (menos grupos):**

| Grupo consolidado | Junta |
|-------------------|--------|
| LGPD - Avaliações (AIPD / TIA / RIPD) | AIPD + TIA + RIPD (~38) |
| LGPD - Operação | Consentimentos + Titulares (~16) |
| LGPD - Cadastros | Taxonomia (~20) |
| LGPD - Inventário | Inventário/ROPA/Mapping (~18) |
| LGPD - Geral | Dashboard/Termos/Legal (~10) |

---

## O que **não** cisãoar agora

| Grupo | Páginas | Motivo |
|-------|--------:|--------|
| Comunicação Social | 40 | P3 - só se públicos forem distintos |
| Reserva de Salas, SAC, Logs, etc. | ≤34 | OK |

### P2 aplicado (Estoque / CRM)

| Novo grupo | Conteúdo |
|------------|----------|
| **Estoque - Itens e movimentações** | Itens, estoques, posições, unidades, categorias, entradas/saídas/transferências/ajustes, relatórios de saldo/histórico |
| **Estoque - Custeio** | Períodos de custo, DRE/RH, fatores, simulações, operações/recursos/papéis de produção |
| **CRM - Operação** | Dashboard, pipeline, parceiros, oportunidades, atividades, tags, automações, relatórios, import/export |
| **CRM - Integrações e configurações** | WhatsApp, SAP API, MCP chat, calendário (`directory=settings` no grupo CRM) |

---

## Efeito na matriz de permissões

**Antes (exemplo):** um único “Gestão de Pessoas” com 178 toggles e “Autorizar grupo” liberando ATS + portal + desempenho de uma vez.

**Depois:** o admin vê, por exemplo:

- Gestão de Pessoas - Talentos (ATS) - Autorizar grupo  
- Gestão de Pessoas - Portal / Solicitações - Autorizar grupo  
- …

Mesma lógica para SST e LGPD.

---

## Como será a alteração técnica (quando aprovado)

1. Criar os novos grupos (`INSERT` em `adms_groups_pages` por **nome**).
2. `UPDATE adms_pages SET adms_groups_page_id = …` conforme o mapa.
3. **Não** tocar em `adms_access_levels_pages` (quem já tinha a página continua tendo).
4. Atualizar seeds para resolver grupo por nome.
5. Homolog → produção.

---

## Decisões pedidas a você

1. **GP:** manter 4 grupos “fortes” + Analytics (ou fundir Analytics em Organização)?  
2. **Nome Folha:** criar **Gestão de Pessoas - Folha / Documentos RH** só se listarmos páginas de folha; senão não criar grupo vazio/quase vazio.  
3. **SST:** 7 grupos como na tabela, ou consolidar Acidentes+Dashboard?  
4. **LGPD:** 8 grupos finos (tabela) ou 5 consolidados (AIPD/TIA/RIPD juntos)?  
5. Ordem de execução: **GP → SST → LGPD** (recomendado).

Quando aprovar o recorte, o próximo artefato é o **mapa página a página** (id/controller → grupo) para a migration - sem aplicar até você autorizar.
