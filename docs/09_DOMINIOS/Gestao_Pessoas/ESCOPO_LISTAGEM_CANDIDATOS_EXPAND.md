# Escopo de listagem de candidatos — Expand/Contract Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026 (Expand); 27/07/2026 (Contract).
- Status: infraestrutura entregue; **Contract aplicado** (mesmo critério de Vagas).

## Modelo

Permissão técnica `RhCandidatosViewAll`:

- Concedida a quem já tem `RhCandidatos` (Expand).
- Com a permissão / Super: lista todos.
- Sem a permissão (`related`): apenas candidatos vinculados a pelo menos uma vaga cujo `responsavel_id` é o usuário.
- Candidatos **sem vaga** só aparecem no modo `all`.

## Contract

Migration `20260727130000_contract_rh_candidatos_view_all_scope.php`:

- **Mantém** ViewAll em níveis RH / DP / Super Admin.
- **Revoga** nos demais (filtro `related` passa a valer).

Pendência conhecida: restringir bypass de gestor CRM por área/equipe (matriz).

## Alinhamento objeto × listagem

`RhCandidatoPermissionService::canAccessCandidato` usa o mesmo critério ViewAll
da listagem (`resolveCandidatosListScope`). Sem ViewAll: gestor ou responsável
de vaga vinculada.

## Migrations

- `database/migrations/20260719230000_register_rh_candidatos_view_all_scope.php` (Expand)
- `database/migrations/20260727130000_contract_rh_candidatos_view_all_scope.php` (Contract)
