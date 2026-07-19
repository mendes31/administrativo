# ADR-0003 — Storage privado e retenção de currículos

- Status: Aprovado
- Data: 2026-07-19
- Responsável: Arquitetura / Gestão de Pessoas (Talentos)
- Módulos impactados: RH candidatos, LGPD, FileServer

## Contexto

Currículos continham dados pessoais sensíveis em caminho público, download sem
autorização por objeto e anonimização que não removia o arquivo físico de forma
confiável. A Fase 0 exigiu conter esses riscos sem reescrever o ATS.

## Decisão

1. Novos anexos ficam em storage privado (`storage/private/rh_candidatos/`).
2. Download apenas por rota autorizada com policy de objeto
   (`RhCandidatoPermissionService`), com registro append-only em
   `rh_candidato_anexo_access_logs`.
3. Retenção LGPD anonimiza o registro e remove anexos físicos via serviço único.
4. Legado em `public/adms/uploads/rh_candidatos/` permanece legível até migração
   completa (leitura dual).

## Alternativas consideradas

- Manter uploads públicos com URL ofuscada.
- Mover imediatamente todos os arquivos legados (alto risco operacional).
- Extrair microserviço de documentos.

## Consequências

### Positivas

- fecha exposição pública de novos currículos;
- centraliza path físico e exclusão;
- alinha retenção à política LGPD do domínio.

### Negativas e riscos

- arquivos legados ainda podem existir no public até varredura;
- download autorizado ainda sem UI de consulta do access log;
- deploy precisa excluir `storage/private` do FTP público.
