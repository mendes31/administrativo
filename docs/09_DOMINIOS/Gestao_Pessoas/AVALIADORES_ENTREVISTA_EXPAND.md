# Painel de avaliadores da entrevista — Expand Fase 2

- Domínio: Gestão de Pessoas / Talentos.
- Data: 27/07/2026.
- Status: painel + **convite/aceite** de avaliadores adicionais.

## Modelo

Tabela `rh_entrevista_avaliadores`:

- unique `(rh_entrevista_id, avaliador_id)`
- `papel`: `principal` | `avaliador`
- `status`: `ativo` | `convidado` | `recusado` | `removido`
- `convidado_at` / `respondido_at`
- backfill de `rh_entrevistas.entrevistador_id` como principal

O campo legado `entrevistador_id` permanece canônico para o principal durante o Expand.

## Regras

- **Principal**: entra/atualiza como `ativo` imediato (sem convite).
- **Adicional novo**: nasce `convidado`; recebe notificação in-app + e-mail; só após **Aceitar** passa a `ativo` e ganha `canViewEntrevista` pleno (já com `convidado` pode abrir a entrevista para responder).
- **Recusar** → `recusado` (sai do escopo de leitura).
- **Reenviar** (gestor/`canManageEntrevista` + ACL `RhEntrevistasReenviarConviteAvaliador`): reabre como `convidado` e dispara novo convite.
- Remoção é lógica (`removido`) para preservar histórico.
- Scorecard continua por `avaliador_id`; só quem está `ativo` deve avaliar na prática (edição da entrevista exige gerenciar vaga).
- Controllers: `RhEntrevistasAceitarAvaliacao`, `RhEntrevistasRecusarAvaliacao`, `RhEntrevistasReenviarConviteAvaliador`.
- Serviço: `RhEntrevistaAvaliadorConviteService` (sino + `SendEmailService`, prefixo `[TESTE]` em homolog).

## Migrations

- `database/migrations/20260719180000_create_rh_entrevista_avaliadores.php`
- `database/migrations/20260727160000_expand_rh_entrevista_avaliador_convite_aceite.php`

## Próximos incrementos

- agenda/ICS para avaliadores;
- convite via outbox unificado (opcional, hoje é envio síncrono como salas/solicitações).

## Testes manuais

Roteiro passo a passo: [ROTEIRO_TESTES_MANUAIS_ATS_20260727.md](ROTEIRO_TESTES_MANUAIS_ATS_20260727.md).
