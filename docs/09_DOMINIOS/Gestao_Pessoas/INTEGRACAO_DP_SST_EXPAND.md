# Integração progressiva DP / SST — Expand Fase 4

- Domínio: Gestão de Pessoas / Jornada × DP × SST.
- Data: 19/07/2026.
- Status: 1º incremento — fan-out síncrono LNT + outbox de eventos de jornada.

## Objetivo

Ao admitir, movimentar ou desligar, notificar domínios irmãos sem acoplar
folha/SST ao fluxo transacional do RH.

## Regras

- Produtor: serviços de conversão, movimentação e offboarding.
- Consumidor síncrono imediato: LNT de Treinamentos (`TrainingLntEventService`).
- Contrato assíncrono: eventos na `adms_domain_event_outbox` para DP/SST/Analytics.
- Dual-write best-effort: falha de integração não reverte a jornada (log warning).
- Payload só com IDs e datas; sem PII excessiva.
- Folha e ASO/EPI continuam ownership de DP/SST (sem criar documentos automaticamente).

## Eventos

| Evento | Origem | Idempotência |
|--------|--------|--------------|
| `jornada.ColaboradorAdmitido` | conversão | `jornada.user.{userId}.conversao.{conversaoId}.v1` |
| `jornada.LotacaoAlterada` | movimentação | `jornada.movimentacao.{id}.v1` |
| `jornada.ColaboradorDesligado` | offboarding concluído | `jornada.offboarding.{planoId}.v1` |

## Efeitos LNT

- Admissão → `registerNovoColaborador`
- Movimentação com mudança de cargo → `registerAlteracaoCargo`
- Desligamento → `registerColaboradorDesligado`

## Fora deste incremento

- worker dedicado DP/SST consumindo a outbox;
- geração automática de holerite / ASO / EPI;
- Contract de FKs para `rh_pessoas`.

## Serviço

`RhJornadaIntegracaoService`
