# Carreira — Expand Fase 5 (paridade de mercado)

- Domínio: Gestão de Pessoas / Desenvolvimento.
- Data: 20/07/2026.
- Status: 1º incremento — trilhas, níveis e registro de promoções.

## Objetivo

Permitir cadastrar **trilhas de carreira** com **níveis** ordenados (opcionalmente
ligados a cargos) e registrar **promoções** de colaboradores, com aprovação
e aplicação opcional do cargo em `adms_users`.

## Regras

- Trilha: `name` obrigatório; status `active|inactive`.
- Nível: pertence a uma trilha; `level_order` >= 1; `position_id` opcional.
- Promoção: `user_id` + `to_position_id` + `effective_date` obrigatórios;
  `from_position_id` opcional; vínculo opcional a trilha/nível;
  status `draft|approved|applied|cancelled`.
- `approved` preenche `approved_by` / `approved_at`.
- `applied` atualiza `adms_users.user_position_id` para `to_position_id`
  (sem faixa salarial neste incremento).

## Modelo

### `adms_career_tracks` / `adms_career_levels` / `adms_career_promotions`

## UI / ACL

- Trilhas: `ListCareerTracks`, `CreateCareerTrack`, `ViewCareerTrack`, `UpdateCareerTrack`
- Promoções: `ListCareerPromotions`, `CreateCareerPromotion`, `ViewCareerPromotion`, `UpdateCareerPromotion`
- ACL espelhada de `ListCriticalPositions` (fallback Talent/Calibração)
- Menu Desempenho → Trilhas de Carreira / Promoções

## Fora deste incremento

- faixas salariais / histórico salarial;
- organograma visual de carreira;
- auto-promoção a partir de sucessão/Nine Box;
- matching PDI → próximo nível.

## Migration

`database/migrations/20260720240000_create_adms_career_tables.php`

## Dependências

[DESENVOLVIMENTO_SUCESSAO_EXPAND.md](DESENVOLVIMENTO_SUCESSAO_EXPAND.md)
