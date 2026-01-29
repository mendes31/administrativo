# Problema: index.php retorna 404 mesmo acessando diretamente

## Situação

- ✅ `index.php` existe no diretório
- ❌ Acesso direto retorna 404
- ❌ Isso indica problema de configuração do Apache, não do `.htaccess`

## Possíveis Causas

### 1. Arquivo não está no diretório correto do servidor web

**Verificar:**
```bash
# Verificar o caminho completo
pwd

# Verificar se está no diretório web correto
ls -la /home/administrativotiaraju/www/administrativo/index.php
```

### 2. Apache não está configurado para servir este diretório

**Verificar:**
- O VirtualHost pode não estar apontando para o diretório correto
- O DocumentRoot pode estar errado

### 3. Permissões incorretas

**Verificar:**
```bash
# Verificar permissões do diretório
ls -ld /home/administrativotiaraju/www/administrativo

# Verificar permissões do index.php
ls -la index.php
```

### 4. Caminho `/administrativo/` não corresponde ao diretório físico

**Verificar:**
- O Apache pode estar servindo de outro diretório
- O alias `/administrativo/` pode não estar configurado

---

## Testes de Diagnóstico

### Teste 1: Verificar caminho completo

```bash
# Verificar onde você está
pwd

# Verificar caminho absoluto do index.php
readlink -f index.php
```

### Teste 2: Verificar se há outro index.php

```bash
# Procurar por index.php no sistema
find ~ -name "index.php" -type f 2>/dev/null
```

### Teste 3: Verificar configuração do Apache (se tiver acesso)

```bash
# Verificar VirtualHost (pode não ter acesso)
cat /etc/apache2/sites-enabled/*.conf | grep -i "administrativo\|documentroot"
```

### Teste 4: Criar arquivo de teste

```bash
# Criar arquivo de teste simples
echo "<?php phpinfo(); ?>" > test.php

# Testar acesso
curl -I "https://administrativotiaraju.kinghost.net/administrativo/test.php"
```

**Se `test.php` funcionar:**
- PHP está funcionando
- O problema é específico do `index.php` ou sua localização

**Se `test.php` não funcionar:**
- Problema de configuração do Apache/VirtualHost

---

## Soluções Possíveis

### Solução 1: Verificar se está no diretório correto

O servidor web pode estar servindo de outro diretório. Verifique com o suporte da KingHost qual é o diretório web correto.

### Solução 2: Verificar configuração do VirtualHost

Se tiver acesso, verifique se o VirtualHost está apontando para o diretório correto.

### Solução 3: Contatar suporte da KingHost

Informe:
- "O arquivo `index.php` existe mas retorna 404 mesmo acessando diretamente"
- "Preciso verificar se o VirtualHost está configurado corretamente para `/administrativo/`"
- "O caminho físico do arquivo é: `/home/administrativotiaraju/www/administrativo/index.php`"

---

## Próximos Passos

1. **Execute o Teste 1** (verificar caminho completo)
2. **Execute o Teste 4** (criar arquivo de teste)
3. **Com base nos resultados**, decidir se precisa contatar suporte

---

## Comandos para Executar Agora

```bash
# 1. Verificar onde você está
pwd

# 2. Verificar caminho absoluto
readlink -f index.php

# 3. Criar arquivo de teste
echo "<?php echo 'PHP funciona!'; ?>" > test.php

# 4. Testar arquivo de teste
curl "https://administrativotiaraju.kinghost.net/administrativo/test.php"
```

