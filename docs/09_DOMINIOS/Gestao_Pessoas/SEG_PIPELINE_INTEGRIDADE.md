# Diagnóstico de integridade — Pipeline ATS

- Domínio: Gestão de Pessoas / Talentos.
- Fase: 0 — segurança, LGPD, integridade e testes.
- Data: 19/07/2026.
- Status: **correções iniciais aplicadas**.

## Correções

| Risco | Correção |
|-------|----------|
| Movimentação de pipeline sem transação / sem validar vínculo | `atualizarStatusVinculo` com `beginTransaction`, `rowCount` e rollback |
| Status geral desatualizado ao vincular/desvincular | Recalcula `status_processo` após vincular/desvincular |
| POST de edição de vaga sem CSRF/autorização por objeto | Valida CSRF + `canEditVagaById` antes do update |
| Entrevistas mutáveis sem autorização de vaga/candidato | `canManageEntrevista` em create/edit/delete |
| Exclusão de entrevista sem CSRF | Token + validação |
| Modal de vínculo na view do candidato sem CSRF | Token no formulário AJAX |

## Pendências (próximos incrementos)

- Transação atômica nas substituições em massa (`RhVagasCandidatos` / `RhCandidatosVagas`).
- Autorização GET em telas de gestão de vínculos.
- Histórico imutável de etapas (Fase 1).

## Testes

`tests/Characterization/PipelineIntegrityContractTest.php`
