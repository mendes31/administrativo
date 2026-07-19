# Status geral do candidato — projeção

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: contrato documentado e aplicado no cadastro/edição.

## Cadeia de verdade

```
rh_candidaturas_historico  (transições imutáveis)
        ↓ dual-write
rh_candidatos_vagas.status (projeção por vaga / Kanban)
        ↓ agregação
rh_candidatos.status_processo (projeção geral)
```

## Regras de agregação

Prioridade (maior → menor), apenas vínculos em vagas `aberta` ou `pausada`:

1. `contratado` / `anonimizado` no candidato — **protegidos** (pipeline não sobrescreve)
2. qualquer vínculo `aprovado` → `aprovado`
3. senão qualquer `em_entrevista` → `em_entrevista`
4. senão qualquer `candidatado` → `candidatado`
5. senão só `reprovado`/`desistiu` → `reprovado`
6. sem vínculos ativos → `candidatado`

Legado normalizado: `recebido`→`candidatado`, `em_analise`→`em_entrevista`, `banco_talentos`→`aprovado`.

## UI

- Cadastro: status inicia em `candidatado` (não editável).
- Edição: status exibido como projeção; opção explícita **Marcar como contratado**.
- Pipeline continua sendo o lugar de mover etapas por vaga.

## Código

- `RhCandidatoStatusProcessoProjector`
- `RhCandidatosRepository::calcularStatusGeralPorVinculos` / `listStatusVinculosAtivos`
