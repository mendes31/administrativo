# Agenda e reagendamento de entrevista — Expand Fase 2

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: histórico auditável de reagendamento entregue.

## Modelo

Tabela append-only `rh_entrevista_reagendamentos`:

- `data_hora_anterior` / `data_hora_nova`
- `motivo` (obrigatório no reagendamento)
- `reagendado_por`, `created_at`

`rh_entrevistas.data_hora` continua sendo o horário atual (agenda linear).

## Regras

- Primeiro agendamento (`pendente`/vazio → data definida) **não** gera histórico (evita falso reagendamento do `NOW()` provisório).
- Alterar data/hora com resultado já `agendado`/`aprovado`/`reprovado` exige motivo e grava o histórico na mesma transação (`FOR UPDATE` + update + append).
- Sem calendário, conflito de agenda, e-mail, outbox ou acoplamento ao módulo de salas.

## Migration

`database/migrations/20260719190000_create_rh_entrevista_reagendamentos.php`

## Próximos incrementos

- duração / janela de horário;
- templates e outbox de comunicação;
- detecção de conflito (somente após outbox/eventos maduros).
