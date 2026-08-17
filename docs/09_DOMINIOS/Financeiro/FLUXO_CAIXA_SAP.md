# Fluxo de Caixa SAP

**Status:** V1.0 em homologação (código da primeira entrega existente; motor ainda não fechado contra o SAP)  
**ADR:** [ADR-0010](../../08_ADR/ADR-0010_FLUXO_CAIXA_SAP.md)  
**Ajuda F1:** `fin-cash-flow-dashboard`, `list-fin-cash-accounts`, `list-fin-cash-investments`

Este arquivo é o plano vivo. Não é um “plano de implementação” único: a V1 é só o
que precisa fechar `SAP = Query = Cache = Dashboard`. O restante é roadmap
evolutivo e **não** é pendência da primeira entrega.

---

## Decisões travadas (ADR-0010)

Não reabrir durante as etapas:

- um único módulo **Fluxo de Caixa SAP**;
- Dashboard com visões **diária, mensal, bancos, aplicações e detalhes dos títulos**;
- SAP **não consultado a cada abertura** (sync → cache → tela);
- efetivo e previsto **separados**;
- previsto = **saldo aberto**;
- transferências internas **neutras no consolidado**;
- aplicações **separadas** da conta corrente;
- limites **separados** da disponibilidade própria;
- **moeda local** na V1;
- fluxo previsto **não é** pipeline comercial (Pedido → NF → Título → Recebimento).

---

# Escopo homologável da V1.0

Objetivo: o núcleo financeiro conferir com exemplos reais, não acrescentar fonte
nova (pedido, recorrente, snapshot, analytics).

## O que entra

| Componente | Papel na V1 |
|---|---|
| Contas financeiras | Parametrizar caixa, banco, aplicação, trânsito; incluir/excluir do fluxo |
| Saldo inicial | Razão das contas selecionadas imediatamente antes do período |
| Movimento efetivo | `JDT1` (entradas − saídas na `RefDate`) |
| A receber | `OINV` + `INV6`, saldo aberto, por vencimento |
| A pagar | `OPCH` + `PCH6`, saldo aberto, por vencimento |
| Transferências | Neutras no consolidado; visíveis por conta |
| Aplicações SAP | Contas classificadas como aplicação, fora da conta corrente |
| Aplicações locais | Lançamentos no Administrativo (aplicação, resgate, rendimento) |
| Limites | Cadastro local; só disponibilidade ampliada |
| Dashboard | KPIs, diário, mensal, bancos, aplicações, lista de títulos a pagar/receber, drill (previsto no cache; efetivo ainda pode ir ao SAP) |

## O que não entra na V1.0

- previsões manuais e recorrentes;
- pedidos `ORDR` / `OPOR`;
- snapshot previsto × realizado e acurácia;
- análises por cliente/fornecedor como tela própria;
- projeto, categoria, centro de custo no fluxo;
- “quanto do pedido já foi faturado”;
- consulta SAP pesada a cada filtro;
- menus separados por visão.

## Critério de fechamento da V1.0

Cada linha da matriz abaixo homologada com **pelo menos um exemplo real** no SAP
(razão / títulos / extrato) até:

```text
SAP = Query = Cache = Dashboard
```

Enquanto isso não ocorrer, a V1.0 permanece aberta. Não se inicia V1.1+.

---

# Matriz de conciliação (V1.0)

| Componente | Nossa fonte | Referência SAP | Regra | Status |
|---|---|---|---|---|
| Saldo inicial | Cache sincronizado (`adms_fin_cash_opening` + diário anterior) | Fluxo / razão SAP | Soma débito − crédito das contas **caixa + bancos** selecionadas, `RefDate` &lt; início do período | ⏳ |
| Movimento efetivo | `JDT1` → `adms_fin_cash_daily` | Razão SAP / extrato | Entradas − saídas no dia; não misturar com previsto | ⏳ |
| A receber | `OINV` + `INV6` → `adms_fin_cash_forecasts` (AR) | Títulos em aberto SAP | Saldo aberto (parcela − pago); vencido aberto continua no previsto | ⏳ |
| A pagar | `OPCH` + `PCH6` → forecasts (AP) | Títulos em aberto SAP | Idem, lado fornecedor | ⏳ |
| Transferências | Mesmo `TransId` entre contas financeiras | Lançamento bancário SAP | Consolidado = 0; por conta = saída + entrada | ⏳ |
| Aplicações SAP | Contas tipo `INVESTMENT` | Razão das contas de aplicação | Fora do saldo financeiro / conta corrente | ⏳ |
| Aplicações locais | `adms_fin_cash_investments` | Administrativo (não SAP) | Somadas à parte; não duplicar rendimento já no razão da conta de aplicação | ⏳ |
| Limites | `credit_limit` na conta financeira | — (sem fonte SAP homologada) | Não compõem disponibilidade própria | ⏳ |

Status: ⏳ pendente · 🟡 em conferência · ✅ fechado.

### Como homologar cada linha

1. Escolher no SAP um dia ou um título concreto (não totais “do mês” no primeiro teste).
2. Rodar a query da API (a mesma do sync) e anotar o valor.
3. Conferir o cache MySQL (`adms_fin_cash_*`).
4. Conferir o número no Dashboard (KPI / célula do dia / drill).
5. Só marcar ✅ se os quatro coincidirem, com nota do exemplo (DocNum / TransId / conta).

Roteiro mínimo (já alinhado ao plano técnico original): NF a receber aberta; NF com parcelas; pagamento parcial; pagamento total; cancelamento; transferência entre bancos; tarifa; aplicação/resgate local; título vencido ainda aberto.

---

# Roadmap evolutivo

Não é backlog da V1. Só avança depois de `SAP = Query = Cache = Dashboard`.

| Fase | Objetivo | Prioridade |
|---|---|---|
| **V1.0** | Homologar saldo inicial, contas financeiras, `JDT1`, A/R, A/P, transferências e aplicações | Agora |
| **V1.1** | Drill-down 100% local/cacheado, inclusive efetivo | Depois da homologação |
| **V1.2** | Previsões manuais e recorrentes locais | Próxima evolução funcional |
| **V1.3** | Pedidos `ORDR`/`OPOR` com anti-duplicidade contra NF/título | Evolução |
| **V1.4** | Snapshot previsto × realizado e acurácia | Evolução |
| **V1.5** | Análises por cliente/fornecedor | Evolução |
| **V1.6** | Projeto, categoria, centro de custo e análises avançadas | Futuro |

### Notas por fase

**V1.1** — persistir linhas efetivas no sync (hoje o clique no efetivo ainda pode ir ao SAP). O previsto já está no cache.

**V1.2** — melhor evolução funcional depois do núcleo: a tesouraria lança o que o SAP não tem, no mesmo molde das aplicações. Origem visível no fluxo. Não misturar com título SAP.

**V1.3** — pedido só entra no previsto se ainda não gerou parcela em aberto. Quando nascer `INV6`/`PCH6`, o pedido sai. Não usar essa fase para KPI de conversão comercial.

**V1.4** — acurácia exige **congelar** o previsto de um dia e comparar com o efetivo depois. Comparar previsto de hoje com efetivo de hoje não mede qualidade da previsão.

**V1.5 / V1.6** — cliente/fornecedor já existem no título (`CardCode`). Projeto/categoria/CC só com de-para SAP explícito; sem mapa a tela mente.

---

# Fronteira: caixa vs. pipeline

```text
Fluxo de caixa previsto     Pipeline comercial
título em aberto            pedido → NF → título → recebimento
vencimento                  conversão / funil
saldo diário                “quanto já foi faturado”
```

Um pedido pode, mais tarde, **ajudar a prever caixa**. “Quanto dos pedidos já foi faturado” **não** entra no acumulado diário.

---

# Implementação atual (referência)

| Peça | Onde |
|---|---|
| Dashboard | `fin-cash-flow-dashboard` |
| Sync web / CLI | `fin-cash-flow-dashboard-sync` / `php scripts/sync_fin_cash_flow_sap.php` |
| Contas | `list-fin-cash-accounts` |
| Aplicações locais | `list-fin-cash-investments` |
| Serviço SAP | `FinCashFlowSapSyncService` (API já parametrizada) |
| Consolidação | `FinCashFlowDashboardService` (só MySQL + lançamentos locais) |

A existência do código **não** fecha a V1.0. Fecha a matriz.
