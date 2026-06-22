# Alinhar produção com o Git (deploy completo)

Use este guia quando produção ficou atrás do `dev-master` (ex.: `menu.php` restaurado de `.bak`, menu SST incompleto).

**Regra:** publicar código **só** via push → GitHub Actions (FTP). Não usar FileZilla para ficheiros PHP do projeto.

---

## Antes do deploy (SSH — uma vez)

```bash
cd ~/www/administrativo

# Força o FTP a reenviar ficheiros que o estado antigo marca como "já sincronizados"
mv .ftp-deploy-sync-state.json .ftp-deploy-sync-state.json.bak 2>/dev/null || true

# Opcional: backup do menu atual
cp app/adms/Views/partials/menu.php app/adms/Views/partials/menu.php.pre-deploy-$(date +%Y%m%d) 2>/dev/null || true
```

No PC: `git push origin dev-master` (ou merge para `main`, conforme o ramo do workflow).

Aguarde o workflow **Deploy PHP para Kinghost** terminar com **SUCESSO** (sem timeout `421`).

---

## Ficheiros críticos deste alinhamento

### 1. Menu (obrigatório)

| Ficheiro | Linhas ~ | Notas |
|----------|----------|--------|
| `app/adms/Views/partials/menu.php` | 1778 | Entradas SST equipamentos + proteção `menuPermission` |

### 2. Módulo SST — Equipamentos e vistorias

**Controllers** (`app/adms/Controllers/sst/`):

- `SstListEquipamentoTipos.php`, `SstCreateEquipamentoTipo.php`, `SstViewEquipamentoTipo.php`, `SstUpdateEquipamentoTipo.php`, `SstDeleteEquipamentoTipo.php`
- `SstListEquipamentos.php`, `SstCreateEquipamento.php`, `SstViewEquipamento.php`, `SstUpdateEquipamento.php`, `SstDeleteEquipamento.php`
- `SstListEquipamentoVistorias.php`, `SstMinhasEquipamentoVistorias.php`, `SstExecuteEquipamentoVistoria.php`, `SstGenerateEquipamentoVistoria.php`
- `SstEquipamentoSettings.php`, `SstManageEquipamentoChecklistItem.php`

**Views** (`app/adms/Views/sst/equipamentos/`): `tipos_list.php`, `tipo_form.php`, `tipo_view.php`, `list.php`, `form.php`, `view.php`, `vistorias_list.php`, `minhas_vistorias.php`, `vistoria_execute.php`, `settings.php`

**Models / Services / Helpers**: repositórios `SstEquipamento*`, `SstEquipamentoVistoriaGeneratorService`, `SstEquipamentoPeriodicidadeHelper`

**Outros**: `app/adms/Controllers/Services/PageLayoutService.php`, `routes/LoadPageAdm.php`, `scripts/cron_sst_equipamento_vistorias.php`

### 3. Treinamentos (correções KPI / matriz)

- `app/adms/Models/Repository/TrainingUsersRepository.php`
- `app/adms/Models/Repository/TrainingsRepository.php`
- Controllers em `app/adms/Controllers/trainings/` (KPI, matriz, posições, versões)
- `app/adms/Views/trainings/matrixManager.php`

### 4. Migrations (não vão por FTP — correr no servidor)

```bash
cd ~/www/administrativo
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

| Migration | Função |
|-----------|--------|
| `20260622100000_create_adms_sst_equipamentos_tables.php` | Tabelas SST equipamentos |
| `20260622100100_seed_sst_equipamento_tipos_checklists.php` | Tipos/checklists iniciais |
| `20260622100200_register_sst_equipamentos_pages.php` | Páginas em `adms_pages` |
| `20260622100300_sync_sst_equipamentos_pages_permissions.php` | Permissões |
| `20260623100000_add_sst_equipamento_vistoria_settings.php` | Settings vistorias |
| `20260623100100_register_sst_equipamento_settings_page.php` | Página config |
| `20260623110000_register_sst_generate_equipamento_vistoria_page.php` | Geração vistorias |
| `20260623120000_bump_menu_cache_sst_equipamentos.php` | Invalida cache menu |

### 5. Infra deploy (opcional no servidor)

- `scripts/generate_ftp_deploy_state.php` — só no Git; usar no PC se o estado FTP corromper de novo
- `scripts/verify_production_deploy.php` — validação pós-deploy
- `.github/workflows/deploy.yml` — não é enviado ao site (excluído)

### 6. Não enviados pelo FTP (normal)

- `vendor/` — já deve existir no servidor (`composer install` se faltar)
- `.env` — específico de produção
- `logs/`, `storage/cache/`

---

## Depois do deploy (SSH)

```bash
cd ~/www/administrativo

php scripts/verify_production_deploy.php

php vendor/bin/phinx migrate -c database/phinx.php -e production

wc -l app/adms/Views/partials/menu.php
php -l app/adms/Views/partials/menu.php
grep -c "Tipos de equipamento" app/adms/Views/partials/menu.php
```

Saia e entre no portal (cache de permissões de menu).

---

## Se o deploy FTP falhar com timeout

1. Não regenere o estado manualmente com ficheiros errados no servidor.
2. Corrija primeiro `menu.php` (via novo deploy após `mv` do estado).
3. Só após deploy **bem-sucedido**, no PC: `php scripts/generate_ftp_deploy_state.php` e envie o JSON se necessário (ver `docs/COMANDOS_SERVIDOR_SSH.md`).

---

## Lista completa Git (desde módulo SST)

Gerar localmente:

```bash
git diff --name-only a7dcf23^..HEAD
```
