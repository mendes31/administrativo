# Dashboard de Produção (BEAS / SAP)

**Status:** V1.0 em implementação (cache + dashboard; de-para BEAS pode ser
refinado)  
**ADR:** [ADR-0011](../../08_ADR/ADR-0011_MODULO_PRODUCAO_BEAS.md)  
**Ajuda F1:** `prod-production-dashboard`

Este arquivo é o plano vivo. A V1 fecha `SAP/BEAS = Query = Cache = Dashboard`
para os indicadores de ordem/volume. OEE, paradas e consumo de MP são roadmap.

---

## Decisões travadas (ADR-0011)

Não reabrir durante as etapas:

- um único módulo **Produção**;
- Dashboard concentra as visões;
- SAP/BEAS **não consultado a cada abertura** (sync → cache → tela);
- SKU (unidades recebidas) ≠ produto (`ItemCode` distintos);
- fonte operacional = BEAS/SAP; Administrativo = cache;
- custeio fabril **não** entra neste painel.

---

# Escopo homologável da V1.0

## O que entra

| Componente | Papel na V1 |
|---|---|
| SKUs produzidos | Soma das unidades recebidas (`OIGN`/`IGN1.Quantity`) no período |
| Produtos produzidos | Distintos `ItemCode` recebidos no período |
| Volume produzido | Mesma soma de unidades (série realizado × planejado) |
| Ordens iniciadas | OPs cujo **primeiro** apontamento (`MIN(BEAS_ARBZEIT.ANFZEIT)`) cai no período |
| Ordens concluídas | Posição principal encerrada (`ABGKZ = J`) com data de encerramento no período |
| Em andamento / atraso | Snapshot das OPs abertas (`ABGKZ <> J`) vs. prazo (`LIEFERDATUM`) |
| Aderência ao plano | Recebido no período / `BEAS_FTPOS.MENGE` das posições com `BELDAT` no período |
| Série mensal | Unidades, produtos distintos, iniciado × concluído, realizado × planejado |
| Ranking | Top 20 `ItemCode` por quantidade recebida (query 4 do PCP) |
| Ordens em aberto | Tabela operacional (OP, item, depósito, prazo, status) |
| Sync | Incremental no 1º acesso do dia + botão; `--full` só CLI |

Recorte fixo do PCP: somente `ItemCode` `4%`, `STUFE = 0`, depósitos `TJQP` e `APQP`.

## O que não entra na V1.0 (UI mostra "—")

| Indicador | Motivo |
|---|---|
| OEE (disponibilidade × performance × qualidade) | Exige apontamento de parada/ciclo BEAS |
| Tempo de parada por linha/motivo | Tabelas de tempo BEAS ainda não mapeadas |
| Taxa de refugo fina | `RjctQty` do `OWOR` entra se vier; senão V1.1 |
| Lead time / ciclo médio | Depende de início/fim confiáveis no BEAS |
| Consumo MP real vs. planejado | BOM da OP (BEAS_FTSTL / WOR1) |
| Produtividade por turno | Recurso/turno BEAS |
| Custo unitário | Domínio Custos / CUSTEIO FABRIL |

## Fonte na V1

1. **Produzido (cards 1–4, ranking, série mensal):** `OIGN` + `IGN1` com
   `U_beas_belnrid`, `ItemCode LIKE '4%'`, `WhsCode IN ('TJQP','APQP')` e
   existência de `BEAS_FTPOS` (`BELNR_ID` + `ItemCode`, `STUFE = 0`). Data =
   `OIGN.DocDate` (fim exclusivo no HANA, inclusivo no cache DATE).
2. **Início real:** `MIN(BEAS_ARBZEIT.ANFZEIT)` por OP, depois filtro de
   período (não qualquer apontamento no intervalo).
3. **Refugo:** `BEAS_ARBZEIT.MENGE_GUT_RM` / `MENGE_SCHLECHT_RM` nas posições
   `STUFE = 0`.
4. **Plano / abertas:** `BEAS_FTPOS` + `BEAS_FTHAUPT` (`MENGE`, `ABGKZ`,
   `BELDAT`, `LIEFERDATUM`, `AUFTRAG`).
5. **Fallback:** `OWOR` só se `BEAS_FTHAUPT`/`BEAS_FTPOS` não existirem.

Cache MySQL: `adms_prod_receipt_fact` (entradas), `adms_prod_wo_fact` (OPs),
`adms_prod_scrap_day` (refugo diário).

Homologação: os cards SKUs/Produtos devem bater com a query 1 do PCP no
mesmo período e depósito.

## Homologação

`SAP/BEAS = Query = Cache = Dashboard` para: SKUs, produtos, volume, iniciadas,
concluídas, em aberto e aderência, num período real combinado com o BEAS.
