# Comunicação de entrevista + outbox — Expand Fase 0.5/2

- Domínio: Gestão de Pessoas / Talentos (+ infraestrutura de eventos).
- Data: 19/07/2026.
- Status: registro transacional, preflight e **worker SMTP CLI entregues**.

## Modelo

1. `adms_domain_event_outbox` — outbox genérica (`pending` neste incremento).
2. `rh_entrevista_comunicacoes` — intenção de e-mail com snapshot de template e histórico de entrega.
3. Templates PHP: `RhEntrevistaEmailTemplateCatalog` (`rh.entrevista.agendada` / `reagendada` v2).
4. Worker CLI: `scripts/rh_entrevista_comunicacoes_worker.php`.

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

- Retry automático de `failed` ou `processing` (evita duplicidade após resultado SMTP incerto)
- Outros eventos do catálogo

## Reenvio manual (Expand)

Na visualização da entrevista, comunicações `failed` ou `blocked` podem ser
**reenviadas** (permissão `RhEntrevistasResendComunicacao`):

- cria **nova** intenção `recorded` + outbox (idempotência
  `talentos.entrevista.{id}.reenvio.{source}.{seq}.v1`);
- atualiza destinatário/template a partir dos dados atuais da entrevista;
- o registro original permanece no histórico;
- impede segundo reenvio enquanto houver intenção aberta
  (`recorded`/`ready`/`processing`) ligada à mesma origem;
- o envio SMTP continua dependendo do interruptor da Configuração de E-mail e do worker.

Migration: `database/migrations/20260719234000_add_rh_entrevista_comunicacao_reenvio.php`.

## Preflight (CLI)

```bash
php scripts/rh_entrevista_comunicacoes_preflight.php
php scripts/rh_entrevista_comunicacoes_preflight.php --limit=20
# somente com RH_ENTREVISTA_PREFLIGHT_APPLY=true no .env:
php scripts/rh_entrevista_comunicacoes_preflight.php --apply
```

Promove `recorded → ready|blocked` sem enviar e-mail. Destinatário inválido ou outbox inconsistente → `blocked`.

## Worker SMTP (CLI)

Dry-run por padrão:

```bash
php scripts/rh_entrevista_comunicacoes_worker.php
php scripts/rh_entrevista_comunicacoes_worker.php --limit=20
```

Envio real exige **os dois controles**:

1. Interruptor **"Envio automático de comunicações de entrevista"** ligado na tela
   *Configuração de E-mail* (coluna `adms_email_config.rh_entrevista_send_enabled`,
   default desligado). O controle é **específico** dessas comunicações; não afeta os
   demais e-mails do sistema.
2. Argumento `--send` no script:

```bash
php scripts/rh_entrevista_comunicacoes_worker.php --send
```

Migration do interruptor:
`database/migrations/20260719233000_add_rh_entrevista_send_toggle_to_adms_email_config.php`.

Fluxo: `ready → processing → sent|failed`; o evento correspondente passa de
`pending → processing → published|failed`.

- Claim condicional evita dois workers enviarem o mesmo registro.
- Fora de produção, o destinatário é obrigatoriamente o `test_recipient` da
  configuração de e-mail; o endereço do candidato não é usado.
- `failed` não é reenviado automaticamente.
- Se o SMTP aceitar a mensagem e falhar a persistência, o registro fica em
  `processing` para análise manual, evitando reenvio potencialmente duplicado.
- Não execute o preflight nem o worker continuamente sem agendador/cron
  supervisionado e alertas de falha.

## Migration

`database/migrations/20260719200000_create_domain_event_outbox_and_rh_entrevista_comunicacoes.php`

`database/migrations/20260719232000_prepare_rh_entrevista_email_worker.php`

`database/migrations/20260719233000_add_rh_entrevista_send_toggle_to_adms_email_config.php`

`database/migrations/20260719234000_add_rh_entrevista_comunicacao_reenvio.php`
