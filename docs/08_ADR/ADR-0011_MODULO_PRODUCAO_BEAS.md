# ADR-0011 — Módulo Produção: um dashboard, cache local e fonte BEAS/SAP

- Status: Aprovado
- Data: 2026-08-18
- Responsável: Arquitetura / PCP / Produção
- Módulos impactados: `production` (dashboard); sync via API SAP B1 já
  parametrizada; ACL no grupo **Produção**

## Contexto

A produção fabril é gerenciada no **BEAS** (add-on de manufatura do SAP
Business One), não no Administrativo. Os protótipos de dashboard pedem visão
gerencial (SKUs, produtos, ordens, aderência, eficiência) sem substituir o
chão de fábrica.

Consultar o HANA a cada filtro repetiria o problema já rejeitado no
Dashboard de Vendas CRM e no Fluxo de Caixa SAP (timeout, carga e tela lenta).
As tabelas BEAS específicas ainda podem ser refinadas; a V1 precisa nascer com
contrato estável de cache e UI.

## Decisão

1. **Um único módulo** *Produção*. Na V1 não criar pastas de menu por visão
   (ordens, OEE, paradas, Pareto). O **Dashboard** concentra os indicadores.
2. O SAP/BEAS **não é consultado a cada abertura**. Fluxo: API SAP →
   sincronização → cache MySQL → dashboard. Sync incremental no 1º acesso do
   dia e no botão; carga completa só via CLI.
3. **Fonte de verdade operacional permanece o BEAS/SAP.** O Administrativo
   guarda **cache** (projeção), não cadastro de OP.
4. **SKU ≠ produto (glossário PCP).** No dashboard, **SKUs produzidos** =
   unidades recebidas (`SUM(IGN1.Quantity)`). **Produtos produzidos** =
   `ItemCode` distintos. Volume no gráfico é a mesma quantidade recebida.
   Não usar grupo de item (`OITB`) como “produto” neste painel.
5. **Produzido vem da entrada de estoque**, não do cabeçalho da OP: `OIGN` +
   `IGN1` com `U_beas_belnrid`, item `4%`, depósitos `TJQP`/`APQP` e posição
   principal `BEAS_FTPOS.STUFE = 0`. Início real = `MIN(BEAS_ARBZEIT.ANFZEIT)`.
   `OWOR` só entra se as tabelas BEAS não existirem.
6. OEE, paradas e ciclo médio ficam na UI como **V1.1** (valor “—”). Taxa de
   refugo usa `BEAS_ARBZEIT` (`MENGE_GUT_RM` / `MENGE_SCHLECHT_RM`) quando o
   cache diário existir. Não inventar número.
7. **Moeda e custeio fabril ficam fora.** Custo por unidade continua no
   domínio Custos/Estoque (`CUSTEIO_FABRIL`).

Estas decisões não se reabrem em cada etapa. Mudança incompatível gera novo ADR.

## Alternativas consideradas

- Consulta HANA a cada filtro — rejeitada: mesmo padrão rejeitado no
  ADR-0010 e no Dashboard de Vendas CRM.
- Vários menus (ordens, eficiência, paradas) já na V1 — rejeitada: duplica
  ACL e sync sem o mapeamento BEAS fechado.
- Mock com dados fictícios na tela de produção — rejeitada: quebra
  homologação `SAP = Query = Cache = Dashboard`.
- Tratar SKU e produto como o mesmo contador — rejeitada: os dois
  protótipos e o PCP distinguem código de item e família/produto.

## Consequências

### Positivas

- PCP e desenvolvimento compartilham o recorte da V1;
- a tela funciona vazia/com `OWOR` até o de-para BEAS;
- evoluções (OEE, downtime, consumo MP) não parecem pendência da primeira
  entrega.

### Negativas e riscos

- `OWOR` pode ser incompleto se o BEAS não gravar a OP padrão;
- linha produtiva/turno podem vir vazios até mapear recurso BEAS;
- OEE e paradas ficam visíveis mas sem valor até V1.1.

## Relação com o código

- Controllers: `ProdProductionDashboard`, `ProdProductionDashboardData`,
  `ProdProductionDashboardSync` em `app/adms/Controllers/production/`.
- Sync: `ProdProductionSapSyncService` + `scripts/sync_prod_production_sap.php`.
- Plano vivo:
  [`docs/09_DOMINIOS/Producao/DASHBOARD_PRODUCAO_BEAS.md`](../09_DOMINIOS/Producao/DASHBOARD_PRODUCAO_BEAS.md).
