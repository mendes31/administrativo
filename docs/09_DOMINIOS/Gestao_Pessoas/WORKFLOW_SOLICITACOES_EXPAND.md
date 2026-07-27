# Workflow de solicitações (etapas, delegação, escalação) — Expand

- Domínio: Gestão de Pessoas / Solicitações (Portal).
- Data: 21/07/2026.
- Status: 1º incremento — etapas por tipo, delegação, escalação SLA, visão de árvore.
- ADR: [ADR-0007](../../08_ADR/ADR-0007_WORKFLOW_SOLICITACOES.md).

## Objetivo

Separar **quem aprova** de **quem acompanha**, com rede de segurança na ausência do
gestor imediato: delegação → escalação 1 nível → RH.

## Regras

### Cadastro do fluxo (por tipo)

Em **Gestão de Pessoas → Organização → Tipos de Solicitação → Editar**:

- editor livre de etapas (adicionar / remover / reordenar via ordem da lista);
- cada etapa define o aprovador: `immediate` | `hr` | `fixed_user`;
- na etapa `immediate`: SLA + máx. níveis + política (`next_level` | `next_stage` | `hr` | `none`);
- exceções por nível de acesso (solicitante ou gestor imediato) **pulam** etapas `immediate` e seguem para a próxima do fluxo.

Flags `requires_manager_approval` / `requires_hr_approval` passam a ser **derivadas** das etapas.

Manual: `docs/manual/content/gestao_pessoas/update-request-type.html`.

### Etapas

- Tabela `adms_request_type_stages` (`immediate|hr|fixed_user`), ordenadas por `stage_order`.
- Motor avança pela ordem configurada (não hardcode gestor→RH).
- Status legados: `pending_manager_approval` / `pending_hr_approval`.
- Snapshot na solicitação: `escalate_after_hours`, `max_escalation_levels`, `escalation_count`.

### Ausência do imediato

1. Delegação vigente → substituto.
2. SLA + limíte de níveis → sobe N vezes na hierarquia.
3. Limite esgotado ou sem gestor acima → RH.

## Migrations

- `database/migrations/20260721100000_expand_employee_request_workflow.php`
- `database/migrations/20260721110000_add_max_escalation_levels_to_request_workflow.php`
- `database/migrations/20260721120000_add_skip_immediate_access_levels_to_request_types.php`
- `database/migrations/20260721130000_add_requires_hr_approval_to_request_types.php`
