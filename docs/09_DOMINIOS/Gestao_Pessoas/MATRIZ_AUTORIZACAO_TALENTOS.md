# Matriz de autorização — Talentos (Recrutamento)

- Domínio: Gestão de Pessoas / Talentos.
- Fase: 0.5.
- Data: 19/07/2026.
- Status: vigente para o comportamento atual pós-Fase 0.
- Implementação: `RhPermissionService`, `RhCandidatoPermissionService`.

## Papéis e relações

| Papel/relação | Como é determinado hoje |
|---------------|-------------------------|
| Super / acesso total | `UserAccessHelper::hasFullSystemAccess()` |
| Operador RH (ACL) | Permissão em páginas `RhCandidatos*` / `RhVagas*` / `RhEntrevistas*` |
| Responsável da vaga | `rh_vagas.responsavel_id = user_id` |
| Gestor | `CrmPermissionService::isManager()` (hierarquia) |
| Entrevistador | Campo `entrevistador_id` + painel `rh_entrevista_avaliadores` (ainda sem policy exclusiva de leitura) |

## Matriz

| Recurso | Ação | Quem pode | Escopo | Condições | Auditoria |
|---------|------|-----------|--------|-----------|-----------|
| Candidato | `list` | ACL `RhCandidatos` | `all` se `RhCandidatosViewAll` / Super; senão `related` (vaga sob responsabilidade) | Expand compatível; sem vaga só em `all` | Não |
| Candidato | `view` / `update` / `delete` | `RhCandidatosViewAll` / Super **ou** gestor **ou** responsável de vaga vinculada | Objeto | Sessão; delete exige POST+senha+justificativa | Sim (delete/update sensível) |
| Candidato | `create` | ACL `RhCandidatosCreate` | — | Consentimento LGPD obrigatório + termo `curriculo_candidato` | Consentimento em `lgpd_consentimentos` |
| Anexo/currículo | `download` | Mesma regra de acesso ao candidato | Objeto | Storage privado; rota `rh-candidatos-download-anexo`; log em `rh_candidato_anexo_access_logs` | Sim (access log) |
| Vaga | `list` | ACL `RhVagas` | `all` se `RhVagasViewAll` / Super; senão `responsible` | Expand compatível: ViewAll concedida a quem já tem RhVagas | Não |
| Vaga | `view` | ACL `RhVagas*` + `canViewVaga` | Objeto: ViewAll / Super / responsável | Deep-link sem relação negado | Não |
| Vaga | `update` / `delete` | Super, responsável ou gestor | Objeto | CSRF no update; delete com senha+justificativa | Sim |
| Candidatura (vínculo) | `create` / `delete` / `sync` | `canManagePipeline` da vaga | Vaga | Vaga não fechada/cancelada; sync atômico | Parcial |
| Candidatura | `transition` (pipeline) | `canManagePipelineByVagaId` | Vínculo existente | CSRF; status válido; transação | Log de status do candidato |
| Entrevista | `list` | ACL `RhEntrevistas` | `all` se `RhEntrevistasViewAll` / Super; senão `related` | Expand compatível | Não |
| Entrevista | `view` | ACL + `canViewEntrevista` | Objeto: ViewAll / Super / entrevistador / avaliador ativo / responsável ou gestor da vaga | Alinhado ao modo `related` da listagem | Não |
| Entrevista | `create` / `update` / `delete` | `canManageEntrevista` (vaga ou candidato) | Objeto | CSRF; delete só POST | Parcial |
| Comunicação entrevista | `resend` | ACL `RhEntrevistasResendComunicacao` + `canManageEntrevista` | Objeto | CSRF POST; só `failed`/`blocked`; nova intenção | Expand |

## Negação

- Sem sessão → download/currículo negado.
- Sem ACL e sem relação com a vaga → candidato/entrevista negados.
- Vaga fechada/cancelada → não vincula nem move pipeline.
- Candidato `contratado` / `anonimizado` → status geral não é sobrescrito pelo pipeline.

## Lacunas conhecidas (próximos incrementos)

1. ~~Contract de `RhEntrevistasViewAll` / `RhCandidatosViewAll`~~ (aplicado 27/07/2026; mesmo critério RH/DP/Super que Vagas).
2. ~~Entrevistador/avaliador designado ainda não tem policy “somente suas entrevistas” na **visualização**~~ (`canViewEntrevista`); edição continua em `canManageEntrevista`.
3. Escopo de gestor não valida área da vaga (qualquer gestor CRM passa na edição/pipeline e na ficha de candidato).
4. Convite/aceite de avaliador ainda não existe (painel é interno, sem comunicação).
5. UI/exportação do log de download de currículo ainda não existe (registro backend já grava).

## Referências

- [Modelo de autorização](../../05_AUTORIZACAO/MODELO_AUTORIZACAO.md)
- [Diagnóstico currículos](SEG_CURRICULOS_DIAGNOSTICO.md)
- [Diagnóstico pipeline](SEG_PIPELINE_INTEGRIDADE.md)
