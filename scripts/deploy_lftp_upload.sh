#!/usr/bin/env bash
# Deploy para Kinghost via lftp — SOMENTE upload (mirror -R sem --delete).
# Nunca remove ficheiros no servidor. Exclusões alinhadas a scripts/deploy_excludes.php
#
# Uso (GitHub Actions ou local):
#   FTP_HOST=... FTP_USER=... FTP_PASS=... ./scripts/deploy_lftp_upload.sh [tentativa]

set -euo pipefail

attempt="${1:-1}"

if [[ -z "${FTP_HOST:-}" || -z "${FTP_USER:-}" || -z "${FTP_PASS:-}" ]]; then
  echo "❌ Defina FTP_HOST, FTP_USER e FTP_PASS."
  exit 2
fi

echo "════════════════════════════════════════════════════════"
echo "📤 Deploy lftp — tentativa ${attempt}"
echo "   Política: só envia/atualiza do Git; NUNCA apaga no servidor."
echo "════════════════════════════════════════════════════════"

lftp -u "$FTP_USER","$FTP_PASS" "$FTP_HOST" <<'EOF'
set cmd:fail-exit yes
set ftp:passive-mode true
set ftp:ssl-allow no
set dns:order "inet inet6"
set net:timeout 120
set net:max-retries 50
set net:reconnect-interval-base 2
set net:reconnect-interval-max 10
set net:idle 60
set xfer:clobber on

mirror -R --parallel=2 --ignore-time --continue --no-perms --verbose \
  --exclude-glob .git/** \
  --exclude-glob .github/** \
  --exclude-glob .gitignore \
  --exclude-glob .env \
  --exclude-glob .ftp-deploy-sync-state.json \
  --exclude-glob '*.log' \
  --exclude-glob '**/*.log' \
  --exclude-glob vendor/** \
  --exclude-glob lib/** \
  --exclude-glob node_modules/** \
  --exclude-glob .vscode/** \
  --exclude-glob .DS_Store \
  --exclude-glob LICENSE.txt \
  --exclude-glob storage/sst/epi_fichas/** \
  --exclude-glob storage/sst/attachments/** \
  --exclude-glob storage/lgpd/consentimentos/** \
  --exclude-glob storage/private/payroll/** \
  --exclude-glob storage/cache/** \
  --exclude-glob storage/logs/** \
  --exclude-glob logs/** \
  --exclude-glob app/storage/cache/** \
  --exclude-glob app/storage/logs/** \
  --exclude-glob public/adms/uploads/** \
  --exclude-glob 'app/adms/Controllers/receive copy/**' \
  ./ ./
quit
EOF

echo "✅ Tentativa ${attempt} concluída (sem remoções no servidor)."
