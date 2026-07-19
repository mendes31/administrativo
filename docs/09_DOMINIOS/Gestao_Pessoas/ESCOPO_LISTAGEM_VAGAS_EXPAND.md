# Escopo de listagem de vagas — Expand Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: infraestrutura entregue; comportamento atual preservado.

## Modelo

Permissão técnica `RhVagasViewAll`:

- Migration concede a **todos** os níveis que já possuem `RhVagas`.
- Com a permissão (ou Super Admin): listagem = todas as vagas (`mode=all`).
- Sem a permissão: listagem = apenas `responsavel_id = usuário` (`mode=responsible`).

## Contract futuro (não feito agora)

Retirar `RhVagasViewAll` de perfis de gestor/responsável para ativar o filtro em produção.
Decisões pendentes: escopo de gestor por área/equipe; requisições.

## Migration

`database/migrations/20260719210000_register_rh_vagas_view_all_scope.php`
