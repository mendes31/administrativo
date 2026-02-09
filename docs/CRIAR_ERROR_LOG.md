# Criar Arquivo error.log

## Via File Manager (Kinghost)

1. No gerenciador de arquivos, navegue até `app/logs/`
2. Clique em "NOVO ARQUIVO"
3. Digite o nome: `error.log`
4. Deixe o arquivo vazio e salve
5. Clique com o botão direito no arquivo e defina permissões: `666` (leitura e escrita para todos)

## Via SSH

```bash
cd ~/www/administrativo
touch app/logs/error.log
chmod 666 app/logs/error.log
```

## Verificar Logs Existentes

Você já tem vários arquivos de log. Pode verificar se há informações relevantes:

### Ver logs de treinamentos:
```bash
cd ~/www/administrativo
tail -n 50 app/logs/debug_training_applications.log
```

### Ver logs de sessão (pode ter informações de debug):
```bash
tail -n 50 app/logs/session_debug.log
```

### Ver logs de filtro global:
```bash
tail -n 50 app/logs/filtro_global_debug.log
```

## Configurar PHP para Usar error.log

Se o PHP não estiver logando automaticamente, você pode verificar a configuração:

```bash
php -r "echo 'error_log: ' . ini_get('error_log') . PHP_EOL;"
php -r "echo 'log_errors: ' . (ini_get('log_errors') ? 'On' : 'Off') . PHP_EOL;"
```

## Alternativa: Usar um dos logs existentes

Se preferir, podemos usar um dos logs existentes para debug. Por exemplo, adicionar logs em `debug_training_applications.log`:

```php
error_log("getDepartmentStatistics: ...", 3, __DIR__ . '/../app/logs/debug_training_applications.log');
```

