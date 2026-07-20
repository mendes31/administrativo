# Avaliações de desempenho vinculadas ao ciclo — Expand Fase 5

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 2º incremento — UI/regras de vínculo review ↔ ciclo (FK já no Expand 1).

## Objetivo

Permitir vincular avaliações 90°/180°/360°/anual ao ciclo de desempenho já
existente, reaproveitando o CRUD em `performance/` (sem novo módulo).

## Regras

- `performance_cycle_id` opcional (legado sem ciclo continua válido).
- Nova avaliação só vincula a ciclo `draft` ou `open`.
- Ciclo `closed` não aceita novos vínculos; vínculo já existente pode ser mantido.
- Ao escolher um ciclo na criação, o período da avaliação pode ser pré-preenchido
  com `period_start` / `period_end` do ciclo (editável).
- Mesmas regras de ACL do CRUD atual de avaliações.

## Modelo

Sem migration nova. Usa:

- `adms_performance_reviews.performance_cycle_id` (já criada em `20260719250000`)

## UI / ACL

- Select de ciclo em criar/editar avaliação
- Filtro e coluna na listagem
- Exibição no detalhe + link a partir do ciclo
- Páginas existentes (`List/Create/Update/ViewPerformanceReview`)

## Fora deste incremento

- geração em massa de avaliações por ciclo;
- calibração;
- Nine Box filtrado por ciclo (pode usar filtro de período já existente);
- tornar ciclo obrigatório;
- PDI operacional.

## Dependência

[DESENVOLVIMENTO_CICLOS_EXPAND.md](DESENVOLVIMENTO_CICLOS_EXPAND.md)
