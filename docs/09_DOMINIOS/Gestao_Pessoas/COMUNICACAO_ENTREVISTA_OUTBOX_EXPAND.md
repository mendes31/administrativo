# Comunicação de entrevista + outbox — Expand Fase 0.5/2

- Domínio: Gestão de Pessoas / Talentos (+ infraestrutura de eventos).
- Data: 19/07/2026.
- Status: **registro transacional** entregue; **envio SMTP / worker ainda não**.

## Modelo

1. `adms_domain_event_outbox` — outbox genérica (`pending` neste incremento).
2. `rh_entrevista_comunicacoes` — intenção de e-mail com snapshot de template (`recorded`).
3. Templates PHP: `RhEntrevistaEmailTemplateCatalog` (`rh.entrevista.agendada` / `reagendada` v1).

## Quando registra

| Momento | Evento | Comunicação |
|---------|--------|-------------|
| Create manual (serviço) | `EntrevistaAgendada` | purpose `agendamento` |
| Edit: pendente → agendada | `EntrevistaAgendada` | purpose `agendamento` |
| Reagendamento (data muda já agendada) | `EntrevistaReagendada` | purpose `reagendamento` |
| Create provisório pelo pipeline (`pendente`+NOW) | — | — |

Idempotência:

- Agendada: `talentos.entrevista.{id}.agendada.v1`
- Reagendada: `talentos.entrevista.{id}.reagendamento.{reagendamento_id}.v1`

## Não faz (ainda)

- Worker / publicação externa
- `SendEmailService` / SMTP
- Botão reenviar
- Outros eventos do catálogo

## Migration

`database/migrations/20260719200000_create_domain_event_outbox_and_rh_entrevista_comunicacoes.php`
