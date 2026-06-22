# Deploy em produção — Kinghost

Guia de referência do pipeline actual (jun/2026). Produção: `https://tiaraju.com.br/administrativo/`.

---

## Resumo

| Item | Valor |
|------|--------|
| Disparo | Push em `dev-master` ou `main` |
| Workflow | `.github/workflows/deploy.yml` |
| Método | **lftp upload only** (`scripts/deploy_lftp_upload.sh`) |
| Tentativas | Até 3 (pausa 45s entre falhas) |
| Validação | SHA-256 de 12 ficheiros críticos |
| Tempo típico | ~3 min (só ficheiros alterados) |
| SSH | `tiaraju02@web119.kinghost.net` |
| Path | `/home/tiaraju/www/administrativo` |

---

## Fluxo no GitHub Actions

```
Checkout
  → Verificar secrets FTP
  → Instalar lftp
  → Deploy lftp — tentativa 1
  → [Aguardar 45s → tentativa 2 → Aguardar 45s → tentativa 3]  (só se falhar)
  → Verificar SHA-256 (12 ficheiros críticos)
  → Resumo + resultado final
```

**Sucesso esperado no log:**

- `Tentativa 1: success`
- `Tentativa 2/3: skipped`
- `Verificação SHA-256: success`
- `SUCESSO — deploy seguro concluído`

---

## Política: só upload, nunca apagar

O deploy **apenas envia ou sobrescreve** ficheiros que existem no Git. **Não remove** pastas ou ficheiros que existam só em produção.

Implementação: `lftp mirror -R` **sem** a flag `--delete`.

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
| `force_full_resync` / FTP-Deploy-Action antigo | Apagava `public/adms/uploads/users/*` |
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
