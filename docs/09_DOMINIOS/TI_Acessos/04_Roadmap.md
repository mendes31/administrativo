# TI / Acessos — Roadmap

## Critérios de entrada

- diagnóstico inicial concluído;
- capacidades classificadas;
- ADR de limite de domínio aprovado;
- gate arquitetural atendido.

## Fases

### Fase 0 — Estabilização / diagnóstico

- Escopo: artefatos 01–06, mapa de domínios, catálogo, ADR-0008.
- Resultado: domínio reconhecido na governança.
- Critério de saída: documentação indexada.

### Fase 1 — Essenciais (MVP)

- Escopo: `ti_sistemas`, `ti_acessos`, CRUD, aba no usuário, card no offboarding,
  regra do item `revogar_acessos`, manual.
- Resultado: mapa utilizável no desligamento.
- Dependências: `adms_users`, offboarding RH.
- Critério de saída: critérios de aceite do funcional.

### Fase 2 — Recomendadas

- Matriz cargo × sistema; alertas; solicitação/aprovação.
- Depende de dados maduros da Fase 1.

### Fase 3 — Avançadas

- Discovery de rede; sync de diretórios/SaaS; contratos/licenças.

## Dependências entre fases

Fase 1 → Fase 2 → Fase 3. Discovery não bloqueia o MVP.

## Iniciativas suspensas ou descartadas

- Fundir com inventário LGPD ou Estoque — descartado (semântica distinta).
- Discovery como fonte única do inventário — descartado na Fase 1.
