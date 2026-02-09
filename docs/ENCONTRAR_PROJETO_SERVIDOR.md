# Como Encontrar o Projeto no Servidor

## Passos para Localizar o Projeto

### 1. Verificar a pasta www

```bash
cd ~/www
ls
```

Procure pela pasta `administrativo` ou similar.

### 2. Se não encontrar, procurar em todo o diretório home

```bash
find ~ -maxdepth 3 -type d -name "administrativo" 2>/dev/null
```

### 3. Procurar o arquivo do script diretamente

```bash
find ~ -type f -name "update_training_statuses.php" 2>/dev/null
```

### 4. Verificar se está em public_html

```bash
cd ~/public_html 2>/dev/null && ls || echo "Pasta public_html não existe"
```

### 5. Verificar estrutura comum do Kinghost

No Kinghost, o projeto geralmente está em:
- `~/www/administrativo`
- `~/public_html/administrativo`
- `~/www/tiaraju.com.br/administrativo`
- `~/public_html/tiaraju.com.br/administrativo`

## Depois de Encontrar

Quando encontrar o caminho, execute:

```bash
cd /caminho/encontrado/administrativo
php scripts/update_training_statuses.php
```

Ou com caminho absoluto:

```bash
php /caminho/completo/para/administrativo/scripts/update_training_statuses.php
```

