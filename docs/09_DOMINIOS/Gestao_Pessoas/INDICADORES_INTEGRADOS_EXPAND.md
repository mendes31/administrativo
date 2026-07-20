# Indicadores Integrados — Expand Fase 6 (1º incremento)

- Domínio: Gestão de Pessoas / People Analytics.
- Data: 20/07/2026.
- Status: 1º incremento — faixa de KPIs no dashboard People Analytics.

## Objetivo

Exibir no **People Analytics** um resumo operacional das capacidades das
Fases 5 e 6 (desenvolvimento, clima, quadro), sem criar página/ACL nova.

## Indicadores

1. **Ciclo aberto** — % avaliações concluídas / total no ciclo `open` mais recente.
2. **PDI** — planos `active` + média de `%` das ações desses planos.
3. **eNPS** — score da campanha eNPS mais recente (qualquer status).
4. **Quadro** — soma de gaps das linhas `active` do mês/ano corrente.
5. **Talent pool** — nomeações `active`.
6. **Sucessão** — cargos críticos ativos; quantos têm sucessor; quantos `ready_now`.

## UI / ACL

- Mesma tela `PeopleAnalytics` / `people-analytics`.
- Bloco “Indicadores integrados” com links para as listagens.
- Endpoint JSON `people-analytics/metrics` inclui chave `integrated`.

## Fora deste incremento

- filtrar esses KPIs pelos filtros demográficos GET;
- histórico multi-período / drill-down;
- automação/IA.

Retenção qualitativa: ver [CUSTOS_RETENCAO_EXPAND.md](CUSTOS_RETENCAO_EXPAND.md).

## Dependências

Ciclos, PDI, pulse, quadro, talent pool e sucessão já entregues.
