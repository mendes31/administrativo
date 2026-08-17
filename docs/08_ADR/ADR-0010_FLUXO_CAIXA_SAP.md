# ADR-0010 — Fluxo de Caixa SAP: um módulo, cache local e fronteiras do previsto

- Status: Aprovado
- Data: 2026-08-14
- Responsável: Arquitetura / Tesouraria / Financeiro
- Módulos impactados: `cashFlow` (dashboard, contas financeiras, aplicações
  locais), sync via API SAP B1 já parametrizada; consome ACL do grupo de
  relatórios financeiros (`CashFlow`)

## Contexto

A tesouraria opera o caixa numa planilha (efetivo, previsto, bancos, aplicações
e limites). O SAP Business One é a fonte transacional, mas a tela padrão do ERP
não substitui a visão gerencial. Uma primeira entrega no Administrativo já
sincroniza contas, `JDT1`, A/R, A/P e lançamentos locais de aplicação.

Planos posteriores misturaram, no mesmo backlog, homologação do motor
(saldo = razão) com evoluções (pedidos, recorrentes, acurácia, analytics por
projeto). Sem decisões travadas, cada etapa reabre o desenho: vários menus,
consulta SAP a cada abertura, pedido misturado com título, limite somado ao
caixa próprio.

## Decisão

1. **Um único módulo** *Fluxo de Caixa SAP* no Financeiro. Não criar pastas de
   menu por visão (diário, mensal, posição, análises) na V1.
2. O **Dashboard** concentra as visões: diário, mensal, bancos/contas, aplicações
   e o detalhe dos títulos a pagar/receber. Contas financeiras e lançamentos de
   aplicação são telas de apoio, não painéis paralelos.
3. O SAP **não é consultado a cada abertura** da tela. Fluxo: API SAP →
   sincronização → cache MySQL → dashboard. Sync incremental no 1º acesso do
   dia e no botão; carga completa só via CLI. Drill-down de efetivo pode
   consultar o SAP até a V1.1 (depois fica 100% no cache).
4. **Efetivo e previsto permanecem visualmente e analiticamente separados.**
   Efetivo = movimento que já alterou caixa/banco (`JDT1.RefDate`). Previsto =
   compromisso ainda aberto, pela data de vencimento.
5. **Previsto usa saldo aberto** (parcela − pago), nunca o valor original
   integral. Título vencido e ainda aberto continua no previsto, destacado
   como vencido.
6. **Transferência interna é neutra no consolidado** (impacto zero) e permanece
   visível na visão por conta.
7. **Aplicações ficam fora da conta corrente.** Disponibilidade própria =
   saldo financeiro + aplicações. Limites **não** entram nessa conta; só na
   disponibilidade ampliada.
8. **Moeda local na V1.** Documentos em moeda estrangeira ficam fora do
   escopo homologável até decisão explícita.
9. **Fluxo financeiro previsto ≠ pipeline comercial.** Pedido de venda/compra
   pode, no futuro (V1.3), ajudar a prever caixa, com anti-duplicidade contra
   NF/título. “Quanto dos pedidos já foi faturado” é análise Pedido → NF →
   Título → Recebimento e **não contamina o saldo diário**.
10. Previsões manuais e recorrentes (V1.2) são **fonte local**, no mesmo
    espírito das aplicações: o SAP não é a tesouraria.
11. Homologação da V1.0 exige `SAP = Query = Cache = Dashboard` na matriz de
    conciliação. Evoluções (V1.1+) só depois dessa igualdade.

Estas decisões não se reabrem em cada etapa. Mudança incompatível gera novo ADR.

## Alternativas consideradas

- Consulta SAP a cada filtro — rejeitada: timeout, carga no HANA e tela
  lenta; o padrão já usado (Dashboard de Vendas CRM) é cache local.
- Vários menus (diário, mensal, posição, previsões, análises) já na V1 —
  rejeitada: duplica ACL, sync e filtros sem o motor homologado.
- Somar pedido aberto no mesmo acumulado dos títulos — rejeitada: duplica
  caixa quando o pedido vira NF.
- Tratar limite bancário como saldo próprio — rejeitada: mistura recurso
  próprio com crédito.
- Medir acurácia comparando previsto de hoje com efetivo de hoje —
  rejeitada: são naturezas diferentes; acurácia exige snapshot (V1.4).

## Consequências

### Positivas

- tesouraria e desenvolvimento compartilham o mesmo recorte da V1;
- evoluções não parecem pendência da primeira entrega;
- pipeline comercial não distorce o caixa diário.

### Negativas e riscos

- drill de efetivo ainda pode ir ao SAP até a V1.1;
- pedidos e recorrentes ficam de fora da V1 — a planilha atual pode ter
  previsões que o dashboard ainda não mostra;
- limites dependem de cadastro local até existir fonte SAP.

## Relação com o código

- Controllers: `FinCashFlowDashboard`, `FinCashFlowDashboardData`,
  `FinCashFlowDashboardSync`, contas e aplicações em
  `app/adms/Controllers/cashFlow/`.
- Sync: `FinCashFlowSapSyncService` + `scripts/sync_fin_cash_flow_sap.php`.
- Plano vivo e matriz de conciliação:
  [`docs/09_DOMINIOS/Financeiro/FLUXO_CAIXA_SAP.md`](../09_DOMINIOS/Financeiro/FLUXO_CAIXA_SAP.md).
