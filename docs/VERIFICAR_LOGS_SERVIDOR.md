# Como Verificar os Logs do Servidor

## Método 1: Via SSH (Terminal)

### 1. Conectar ao servidor via SSH

```bash
ssh tiaraju02@web119.kinghost.net
# ou o comando que você usa para conectar
```

### 2. Navegar para o diretório do projeto

```bash
cd ~/www/administrativo
```

### 3. Verificar se o arquivo de log existe

```bash
ls -la app/logs/error.log
```

### 4. Ver os últimos logs (últimas 50 linhas)

```bash
tail -n 50 app/logs/error.log
```

### 5. Ver logs em tempo real (monitorar enquanto acessa o dashboard)

```bash
tail -f app/logs/error.log
```

Pressione `Ctrl+C` para parar o monitoramento.

### 6. Procurar por logs específicos

```bash
# Procurar por "getDepartmentStatistics"
grep "getDepartmentStatistics" app/logs/error.log

# Procurar por "TrainingKpiDashboard"
grep "TrainingKpiDashboard" app/logs/error.log

# Ver últimas 20 linhas que contêm "department"
grep "department" app/logs/error.log | tail -n 20
```

### 7. Ver logs de hoje

```bash
# Se o log incluir data/hora
grep "$(date +%Y-%m-%d)" app/logs/error.log

# Ou ver últimas 100 linhas (geralmente são do dia atual)
tail -n 100 app/logs/error.log
```

## Método 2: Via FTP/File Manager

1. Conecte-se ao servidor via FTP ou use o File Manager do painel Kinghost
2. Navegue até: `www/administrativo/app/logs/`
3. Baixe o arquivo `error.log`
4. Abra em um editor de texto local

## Método 3: Via Painel Kinghost (se disponível)

1. Acesse o painel da Kinghost
2. Procure por "Logs" ou "Arquivos de Log"
3. Navegue até o arquivo `error.log`

## Comandos Úteis

### Ver tamanho do arquivo de log

```bash
ls -lh app/logs/error.log
```

### Limpar o arquivo de log (cuidado!)

```bash
# Criar backup antes
cp app/logs/error.log app/logs/error.log.backup

# Limpar o arquivo
> app/logs/error.log
```

### Verificar permissões do arquivo de log

```bash
ls -l app/logs/error.log
```

Se não tiver permissão de escrita, ajuste:

```bash
chmod 666 app/logs/error.log
```

## Exemplo Completo: Verificar Logs Após Acessar Dashboard

```bash
# 1. Conectar ao servidor
ssh tiaraju02@web119.kinghost.net

# 2. Ir para o diretório do projeto
cd ~/www/administrativo

# 3. Limpar a tela
clear

# 4. Monitorar logs em tempo real
tail -f app/logs/error.log

# 5. Em outro terminal/navegador, acesse o dashboard:
# tiaraju.com.br/administrativo/training-kpi-dashboard

# 6. Volte ao terminal e veja os logs aparecendo
# Pressione Ctrl+C para parar
```

## O Que Procurar nos Logs

Após acessar o dashboard, procure por:

1. **Logs de debug que adicionamos:**
   ```
   getDepartmentStatistics: Primeiro departamento
   TrainingKpiDashboard: Primeiro departamento passado para view
   ```

2. **Erros PHP:**
   ```
   PHP Warning
   PHP Fatal error
   PHP Parse error
   ```

3. **Erros de banco de dados:**
   ```
   SQLSTATE
   PDOException
   ```

4. **Erros de execução:**
   ```
   Erro ao carregar departmentStats
   ```

## Se o Arquivo de Log Não Existir

Se o arquivo `error.log` não existir, verifique:

1. **Se o diretório existe:**
   ```bash
   ls -la app/logs/
   ```

2. **Se não existir, crie:**
   ```bash
   mkdir -p app/logs
   touch app/logs/error.log
   chmod 666 app/logs/error.log
   ```

3. **Verificar configuração de logs no PHP:**
   ```bash
   php -i | grep error_log
   ```

## Verificar Logs do PHP (Alternativa)

Se o arquivo `app/logs/error.log` não estiver sendo usado, o PHP pode estar logando em outro lugar:

```bash
# Verificar onde o PHP está logando
php -r "echo ini_get('error_log');"

# Verificar logs do sistema (se tiver acesso)
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/php-fpm/error.log
```

