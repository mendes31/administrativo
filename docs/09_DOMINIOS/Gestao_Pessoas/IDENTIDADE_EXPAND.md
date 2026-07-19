# Identidade — Pessoa, Conta, Vínculo e Lotação — Expand Fase 4

- Domínio: Gestão de Pessoas / Núcleo.
- Data: 19/07/2026.
- Status: 1º incremento — schema sombra + backfill + dual-write; leitura app ainda em `adms_users`.
- ADR: [ADR-0002](../../08_ADR/ADR-0002_PESSOA_E_CONTA.md), [ADR-0006](../../08_ADR/ADR-0006_MODELO_FISICO_IDENTIDADE.md).

## Objetivo

Expandir o schema com Pessoa, Vínculo e Lotação físicos, sincronizados a partir
da fachada `adms_users` (Conta), sem migrar consumidores neste incremento.

## Regras

- `adms_users` permanece fonte de verdade operacional (Conta + campos legados).
- Uma Pessoa pode ter no máximo um `adms_user_id` neste incremento (1:1).
- Um Vínculo ativo por `adms_user_id` por vez.
- Uma Lotação vigente por vínculo por vez.
- Backfill idempotente na migration a partir de `adms_users`.
- Dual-write após conversão, movimentação e conclusão de offboarding.
- Não remove colunas de `adms_users`.
- Não altera login/ACL.

## Modelo

### `rh_pessoas`

| Campo | Uso |
|-------|-----|
| `adms_user_id` | Conta vinculada (único, nullable) |
| `cpf` | identidade civil (único quando preenchido) |
| `nome` / `email` / `data_nascimento` / `sexo` | snapshot demográfico |

### `rh_vinculos`

| Campo | Uso |
|-------|-----|
| `rh_pessoa_id` / `adms_user_id` | relação |
| `status` | `ativo\|encerrado` |
| `data_inicio` / `data_fim` | vigência |

### `rh_lotacoes`

| Campo | Uso |
|-------|-----|
| `rh_vinculo_id` | vínculo |
| `departamento_id` / `cargo_id` / `gestor_user_id` | lotação |
| `vigente` | flag da lotação atual |
| `data_inicio` / `data_fim` | vigência |

## UI / ACL

- `RhPessoas` listagem (`rh-pessoas`) — somente leitura
- `RhPessoasView` (`rh-pessoas-view`) — detalhe + vínculo/lotação
- Permissão espelhada de `RhVagas`

## Fora deste incremento

- pessoa sem conta;
- múltiplos vínculos/lotações sobrepostos com UI de edição;
- migrar FKs dos módulos para `rh_pessoas` / `rh_vinculos`;
- Contract (remover campos de identidade de `adms_users`).

## Migration

`database/migrations/20260719244000_create_rh_identidade_pessoa_vinculo_lotacao.php`
