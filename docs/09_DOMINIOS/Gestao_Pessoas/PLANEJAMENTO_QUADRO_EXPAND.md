# Planejamento de Quadro — Expand Fase 6 (1º incremento)

- Domínio: Gestão de Pessoas / People Analytics.
- Data: 20/07/2026.
- Status: 1º incremento — linhas de quadro planejado vs efetivo.

## Objetivo

Permitir ao RH registrar o **quadro planejado** (headcount alvo) por
departamento, cargo e período (mês/ano), comparar com o **efetivo** atual
(`adms_users` ativos) e ver o **gap** (planejado − efetivo).

## Regras

- Linha: `department_id`, `position_id` (opcional = só área),
  `period_year`, `period_month` (1–12), `planned_count` (≥ 0),
  `status` (`draft|active|closed`), `notes`, `created_by`.
- Unicidade: `(department_id, position_id, period_year, period_month)` —
  `position_id` NULL tratado como 0 na unique lógica (uma linha sem cargo por área/período).
- Efetivo: `COUNT` de `adms_users` com mesmo dept/cargo,
  `status = 'Ativo'` e `data_desligamento` vazio/nulo (não persistido).
- Gap = `planned_count − actual_count` (positivo = falta gente; negativo = acima do plano).
- Status `closed`: não edita quantidade neste incremento (só notas/status via update controlado).

## Modelo

- `adms_headcount_plans`

## UI / ACL

- `ListHeadcountPlans`, `CreateHeadcountPlan`, `ViewHeadcountPlan`,
  `UpdateHeadcountPlan`
- Menu People Analytics → Planejamento de Quadro
- Manual com padrão completo (Função, Termos, Fluxo, Campos, Problemas)

## Fora deste incremento

- workflow de aprovação do quadro;
- forecast multi-ano / cenários;
- sync automático requisição → vaga → quadro;
- histórico mensal snapshot do efetivo;
- custos e retenção (próximo item Fase 6).

## Migration

`database/migrations/20260720290000_create_adms_headcount_plans.php`

## Dependências

Pulse/eNPS 1º incremento; cadastros de departamento e cargo; usuários com
departamento/cargo preenchidos para o efetivo fazer sentido.
