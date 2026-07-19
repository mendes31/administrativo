# Catálogo de eventos — Talentos (Recrutamento)

- Domínio: Gestão de Pessoas / Talentos.
- Fase: 0.5.
- Data: 19/07/2026.
- Status: **contratos documentados**; publicação via outbox ainda não implementada.
- Os nomes abaixo são o alvo. Hoje parte dos fatos só gera log de alteração ou efeito colateral síncrono.

## Eventos

| Evento | Produtor | Consumidores previstos | Payload mínimo | Versão | LGPD | Idempotência | Criticidade | Origem atual |
|--------|----------|------------------------|----------------|--------|------|--------------|-------------|--------------|
| `VagaAberta` | Talentos | Portal, Comunicação, Analytics | `vaga_id`, `status`, `responsavel_id` | 1 | Interna | `vaga_id`+`opened_at` | Normal | create vaga |
| `VagaEncerrada` | Talentos | Portal, Analytics | `vaga_id`, `status`, `motivo?` | 1 | Interna | `vaga_id`+`closed_at` | Normal | fechar/excluir |
| `CandidatoCadastrado` | Talentos | LGPD, Analytics | `candidato_id`, `lgpd_consentimento_id` | 1 | Pessoal | `candidato_id` | Alta | create candidato |
| `CandidatoAnonimizado` | Talentos | Analytics, Auditoria | `candidato_id`, `motivo` | 1 | Pessoal | `candidato_id`+`anon_at` | Crítica | retenção LGPD |
| `CandidaturaMovimentada` | Talentos | Comunicação, Analytics | `candidato_id`, `vaga_id`, `status_de`, `status_para` | 1 | Pessoal | `vinculo_id`+`status`+`ts` | Alta | pipeline → `rh_candidaturas_historico` (sem outbox ainda) |
| `CandidaturaVinculada` | Talentos | Comunicação, Analytics | `candidato_id`, `vaga_id` | 1 | Pessoal | `candidato_id`+`vaga_id` | Normal | vincular/sync → histórico |
| `CandidaturaDesvinculada` | Talentos | Analytics | `candidato_id`, `vaga_id` | 1 | Pessoal | `candidato_id`+`vaga_id`+`ts` | Normal | desvincular/sync → histórico |
| `EntrevistaAgendada` | Talentos | Comunicação | `entrevista_id`, `candidato_id`, `vaga_id?`, `data_hora` | 1 | Pessoal | `entrevista_id` | Normal | create/edit entrevista |
| `EntrevistaResultadaRegistrada` | Talentos | Pipeline, Analytics | `entrevista_id`, `resultado` | 1 | Pessoal | `entrevista_id`+`resultado` | Alta | edit entrevista / pipeline |
| `CurriculoAnexado` | Talentos | Auditoria | `candidato_id`, `anexo_id` | 1 | Pessoal | `anexo_id` | Normal | upload |
| `CurriculoBaixado` | Talentos | Auditoria LGPD | `candidato_id`, `anexo_id`, `actor_id` | 1 | Pessoal | `anexo_id`+`actor`+`ts` | Alta | **ainda não emitido** |
| `OfertaAceita` | Talentos | Pré-admissão, Organização | `candidato_id`, `vaga_id` | 1 | Pessoal | `candidato_id`+`vaga_id` | Crítica | **futuro Fase 1+** |

## Correlação

Usar `correlation_id` por jornada: requisição → vaga → candidatura → entrevista → oferta → vínculo.

## Regras

- Não incluir arquivo de currículo, PII excessiva nem parecer textual completo no payload público.
- Preferir IDs; consumidores buscam detalhes com autorização própria.
- Outbox na mesma transação da alteração (padrão do Plano Diretor) — implementação pendente.

## Referências

- [Eventos e auditoria](../../07_EVENTOS/EVENTOS_AUDITORIA.md)
- [Roadmap](04_Roadmap.md)
