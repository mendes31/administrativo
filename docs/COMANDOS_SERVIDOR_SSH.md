# 🖥️ Comandos para Executar no Servidor via SSH

## 📍 **PASSO 1: Navegar para o Diretório do Projeto**

Você está em `~` (home), mas precisa estar no diretório do projeto:

```bash
# Verificar onde você está
pwd

# Navegar para o diretório do projeto (ajuste conforme necessário)
cd ~/www/administrativo

# OU se o projeto estiver em outro local, tente:
cd /files/administrativo
# ou
cd /home/tiaraju/public_html/administrativo
# ou
cd /var/www/administrativo

# Verificar se está no diretório correto (deve mostrar vendor, database, app, etc.)
ls -la
```

## ⚙️ **Editar o `.env` no servidor**

O ficheiro `.env` fica na **raiz do projeto** (mesmo nível que `index.php` e `vendor/`). Formato: uma variável por linha, `CHAVE=valor`, sem aspas exceto se o valor tiver espaços.

### **Opção A — SSH (recomendado)**

```bash
cd /caminho/do/administrativo   # pasta onde está o .env
cp .env .env.bak.$(date +%Y%m%d)   # backup antes de editar
nano .env                          # ou: vim .env
```

- No **nano**: edite, depois `Ctrl+O` (guardar), Enter, `Ctrl+X` (sair).
- No **vim**: `i` para inserir, `Esc`, `:wq` para guardar e sair.
- Confirme que a linha existe, ex.: `URL_ADM=https://seu-dominio.com.br/administrativo/` (com barra final, caminho certo da app).

**Nota:** em muitos hostings o PHP já lê o `.env` no próximo pedido; não é obrigatório reiniciar Apache, mas se usarem **OPcache** agressivo ou cache de config, pode ser preciso reiniciar PHP-FPM/Apache no painel.

### **Opção B — FTP / SFTP / FileZilla**

Ligue ao servidor, vá à pasta do projeto, **descarregue** o `.env`, edite no PC, **volte a enviar** (modo texto/ASCII). Faça backup do original no servidor antes.

### **Opção C — Painel (Kinghost, cPanel, etc.)**

Gestor de ficheiros → pasta do site → editar `.env` online. Guarde e teste o site.

**Segurança:** não commite o `.env` no Git; não partilhe passwords em tickets com screenshot completo.

---

## ✅ **PASSO 2: Verificar se o Vendor Existe**

```bash
# Verificar se a pasta vendor existe
ls -la vendor/

# Se não existir, você precisa rodar o Composer primeiro
php composer.phar install --no-dev --optimize-autoloader
# OU
composer install --no-dev --optimize-autoloader
```

## 🚀 **PASSO 3: Executar as Migrations**

```bash
# Verificar status das migrations
php vendor/bin/phinx status -c database/phinx.php -e production

# Executar todas as migrations
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

### Sessões (`adms_sessions`) — ordem no deploy

Em releases que alterem `app/adms/Models/Repository/AdmsSessionsRepository.php` ou migrations de sessão:

1. **Rodar as migrations antes** (ou no mesmo deploy, antes de liberar tráfego), para aplicar deduplicação, índice único `(user_id, session_id(190))` (compatível com limite de 767 bytes do InnoDB/`utf8mb4`) e limpeza de linhas antigas com `status = 'invalidada'`. Ex.: migration `20260402150000_adms_sessions_dedupe_and_unique.php`.
2. Só então o código que usa `INSERT ... ON DUPLICATE KEY UPDATE` em `updateSessionActivity` fica consistente com o banco.

**Observação:** invalidação de sessão no banco passou a ser **remoção da linha** (`DELETE`). O histórico de login/logout e eventos relacionados continua em **`adms_log_acessos`**, não em `adms_sessions`.

### Permissões por nível (`adms_access_levels_pages`) — ordem no deploy

Em releases que incluam deduplicação, índice único `(adms_access_level_id, adms_page_id)` e `INSERT ... ON DUPLICATE KEY UPDATE` nos repositórios de páginas/ACL:

1. **Enviar o código** e **rodar as migrations antes** de liberar tráfego (ou numa janela com pouco uso), na ordem natural do Phinx:
   - `20260411140000_dedupe_adms_access_levels_pages.php` — remove duplicados na tabela de permissões.
   - `20260411160000_unique_adms_access_levels_pages_level_page.php` — garante unicidade e cria `uk_alp_access_level_page`.
   - `20260412120000_remove_obsolete_apply_evaluation_page.php` — remove a página obsoleta **ApplyEvaluation** (`apply-evaluation`) e respetivas linhas em `adms_access_levels_pages` (e tabelas de escopo, se existirem).
2. Só depois o código atualizado (`AccessLevelsPagesRepository`, `PagesRepository`, etc.) fica alinhado com o índice único.

**Seeds:** a entrada **Aplicar Avaliação (OBSOLETO)** foi retirada de `AddAdmsPages`; voltar a correr essa seed **não** recria essa rota. Não é obrigatório correr seed só por causa desta alteração se a migration já limpou produção.

### Timeline — índice em comentários (performance do feed)

Migration `20260519150000_add_timeline_comments_post_status_index.php`: cria índice composto `(post_id, status)` em `adms_timeline_comments` para acelerar contagens de comentários ativos no feed. **Não altera dados nem regras** — só o plano de execução do MySQL.

### Timeline — destaque e celebrações (aniversário / tempo de empresa)

Migrations (rodar **depois** de enviar o código da timeline para o servidor):

| Migration | O que faz |
|-----------|-----------|
| `20260520120000_timeline_featured_and_celebration_types` | Coluna `is_featured`, tipos `birthday` / `tenure` em `post_type` |
| `20260520120001_register_timeline_feature_post_permission` | Página **Destaque na Timeline** (`TimelineFeaturePost`) + cópia inicial para quem tem moderação |
| `20260520140100_bump_menu_cache_timeline_feature` | Invalida cache de permissões em sessão (checkbox “Destaque” após deploy) |

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

**Super Administrador:** após o deploy do código corrigido, o checkbox **Destaque** deve aparecer mesmo sem a permissão explícita. Demais níveis precisam da página **Destaque na Timeline** em *Níveis de Acesso*.

Se o checkbox ainda não aparecer: confirme que `feed.php` e `Timeline.php` foram publicados, rode as migrations acima e **saia e entre** de novo (ou aguarde a migration de bump do cache).

```bash
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

## 🌱 **PASSO 4: Executar as Seeds**10

```bash
# Executar todas as seeds
php vendor/bin/phinx seed:run -c database/phinx.php -e production

# OU executar seeds específicas
php vendor/bin/phinx seed:run -c database/phinx.php -e production -s AddAdmsPages
php vendor/bin/phinx seed:run -c database/phinx.php -e production -s AddDepartments
```

## ⏱️ **Importação de folha PDF (pedido “cai” / browser fecha em produção)**

PDFs com **muitas páginas** podem demorar vários minutos. O PHP já chama `set_time_limit(0)`, `ignore_user_abort(true)` e, por defeito, `memory_limit` **512M** (sobrescrevível com `PAYROLL_IMPORT_MEMORY_LIMIT` no `.env`).

Se o **browser** mostrar erro de rede, página em branco ou fechar sozinho **antes** de aparecer a mensagem de sucesso, o problema costuma ser **timeout à frente do PHP**:

| Camada | O que ajustar (exemplo) |
|--------|-------------------------|
| **Nginx** (PHP-FPM) | `fastcgi_read_timeout 900s;`, `client_max_body_size` ≥ tamanho do PDF |
| **Apache** | `TimeOut 900` (ou valor alto no vhost) |
| **PHP-FPM** | `request_terminate_timeout = 900` (ou `0` com cuidado) |
| **Cloudflare** (se usar) | timeout do plano (ex.: ~100 s no gratuito) — pedidos longos podem falhar; testar sem proxy ou subir timeout |
| **Hosting** | painel (Kinghost, etc.) pode ter limite de execução; abrir chamado para aumentar |

Depois de alterar o servidor, **reinicie** Nginx/Apache e PHP-FPM. Confira também `upload_max_filesize` e `post_max_size` no `php.ini` ≥ ao PDF.

### **404 do WordPress (“Epic 404”) após processar o PDF**

**Causa:** no `.env`, `URL_ADM` está só na **raiz do site** (onde está o WordPress), por exemplo `https://www.tiaraju.com.br/`, em vez do caminho da app (`.../administrativo/`). O redirect pós-importação vai para `https://www.tiaraju.com.br/import-payroll-documents`, que o WordPress não reconhece.

**Solução:** em produção, defina `URL_ADM` com o URL completo do módulo, **incluindo** `/administrativo/` no fim, ex.: `https://www.tiaraju.com.br/administrativo/` (ajuste domínio e HTTPS). O código também tenta corrigir automaticamente hosts `*.tiaraju.com.br` e `*.administrativotiaraju.kinghost.net` quando falta esse segmento (`UrlAdmHelper`).

---

## ⚠️ **PROBLEMAS COMUNS**

### **1. "Could not open input file: vendor/bin/phinx"**
**Causa:** Você não está no diretório correto do projeto.

**Solução:**
```bash
# Encontrar onde está o projeto
find ~ -name "phinx.php" -type f 2>/dev/null
# ou
find / -name "database/phinx.php" -type f 2>/dev/null | head -5

# Depois navegar para o diretório encontrado
cd /caminho/encontrado/..
```

### **2. "Permission denied" no .bash_history**
**Causa:** Problema de permissões no arquivo de histórico.

**Solução (não crítico, mas pode corrigir):**
```bash
# Corrigir permissões do .bash_history
chmod 600 ~/.bash_history
# ou
touch ~/.bash_history && chmod 600 ~/.bash_history
```

### **3. Warning do New Relic**
**Causa:** Extensão PHP não disponível (não é crítico).

**Solução:** Pode ignorar, é apenas um aviso.

### **4. "vendor não existe"**
**Causa:** Composer não foi executado.

**Solução:**
```bash
# Verificar se composer.phar existe
ls -la composer.phar

# Se existir, instalar dependências
php composer.phar install --no-dev --optimize-autoloader

# Se não existir, baixar o Composer
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### **5. Phinx `status`: `MISSING MIGRATION FILE` (ex.: `20250120130000` / `CreateAdmsStrategicPlanObservations`)**

**Causa:** essa migration foi **renomeada/reordenada** no Git (passou a existir só como `20250710160010_create_adms_strategic_plan_observations.php`). No servidor, o `phinxlog` ainda tem a versão **antiga** (`20250120130000`) marcada como executada, mas o ficheiro `.php` com esse timestamp **já não está** no repositório — o Phinx acusa ficheiro em falta.

**Não é falha da app:** o schema já foi aplicado quando a migration antiga correu; só o registo no `phinxlog` ficou desalinhado do nome atual do ficheiro.

**Solução (MySQL / phpMyAdmin)** — alinhar o log à migration nova (igual ao script completo em `scripts/ajustar_phinxlog_apos_reorganizacao.sql` e `docs/AJUSTAR_PHINXLOG_SERVIDOR.md`):

```sql
-- Remove o registo da versão antiga (ficheiro já não existe no repo)
DELETE FROM phinxlog WHERE version = 20250120130000;

-- Regista a versão nova, só se ainda não existir (evita duplicar)
INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint)
SELECT 20250710160010, 'CreateAdmsStrategicPlanObservations', NOW(), NOW(), 0
WHERE NOT EXISTS (SELECT 1 FROM phinxlog WHERE version = 20250710160010);
```

Depois confira:

```bash
php vendor/bin/phinx status -c database/phinx.php -e production
```

Se o `phinx status` listar **outras** linhas `MISSING` para timestamps antigos (LGPD, índices de training, etc.), use o script SQL completo `scripts/ajustar_phinxlog_apos_reorganizacao.sql` ou leia `docs/REORGANIZACAO_MIGRATIONS.md`.

### **6. `fatal: not a git repository` (ao rodar `git pull`)**

**Causa:** o site em produção foi publicado por **FTP/upload** ou cópia de ficheiros. Nessa pasta **não existe** a pasta oculta `.git`, portanto não é um clone — o Git não sabe qual remoto puxar.

**O que fazer:**

| Situação | Ação |
|----------|------|
| Continuar sem Git no servidor | Atualizar código só por **FTP / gerenciador de ficheiros** (Kinghost), enviando os mesmos caminhos do teu PC. |
| Passar a usar `git pull` no servidor | Uma vez: `git clone https://github.com/.../administrativo.git` noutra pasta (ou backup da atual), configurar o domínio para essa pasta, `.env`, `vendor` com `composer install`, permissões. **Não é só copiar `.git` para dentro da pasta antiga** sem planeamento. |
| CI/CD (GitHub Actions → FTP) | O deploy já envia ficheiros; SSH serve para `phinx migrate`, não para `git pull`. |

Para confirmar no SSH:

```bash
ls -la ~/www/administrativo/.git
# Se "No such file or directory" → não há repositório Git aí.
```

## 📋 **CHECKLIST RÁPIDO**

Execute na ordem:

```bash
# 1. Navegar para o projeto
cd ~/www/administrativo  # Ajuste conforme necessário

# 2. Verificar se está no lugar certo
ls -la | grep -E "vendor|database|app|.env"

# 3. Verificar se vendor existe
test -d vendor && echo "✅ Vendor existe" || echo "❌ Precisa rodar composer install"

# 4. Executar migrations
php vendor/bin/phinx migrate -c database/phinx.php -e production

# 5. Executar seeds
php vendor/bin/phinx seed:run -c database/phinx.php -e production
```

## 🔍 **ENCONTRAR O DIRETÓRIO DO PROJETO**

Se você não souber onde está o projeto:

```bash
# Procurar pelo arquivo phinx.php
find ~ -name "phinx.php" 2>/dev/null

# Procurar pela pasta database
find ~ -type d -name "database" 2>/dev/null | grep administrativo

# Procurar pelo arquivo .env
find ~ -name ".env" 2>/dev/null | grep administrativo

# Listar diretórios comuns
ls -la ~/www/
ls -la /files/
ls -la /home/tiaraju/public_html/
```

---

## GitHub Actions: erro FTP `ETIMEDOUT` (control socket)

Se o workflow **Deploy PHP para Kinghost** falha com:

`connect ETIMEDOUT … :21` ou `Failed to connect … are you sure your server works via FTP?`

**O que isso significa:** o runner do GitHub Actions **não consegue abrir a conexão TCP** na porta **21** do servidor FTP. A falha ocorre **antes** de usuário, senha ou modo passivo — não é problema de `exclude`, `timeout` ou versão do action.

**Mensagens como IPv6 `ENETUNREACH`** costumam aparecer junto; o bloqueio ou rota inválida no IPv6 não é o ponto principal se o IPv4 também dá timeout.

### Causas comuns em hospedagem compartilhada (ex.: Kinghost)

- Firewall que **não aceita** conexões FTP vindas de **IPs internacionais** ou de **faixas usadas pelos runners** do GitHub (Azure).
- FTP **restrito** a IPs brasileiros ou à rede do próprio painel.
- Serviço FTP **indisponível** para acesso externo.

### O que normalmente **não** resolve sozinho

- Aumentar `timeout` no `FTP-Deploy-Action`.
- Trocar `protocol` para FTPS (só faz sentido **depois** que a porta 21 ou a porta FTPS aceitar conexão).

### Caminhos práticos

1. **Kinghost/suporte:** perguntar se FTP/FTPS permite acesso a partir de **GitHub Actions** e se existe **liberação por IP** (a Meta publica faixas dos runners; mudam com o tempo).
2. **Self-hosted runner** do GitHub em uma máquina/VPS que **já consiga** conectar no FTP da hospedagem (mesmo país/rede).
3. **Deploy fora do GitHub:** artefato no Actions + upload manual (**FileZilla**) ou **SSH/rsync** se o plano tiver SSH no mesmo diretório do site.
4. **Teste rápido:** no seu PC (mesma rede que você usa no dia a dia), testar porta 21 no host FTP (PowerShell: `Test-NetConnection SEU_HOST -Port 21`). Se abrir aí e **só** falhar no Actions, reforça bloqueio de IP no servidor.

### Se o painel exigir **FTPS explícito**

No `deploy.yml`, no passo `SamKirkland/FTP-Deploy-Action`, é possível usar `protocol: ftps` e, se necessário, `port` conforme a documentação do provedor — **somente** depois de confirmar que a conexão TCP na porta correta responde a partir de uma origem externa (ou do runner).

### GitHub Actions: `lftp` e `mirror: unrecognized option '--exclude-regex'`

O pacote `lftp` do **ubuntu-latest** costuma ser antigo: a opção **`--exclude-regex`** do comando `mirror` **não existe** nessa versão. Use **`--exclude-glob '*.log'`** e, se suportado, **`--exclude-glob '**/*.log'`** em vez de regex.

Use também **`set cmd:fail-exit yes`** no início do script enviado ao `lftp`: assim, se o `mirror` falhar, o `lftp` termina com código de erro e o passo do Actions **falha de verdade**, em vez de continuar e imprimir mensagens de sucesso enganadoras.

### Estado de referência do workflow de deploy

Pushes em `main` ou `dev-master` disparam o deploy. O conteúdo exato de `.github/workflows/deploy.yml` depende do commit (FTP + fallback lftp, ou SSH/rsync noutra variante). Consulte o ficheiro no ramo em uso.

### Arquivos sempre como `uploading` no GitHub Actions (estado FTP desatualizado)

O **FTP-Deploy-Action** grava no servidor o ficheiro **`.ftp-deploy-sync-state.json`** (hash SHA-256 de cada ficheiro). Só no **fim** de um deploy **bem-sucedido** esse ficheiro é atualizado.

| Situação | Efeito no próximo deploy |
|----------|---------------------------|
| Timeout FTP (`421 No transfer timeout`) a meio do envio | Estado **não** gravado → ficheiros voltam como `uploading` |
| Upload manual (FileZilla/SCP) sem atualizar o estado | Servidor tem o ficheiro, mas o Action **não sabe** → `uploading` de novo |
| Deploy interrompido / dois deploys ao mesmo tempo | Estado incompleto ou corrompido |

**Importante:** `uploading` ≠ ficheiro errado no Git; significa “ainda não está registado no estado de sync”.

#### Passo 1 — Garantir que o servidor está igual ao Git

Exemplo (menu truncado):

```bash
cd ~/www/administrativo
wc -l app/adms/Views/partials/menu.php    # ~1777
php -l app/adms/Views/partials/menu.php   # No syntax errors
```

Corrija no PC com `scp` o ficheiro completo **antes** de regenerar o estado.

#### Passo 2 — Regenerar o estado a partir do repositório local (PC)

```powershell
cd C:\wamp64\www\administrativo
php scripts/generate_ftp_deploy_state.php
scp .ftp-deploy-sync-state.json tiaraju02@web119.kinghost.net:/home/tiaraju/www/administrativo/
```

No servidor:

```bash
ls -lh ~/www/administrativo/.ftp-deploy-sync-state.json
```

#### Passo 3 — Próximo deploy

Deve aparecer sobretudo **`File content is the same, doing nothing`** e só `replacing`/`uploading` em ficheiros **realmente novos ou alterados** no Git.

#### Alternativa (sem script)

Apagar o estado e deixar **um** deploy FTP concluir até ao fim (pode demorar e ainda falhar por timeout na Kinghost):

```bash
cd ~/www/administrativo
mv .ftp-deploy-sync-state.json .ftp-deploy-sync-state.json.bak
# Disparar deploy no GitHub Actions e aguardar SUCESSO completo
```

#### Evitar repetir

- Não enviar ficheiros grandes (ex.: `menu.php`) só por FileZilla; usar deploy ou `scp` + regenerar estado.
- Após `scp` manual, validar com `wc -l` e `php -l`.
- O workflow usa `concurrency` para não correr dois deploys em paralelo no mesmo ramo.

---

## 🔔 **Web Push (PWA) — limpeza de inscrições 410/404 e logs**

### O que faz

- **Automático:** sempre que um push é enviado (notificação in-app, teste admin), respostas **410 Gone** ou **404 Not Found** removem a linha em `adms_push_subscriptions`.
- **Manutenção (cron):** ping silencioso (`maintenance: true` no payload — o Service Worker **não** exibe notificação) para achar inscrições mortas sem esperar um evento real.
- **Log dedicado:** `logs/push_dmY.log` (ex.: `logs/push_20052026.log`).

### Cron no servidor (recomendado 1x/dia)

```bash
cd /caminho/do/administrativo
php scripts/cron_push_subscriptions_cleanup.php
# opcional: tamanho do lote (padrão 200, máx. 500)
php scripts/cron_push_subscriptions_cleanup.php 300
```

Crontab (exemplo, 03:00):

```cron
0 3 * * * cd /home/tiaraju/public_html/administrativo && php scripts/cron_push_subscriptions_cleanup.php >> logs/cron_push_cleanup.log 2>&1
```

### Painel admin

Em **Configuração Push (PWA)** → **Verificar inscrições inválidas** (até 200 por clique). Requer permissão `PrunePushSubscriptions` no nível de acesso (nova página na seed `AddAdmsPages`; em produção já existente, conceda a página ao perfil admin ou rode a seed de páginas).

### Monitorar em produção

```bash
cd /caminho/do/administrativo
tail -f logs/push_$(date +%d%m%Y).log
# ou no Windows/PowerShell, ajuste a data ddmmyyyy
grep -E "removida|Manutenção|Falha" logs/push_*.log | tail -30
```

Interpretação rápida:

| Log | Significado |
|-----|-------------|
| `Inscrição push inválida removida (410/404)` | Limpeza OK |
| `Manutenção push concluída` | Cron/manual terminou; ver `removed` |
| `Falha no envio Web Push` com `will_remove: false` | Erro temporário (rede, VAPID) — linha **mantida** |

### Deploy desta funcionalidade

Subir `service-worker.js`, `public/adms/js/pwa-app.js`, views do perfil/layout e agendar o cron. HTTPS obrigatório em produção (`URL_ADM` com `https://`).

### PWA — instalar (sem versão manual do app)

- **Todas as páginas logadas** → cards no topo (via `main.php`): **1) Ative as notificações** (PWA instalado, push pendente) → **2) Instale o aplicativo**.
- **Meu Perfil** → instalar, push e dispositivos.
- **Não** é necessário `$pushSwVersion` nem banner “Atualizar aplicativo” a cada feature: páginas PHP e JS/CSS com `?v=` no próprio arquivo refletem ao navegar ou recarregar (F5).
- O **service worker** (`service-worker.js`, URL fixa) só cuida de **push**; se o arquivo mudar, o navegador atualiza em silêncio (`skipWaiting` + reload automático).
- **Ícone na barra de status (Android):** o SW (v8) usa **URLs de string** (mesma origem do PWA) para `icon` e `badge`; versões anteriores passavam Blobs, que a Notification API não aceita (causava sino genérico). Um fetch handler cache-first serve os PNGs sem depender da rede.
- Em deploy, suba `pwa-app.js` com bump só em `pwa-app.js?v=` no layout quando alterar esse script.

### Publicação agendada de informativos

Informativos com `publish_at` futuro são salvos como `ativo = 0`. A ativação + push acontece automaticamente quando qualquer usuário acessa **Login**, **Dashboard**, **Listagem** ou **Visualização de Informativos** (throttle 60 s). Não é necessário cron externo em cenários normais de uso.

Se necessário ativar fora do horário de acesso (ex.: madrugada), existe o endpoint opcional `informativos-publish-cron?token=...` (token via `CRON_INFORMATIVOS_TOKEN` no `.env`).


