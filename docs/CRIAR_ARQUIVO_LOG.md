# Como Criar o Arquivo de Log

## Se o arquivo não existir, execute:

```bash
cd ~/www/administrativo

# 1. Verificar se o diretório existe
ls -la app/logs/

# 2. Se o diretório não existir, criar
mkdir -p app/logs

# 3. Criar o arquivo de log
touch app/logs/error.log

# 4. Dar permissão de escrita
chmod 666 app/logs/error.log

# 5. Verificar se foi criado
ls -la app/logs/error.log
```

## Verificar onde o PHP está logando

Se o arquivo não existir, o PHP pode estar logando em outro lugar:

```bash
# Verificar configuração do PHP
php -r "echo ini_get('error_log');"

# Ou verificar todos os logs do PHP
php -i | grep error_log
```

## Alternativa: Ver logs do Apache/PHP-FPM

Se o arquivo customizado não existir, os logs podem estar em:

```bash
# Logs do Apache (se usar Apache)
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/httpd/error_log

# Logs do PHP-FPM (se usar PHP-FPM)
tail -f /var/log/php-fpm/error.log

# Logs do sistema
tail -f /var/log/messages | grep php
```

## Verificar se há erros no PHP

Você também pode verificar diretamente se há erros ao acessar o dashboard:

```bash
# Criar um script de teste
cd ~/www/administrativo
php -r "
require 'vendor/autoload.php';
use App\adms\Controllers\trainings\TrainingKpiDashboard;
\$controller = new TrainingKpiDashboard();
\$reflection = new ReflectionClass(\$controller);
\$method = \$reflection->getMethod('getDepartmentStatistics');
\$method->setAccessible(true);
\$result = \$method->invoke(\$controller);
echo json_encode(\$result[0] ?? [], JSON_PRETTY_PRINT);
"
```

