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
6. Envie e-mail gratuito via SMTP: [Solicitar conta SMTP](https://www.iagente.com.br/solicitacao-conta-smtp/origin/celke)

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

Acessar o projeto: [Acessar](http://localhost/administrativo)

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
