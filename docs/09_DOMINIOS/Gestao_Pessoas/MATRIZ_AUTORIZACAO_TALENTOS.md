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
| Candidato | `list` | ACL `RhCandidatos` | Todos (sem filtro por objeto na listagem) | Página privada | Não |
| Candidato | `view` / `update` / `delete` | ACL RH **ou** gestor **ou** responsável de vaga vinculada | Objeto | Sessão; delete exige POST+senha+justificativa | Sim (delete/update sensível) |
| Candidato | `create` | ACL `RhCandidatosCreate` | — | Consentimento LGPD obrigatório + termo `curriculo_candidato` | Consentimento em `lgpd_consentimentos` |
| Anexo/currículo | `download` | Mesma regra de acesso ao candidato | Objeto | Storage privado; rota `rh-candidatos-download-anexo` | Não (futuro: log de acesso) |
| Vaga | `view` | ACL `RhVagas*` | Todos na listagem | — | Não |
| Vaga | `update` / `delete` | Super, responsável ou gestor | Objeto | CSRF no update; delete com senha+justificativa | Sim |
| Candidatura (vínculo) | `create` / `delete` / `sync` | `canManagePipeline` da vaga | Vaga | Vaga não fechada/cancelada; sync atômico | Parcial |
| Candidatura | `transition` (pipeline) | `canManagePipelineByVagaId` | Vínculo existente | CSRF; status válido; transação | Log de status do candidato |
| Entrevista | `create` / `update` / `delete` | `canManageEntrevista` (vaga ou candidato) | Objeto | CSRF; delete só POST | Parcial |

## Negação

- Sem sessão → download/currículo negado.
- Sem ACL e sem relação com a vaga → candidato/entrevista negados.
- Vaga fechada/cancelada → não vincula nem move pipeline.
- Candidato `contratado` / `anonimizado` → status geral não é sobrescrito pelo pipeline.

## Lacunas conhecidas (próximos incrementos)

1. Listagens de candidatos/vagas ainda sem escopo por objeto (operador RH vê tudo).
2. Entrevistador/avaliador designado ainda não tem policy “somente suas entrevistas” (painel existe; ACL separada).
3. Log de download de currículo ainda não existe.
4. Escopo de gestor não valida área da vaga (qualquer gestor CRM passa).
5. Convite/aceite de avaliador ainda não existe (painel é interno, sem comunicação).

## Referências

- [Modelo de autorização](../../05_AUTORIZACAO/MODELO_AUTORIZACAO.md)
- [Diagnóstico currículos](SEG_CURRICULOS_DIAGNOSTICO.md)
- [Diagnóstico pipeline](SEG_PIPELINE_INTEGRIDADE.md)
