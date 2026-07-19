# Etapas do pipeline ATS — Expand (Fase 1)

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: catálogo dos **mesmos 5 códigos** com rótulo/ordem/classe configuráveis.

## Modelo

Tabela `rh_pipeline_stages`:

| Campo | Uso |
|-------|-----|
| `code` | Código estável (`candidatado`, `em_entrevista`, …) — único |
| `label` | Rótulo exibido no Kanban/select |
| `display_order` | Ordem das colunas |
| `column_class` | Classe Bootstrap do corpo da coluna |
| `is_active` | Reservado; Expand atual exige os 5 códigos ativos |

Leitura: `RhPipelineStageCatalog` (DB completo → fallback PHP).

## Não alterado neste incremento

- Motivos ainda indexados pelos 5 códigos.
- Prioridade de `status_processo`.
- Inclusão/exclusão dinâmica de etapas.
- FK `stage_id` nos vínculos.

## Migration

`database/migrations/20260719150000_create_rh_pipeline_stages.php`
