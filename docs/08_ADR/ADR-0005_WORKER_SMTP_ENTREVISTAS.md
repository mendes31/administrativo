# ADR-0005 — Worker SMTP de entrevistas com outbox

- Status: Aprovado
- Data: 2026-07-19
- Responsável: Arquitetura / Gestão de Pessoas (Talentos)
- Módulos impactados: entrevistas, comunicação, SMTP, outbox

## Contexto

Agendamentos e reagendamentos já registram evento e snapshot de comunicação na
mesma transação, mas não entregam o e-mail. O sistema já possui configuração
SMTP central no banco e `SendEmailService`.

O envio precisa tolerar concorrência e falhas sem disparar mensagens duplicadas
ou atingir candidatos a partir de bases locais/homologação.

## Decisão

1. Consumir somente comunicações `ready` ligadas a eventos `pending`.
2. Fazer claim condicional e transacional:
   `ready/pending → processing/processing`.
3. Reutilizar `SendEmailService` e a configuração central `adms_email_config`.
4. Finalizar de forma transacional:
   `sent/published` ou `failed/failed`.
5. Exigir interruptor **específico** dessas comunicações na tela Configuração de
   E-mail (`adms_email_config.rh_entrevista_send_enabled`, default desligado)
   **e** argumento CLI para envio real. O interruptor não afeta os demais
   e-mails do sistema.
6. Fora de produção, enviar somente ao `test_recipient` configurado.
7. Não repetir automaticamente `failed` nem `processing` incerto.

## Alternativas consideradas

- Enviar e-mail síncrono no cadastro/edição da entrevista.
- Cron sem claim transacional.
- Retry automático após qualquer falha.
- Implementar um segundo cliente SMTP específico para o ATS.

## Consequências

### Positivas

- o fluxo transacional de entrevista não depende da rede SMTP;
- concorrência entre workers não duplica claims;
- ambientes não produtivos não usam o endereço do candidato;
- estados `sent`/`failed` e outbox preservam histórico operacional.

### Negativas e riscos

- SMTP não oferece confirmação transacional com o banco;
- `processing` após aceite SMTP exige análise manual;
- o worker precisa de agendamento, monitoramento e alerta operacionais;
- reenvio futuro precisa de comando/policy explícitos.
