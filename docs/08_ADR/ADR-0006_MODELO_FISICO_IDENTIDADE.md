# ADR-0006 — Modelo físico inicial de Pessoa, Vínculo e Lotação

- Status: Aceito
- Data: 2026-07-19
- Responsável: Arquitetura e Gestão de Pessoas
- Módulos impactados: RH / jornada; consumidores de `adms_users` (sem alteração neste passo)

## Contexto

ADR-0002 separa conceitualmente Pessoa e Conta, mas deixa o modelo físico
condicionado a inventário e Expand/Contract. A jornada (conversão, movimentação,
offboarding) já grava estado em `adms_users`, o que impede histórico
organizacional limpo.

## Decisão

Neste 1º Expand:

1. Criar `rh_pessoas`, `rh_vinculos` e `rh_lotacoes` como schema sombra.
2. Tratar `adms_users` como Conta (fachada) com dual-write para o novo modelo.
3. Manter leituras operacionais em `adms_users` até Contract futuro.
4. Relação 1:1 Pessoa↔Conta neste incremento (relaxável depois).

Detalhe operacional: [IDENTIDADE_EXPAND.md](../09_DOMINIOS/Gestao_Pessoas/IDENTIDADE_EXPAND.md).

## Alternativas consideradas

- Continuar só em `adms_users` — rejeitada (histórico e ownership).
- Big-bang substituindo FKs — rejeitada (risco alto).
- Só documentar sem schema — rejeitada (não desbloqueia dual-write).

## Consequências

### Positivas

- base física para histórico de vínculo/lotação;
- dual-write gradual sem quebrar o Portal;
- Conta técnica pode existir sem Pessoa no futuro.

### Negativas e riscos

- divergência temporária se sync falhar;
- backfill de CPF duplicado exige tratamento;
- inventário completo de FKs ainda pendente antes do Contract.
