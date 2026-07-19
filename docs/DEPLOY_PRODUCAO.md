# Deploy em produção — Kinghost

Guia de referência do pipeline actual (jun/2026). Produção: `https://tiaraju.com.br/administrativo/`.

---

## Resumo

| Item | Valor |
|------|--------|
| Disparo | Push em `dev-master` ou `main` |
| Workflow | `.github/workflows/deploy.yml` |
| Método principal | **FTP-Deploy-Action** (incremental por hash — rápido) |
| Fallback | **lftp** upload-only (`scripts/deploy_lftp_upload.sh`) |
| Tentativas | 2× incremental + 1× lftp se FTP falhar |
| Validação | SHA-256 de 12 ficheiros críticos |
| Tempo típico | **~15–30 s** (só docs/PHP alterados) · ~3 min no fallback lftp |
| SSH | `tiaraju02@web119.kinghost.net` |
| Path | `/home/tiaraju/www/administrativo` |

---

## Fluxo no GitHub Actions

```
Checkout → Verificar secrets → [Instalar lftp]
  → Deploy incremental — tentativa 1   (FTP-Deploy-Action, hash)
  → [30s → tentativa 2 se falhar]
  → [Fallback lftp se ambas falharem]
  → Verificar SHA-256
  → Resumo + resultado final
```

**Sucesso rápido (normal):**

- `Incremental tentativa 1: success`
- `Incremental tentativa 2: skipped`
- `Fallback lftp: skipped`
- Log: `File content is the same, doing nothing` na maioria dos ficheiros
- **~15–30 segundos** total

---

## Política: nunca perder dados de produção no deploy

Regra obrigatória da plataforma: **o deploy envia código; não apaga nem
substitui dados de runtime gerados pelos utilizadores**.

| Camada | O que o deploy faz | O que NÃO faz |
|--------|--------------------|---------------|
| Código (`app/`, `routes/`, views, scripts) | Envia/atualiza ficheiros alterados | — |
| Banco MySQL | Nada | Não corre migrations sozinho |
| Uploads e anexos | Nada (excluídos) | Não envia, não sobrescreve, não apaga |
| `.env` de produção | Nada (excluído) | Não sobrescreve secrets |
| `vendor/` / `lib/` | Nada (excluídos) | Dependências ficam no servidor |

**Mecanismos de segurança (todos obrigatórios):**

1. `dangerous-clean-slate: false` no FTP-Deploy-Action — sem limpeza da raiz.
2. lftp **sem** `--delete` — fallback só faz upload.
3. Listas de exclusão alinhadas em três sítios (devem permanecer iguais):
   - `.github/workflows/deploy.yml`
   - `scripts/deploy_excludes.php`
   - `scripts/deploy_lftp_upload.sh`
4. Uploads **fora do Git** (`.gitignore`) — evidências e anexos não entram no
   repositório nem no pipeline.

### Caminhos excluídos (nunca enviados pelo deploy)

- `public/adms/uploads/**` — fotos, anexos, timeline, CRM, salas, políticas,
  informativos, etc.
- `app/public/adms/uploads/**` — anexos cifrados do Canal de Denúncias
  (`WhistleblowingUploadService` grava em `app/public/...`, não em
  `public/...`). São dois diretórios distintos; ambos devem estar excluídos.
- `.env` — configuração específica de produção
- `vendor/`, `lib/` — dependências no servidor (`composer install` se necessário)
- `storage/cache/**`, `storage/logs/**`, `logs/**`
- `storage/sst/epi_fichas/**`, `storage/sst/attachments/**`,
  `storage/sst/treinamento_certificados/**`
- `storage/lgpd/consentimentos/**`, `storage/private/payroll/**` (excepto `.gitkeep`)
- `storage/private/rh_candidatos/**` — currículos ATS (excepto `.gitkeep`)
- `.git/`, `.github/`, `node_modules/`, ficheiros `*.log`

Lista canónica: `scripts/deploy_excludes.php` e `scripts/deploy_lftp_upload.sh`.

### Banco de dados

O workflow **não executa** `phinx migrate`. Schema só muda com comando manual
no servidor (ou processo explícito documentado). Antes de migrar em produção:

1. backup do banco;
2. revisão da migration (sem `DROP`/`TRUNCATE` destrutivo sem plano);
3. janela e rollback definidos.

---

## Raiz FTP

O login FTP/WebFTP **já abre dentro de** `~/www/administrativo/`. O deploy publica na **raiz da sessão** (`./`).

**Errado:** `server-dir: administrativo/` — criava pasta aninhada `Administrativo/` e o site não recebia os ficheiros.

**Correcto:** ficheiros caem directamente em `app/`, `routes/`, `index.php`, etc.

Diagnóstico local:

```powershell
# Definir FTP_HOST, FTP_USER, FTP_PASS (secrets ou .env)
php scripts/detect_ftp_deploy_root.php
```

---

## Rotina de desenvolvimento

### 1. Desenvolver e commitar localmente

```powershell
cd C:\wamp64\www\administrativo
git add .
git commit -m "Descrição clara da alteração"
git push origin dev-master
```

### 2. Acompanhar no GitHub

**Actions → Deploy PHP para Kinghost (upload seguro)** — aguardar verde.

### 3. Migrations (se aplicável)

Se o commit incluir `database/migrations/*.php`:

```bash
cd /home/tiaraju/www/administrativo
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

### 4. Verificar (opcional)

```bash
php scripts/verify_production_deploy.php
```

---

## Ficheiros críticos verificados (SHA-256)

Definidos em `scripts/deploy_critical_manifest.php`:

- `routes/LoadPageAdm.php`
- Controllers/views do Dashboard de Necessidades de Treinamento
- `PageLayoutService.php`, `TrainingUsersRepository.php`, `TrainingsRepository.php`
- `menu.php`
- Scripts de deploy e migration de registo da página

Se a verificação falhar após upload, o job **falha** — investigar antes de considerar produção OK.

---

## Hotfix de emergência (SCP)

Quando o Actions falhar e for urgente corrigir um ficheiro:

```powershell
scp caminho/local/arquivo.php tiaraju02@web119.kinghost.net:/home/tiaraju/www/administrativo/caminho/remoto/arquivo.php
```

Pacote pré-definido (dashboard de compliance):

```powershell
.\scripts\hotfix_training_compliance_deploy.ps1
```

No servidor:

```bash
php scripts/verify_production_deploy.php
```

---

## O que NÃO fazer

| Acção | Motivo |
|-------|--------|
| FileZilla para PHP do projecto | Desalinha produção; use push → Actions |
| `force_full_resync` / apagar estado FTP | Pode causar reenvio massivo; uploads estão em exclude mas evite |
| `dangerous-clean-slate: true` | Apaga ficheiros no servidor — **proibido** |
| lftp com `--delete` | Remove uploads e dados de runtime — **proibido** |
| Versionar uploads / `.enc` / PDFs de produção | LGPD e risco de sobrescrita no fallback |
| Subpasta `administrativo/` no FTP | Duplica estrutura; site fica desactualizado |
| Apagar `.env` ou `vendor/` no servidor | Quebra produção |
| Ignorar job vermelho no Actions | SHA-256 indica código divergente |
| Correr migration sem backup | Perda de dados no banco |

---

## Recuperação de uploads apagados

Se uploads foram removidos por incidente (deploy antigo com ressync, erro
manual, etc.), solicitar **restauro de backup** à Kinghost para ambos os
caminhos:

- `/home/tiaraju/www/administrativo/public/adms/uploads/`
- `/home/tiaraju/www/administrativo/app/public/adms/uploads/`

(e, se aplicável, `storage/private/payroll/`, `storage/sst/`, `storage/lgpd/`).

---

## Referências

- [COMANDOS_SERVIDOR_SSH.md](COMANDOS_SERVIDOR_SSH.md) — SSH, PuTTY, cron, troubleshooting FTP
- [DEPLOY_ALINHAR_PRODUCAO.md](DEPLOY_ALINHAR_PRODUCAO.md) — alinhar produção após incidentes
