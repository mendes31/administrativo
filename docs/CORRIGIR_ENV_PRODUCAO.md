# Como Corrigir o .env em Produção

## Problema Identificado

O erro `ERR_NAME_NOT_RESOLVED` indica que o link está sendo gerado com o domínio errado `raju.kinghost.net` em vez de `www.administrativotiaraju.kinghost.net`.

Isso acontece porque o `.env` em produção está configurado com o domínio incorreto.

## Solução Imediata

### 1. Conectar ao Servidor via Putty

```bash
# Conectar ao servidor
ssh administrativotiaraju@web1102.kinghost.net
```

### 2. Navegar até o Diretório do Projeto

```bash
cd www/administrativo
```

### 3. Verificar o Conteúdo Atual do .env

```bash
cat .env | grep URL_ADM
```

**Provavelmente está assim (ERRADO):**
```
URL_ADM=http://raju.kinghost.net/administrativo/
# ou
URL_ADM=raju.kinghost.net/administrativo/
# ou
URL_ADM=http://www.raju.kinghost.net/administrativo/
```

### 4. Editar o .env

```bash
nano .env
# ou
vi .env
```

### 5. Corrigir a Linha URL_ADM

**DEVE estar assim (CORRETO):**
```
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

**Importante:**
- ✅ Deve começar com `https://` (não `http://`)
- ✅ Deve ter `www.` antes de `administrativotiaraju`
- ✅ Deve terminar com `/administrativo/`
- ✅ NÃO deve ter espaços antes ou depois do `=`

### 6. Salvar e Sair

**No nano:**
- Pressione `Ctrl + O` para salvar
- Pressione `Enter` para confirmar
- Pressione `Ctrl + X` para sair

**No vi:**
- Pressione `Esc` para garantir que está no modo de comando
- Digite `:wq` e pressione `Enter` para salvar e sair

### 7. Verificar se Foi Salvo Corretamente

```bash
cat .env | grep URL_ADM
```

**Deve mostrar:**
```
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/
```

### 8. Testar

1. Gere um novo link de recuperação de senha
2. Envie via WhatsApp
3. Teste no celular

## Correção Automática no Código

O código agora tem uma correção automática que:
- Detecta domínios incorretos (`raju.kinghost.net`, etc.)
- Substitui automaticamente pelo domínio correto
- Adiciona logs detalhados para diagnóstico

**Mas é importante corrigir o `.env` para evitar problemas futuros!**

## Verificar Logs

Após gerar um link, verifique os logs:

```bash
tail -n 50 app/logs/*.log | grep "RecoverPassword"
```

Você deve ver mensagens como:
```
RecoverPassword - URL_ADM original: http://raju.kinghost.net/administrativo/
RecoverPassword - Domínio corrigido de 'raju.kinghost.net' para 'www.administrativotiaraju.kinghost.net'
RecoverPassword - URL final gerada: https://www.administrativotiaraju.kinghost.net/administrativo/reset-password/...
```

## Exemplo Completo do .env Correto

```env
# URL do Sistema Administrativo
URL_ADM=https://www.administrativotiaraju.kinghost.net/administrativo/

# Configurações de Banco de Dados
DB_HOST=localhost
DB_NAME=administrativo
DB_USER=seu_usuario
DB_PASS=sua_senha

# Outras configurações...
```

## Troubleshooting

### Se ainda não funcionar após corrigir o .env:

1. **Verificar se o arquivo foi salvo:**
   ```bash
   cat .env | grep URL_ADM
   ```

2. **Verificar permissões do arquivo:**
   ```bash
   ls -la .env
   ```
   Deve ser `-rw-r--r--` ou similar

3. **Verificar se o PHP está lendo o .env:**
   ```bash
   php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); \$dotenv->load(); echo \$_ENV['URL_ADM'] ?? 'NÃO ENCONTRADO';"
   ```

4. **Limpar cache do PHP (se houver):**
   ```bash
   # Se usar OPcache
   php -r "opcache_reset();"
   ```

5. **Reiniciar o servidor web (se necessário):**
   ```bash
   # Depende da configuração do servidor
   # Pode ser necessário contatar o suporte da hospedagem
   ```

## Prevenção

Para evitar esse problema no futuro:

1. **Sempre verificar o `.env` após deploy**
2. **Usar variáveis de ambiente do servidor (se disponível)**
3. **Documentar a configuração correta**

## Nota Importante

A correção automática no código é uma **medida de segurança**, mas o ideal é que o `.env` esteja correto desde o início. Isso evita:
- Logs desnecessários
- Processamento extra
- Possíveis problemas de performance
- Confusão em troubleshooting

