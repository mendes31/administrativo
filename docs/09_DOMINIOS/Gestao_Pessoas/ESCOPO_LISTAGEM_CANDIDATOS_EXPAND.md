# Escopo de listagem de candidatos — Expand Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: infraestrutura entregue; comportamento atual preservado.

## Modelo

Permissão técnica `RhCandidatosViewAll`:

- Concedida a quem já tem `RhCandidatos`.
- Com a permissão / Super: lista todos.
- Sem a permissão (`related`): apenas candidatos vinculados a pelo menos uma vaga cujo `responsavel_id` é o usuário.
- Candidatos **sem vaga** só aparecem no modo `all`.

## Contract futuro

1. Retirar `RhCandidatosViewAll` de perfis restritos quando a política estiver definida.
2. Restringir bypass de gestor CRM por área/equipe (lacuna da matriz).

## Alinhamento objeto × listagem

`RhCandidatoPermissionService::canAccessCandidato` usa o mesmo critério ViewAll
da listagem (`resolveCandidatosListScope`). Sem ViewAll: gestor ou responsável
de vaga vinculada.

## Migration

`database/migrations/20260719230000_register_rh_candidatos_view_all_scope.php`
