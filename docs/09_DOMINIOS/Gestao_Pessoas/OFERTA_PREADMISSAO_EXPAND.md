# Oferta e pré-admissão — Expand Fase 3

- Domínio: Gestão de Pessoas / Talentos.
- Data: 19/07/2026.
- Status: 1º incremento — oferta + aceite/recusa pelo RH + checklist de documentos.

## Objetivo

Registrar oferta formal ligada à candidatura (`rh_candidatos_vagas`), aceitar ou
recusar sem criar vínculo empregatício, e acompanhar documentos de pré-admissão.

## Regras

- Oferta pertence à **candidatura** (candidato + vaga), não ao candidato isolado.
- Só cria oferta se o vínculo estiver em `aprovado` (ou já existir oferta ativa).
- No máximo **uma oferta ativa** por candidatura (`rascunho|enviada|aceita`).
- Aceite inicia pré-admissão (checklist); **não** marca `contratado` nem cria Pessoa/Vínculo.
- Aceite/recusa neste incremento são **registrados pelo RH** (sem portal do candidato).
- Pipeline Kanban permanece com os 5 códigos; oferta é processo paralelo documentado no histórico.

## Modelo

### `rh_ofertas`

| Campo | Uso |
|-------|-----|
| `rh_candidatura_id` | FK `rh_candidatos_vagas.id` |
| `rh_candidato_id` / `rh_vaga_id` | desnormalizados para consulta |
| `status` | `rascunho\|enviada\|aceita\|recusada\|cancelada` |
| `salario_oferecido`, `tipo_contrato`, `data_inicio_prevista`, `validade_ate` | condições |
| `observacoes` / `resposta_observacoes` | texto RH / motivo resposta |
| `enviado_em` / `respondido_em` | timestamps |
| `created_by_user_id` | ator |

### `rh_pre_admissao_documentos`

Checklist gerado no aceite (catálogo PHP default). Status por item:
`pendente|recebido|aprovado|recusado`.

## UI / ACL

- `RhOfertasCreate` / `RhOfertasView` (URL `rh-ofertas-create`, `rh-ofertas-view`)
- Entrada na visualização do candidato (vínculos `aprovado`)
- Permissão espelhada dos níveis com `RhVagas`

## Histórico

`tipo_evento = oferta`, `origem = oferta`, com `status_novo` descritivo
(`oferta_enviada`, `oferta_aceita`, `oferta_recusada`, `oferta_cancelada`).

## Fora deste incremento

- e-mail/outbox de envio da oferta;
- aceite pelo candidato (token/portal);
- upload de arquivos dos documentos;
- conversão auditável Pessoa/Vínculo;
- novas colunas no Kanban.

## Migration

`database/migrations/20260719238000_create_rh_ofertas_pre_admissao.php`
