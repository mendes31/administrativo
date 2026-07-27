# Diagnóstico de segurança e LGPD — Currículos (ATS)

- Domínio: Gestão de Pessoas / Talentos (Recrutamento).
- Fase: 0 — segurança, LGPD, integridade e testes.
- Data: 19/07/2026.
- Status: **correções Fase 0 aplicadas** (incluindo C3, A3, A4 e storage privado).
- Migration necessária em cada ambiente:
  `database/migrations/20260719130000_secure_rh_candidatos_anexos_and_lgpd.php`

## Fluxo atual

1. Cadastro exige checkbox de consentimento + termo LGPD `curriculo_candidato`.
2. Consentimento gravado em `lgpd_consentimentos` e vinculado ao candidato.
3. Upload via `RhCandidatoAnexoService` em `storage/private/rh_candidatos/<id>/`.
4. Leitura dual: privado primeiro; legado em `public/adms/uploads/rh_candidatos/`.
5. Download por `rh-candidatos-download-anexo/{id}` com autorização por objeto
   (`RhCandidatoPermissionService`). `serve-file` legado também exige a mesma
   policy para caminhos `rh_candidatos/`. Downloads autorizados geram registro
   em `rh_candidato_anexo_access_logs` (falha de log não bloqueia a entrega).
6. Retenção/anonimização remove arquivos nos dois locais e limpa PII ampliada.
7. FK `ON DELETE CASCADE` em `rh_candidatos_anexos` (após limpeza de órfãos).
   O access log **não** tem FK CASCADE — a evidência permanece.

## Achados — status final

| # | Achado | Status |
|---|--------|--------|
| C1 | Pasta pública sem negação Apache | **Corrigido** (.htaccess + novos arquivos no privado) |
| C2 | `serve-file` sem sessão | **Corrigido** (sessão + policy por objeto) |
| C3 | Sem autorização por objeto | **Corrigido** (`RhCandidatoPermissionService` em view/edit/delete/download) |
| C4 | Exclusão física com caminho errado | **Corrigido** |
| A1 | Validação fraca de upload | **Corrigido** |
| A2 | Exclusão GET sem anexos | **Corrigido** |
| A3 | Sem FK/cascade | **Corrigido** (migration) |
| A4 | Consentimento automático sem evidência | **Corrigido** (checkbox + termo + registro) |
| M1–M4 | Anonimização/nome/enctype/storage | **Corrigido** / dual-read privado |
| L1 | Sem log de download | **Corrigido** (`rh_candidato_anexo_access_logs`; UI listagem + Excel + PDF) |

## Deploy / produção

- Excluir `storage/private/rh_candidatos/**` do FTP (já em `deploy_excludes.php`,
  `deploy.yml`, `lftp`).
- Rodar migration em produção **manualmente** após o deploy de código:
  `php vendor/bin/phinx migrate -c database/phinx.php -e production`
  (inclui `20260719231000_create_rh_candidato_anexo_access_logs` quando aplicável).
- Backup do banco antes da migration (FK + limpeza de órfãos).

## Testes

- `tests/Characterization/CurriculoStorageContractTest.php`
- `tests/Unit/RhCandidatoAnexoServiceTest.php`

## Referências

- [Documento técnico](03_Tecnico.md)
- [Estratégia de testes](05_Testes.md)
- [Deploy em produção](../../DEPLOY_PRODUCAO.md)
