# Projeto Administrativo

## Requisitos

- PHP 8.3 ou superior
- MySQL 8.0 ou superior
- Composer

## Como rodar o projeto baixado

1. Duplique o arquivo `.env.exemple` e renomeie para `.env`.
2. Altere no arquivo `.env` as credenciais do banco de dados.
3. Crie o banco de dados com a collation `utf8mb4_unicode_ci`.
4. Altere no arquivo `.env` o endereço da aplicação na variável de ambiente `URL_ADM`.
5. Altere no arquivo `.env` as credenciais do servidor para enviar e-mail.
6. Script para gerar .htaccess dinamicamente baseado nas variáveis de ambiente ou altere o .htacces manual.

# Da raiz do projeto
```bash
python3 scripts/fix_urls.py
```

```bash
bash scripts/fix_urls.sh
```

7. Execute Script (update_urls ou update_urls) para atualizar URLs hardcoded para usar a variável de ambiente URL_ADM .
8. Envie e-mail gratuito via SMTP: [Solicitar conta SMTP](https://www.iagente.com.br/solicitacao-conta-smtp/origin/celke)

Instalar as dependências:

```bash
composer install
```

```bash
composer require --dev phpunit/phpunit
```

Executar as migrations:

```bash
vendor/bin/phinx migrate -c database/phinx.php
```

**Sessões (`adms_sessions`) e deploy:** em produção, execute as **migrations antes** (ou no mesmo release, antes de atender tráfego) quando houver mudanças no repositório de sessões ou na migration `20260402150000_adms_sessions_dedupe_and_unique.php`. Essa migration deduplica linhas, cria índice único `(user_id, session_id(190))` (prefixo por limite de 767 bytes do InnoDB com `utf8mb4`) e remove registros antigos com `status = 'invalidada'`. O código usa `INSERT ... ON DUPLICATE KEY UPDATE` em `updateSessionActivity`, que depende desse índice. Invalidação de sessão **apaga** a linha no banco; auditoria de acessos segue em **`adms_log_acessos`**.

> Nota (Gestão de Treinamentos): o fluxo oficial de hardening é via **migrations + validações PHP**.  
> O arquivo `scripts/training_hardening_validacoes.sql` é **opcional (admin only)** e não deve ser o caminho padrão de deploy.

Executar as seeds:

```bash
vendor/bin/phinx seed:run -c database/phinx.php
```

---

## Integração SAP B1 via API HTTP

A aplicação **não se conecta diretamente ao SAP B1**. Em vez disso, ela se comunica com uma **API HTTP própria**, que por sua vez acessa o banco de dados do SAP (HANA) e retorna os dados já consolidados.

A configuração dessa API é feita **exclusivamente pela tela**:

- Menu: `Administração > Configurações > Configuração SAP API`
- Rota/controller: `sap-api-config` / `SapApiConfig`

Nessa tela são definidos:

- **URL Base da API**: endereço do serviço HTTP (host + porta), por exemplo `http://192.168.1.223:5000`. Internamente, o sistema monta as consultas como `base_url + "/query?sql="`.
- **Token de Autenticação (opcional)**: se preenchido, é enviado em `Authorization: Bearer <token>` em todas as chamadas (relatórios e health check).
- **Timeout (ms)**: tempo máximo de espera na requisição HTTP.
- **Page Size**: tamanho padrão de página usado pela API (para paginação interna).
- **Endpoint de Health Check**: caminho usado pelo botão “Executar Health Check” (ex.: `/health`).
- **Ativar integração com a API SAP**: habilita/desabilita globalmente o uso da API.

As variáveis de ambiente `SAP_REPORT_API_URL` e `SAP_REPORT_API_TIMEOUT` passaram a ser **apenas legado/documentação**; toda configuração ativa deve ser feita pela tela acima.

---

## Integração SAP B1 via API HTTP

A aplicação **não se conecta diretamente ao SAP B1**. Em vez disso, ela se comunica com uma **API HTTP própria**, que por sua vez acessa o banco de dados do SAP (HANA) e retorna os dados já consolidados.

A configuração dessa API é feita **exclusivamente pela tela**:

- Menu: `Administração > Configurações > Configuração SAP API`
- Rota/controller: `sap-api-config` / `SapApiConfig`

Nessa tela são definidos:

- **URL Base da API**: endereço do seu serviço HTTP (host + porta), por exemplo `http://192.168.1.223:5000`.  
  Internamente, o sistema monta as consultas como `base_url + "/query?sql="`.
- **Token de Autenticação (opcional)**: se preenchido, é enviado em `Authorization: Bearer <token>` em todas as chamadas (relatórios e health check).
- **Timeout (ms)**: tempo máximo de espera na requisição HTTP.
- **Page Size**: tamanho padrão de página usado pela API (para paginação interna).
- **Endpoint de Health Check**: caminho usado pelo botão “Executar Health Check” (ex.: `/health`).
- **Ativar integração com a API SAP**: habilita/desabilita globalmente o uso da API.

As variáveis de ambiente `SAP_REPORT_API_URL` e `SAP_REPORT_API_TIMEOUT` passaram a ser **apenas legado/documentação**; toda configuração ativa deve ser feita pela tela acima.

<!-- Acessar o projeto: [Acessar](http://localhost/administrativo) -->
<!-- # Acessar o projeto: [Acessar](http://www.administrativotiaraju.kinghost.net/administrativo/) -->

---

## Sequência para criar o projeto

Criar o arquivo `composer.json` com a instrução básica:

```bash
composer init
```

Instalar a dependência Monolog, biblioteca PHP que permite criar arquivo de log:

```bash
composer require monolog/monolog
```

Instalar a biblioteca para gerenciar variáveis de ambiente:

```bash
composer require vlucas/phpdotenv
```

Instalar a biblioteca para criar/executar migration e seed:

```bash
composer require robmorgan/phinx
```

Criar o arquivo `phinx.php` com as configurações e alterar as mesmas:

```bash
vendor/bin/phinx init -f php
```

Testar as configurações do phinx:

```bash
vendor/bin/phinx test
```

Criar o diretório database para adicionar o arquivo phinx:

```bash
mkdir database/
```

Criar o diretório para as migrations:

```bash
mkdir database/migrations/
```

Criar a migration:

```bash
vendor/bin/phinx create AdmsUsers -c database/phinx.php
```

Executar as migrations:

```bash
vendor/bin/phinx migrate -c database/phinx.php
```

Executar o rollback na última migration (caso necessário reverter as alterações realizadas):

```bash
vendor/bin/phinx rollback -c database/phinx.php
```

Criar o diretório seeds:

```bash
mkdir database/seeds/
```

Criar a seed:

```bash
vendor/bin/phinx seed:create AddAdmsUsers -c database/phinx.php
```

Executar as seeds:

```bash
vendor/bin/phinx seed:run -c database/phinx.php
```

Instalar a biblioteca para validar o formulário:

```bash
composer require "rakit/validation"
```

Instalar a biblioteca phpmailer para enviar e-mail:

```bash
composer require phpmailer/phpmailer
```

---

## Matriz de Treinamentos – Regras de Atualização

A matriz de treinamentos (`adms_training_users`) é mantida automaticamente pelo sistema com base em eventos do cadastro de usuários, cargos e treinamentos.  
Os pontos principais são:

- **Cadastro de usuário (`CreateUser`)**  
  - Após criar um usuário com cargo, o sistema chama `TrainingMatrixService::updateMatrixForUser($userId)`.
  - Resultado: são criados vínculos apenas para treinamentos **obrigatórios e ativos** do cargo do colaborador ativo.

- **Edição de usuário (`UpdateUser`)**  
  - **Ativo → Inativo**: remove vínculos ativos (`removeActiveLinksByUser`) e cancela avaliações pendentes/em andamento.
  - **Inativo → Ativo**: recria vínculos obrigatórios com `recreateLinksForReactivatedUser($userId)` (somente treinamentos ativos).
  - **Troca de cargo**: usa `checkAndFixUserLinks($userId)` para:
    - converter vínculos individuais em vínculos por cargo quando o treinamento passa a ser obrigatório;
    - criar vínculos obrigatórios que faltam para o novo cargo;
    - manter o histórico de treinamentos concluídos.
  - **Edição sem troca de status/cargo**: `updateMatrixForUser($userId)` apenas recalcula os vínculos daquele colaborador.

- **CRUD de treinamentos (`CreateTraining`, `UpdateTraining`, `DeleteTraining`)**  
  - **Criar**: `updateMatrixForAllUsers()` para aplicar o novo treinamento obrigatório a todos os usuários impactados.
  - **Ativo → Inativo**: remove vínculos ativos com `removeActiveLinksByTraining($trainingId)` e preserva apenas o histórico concluído.
  - **Inativo → Ativo**: `recreateLinksForReactivatedTraining($trainingId)` recria vínculos para usuários ativos dos cargos obrigatórios.
  - **Editar treinamento ativo sem mudar status**: `updateMatrixForAllUsers()` para refletir as novas regras.

- **Vínculos de cargos x treinamentos (`TrainingPositions`)**  
  - Ao salvar os vínculos:
    - Remove vínculos ativos de cargos que deixaram de ser obrigatórios (`removeActiveLinksByCargoAndTraining`).
    - Para cada cargo ainda obrigatório, busca usuários ativos do cargo e chama `TrainingUsersRepository::recreateLinksForUser($userId, $cargoId)` para alinhar a matriz.

- **Exclusão de cargo (`DeletePosition`)**  
  - Após excluir um cargo, o sistema chama `updateMatrixForAllUsers()` para limpar/ajustar vínculos que ficaram inconsistentes.

- **Ferramentas administrativas da matriz (`TrainingMatrixManager`)**  
  - Tela somente leitura com estatísticas da matriz (totais e distribuição por status).
  - A sincronização é automática; não há mais botões de atualização manual.

- **Nova versão de treinamento (`NewTrainingVersion`)**  
  - Após `createNewVersion()`, o sistema:
    - remove vínculos da versão anterior em `adms_training_users` (histórico permanece em `adms_training_applications`);
    - chama `syncAfterTrainingVersion($newId)` para materializar vínculos da versão atual nos cargos obrigatórios;
    - recalcula status dinâmicos imediatamente.

> Importante: o `TrainingUsersRepository` não chama mais `recreateLinksForUser` internamente em operações básicas (como `insertOrUpdate`), para evitar recursão e estouro de memória.  
> O recálculo “global” da matriz sempre deve ser feito via `TrainingMatrixService` ou pelas telas administrativas citadas acima.

---

## Como usar o GitHub

Baixar os arquivos do Git:

```bash
git clone --branch <branch_name> <repository_url> .
```

Definir as configurações do usuário:

```bash
git config --local user.name "mendes31 Rafael Mendes"
git config --local user.email "raffaell_mendez@hotmail.com"
```

Verificar a branch:

```bash
git branch
```

Baixar as atualizações:

```bash
git pull
```

Adicionar todos os arquivos modificados no staging area:

```bash
git add .
```

Commit representa um conjunto de alterações em um ponto específico da história do seu projeto, registra apenas as alterações adicionadas ao índice de preparação.  
O comando -m permite que insira a mensagem de commit diretamente na linha de comando:

```bash
git commit -m "Descrição do commit"
```

Enviar os commits locais para um repositório remoto:

```bash
git push <remote> <branch>
git push origin dev-master
```

---

## Deploy em produção (Kinghost)

Publicação **automática** via GitHub Actions ao fazer push em `dev-master` ou `main`.

**Documentação completa:** [docs/DEPLOY_PRODUCAO.md](docs/DEPLOY_PRODUCAO.md) · [docs/COMANDOS_SERVIDOR_SSH.md](docs/COMANDOS_SERVIDOR_SSH.md) (SSH/PuTTY).

### Fluxo normal

```
git commit → git push origin dev-master → GitHub Actions (~15–30s se poucos ficheiros) → produção actualizada
```

1. Push dispara o workflow **Deploy PHP para Kinghost (incremental + seguro)**.
2. **2 tentativas FTP-Deploy-Action** (incremental por hash — **rápido**, como antes).
3. Se falhar (timeout Kinghost): **1 fallback lftp** upload-only (sem `--delete`).
4. **Verificação SHA-256** de 12 ficheiros críticos.

### Política de segurança

| Regra | Detalhe |
|-------|---------|
| **Incremental rápido** | FTP-Deploy-Action compara hash (`.ftp-deploy-sync-state.json`) — só envia alterados |
| **Só upload no fallback** | lftp `mirror -R` **sem** `--delete` |
| **Uploads protegidos** | `public/adms/uploads/**` excluído (fotos, anexos, timeline, CRM) |
| **Não enviar** | `.env`, `vendor/`, `storage/cache/`, `logs/`, dados SST/LGPD/folha |
| **Raiz FTP** | Login abre em `~/www/administrativo/` — deploy usa `./` (não criar subpasta `administrativo/`) |

**Não usar FileZilla** para PHP do projecto. **Nunca** usar ressync forçado que apague estado FTP — uploads estão em `exclude`.

### Migrations após deploy

O FTP **não executa** migrations. Se o commit incluir ficheiros em `database/migrations/`, no **PuTTY**:

```bash
cd /home/tiaraju/www/administrativo
php vendor/bin/phinx migrate -c database/phinx.php -e production
```

### Verificação manual no servidor (opcional)

```bash
php scripts/verify_production_deploy.php
```

### Hotfix de emergência (SCP)

Se o GitHub Actions falhar e for urgente:

```powershell
.\scripts\hotfix_training_compliance_deploy.ps1
```

Depois no servidor: `php scripts/generate_ftp_deploy_state.php` (opcional, legado) e `php scripts/verify_production_deploy.php`.

### Scripts de deploy

| Script | Uso |
|--------|-----|
| `scripts/deploy_lftp_upload.sh` | Upload FTP (usado pelo GitHub Actions) |
| `scripts/verify_ftp_deploy_hashes.php` | Compara SHA-256 Git vs servidor |
| `scripts/verify_production_deploy.php` | Validação via SSH no servidor |
| `scripts/deploy_excludes.php` | Lista de exclusões (paridade com workflow) |
| `scripts/detect_ftp_deploy_root.php` | Diagnóstico da raiz FTP |

**Produção:** `https://tiaraju.com.br/administrativo/` — SSH `tiaraju02@web119.kinghost.net`, path `/home/tiaraju/www/administrativo`.

---

## Lista de erros
001 - DBConnection.php - Erro de conexão com o banco de dados  
002 - LoadPageAdm.php - Não encontrou a página  
003 - LoadPageAdm.php - Não encontrou a controller  
004 - LoadPageAdm.php - Não encontrou o método  
005 - LoadViewService.php - Não encontrou a VIEW

**001** — falha de conexão (`DbConnection`).  
**003–006** — ver tabela *Fluxo atual* (`LoadPageAdmAccessLevel`); não confundir com o **roteador legado** (`LoadPageAdm`).

No fluxo atual, o **004** aparece quando o **método da controller não é invocável** *ou* quando a **action lança exceção** (SQL inválido, PDO com placeholders duplicados, etc.); a mensagem para o utilizador é a mesma (“Erro 004…”).

A listagem antiga que associava **002–004** todos a `LoadPageAdm.php` (“não encontrou método”) descrevia apenas o **roteador legado**; o `index.php` usa **`LoadPageAdmAccessLevel`** e `adms_pages`.

**Sub-rotas** (ex.: `people-reports/export-training-csv`): não é necessário cadastrar cada segmento em `adms_pages`; basta a página pai `people-reports` com `controller` = `PeopleReports` e `controller_url` = `people-reports`. O segundo segmento vira nome do método (`exportTrainingCsv`). A permissão de acesso segue a da página pai.

### Fluxo atual (`LoadPageAdmAccessLevel` + `PagesRoutesRepository`)

| Código | Origem | Significado |
|--------|--------|-------------|
| **001** | `DbConnection` / conexão | Falha de conexão com o banco de dados. |
| **003** | `LoadPageAdmAccessLevel` | Página/rota **não cadastrada** em `adms_pages` (ou `page_status` ≠ 1), ou pacote inexistente no `JOIN`. |
| **004** | `LoadPageAdmAccessLevel` | Método da controller não invocável **ou** exceção ao executar a action (ex.: `PDOException` / SQL). |
| **005** | `LoadViewService` | Arquivo da view (`.php`) não encontrado no caminho sob `app/`. |
| **006** | `LoadPageAdmAccessLevel` | Classe da controller **não carregada pelo autoload**. Causas comuns: (1) em **Linux**, caixa de `directory` / pacote diferente das pastas; (2) **`adms_pages.controller` com slug** em vez de PascalCase. Em *list-pages* há colunas **Classe (PHP)** e **URL (slug)**. |

### Roteador legado (`LoadPageAdm` — lista branca de controllers)

| Código | Significado |
|--------|-------------|
| **002** | Página não está na lista permitida do roteador. |
| **003** | Controller não encontrada (resolução antiga por diretório). |
| **004** | Método não encontrado. |

### Produção Linux — checklist se aparecer **006**

1. Conferir deploy do arquivo PHP da controller (ex.: `app/adms/Controllers/logs/ListConnectedUsers.php`).
2. No banco: `SELECT controller, directory, adms_packages_page_id FROM adms_pages WHERE controller_url = '...'` — `directory` igual à pasta em disco; pacote apontando para `adms_packages_pages.name = 'adms'`.
3. Rodar `composer dump-autoload -o` no servidor após deploy, se o autoload estiver desatualizado.

### Padrão de cadastro em `adms_pages`

| Campo | Formato | Exemplo |
|-------|---------|---------|
| `controller` | Nome da classe PHP (**PascalCase**), igual ao arquivo em `Controllers/{directory}/` | `ListUsers`, `ListConnectedUsers` |
| `controller_url` | Slug da rota (**kebab-case** minúsculo) | `list-users`, `list-connected-users` |
| `directory` | Pasta real em disco (camelCase ou minúsculo, ex.: `users`, `accessLevels`, `logs`) | `logs` |

O cadastro via **Cadastrar/Editar página** valida esses formatos (`ValidationPageService`).