# Verificar Onde os Logs Estão Sendo Escritos

## 1. Verificar configuração do PHP

Execute no servidor:

```bash
cd ~/www/administrativo
php -r "echo 'error_log: ' . ini_get('error_log') . PHP_EOL;"
php -r "echo 'log_errors: ' . (ini_get('log_errors') ? 'On' : 'Off') . PHP_EOL;"
```

## 2. Verificar arquivo .user.ini

O projeto tem um arquivo `.user.ini` que pode configurar o log:

```bash
cd ~/www/administrativo
cat scripts/.user.ini | grep error_log
```

Se o arquivo existir, ele pode estar configurando o log para:
- `/home/administrativotiaraju/www/administrativo/logs/php_errors.log`
- Ou outro caminho

## 3. Verificar logs existentes

Você já tem vários logs em `app/logs/`. Verifique se há informações relevantes:

```bash
cd ~/www/administrativo

# Ver logs de treinamentos (pode ter informações úteis)
tail -n 50 app/logs/debug_training_applications.log

# Ver logs de sessão
tail -n 50 app/logs/session_debug.log

# Ver logs de filtro global
tail -n 50 app/logs/filtro_global_debug.log
```

## 4. Criar arquivo error.log no local correto

Baseado no `.user.ini`, o log pode estar em:

```bash
cd ~/www/administrativo

# Criar diretório logs (se não existir)
mkdir -p logs

# Criar arquivo de log
touch logs/php_errors.log
chmod 666 logs/php_errors.log

# Verificar
ls -la logs/php_errors.log
```

## 5. Verificar logs do sistema (alternativa)

Se não encontrar logs customizados, verifique logs do sistema:

```bash
# Logs do Apache
tail -f /var/log/apache2/error.log

# Ou logs do PHP-FPM
tail -f /var/log/php-fpm/error.log

# Ou logs do sistema
tail -f /var/log/messages | grep php
```

## 6. Testar se os logs estão funcionando

Crie um arquivo de teste:

```bash
cd ~/www/administrativo
php -r "error_log('TESTE DE LOG - ' . date('Y-m-d H:i:s'));"
```

Depois verifique onde o log foi escrito:

```bash
# Verificar em logs/php_errors.log
tail -n 5 logs/php_errors.log

# Verificar em app/logs/error.log (se criou)
tail -n 5 app/logs/error.log

# Verificar logs do sistema
tail -n 5 /var/log/apache2/error.log
```

