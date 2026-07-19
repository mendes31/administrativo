# Histórico imutável de candidatura — Fase 1 Expand

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: dual-write ativo; Kanban ainda lê projeção mutável.

## Objetivo

Registrar toda transição de candidatura sem alterar o contrato atual do pipeline.

## Modelo

Tabela append-only `rh_candidaturas_historico`:

| Campo | Uso |
|-------|-----|
| `rh_candidatura_id` | ID do vínculo no momento do evento (sem FK: desvínculo ainda é DELETE) |
| `tipo_evento` | `vinculada`, `movimentada`, `desvinculada`, `backfill` |
| `status_anterior` / `status_novo` | transição; `status_novo` nulo em desvínculo |
| `origem` | `pipeline`, `vaga`, `candidato`, `entrevista`, `sync`, `backfill` |
| `ocorrido_em` | instante do evento |

`rh_candidatos_vagas.status` e `rh_candidatos.status_processo` continuam como projeções.

## Pontos de escrita

- `RhVagasRepository::vincularCandidato`
- `RhVagasRepository::atualizarStatusVinculo` (só se o status mudou)
- `RhVagasRepository::desvincularCandidato`
- sync em massa (`sincronizarCandidatosDaVaga` / `sincronizarVagasDoCandidato`)

## UI

Linha do tempo na ficha do candidato (`RhCandidatosView`).

## Contract posterior (não nesta entrega)

1. Encerramento lógico do vínculo (evitar DELETE físico).
2. FK rígida `rh_candidatura_id → rh_candidatos_vagas`.
3. Service único entrevista + movimentação + histórico.
4. Etapas configuráveis.

## Motivos estruturados

Catálogo: `RhCandidaturaMotivoCatalog`. Pipeline e select da vaga exigem `motivo_codigo`; entrevista preenche automaticamente `APROVADO_ENTREVISTA` / `REPROVADO_ENTREVISTA`. `OUTRO` exige observação.

## Migration

`database/migrations/20260719140000_create_rh_candidaturas_historico.php`
