# Como Executar o Script de Atualização de Status no Servidor

## Problema: Script não encontrado

Se você receber o erro:
```
Could not open input file: scripts/update_training_statuses.php
```

Isso significa que você precisa navegar para o diretório correto do projeto.

## Solução

### 1. Navegar para o diretório do projeto

```bash
# Primeiro, encontre onde está o projeto
cd ~/public_html/administrativo
# ou
cd /home/tiaraju02/public_html/administrativo
# ou
cd /var/www/html/administrativo
```

### 2. Verificar se o script existe

```bash
ls -la scripts/update_training_statuses.php
```

### 3. Executar o script

```bash
php scripts/update_training_statuses.php
```

## Caminho Completo (Alternativa)

Se você souber o caminho completo do projeto, pode executar diretamente:

```bash
php /caminho/completo/para/administrativo/scripts/update_training_statuses.php
```

## Exemplo Completo

```bash
# 1. Navegar para o diretório
cd ~/public_html/administrativo

# 2. Verificar se está no lugar certo
pwd
ls scripts/update_training_statuses.php

# 3. Executar
php scripts/update_training_statuses.php
```

## Saída Esperada

Se tudo estiver correto, você verá:

```
=== Atualização de Status Dinâmicos de Treinamentos ===
Iniciando em: 2024-02-09 10:30:00

Status dinâmicos atualizados com sucesso.

Concluído em: 2024-02-09 10:30:15
```

## Verificar se Funcionou

Após executar, você pode verificar:

1. **Arquivo de cache**:
```bash
cat storage/cache/system/training_status_last_run.json
```

2. **Status no banco** (via phpMyAdmin ou SQL):
```sql
SELECT status, COUNT(*) as total
FROM adms_training_users
WHERE status IN ('em_dia', 'dentro_do_prazo', 'proximo_vencimento', 'vencido', 'agendado', 'concluido')
GROUP BY status;
```

## Nota sobre o Warning do NewRelic

O warning sobre `newrelic.so` é apenas um aviso e não impede a execução do script. Pode ser ignorado ou você pode desabilitar a extensão no `php.ini` se necessário.

