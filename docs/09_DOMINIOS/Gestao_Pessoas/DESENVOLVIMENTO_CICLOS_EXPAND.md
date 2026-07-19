# Ciclos de desempenho e vínculo com metas — Expand Fase 5

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 19/07/2026.
- Status: 1º incremento — ciclo operacional + FK opcional em metas/avaliações.

## Objetivo

Introduzir a entidade **ciclo** como âncora temporal de desempenho, reaproveitando
o módulo existente (`adms_performance_goals` / reviews) em vez de duplicar metas
em tabelas `rh_*`.

## Regras

- Ciclo tem nome, ano, `period_start` / `period_end` e status `draft|open|closed`.
- `period_end` >= `period_start`.
- Metas e avaliações existentes ganham `performance_cycle_id` **opcional** (Expand).
- Nova meta pode vincular-se a ciclo `draft` ou `open`; ciclo `closed` não aceita novas metas.
- Fechar ciclo não altera metas/avaliações já vinculadas (somente bloqueia novos vínculos).
- Não recria CRUD de metas — estende o fluxo atual.

## Modelo

### `adms_performance_cycles`

| Campo | Uso |
|-------|-----|
| `name` | rótulo (ex.: Ciclo 2026 H1) |
| `year` | ano civil de referência |
| `period_start` / `period_end` | vigência |
| `status` | `draft\|open\|closed` |
| `description` | observações |
| `created_by` | ator |

### FKs opcionais

- `adms_performance_goals.performance_cycle_id`
- `adms_performance_reviews.performance_cycle_id`

## UI / ACL

- `ListPerformanceCycles` (`list-performance-cycles`)
- `CreatePerformanceCycle` (`create-performance-cycle`)
- `ViewPerformanceCycle` (`view-performance-cycle`)
- `UpdatePerformanceCycle` (`update-performance-cycle`)
- Grupo/página espelhada de `ListPerformanceGoals` (grupo Desempenho)
- Menu Desempenho → Ciclos
- Formulários de meta: select de ciclo + filtro na listagem

## Fora deste incremento

- geração em massa de avaliações a partir do ciclo;
- calibração / Nine Box por ciclo;
- obrigatoriedade de ciclo em metas legadas;
- PDI operacional;
- Contract (remover períodos soltos nas reviews).

## Migration

`database/migrations/20260719250000_create_adms_performance_cycles.php`
