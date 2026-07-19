# Movimentações organizacionais — Expand Fase 4

- Domínio: Gestão de Pessoas / Jornada.
- Data: 19/07/2026.
- Status: 1º incremento — registro auditável + aplicação em `adms_users`.

## Objetivo

Registrar transferência, promoção e mudanças de cargo/departamento/gestor com
histórico próprio, aplicando o novo estado na fachada atual (`adms_users`) até
existirem Lotação/Vínculo físicos (ADR-0002).

## Regras

- Movimentação pertence a um `adms_user_id`.
- Tipos: `transferencia|promocao|alteracao_cargo|alteracao_departamento|alteracao_gestor|outros`.
- Snapshot `*_antes` / `*_depois` de departamento, cargo e gestor.
- `data_vigencia` obrigatória; motivo obrigatório.
- Na confirmação, atualiza `adms_users` (departamento/cargo/gestor) na mesma transação.
- Não cria Pessoa/Vínculo/Lotação físicas neste Expand.

## Modelo

### `rh_movimentacoes`

| Campo | Uso |
|-------|-----|
| `adms_user_id` | colaborador |
| `tipo` | classificação |
| `data_vigencia` | quando vale |
| `departamento_id_antes/depois` | snapshot |
| `cargo_id_antes/depois` | snapshot |
| `gestor_id_antes/depois` | snapshot |
| `motivo` / `observacoes` | evidência |
| `created_by_user_id` | ator |

## UI / ACL

- `RhMovimentacoes` listagem (`rh-movimentacoes`)
- `RhMovimentacoesCreate` (`rh-movimentacoes-create`)
- `RhMovimentacoesView` (`rh-movimentacoes-view`)
- Permissão espelhada de `RhVagas`

## Fora deste incremento

- workflow de aprovação;
- múltiplas lotações com vigência sobreposta;
- impacto automático em folha/SST;
- tabelas físicas de Lotação.

## Migration

`database/migrations/20260719242000_create_rh_movimentacoes.php`
