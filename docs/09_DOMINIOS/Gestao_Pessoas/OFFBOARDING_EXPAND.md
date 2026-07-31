# Offboarding — Expand Fase 4

- Domínio: Gestão de Pessoas / Jornada.
- Data: 19/07/2026.
- Status: 1º incremento — checklist + desligamento em `adms_users`.

## Objetivo

Conduzir o desligamento com checklist auditável e, ao concluir, registrar
`data_desligamento` / motivo / classificação e inativar a conta na fachada
atual (`adms_users`), sem remover ACL implicitamente.

## Regras

- Plano pertence a um `adms_user_id`.
- No máximo um plano `em_andamento` por usuário.
- Não inicia se o usuário já possui `data_desligamento`.
- Tipos: `pedido_demissao|demissao_sem_justa_causa|demissao_justa_causa|termino_contrato|aposentadoria|outros`.
- Itens partem do catálogo PHP `RhOffboardingItemCatalog`.
- Status do plano: `em_andamento` → `concluido` (ação explícita) ou `cancelado`.
- Conclusão exige itens obrigatórios `concluido` ou `dispensado`.
- Na conclusão (mesma transação): marca plano, aplica em `adms_users`
  (`status=Inativo`, `bloqueado=1`, data/motivo/tipo_impacto) e atualiza
  histórico de vínculo (`EmploymentHistoryRepository`) quando existir.
- Não remove páginas/ACL automaticamente.

## Modelo

### `rh_offboarding_planos`

| Campo | Uso |
|-------|-----|
| `adms_user_id` | colaborador |
| `tipo` | classificação do desligamento |
| `status` | `em_andamento\|concluido\|cancelado` |
| `data_prevista` / `data_desligamento` | planejamento / efetivação |
| `motivo` / `tipo_impacto` | evidência + People Analytics |
| `observacoes` | livre |
| `created_by_user_id` / `concluded_by_user_id` | atores |

### `rh_offboarding_itens`

Status por item: `pendente|em_andamento|concluido|dispensado`.

## UI / ACL

- `RhOffboardings` listagem (`rh-offboardings`)
- `RhOffboardingsCreate` (`rh-offboardings-create`)
- `RhOffboardingsView` (`rh-offboardings-view`)
- Permissão espelhada de `RhVagas`

## Fora deste incremento

- integração automática com DP/folha/SST;
- workflow de aprovação;
- recontratação pelo fluxo de offboarding;
- tabelas físicas Pessoa/Vínculo.

## Integração TI / Acessos (ADR-0008)

O item `revogar_acessos` consome o mapa `ti_acessos`: a tela lista acessos ativos
e exige zero ativos para marcar o item como `concluido` (`dispensado` permanece
permitido). A fonte de verdade dos vínculos é o domínio TI / Acessos.

## Migration

`database/migrations/20260719243000_create_rh_offboarding.php`
