# Sucessão — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — cargos críticos + sucessores com readiness.

## Objetivo

Permitir mapear **cargos críticos** e indicar **sucessores** internos com
prontidão (readiness), conectando ao talent pool / PDI já existentes.

## Regras

- Um registro de cargo crítico por `position_id` (unique).
- Status do cargo: `active|inactive`.
- Risco: `high|medium|low`.
- Sucessor: um par único (`critical_position_id`, `user_id`).
- Readiness: `ready_now|ready_1_2y|ready_3y|emergency`.
- `priority_order` (1 = primeiro sucessor).
- Remoção de sucessor = delete da linha (histórico via log de alteração do cargo).

## Modelo

### `adms_critical_positions`

| Campo | Uso |
|-------|-----|
| `position_id` | cargo (unique) |
| `risk_level` | `high\|medium\|low` |
| `status` | `active\|inactive` |
| `notes` | justificativa |
| `created_by` | ator |

### `adms_succession_successors`

| Campo | Uso |
|-------|-----|
| `critical_position_id` | FK |
| `user_id` | sucessor |
| `readiness` | prontidão |
| `priority_order` | ordem |
| `notes` | observações |
| `nominated_by` | ator |

## UI / ACL

- `ListCriticalPositions` (`list-critical-positions`)
- `CreateCriticalPosition` (`create-critical-position`)
- `ViewCriticalPosition` (`view-critical-position`) — sucessores inline
- `UpdateCriticalPosition` (`update-critical-position`)
- ACL espelhada de `ListTalentNominations` (fallback Calibração/Metas)
- Menu Desempenho → Sucessão
- Atalhos: Talent Pool, PDI do sucessor

## Fora deste incremento

- trilhas de carreira / promoções;
- organograma visual de sucessão;
- matching automático HiPo → sucessor;
- readiness calculado por Nine Box;
- multi-empresa / escopo por filial.

## Migration

`database/migrations/20260720230000_create_adms_succession_tables.php`

## Dependências

[DESENVOLVIMENTO_TALENT_POOL_EXPAND.md](DESENVOLVIMENTO_TALENT_POOL_EXPAND.md),
[DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md](DESENVOLVIMENTO_PDI_COMPLETO_EXPAND.md)
