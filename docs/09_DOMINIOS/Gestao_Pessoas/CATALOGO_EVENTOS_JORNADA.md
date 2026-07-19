# Catálogo de eventos — Jornada (admissão / lotação / desligamento)

- Domínio: Gestão de Pessoas / Jornada.
- Data: 19/07/2026.
- Status: 1º incremento — produtores ativos; consumidores DP/SST via outbox (workers futuros).

## Eventos

| Evento | Produtor | Consumidores | Payload mínimo | Idempotência |
|--------|----------|--------------|----------------|--------------|
| `jornada.ColaboradorAdmitido` | Conversão | LNT (síncrono), DP/SST (outbox) | `adms_user_id`, `rh_conversao_id`, `data_admissao` | `jornada.user.{id}.conversao.{conversaoId}.v1` |
| `jornada.LotacaoAlterada` | Movimentação | LNT se cargo muda, DP/SST (outbox) | `adms_user_id`, `rh_movimentacao_id`, cargos/dept | `jornada.movimentacao.{id}.v1` |
| `jornada.ColaboradorDesligado` | Offboarding | LNT (síncrono), DP/SST (outbox) | `adms_user_id`, `rh_offboarding_plano_id`, `data_desligamento` | `jornada.offboarding.{planoId}.v1` |

## Referências

- [INTEGRACAO_DP_SST_EXPAND.md](INTEGRACAO_DP_SST_EXPAND.md)
- [CATALOGO_EVENTOS_TALENTOS.md](CATALOGO_EVENTOS_TALENTOS.md)
