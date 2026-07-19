# Escopo de listagem de entrevistas — Expand Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: infraestrutura entregue; comportamento atual preservado.

## Modelo

Permissão técnica `RhEntrevistasViewAll`:

- Concedida a quem já tem `RhEntrevistas`.
- Com a permissão / Super: lista todas.
- Sem a permissão (`related`): entrevistador principal **ou** avaliador ativo no painel **ou** responsável da vaga vinculada.

## Contract futuro

Retirar `RhEntrevistasViewAll` de perfis restritos quando a política estiver definida.

## Migration

`database/migrations/20260719220000_register_rh_entrevistas_view_all_scope.php`
