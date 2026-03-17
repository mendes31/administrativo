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
  - Permite:
    - atualizar a matriz de **todos** os usuários (`updateMatrixForAllUsers()`);
    - atualizar a matriz de **um usuário específico** (`updateMatrixForUser($userId)`);
    - atualizar a matriz de **um cargo** (recalculando para todos os usuários daquele cargo).

- **Sincronizações pontuais (`TrainingDashboard`, `SyncTrainingLinks`)**  
  - Usam `TrainingUsersRepository::syncUserTrainingLinks($userId, $positionId)` para alinhar manualmente os vínculos de um colaborador com um cargo específico, sem recalcular toda a matriz.

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

## Lista de erros

001 - DBConnection.php - Erro de conexão com o banco de dados  
002 - LoadPageAdm.php - Não encontrou a página  
003 - LoadPageAdm.php - Não encontrou a controller  
004 - LoadPageAdm.php - Não encontrou o método  
005 - LoadViewService.php - Não encontrou a VIEW
006 - teste