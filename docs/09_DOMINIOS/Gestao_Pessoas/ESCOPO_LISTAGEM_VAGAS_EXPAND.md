# Escopo de listagem de vagas — Expand/Contract Fase 0.5

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026 (Expand); 27/07/2026 (Contract piloto).
- Status: infraestrutura entregue; **Contract parcial aplicado em Vagas**.

## Modelo

Permissão técnica `RhVagasViewAll`:

- Migration Expand concede a **todos** os níveis que já possuem `RhVagas`.
- Com a permissão (ou Super Admin): listagem = todas as vagas (`mode=all`).
- Sem a permissão: listagem = apenas `responsavel_id = usuário` (`mode=responsible`).

## Contract (piloto Vagas)

Migration `20260727120000_contract_rh_vagas_view_all_scope.php`:

- **Mantém** `RhVagasViewAll` em níveis cujo nome indica RH / DP / Super Admin
  (ex.: “Analista de Recursos Humanos”).
- **Revoga** nos demais (ex.: “Gerente de TI”), ativando o filtro `responsible`.
- Detalhe da vaga (`RhVagasView`) exige `canViewVaga` (ViewAll / Super / responsável).
- Lista de candidatos disponíveis para vínculo na view respeita `resolveCandidatosListScope`.

Pendências: nenhuma no Contract ViewAll; gestor CRM restrito por área/time (`isManagerOfVaga`).

## Migrations

- `database/migrations/20260719210000_register_rh_vagas_view_all_scope.php` (Expand)
- `database/migrations/20260727120000_contract_rh_vagas_view_all_scope.php` (Contract)
