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

## Política: incremental rápido + uploads protegidos

**Principal:** FTP-Deploy-Action com `dangerous-clean-slate: false` e ficheiros em `exclude` **não são enviados nem apagados** (incl. `public/adms/uploads/**`).

**Fallback:** lftp **sem** `--delete` — só quando o FTP incremental falha (timeout).

### Caminhos excluídos (nunca enviados pelo deploy)

- `public/adms/uploads/**` — fotos de utilizadores, anexos, timeline, CRM, salas, etc.
- `.env` — configuração específica de produção
- `vendor/`, `lib/` — dependências no servidor (`composer install` se necessário)
- `storage/cache/**`, `storage/logs/**`, `logs/**`
- `storage/sst/epi_fichas/**`, `storage/sst/attachments/**`
- `storage/lgpd/consentimentos/**`, `storage/private/payroll/**` (excepto `.gitkeep`)
- `.git/`, `.github/`, `node_modules/`, ficheiros `*.log`

Lista canónica: `scripts/deploy_excludes.php` e `scripts/deploy_lftp_upload.sh`.

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
| Subpasta `administrativo/` no FTP | Duplica estrutura; site fica desactualizado |
| Apagar `.env` ou `vendor/` no servidor | Quebra produção |
| Ignorar job vermelho no Actions | SHA-256 indica código divergente |

---

## Recuperação de uploads apagados

Se uploads em `public/adms/uploads/` foram removidos por deploy antigo (FTP-Deploy-Action com ressync), solicitar **restauro de backup** à Kinghost para:

`/home/tiaraju/www/administrativo/public/adms/uploads/`

---

## Referências

- [COMANDOS_SERVIDOR_SSH.md](COMANDOS_SERVIDOR_SSH.md) — SSH, PuTTY, cron, troubleshooting FTP
- [DEPLOY_ALINHAR_PRODUCAO.md](DEPLOY_ALINHAR_PRODUCAO.md) — alinhar produção após incidentes
