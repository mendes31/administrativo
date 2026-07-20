# Automação e IA — Expand Fase 6 (1º incremento)

- Domínio: Gestão de Pessoas / Desenvolvimento + Analytics.
- Data: 20/07/2026.
- Status: 1º incremento — digest CLI de lembretes (**sem IA**).

## Objetivo

Automatizar **lembretes determinísticos** de pendências de desenvolvimento/clima
via cron CLI, com dry-run padrão e interruptor nas Configurações de Notificações.

## Escopo deste incremento

Digest diário (contagens + e-mail/in-app opcional) para:

1. Campanhas Pulse/eNPS `open` (aviso operacional no log; colaboradores sem resposta
   — amostra limitada no e-mail do colaborador se aplicável em cortes futuros).
2. Ações de PDI com `end_date` &lt; hoje e status `pending|in_progress` (planos `active`).
3. Avaliações `draft` vinculadas a ciclo `open`.

## Regras

- CLI: `scripts/rh_development_reminders_worker.php`
  - default: **dry-run** (imprime resumo);
  - `--send`: envia só se toggle ligado;
  - `--limit=N`: teto de destinatários (default 100).
- Toggles (default **off**):
  - `rh_dev_reminders_email`
  - `rh_dev_reminders_inapp`
- Throttle: no máximo 1 digest/usuário/dia (`storage/cache/system/rh_dev_reminders_*.json`).
- Destinatários no `--send`: colaboradores com item pendente (PDI ação ou avaliação draft).

## Fora deste incremento (IA e demais)

- qualquer LLM / geração de texto / matching inteligente;
- matching Nine Box→PDI (continua adiado para ações em massa/IA); 1º corte na matriz entregue;
- outbox genérica nova;
- UI de agendador (usar cron do SO → CLI);
- custo financeiro de turnover.

## Artefatos

- Service: `RhDevelopmentRemindersService`
- Script: `scripts/rh_development_reminders_worker.php`
- Settings: `NotificationSettingsRegistry`

## Dependências

PDI, ciclos/avaliações e pulse já entregues; dados maduros o suficiente para
contagens. IA permanece explicitamente fora até nova decisão/ADR.
