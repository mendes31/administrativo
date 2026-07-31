# TI / Acessos — Estratégia de testes

## Riscos cobertos

- Acesso ativo duplicado para o mesmo usuário+sistema.
- Conclusão indevida do item `revogar_acessos` com ativos.
- Confusão ACL Portal × mapa TI.
- Rollback de migration.

## Testes de unidade

- `TiAcessoService`: liberar, bloquear duplicata ativa, revogar.
- Regra offboarding: `concluido` exige zero ativos; `dispensado` permitido.

## Testes de integração

- CRUD `ti_sistemas` / `ti_acessos`.
- Listagens por usuário e por sistema.
- Log de alteração gerado.

## Testes de fluxo

1. Cadastrar sistema embarcado → vincular colaborador → ver aba Acessos.
2. Iniciar offboarding → listar ativos → revogar → concluir item.
3. Tentar concluir item com ativo restante → falha com mensagem.

## Autorização

- Sem página no nível: 403/redirect padrão.
- Com `TiAcessosRevoke`: revoga no offboarding.
- Sem `TiAcessosRevoke`: vê lista (se view offboarding) sem ação, ou ação negada.

## Segurança e privacidade

- Não persistir senha do sistema externo.
- CSRF nos formulários de liberação/revogação.

## Migrations

- Base vazia: sobe tabelas e páginas.
- Idempotência do registro de páginas.
- `down`: remove páginas, grupo (se vazio) e tabelas.

## Critérios de conclusão

Roteiro manual da Fase 1 executado; audit do manual `--changed --strict` ok.
