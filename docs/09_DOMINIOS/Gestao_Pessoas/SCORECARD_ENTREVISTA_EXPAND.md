# Scorecard de entrevista — Expand Fase 2

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: scorecard estruturado na edição da entrevista (1º incremento).

## Modelo

- `rh_entrevista_scorecards` — um por `(rh_entrevista_id, avaliador_id)`
- `rh_entrevista_scorecard_itens` — snapshot de critério, peso, nota (0–10) e comentário

Critérios default em `RhEntrevistaScorecardCatalog` (PHP): comunicação, técnica, cultura, motivação, resolução de problemas.

## Regras

- UI na tela `RhEntrevistasEdit` (sem nova permissão).
- Nota ponderada é **informativa**; não altera `resultado` nem o pipeline.
- Visualização exige `RhPermissionService::canManageEntrevista` (correção de autorização do objeto).
- Feedback legado permanece; scorecard é aditivo (Expand).

## Migration

`database/migrations/20260719170000_create_rh_entrevista_scorecards.php`

## Próximos incrementos (Fase 2)

- múltiplos avaliadores com convite/agenda;
- templates de comunicação / outbox;
- calendário e reagendamento.
