# Talent pool / HiPo a partir do Nine Box — Expand Fase 5

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — nomeações de alto potencial por ciclo.

## Objetivo

Governar o **talent pool interno** (HiPo) a partir da Matriz 9BOX e da
calibração: registrar quem foi nomeado em um ciclo, com box e notas.

## Regras

- Uma nomeação por par (`user_id`, `performance_cycle_id`).
- Ciclo obrigatório e existente.
- Status: `active|removed`.
- `nine_box` opcional (1–9), tipicamente preenchido na matriz.
- Nomeação manual (RH/gestor); sem auto-sync boxes 3/6/9.
- Remover = status `removed` (não apaga histórico).

## Modelo

### `adms_talent_nominations`

| Campo | Uso |
|-------|-----|
| `user_id` | colaborador |
| `performance_cycle_id` | ciclo |
| `nine_box` | 1–9 ou null |
| `status` | `active\|removed` |
| `notes` | justificativa |
| `nominated_by` | ator |

Unique: `(user_id, performance_cycle_id)`.

## UI / ACL

- `ListTalentNominations` (`list-talent-nominations`)
- `CreateTalentNomination` (`create-talent-nomination`)
- `ViewTalentNomination` (`view-talent-nomination`)
- `UpdateTalentNomination` (`update-talent-nomination`)
- ACL espelhada de `ListPerformanceCalibrations`
- Menu Desempenho → Talent Pool
- Nine Box: botão “Nomear” quando há ciclo filtrado
- Calibração: atalho para o pool do ciclo

## Fora deste incremento

- trilhas de carreira / níveis / promoções;
- cargos críticos e sucessores N:N;
- readiness / organograma de sucessão;
- auto-nomeação por box;
- matching automático Nine Box → PDI.

## Migration

`database/migrations/20260720220000_create_adms_talent_nominations.php`

## Dependências

[DESENVOLVIMENTO_CALIBRACAO_EXPAND.md](DESENVOLVIMENTO_CALIBRACAO_EXPAND.md),
[DESENVOLVIMENTO_PDI_EXPAND.md](DESENVOLVIMENTO_PDI_EXPAND.md)
