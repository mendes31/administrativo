# Painel de avaliadores da entrevista — Expand Fase 2

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: painel interno entregue (sem convite/comunicação).

## Modelo

Tabela `rh_entrevista_avaliadores`:

- unique `(rh_entrevista_id, avaliador_id)`
- `papel`: `principal` | `avaliador`
- `status`: `ativo` | `removido` (remoção lógica)
- backfill de `rh_entrevistas.entrevistador_id` como principal

O campo legado `entrevistador_id` permanece canônico para o principal durante o Expand.

## Regras

- UI em `RhEntrevistasEdit` / `RhEntrevistasView` (sem nova permissão/rota).
- Designação **não** concede ACL e **não** dispara e-mail/notificação.
- Scorecard continua por `avaliador_id`; o painel só mostra presença/status da avaliação.
- Remoção é lógica para preservar histórico.

## Migration

`database/migrations/20260719180000_create_rh_entrevista_avaliadores.php`

## Próximos incrementos

- policy “avaliador designado vê só suas entrevistas” + escopo de listagem;
- convite/aceite via outbox (Fase 0.5/2);
- agenda e reagendamento com histórico.
