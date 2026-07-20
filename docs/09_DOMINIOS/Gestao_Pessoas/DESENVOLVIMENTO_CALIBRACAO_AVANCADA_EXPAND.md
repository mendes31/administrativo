# Calibração avançada — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento avançado — notas na sessão + lock de reviews.

## Objetivo

Permitir ajustar **desempenho** e **potencial** das avaliações do ciclo
**dentro da sessão de calibração**, e fazer o status `locked` **bloquear
edição** dessas avaliações (integridade pós-comitê).

## Regras

- Edição de notas só com calibração `draft|open` e ACL `UpdatePerformanceCalibration`.
- Campos editáveis na sessão: `overall_score`, `potential_score` (0–10 ou vazio).
- Na 1ª alteração via calibração, se nulos, preencher
  `overall_score_pre_calibration` / `potential_score_pre_calibration`.
- Com calibração `locked`: bloquear Update/RecordResults/Delete da review do ciclo
  e geração em massa no ciclo; mensagem clara.
- Calibração `locked` permanece sem reabrir (igual ao 1º incremento).
- Sem tabela N:N; conjunto = reviews com `performance_cycle_id` do ciclo.

## Modelo

Colunas novas em `adms_performance_reviews`:
- `overall_score_pre_calibration` DECIMAL(5,2) NULL
- `potential_score_pre_calibration` DECIMAL(5,2) NULL

Corrigir persistência de `potential_score` em `PerformanceReviewsRepository::update`.

## UI / ACL

- Grid de notas em `update-performance-calibration`
- View: mostrar potencial + pré-calibração
- Sem página ACL nova

## Fora deste incremento

- multi-departamento / várias sessões por ciclo;
- tabela N:N review↔sessão;
- reabrir calibração locked;
- matching Nine Box→PDI;
- edição de competências/comentários na sessão.

## Migration

`database/migrations/20260720270000_expand_performance_calibration_scores_lock.php`

## Dependências

[DESENVOLVIMENTO_CALIBRACAO_EXPAND.md](DESENVOLVIMENTO_CALIBRACAO_EXPAND.md)
