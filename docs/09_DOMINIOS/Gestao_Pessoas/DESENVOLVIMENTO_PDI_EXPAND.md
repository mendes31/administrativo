# PDI operacional — Expand Fase 5

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — planos + ações + vínculos opcionais a ciclo/competência/treinamento.

## Objetivo

Tornar operacional o schema PDI já existente (`adms_pdi_*`), com CRUD de planos
e ações, reaproveitando competências e treinamentos do sistema.

## Regras

- Plano pertence a `user_id` (colaborador); `manager_id` opcional.
- Status do plano: `draft|active|completed|cancelled`.
- Período `period_end` >= `period_start`.
- `performance_cycle_id` opcional (Expand).
- Ações: tipos `training|course|mentoring|project|reading|other`; status
  `pending|in_progress|completed|cancelled`.
- `training_id` opcional com FK a `adms_trainings`.
- Competências do plano: preferir `competency_id` do catálogo; `competency_name`
  preenchido a partir do catálogo quando houver ID.
- Sem metas/feedbacks PDI, aprovação formal ou sync automático de treinamento.

## Modelo (deltas)

- `adms_pdi_plans.performance_cycle_id` (nullable FK)
- `adms_pdi_competencies.competency_id` (nullable FK)
- FK `adms_pdi_actions.training_id` → `adms_trainings`

## UI / ACL

- `ListPdiPlans` (`list-pdi-plans`)
- `CreatePdiPlan` (`create-pdi-plan`)
- `ViewPdiPlan` (`view-pdi-plan`) — ações e competências inline
- `UpdatePdiPlan` (`update-pdi-plan`)
- ACL espelhada de `ListPerformanceGoals`; directory `pdi`
- Menu Desempenho → PDI

## Fora deste incremento

- `adms_pdi_goals` / `adms_pdi_feedbacks`;
- dashboard/relatórios PDI;
- aprovação `approved_by`;
- conclusão automática via treinamento;
- carreira / Nine Box.

## Migration

`database/migrations/20260720210000_expand_adms_pdi_operational.php`
