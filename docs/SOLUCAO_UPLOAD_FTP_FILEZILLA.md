# Solução: Arquivos Não Sendo Enviados via FileZilla/FTP

## 🔍 Problema Identificado

Ao tentar fazer upload manual via FileZilla ou FTP, muitos arquivos e pastas não estão sendo enviados para o novo servidor.

---

## 🔴 Causas Comuns

### 1. **Timeout/Timeout de Conexão**

**Problema:** Uploads grandes ou muitos arquivos podem causar timeout.

**Sintomas:**
- Upload para no meio
- Conexão cai durante o upload
- Arquivos ficam incompletos

**Solução:**
- Aumentar timeout no FileZilla: `Editar → Configurações → Conexão → Timeout de transferência` (aumentar para 300 segundos)
- Usar modo passivo: `Editar → Configurações → Conexão → Modo passivo`

---

### 2. **Limite de Arquivos Simultâneos**

**Problema:** FileZilla pode ter limite de transferências simultâneas.

**Solução:**
- Reduzir transferências simultâneas: `Editar → Configurações → Transferências → Máximo de transferências simultâneas` (reduzir para 1 ou 2)

---

### 3. **Arquivos Ocultos Não Sendo Enviados**

**Problema:** Arquivos que começam com ponto (`.env`, `.htaccess`) podem não aparecer ou não serem enviados.

**Solução:**
- No FileZilla: `Servidor → Forçar exibição de arquivos ocultos`
- Ou enviar manualmente arrastando os arquivos

---

### 4. **Permissões de Diretórios**

**Problema:** Diretórios podem não ter permissão de escrita.

**Solução:**
- Verificar permissões no servidor: `chmod 755` para diretórios
- Verificar se o usuário FTP tem permissão de escrita

---

### 5. **Arquivos Grandes ou Muitos Arquivos**

**Problema:** Upload de muitos arquivos pode falhar silenciosamente.

**Solução:**
- Enviar em lotes menores
- Usar compressão (ZIP) e descompactar no servidor
- Usar Git (já configurado) em vez de FTP manual

---

## ✅ Soluções

### Solução 1: Configurar FileZilla Corretamente

**Configurações Recomendadas:**

1. **Timeout:**
   - `Editar → Configurações → Conexão`
   - `Timeout de conexão`: 60 segundos
   - `Timeout de transferência`: 300 segundos

2. **Transferências:**
   - `Editar → Configurações → Transferências`
   - `Máximo de transferências simultâneas`: 1 ou 2
   - `Tentar novamente em caso de falha`: 3 tentativas

3. **Modo Passivo:**
   - `Editar → Configurações → Conexão → Modo passivo`: ✅ Ativado

4. **Mostrar Arquivos Ocultos:**
   - `Servidor → Forçar exibição de arquivos ocultos`: ✅ Ativado

---

### Solução 2: Enviar em Lotes (Recomendado)

**Dividir o upload em partes menores:**

1. **Primeiro: Estrutura de Diretórios**
   - Criar todas as pastas primeiro
   - `app/`, `app/adms/`, `app/adms/Helpers/`, etc.

2. **Segundo: Arquivos Críticos**
   - `index.php`
   - `.htaccess`
   - `composer.json`
   - `composer.lock`

3. **Terceiro: Helpers**
   - `app/adms/Helpers/*.php`

4. **Quarto: Controllers**
   - `app/adms/Controllers/` (pasta por pasta)

5. **Quinto: Views**
   - `app/adms/Views/` (pasta por pasta)

6. **Sexto: Models**
   - `app/adms/Models/`

7. **Sétimo: Routes**
   - `routes/`

8. **Oitavo: Public**
   - `public/`

---

### Solução 3: Usar ZIP (Mais Rápido e Confiável)

**Comprimir localmente e descompactar no servidor:**

**No seu computador:**
1. Criar ZIP do projeto (excluindo `vendor/`, `.env`, `node_modules/`)
2. Fazer upload do ZIP via FileZilla

**No servidor (Putty):**
```bash
cd /home/tiaraju/www/administrativo

# Descompactar (ajustar nome do arquivo)
unzip projeto.zip

# Ou se for .tar.gz
tar -xzf projeto.tar.gz

# Remover arquivo ZIP após descompactar
rm projeto.zip
```

**⚠️ IMPORTANTE:** Excluir do ZIP:
- `vendor/` (será instalado via Composer)
- `.env` (criar manualmente no servidor)
- `node_modules/`
- `.git/`

---

### Solução 4: Usar Git (Recomendado - Já Configurado)

**O projeto já tem deploy automático via GitHub Actions!**

**Vantagens:**
- ✅ Automático
- ✅ Confiável
- ✅ Versionado
- ✅ Não perde arquivos

**Como usar:**
```bash
# Local (no seu computador)
cd C:\wamp64\www\administrativo

# Adicionar todos os arquivos
git add .

# Verificar o que será enviado
git status

# Fazer commit
git commit -m "feat: Migração para novo servidor - todos os arquivos"

# Enviar para Git
git push origin main

# Aguardar deploy automático (GitHub Actions)
# Verificar em: https://github.com/seu-usuario/seu-repo/actions
```

**O GitHub Actions vai:**
- ✅ Enviar todos os arquivos via FTP automaticamente
- ✅ Tentar múltiplas vezes se falhar
- ✅ Mostrar logs detalhados

---

### Solução 5: Verificar o Que Foi Enviado

**No FileZilla:**
- Verificar a coluna "Status" de cada arquivo
- Procurar por erros na aba "Registro de transferências"
- Verificar se há arquivos com status "Falhou"

**No servidor (Putty):**
```bash
# Verificar estrutura de diretórios
find app/ -type d | sort

# Verificar arquivos Helpers
ls -la app/adms/Helpers/

# Comparar quantidade de arquivos
find app/ -type f | wc -l
```

---

## 🔧 Configuração FileZilla Detalhada

### Passo a Passo:

1. **Abrir Configurações:**
   - `Editar → Configurações`

2. **Conexão:**
   ```
   Timeout de conexão: 60
   Timeout de transferência: 300
   Modo passivo: ✅ Ativado
   ```

3. **Transferências:**
   ```
   Máximo de transferências simultâneas: 1
   Tentar novamente em caso de falha: 3
   Intervalo entre tentativas: 5 segundos
   ```

4. **Filtros:**
   - `Ver → Filtros de diretório`
   - Desmarcar "Ocultar arquivos de sistema"
   - Desmarcar "Ocultar arquivos ocultos"

5. **Comparação de Diretórios:**
   - `Ver → Comparação de diretórios`
   - Ativar para ver o que falta

---

## 📋 Checklist: Verificar Upload Completo

### Estrutura de Diretórios (deve existir):

```bash
# No servidor, verificar:
ls -la app/adms/Helpers/          # Deve ter SlugController.php
ls -la app/adms/Controllers/      # Deve ter todas as pastas
ls -la app/adms/Views/           # Deve ter todas as pastas
ls -la app/adms/Models/           # Deve ter todas as pastas
ls -la routes/                    # Deve ter PageController.php
ls -la public/                    # Deve ter estrutura completa
```

### Arquivos Críticos (devem existir):

```bash
# Verificar arquivos essenciais
ls -la index.php
ls -la .htaccess
ls -la composer.json
ls -la .env
ls -la app/adms/Helpers/SlugController.php
ls -la routes/PageController.php
```

---

## 🚀 Solução Rápida: Script de Verificação

**Criar script para verificar o que falta:**

```bash
# No servidor (Putty)
cat > /tmp/verificar_arquivos.sh << 'EOF'
#!/bin/bash
echo "=== VERIFICANDO ARQUIVOS CRÍTICOS ==="

# Arquivos que devem existir
ARQUIVOS=(
    "index.php"
    ".htaccess"
    "composer.json"
    ".env"
    "app/adms/Helpers/SlugController.php"
    "app/adms/Helpers/ClearUrl.php"
    "app/adms/Helpers/GenerateLog.php"
    "routes/PageController.php"
)

for arquivo in "${ARQUIVOS[@]}"; do
    if [ -f "$arquivo" ]; then
        echo "✅ $arquivo"
    else
        echo "❌ $arquivo (FALTANDO!)"
    fi
done

echo ""
echo "=== VERIFICANDO ESTRUTURA DE DIRETÓRIOS ==="
DIRETORIOS=(
    "app/adms/Helpers"
    "app/adms/Controllers"
    "app/adms/Views"
    "app/adms/Models"
    "routes"
    "public"
)

for dir in "${DIRETORIOS[@]}"; do
    if [ -d "$dir" ]; then
        count=$(find "$dir" -type f | wc -l)
        echo "✅ $dir ($count arquivos)"
    else
        echo "❌ $dir (FALTANDO!)"
    fi
done
EOF

chmod +x /tmp/verificar_arquivos.sh
cd /home/tiaraju/www/administrativo
/tmp/verificar_arquivos.sh
```

---

## 💡 Recomendação Final

**Para migração completa, use uma destas opções:**

### Opção 1: Git (Melhor - Já Configurado)
```bash
# Local
git add .
git commit -m "feat: Migração completa para novo servidor"
git push origin main
# Aguardar deploy automático
```

### Opção 2: ZIP + Descompactar
- Comprimir projeto (excluindo vendor, .env)
- Upload do ZIP via FileZilla
- Descompactar no servidor via Putty

### Opção 3: FileZilla em Lotes
- Configurar FileZilla corretamente
- Enviar em lotes pequenos
- Verificar cada lote

---

## ⚠️ IMPORTANTE

**Arquivos que NÃO devem ser enviados:**
- `vendor/` (instalar via Composer)
- `.env` (criar manualmente no servidor)
- `node_modules/`
- `.git/`
- `*.log`

**Arquivos que DEVEM ser enviados:**
- Todos os `.php`
- Todos os diretórios `app/`, `routes/`, `public/`
- `composer.json`, `composer.lock`
- `.htaccess`
- `index.php`

---

## 🔍 Verificar Logs do FileZilla

**No FileZilla:**
- Aba "Registro de transferências"
- Procurar por erros (vermelho)
- Verificar mensagens de timeout ou falha

**Erros comuns:**
- `550 Permission denied` → Problema de permissões
- `421 Timeout` → Timeout de conexão
- `426 Connection closed` → Conexão interrompida

---

## 📊 Resumo

**Problema:** Arquivos não sendo enviados via FileZilla/FTP.

**Causas:**
- Timeout
- Muitos arquivos simultâneos
- Arquivos ocultos
- Limitações do FTP

**Soluções:**
1. ✅ Configurar FileZilla corretamente
2. ✅ Enviar em lotes menores
3. ✅ Usar ZIP + descompactar
4. ✅ **Usar Git (recomendado - já configurado)**

**Recomendação:** Use o Git para garantir que todos os arquivos sejam enviados automaticamente e versionados.


