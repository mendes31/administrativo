# Plano - Separação de grupos ACL (Opção A)

- Status: **P1 + P2 executados** (homolog; produção conforme migrate)
- Data: 2026-07-29
- Base: homologação `tiaraju04_homologacao`
- Relacionado: [Modelo de autorização](MODELO_AUTORIZACAO.md), [Plano Diretor](../00_PLANO_DIRETOR/README.md), [Proposta de nomes](PROPOSTA_NOVOS_GRUPOS_ACL.md)

## Problema

A matriz de permissões lista páginas em lista plana por `adms_groups_pages`.
“Autorizar grupo” em mega-grupos é perigoso; a UX degrada. O **menu** já é
hierárquico; a **ACL** não acompanhou.

Meta operacional (não é limite rígido): preferir grupos em torno de **~20–50**
páginas; acima de **~80** priorizar cisão; entre **50–80** revisar se o
“Autorizar grupo” ainda faz sentido de negócio.

## Inventário completo (todos os grupos)

| Páginas | ID | Grupo | Prioridade |
|--------:|---:|--------|------------|
| 178 | 36 | Gestão de Pessoas | **P1 - cisão** |
| 169 | 42 | Segurança e Medicina | **P1 - cisão** |
| 102 | 31 | LGPD | **P1 - cisão** |
| 62 | 33 | Estoque | **P2 - feito** (Itens × Custeio) |
| 59 | 34 | CRM | **P2 - feito** (Operação × Integrações) |
| 40 | 40 | Comunicação Social | **P3 - observar** |
| 34 | 37 | Reserva de Salas | OK |
| 28 | 25 | Avaliações | OK (já separado do GP) |
| 28 | 24 | Treinamentos | OK (já separado do GP) |
| 22 | 35 | Relatórios Dinâmicos | OK |
| 22 | 41 | SAC | OK |
| 21 | 26 | Configurações | OK |
| 18 | 45 | Canal de Denúncias | OK (grupo protegido - não misturar) |
| 17 | 28 | Logs | OK |
| 16 | 30 | Informativos | OK |
| 16 | 29 | Planejamento Estratégico | OK |
| 16 | 2 | Usuários | OK |
| 13 | 1 | Dashboard | OK |
| 13 | 38 | Dashboards KPI | OK |
| ≤12 | - | Financeiro (Pagar/Receber/Bancos/…) e cadastros finos | OK |
| 0 | 27 | Administração de Senhas | vazio - ignorar ou limpar depois |

### Candidatos a fusão (baixo valor agora)

Grupos com 1–4 páginas (`Documentos`, `Movimentos`, `Base de Dados`,
`Permissões`, `Sessões`, `Ajuda`, `Erros`): só valeria fundir se atrapalharem a
navegação da matriz; **não** bloqueiam a Opção A.

---

## P1 - Cisão recomendada

### 1) Gestão de Pessoas (36) - 178 páginas

Composição (directory): performance 51, rh 51, portal 40, policies 15,
analytics 11, workShifts 5, pdi 4, users 1.

| Novo grupo (proposta) | Conteúdo | ~N |
|------------------------|----------|---|
| Gestão de Pessoas - Talentos / ATS | `Rh*`, requisições de pessoal | ~50 |
| Gestão de Pessoas - Portal e Solicitações | portal, tipos, aprovações, delegações | ~40 |
| Gestão de Pessoas - Desempenho e Carreira | performance, PDI, pulse, career, succession… | ~55 |
| Gestão de Pessoas - Organização e Políticas | policies, work shifts, headcount / analytics RH | ~30 |

Manter **Treinamentos (24)** e **Avaliações (25)** como estão.

### 2) Segurança e Medicina (42) - 169 páginas

Tudo em `directory=sst`. Aproximação temática:

| Novo grupo (proposta) | ~N |
|------------------------|---|
| SST - Medicina / ASO / Exames | ~38 |
| SST - Treinamentos / GHE / PPP | ~32 |
| SST - EPI | ~26 |
| SST - Equipamentos / Vistoria | ~26 |
| SST - Cadastros e vínculos | ~36 |
| SST - Acidentes / Afastamentos + Dashboard/Relatórios | ~11 |

Alinhar nomes ao submenu do menu `sst` (Cadastros, EPI, Equipamentos, etc.).

### 3) LGPD (31) - 102 páginas

| Novo grupo (proposta) | ~N |
|------------------------|---|
| LGPD - AIPD | ~15 |
| LGPD - TIA | ~14 |
| LGPD - RIPD | ~9 |
| LGPD - Inventário / ROPA / Mapping | ~18 |
| LGPD - Consentimentos + Titulares | ~16 |
| LGPD - Taxonomia (finalidades, bases, tipos, classificações) | ~20 |
| LGPD - Dashboard / Termos / Legal | ~10 |

---

## P2 - Revisar (cisão opcional, menor urgência)

### Estoque (33) - 62 páginas

Menu já separa Itens / Custeio / Cadastros. Proposta leve:

| Novo grupo | ~N |
|------------|---|
| Estoque - Itens, posições e movimentações | ~35 |
| Estoque - Custeio (períodos, DRE, simulações, fatores) | ~25 |

### CRM (34) - 59 páginas

| Novo grupo | ~N |
|------------|---|
| CRM - Operação (pipeline, clientes CRM, kanban…) | ~48 |
| CRM - Integrações / settings (WhatsApp, SAP, MCP, testes) | ~11 |

O pedaço `settings` misturado no grupo CRM é o principal cheiro; cisão em 2
já melhora “Autorizar grupo”.

---

## P3 - Observar (sem cisão agora)

### Comunicação Social (40) - 40 páginas

- timeline ~15, gamification ~16, companyEvents ~9.

Possível futuro: **Timeline**, **Gamificação**, **Eventos** (Informativos já é
grupo 30). Só vale se o time liberar gamificação e timeline a públicos
diferentes.

### Reserva de Salas (37) - 34

No limite “confortável”. Deixar.

---

## Escopo técnico da Opção A (quando executar)

1. Inventário fechado + mapa página → grupo (aceite RH/SST/LGPD/TI).
2. Migration **Expand**: `INSERT` grupos por **nome**; `UPDATE adms_pages.adms_groups_page_id`.
3. **Não** alterar `adms_access_levels_pages` (permissão continua no `page_id`).
4. Seeds resolvem grupo por **nome** (nunca hardcode de ID entre ambientes).
5. Homolog → produção com backup das duas tabelas.

### Riscos

| Risco | Mitigação |
|-------|-----------|
| Mapa incompleto | Script “páginas não classificadas”; aceite por domínio |
| “Autorizar grupo” no recorte errado | Nomes claros; liberar só após cisão P1 |
| Hardcode `groups_page_id = 36` (ou 42/31) | Grep + seeds por nome |
| Canal de Denúncias / regras especiais | Não fundir nem renomear sem ADR |
| Escopo demais (P1+P2+P3 num push) | Fases: primeiro GP, depois SST, depois LGPD |

## Ordem sugerida de execução

1. **Fase A - Gestão de Pessoas** (maior dor na matriz de RH).
2. **Fase B - SST**.
3. **Fase C - LGPD**.
4. **Fase D (opcional)** - Estoque e CRM.
5. Comunicação Social / fusões pequenas - só se houver dor real.

## Critérios de sucesso

- Nenhum `page_id` muda de permissão só pela cisão.
- Mega-grupos P1 deixam de existir como “catch-all” com >80 páginas.
- Gate do Plano Diretor: página nova → grupo existente adequado **ou** novo grupo.
- IDs de grupo sempre resolvidos por **nome** em migrations/seeds.

## Decisão pendente

Ver proposta de nomes e tabelas em
[PROPOSTA_NOVOS_GRUPOS_ACL.md](PROPOSTA_NOVOS_GRUPOS_ACL.md).

1. Confirmar prioridade P1 (GP → SST → LGPD).
2. Confirmar se P2 (Estoque/CRM) entra no mesmo projeto ou fica backlog.
3. Validar mapa detalhado página→grupo **antes** de qualquer migration.
