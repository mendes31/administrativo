# Etapas do pipeline ATS — Expand (Fase 1)

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: catálogo de códigos estáveis com rótulo/ordem/classe configuráveis (inclui `banco_talentos`).

## Modelo

Tabela `rh_pipeline_stages`:

| Campo | Uso |
|-------|-----|
| `code` | Código estável (`candidatado`, `em_entrevista`, `aprovado`, `banco_talentos`, …) — único |
| `label` | Rótulo exibido no Kanban/select |
| `display_order` | Ordem das colunas |
| `column_class` | Classe Bootstrap do corpo da coluna |
| `is_active` | Reservado; Expand exige o conjunto ativo completo |

Leitura: `RhPipelineStageCatalog` (DB completo → fallback PHP).

## Banco de talentos

Etapa para finalistas/perfis bons **não contratados nesta vaga**, mantidos consultáveis em novas vagas/áreas.
Motivos: `RESERVA_FINALISTA`, `PERFIL_FUTURO`, `OFERTA_RECUSADA_BANCO`.
Projeção e LGPD: ver `STATUS_PROCESSO_PROJECAO.md`.

## Migrations

- `20260719150000_create_rh_pipeline_stages.php`
- `20260730160000_add_rh_pipeline_stage_banco_talentos.php`
