# Período de experiência — Expand Fase 4

- Domínio: Gestão de Pessoas / Jornada.
- Data: 19/07/2026.
- Status: 1º incremento — vigência + avaliação pós-conversão.

## Objetivo

Controlar o período de experiência do colaborador convertido (padrão 90 dias),
com registro de aprovação, reprovação ou prorrogação única.

## Regras

- Um período ativo por conversão (`rh_conversao_id` único enquanto não cancelado).
- Criado automaticamente na conversão (junto ao onboarding).
- `data_inicio` = data de admissão do usuário (ou hoje).
- `data_fim_prevista` = início + 90 dias (configurável no create).
- Status: `em_andamento|aprovado|reprovado|prorrogado|cancelado`.
- Prorrogação: no máximo **uma** vez; soma dias à `data_fim_prevista` e marca
  `prorrogado_em` / `dias_prorrogacao`.
- Avaliação registra ator, data e observações; não desliga nem altera ACL.

## Modelo

### `rh_periodos_experiencia`

| Campo | Uso |
|-------|-----|
| `rh_conversao_id` | conversão de origem |
| `adms_user_id` / `rh_candidato_id` | contexto |
| `rh_onboarding_plano_id` | opcional |
| `status` | ciclo do período |
| `data_inicio` / `data_fim_prevista` | vigência |
| `dias_prorrogacao` / `prorrogado_em` | extensão |
| `resultado` | `aprovado\|reprovado\|prorrogado` na avaliação |
| `avaliado_em` / `avaliado_por_user_id` / `observacoes` | evidência |

## UI / ACL

- `RhExperienciaView` (`rh-experiencia-view/{id}`)
- Link na oferta convertida e no onboarding
- Permissão espelhada de `RhVagas`

## Fora deste incremento

- desligamento automático na reprovação;
- notificações/SLA;
- múltiplos ciclos de experiência;
- integração DP/folha.

## Migration

`database/migrations/20260719241000_create_rh_periodos_experiencia.php`
