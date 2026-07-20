# Custos e Retenção — Expand Fase 6 (1º incremento)

- Domínio: Gestão de Pessoas / People Analytics.
- Data: 20/07/2026.
- Status: 1º incremento — qualidade de retenção (sem custo financeiro).

## Objetivo

Expor no **People Analytics** indicadores de **retenção** (early turnover,
faixas de tenure no desligamento, estabilidade do efetivo, taxa de retenção)
usando dados já existentes de vínculo.

## Por que sem “custo” neste corte

Não há salário/remuneração em `adms_users` nem valores na folha digital.
Salários em vagas/ofertas **não** são fonte segura para custo de headcount.
Custo estimado fica para incremento futuro com fonte canónica.

## Indicadores

1. **Taxa de retenção (%)** — `100 − turnover` (mesma base do turnover do período).
2. **Early turnover** — desligamentos com tenure &lt; 90 dias e &lt; 365 dias (count + %).
3. **Faixas de tenure no desligamento** — &lt;90d, 90d–1a, 1–3a, ≥3a.
4. **Tenure médio no desligamento** — só desligados do período filtrado.
5. **Estabilidade do efetivo** — % ativos com tenure ≥ 1 ano e ≥ 3 anos (snapshot fim do período).
6. **Qualidade** — reforço dos % regrettable / non / não classificado (já calculados).

## UI / ACL

- Bloco “Retenção” em `people-analytics` (mesma ACL).
- JSON `people-analytics/metrics` → chave `retention`.

## Fora deste incremento

- custo de reposição / custo de turnover em R$;
- voluntary vs involuntary (só existe texto livre + regrettable);
- forecast de retenção;
- automação/IA.

## Dependências

People Analytics + `tipo_impacto_desligamento` no cadastro/histórico.
