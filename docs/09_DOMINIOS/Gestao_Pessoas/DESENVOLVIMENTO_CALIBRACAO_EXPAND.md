# Calibração de desempenho — Expand Fase 5

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — sessão de calibração 1:1 com ciclo + Nine Box por ciclo.

## Objetivo

Registrar uma **sessão de calibração** por ciclo (notas + status), usando as
avaliações já vinculadas ao ciclo, e permitir abrir a Matriz 9BOX filtrada
pelo mesmo ciclo.

## Regras

- Uma calibração por `performance_cycle_id` (unique).
- Criar somente para ciclo existente; preferencialmente `open` (também `draft`).
- Status: `draft|open|locked`.
- `locked` fecha a sessão (notas somente leitura); **não** bloqueia edição de
  avaliações neste incremento.
- Conjunto da calibração = reviews com `performance_cycle_id` do ciclo.
- Nine Box ganha filtro `performance_cycle_id`.

## Modelo

### `adms_performance_calibrations`

| Campo | Uso |
|-------|-----|
| `performance_cycle_id` | ciclo (unique) |
| `status` | `draft\|open\|locked` |
| `session_notes` | atas / decisões |
| `locked_at` / `locked_by` | evidência de fechamento |
| `created_by` | ator |

## UI / ACL

- `ListPerformanceCalibrations` (`list-performance-calibrations`)
- `CreatePerformanceCalibration` (`create-performance-calibration`)
- `ViewPerformanceCalibration` (`view-performance-calibration`)
- `UpdatePerformanceCalibration` (`update-performance-calibration`)
- ACL espelhada de `ListPerformanceGoals`
- Menu Desempenho → Calibração
- Nine Box: select de ciclo

## Fora deste incremento

- lock de edição em reviews;
- edição de notas dentro da sessão;
- calibração multi-departamento;
- tabela N:N review↔sessão.

## Migration

`database/migrations/20260720200000_create_adms_performance_calibrations.php`

## Dependências

[DESENVOLVIMENTO_CICLOS_EXPAND.md](DESENVOLVIMENTO_CICLOS_EXPAND.md),
[DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md](DESENVOLVIMENTO_AVALIACOES_CICLO_EXPAND.md)
