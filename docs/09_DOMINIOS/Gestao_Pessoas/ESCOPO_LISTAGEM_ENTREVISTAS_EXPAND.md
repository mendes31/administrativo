# Escopo de listagem de entrevistas — Expand/Contract Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026 (Expand); 27/07/2026 (Contract).
- Status: infraestrutura entregue; **Contract aplicado**.

## Modelo

Permissão técnica `RhEntrevistasViewAll`:

- Concedida a quem já tem `RhEntrevistas` (Expand).
- Com a permissão / Super: lista todas.
- Sem a permissão (`related`): entrevistador principal **ou** avaliador ativo no painel **ou** responsável da vaga vinculada.

## Contract

Migration `20260727140000_contract_rh_entrevistas_view_all_scope.php`:

- **Mantém** ViewAll em níveis RH / DP / Super Admin.
- **Revoga** nos demais.

## Visualização × listagem

`RhPermissionService::canViewEntrevista` alinha o detalhe ao modo `related`
(entrevistador / avaliador ativo / responsável / ViewAll / Super).
Edição/exclusão continuam em `canManageEntrevista` (pipeline da vaga).

## Migrations

- `database/migrations/20260719220000_register_rh_entrevistas_view_all_scope.php` (Expand)
- `database/migrations/20260727140000_contract_rh_entrevistas_view_all_scope.php` (Contract)
