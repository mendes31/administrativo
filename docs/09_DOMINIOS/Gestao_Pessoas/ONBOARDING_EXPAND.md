# Onboarding pós-conversão — Expand Fase 4

- Domínio: Gestão de Pessoas / Jornada.
- Data: 19/07/2026.
- Status: 1º incremento — checklist operacional após conversão de oferta.

## Objetivo

Iniciar onboarding do colaborador recém-convertido com checklist auditável,
sem depender de tabelas físicas Pessoa/Vínculo (ADR-0002 ainda em evolução).

## Regras

- Um plano por conversão (`rh_conversao_id` único).
- Plano criado automaticamente na conversão bem-sucedida.
- Itens partem do catálogo PHP `RhOnboardingItemCatalog`.
- Status do plano: `em_andamento` → `concluido` quando todos os obrigatórios
  estão `concluido` ou `dispensado`; ou `cancelado` manualmente.
- Não concede/remove ACL automaticamente.
- Não altera `adms_users.status`.

## Modelo

### `rh_onboarding_planos`

| Campo | Uso |
|-------|-----|
| `rh_conversao_id` | FK lógica da conversão |
| `adms_user_id` / `rh_candidato_id` | contexto |
| `status` | `em_andamento\|concluido\|cancelado` |
| `data_inicio` / `data_limite` | vigência operacional |

### `rh_onboarding_itens`

Status por item: `pendente|em_andamento|concluido|dispensado`.

## UI / ACL

- `RhOnboardingView` (`rh-onboarding-view/{planoId}`)
- Link na oferta convertida e no candidato vinculado
- Permissão espelhada de `RhVagas`

## Fora deste incremento

- templates por cargo/área;
- notificações/outbox de tarefas;
- portal do colaborador para auto-conclusão;
- período de experiência formal;
- tabelas Pessoa/Vínculo/Lotação.

## Migration

`database/migrations/20260719240000_create_rh_onboarding.php`
