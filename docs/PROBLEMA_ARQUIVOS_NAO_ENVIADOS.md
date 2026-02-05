# Problema: Arquivos Copiados do FTP Antigo Não Vão para o Novo Servidor

## 🔍 Problema Identificado

Ao copiar arquivos do FTP antigo para o novo servidor, alguns arquivos não estão sendo enviados ou não aparecem no servidor novo.

**Exemplo:** O arquivo `app/adms/Helpers/SlugController.php` não existe no servidor novo, mesmo que exista localmente.

---

## 🔴 Causas Possíveis

### 1. **Deploy Incremental (Principal Causa)**

O GitHub Actions está configurado com **deploy incremental** (`dangerous-clean-slate: false` e `--only-newer`).

**O que isso significa:**
- ✅ Arquivos **novos** → Envia
- 🔄 Arquivos **modificados** → Atualiza
- ⏭️ Arquivos **idênticos** → **PULA** (não envia)

**Problema:** Se o arquivo já existe no servidor (mesmo que incompleto ou com timestamp diferente), o deploy pode não enviá-lo.

---

### 2. **Arquivos Não Commitados no Git**

Se você copiou arquivos manualmente do FTP antigo mas **não fez commit no Git**, eles não serão enviados pelo deploy automático.

**Verificar:**
```bash
# Local (no seu computador)
git status

# Ver se SlugController.php está no Git
git ls-files app/adms/Helpers/SlugController.php
```

---

### 3. **Arquivos no .gitignore**

Arquivos que estão no `.gitignore` **não são enviados** pelo deploy.

**Verificar:**
```bash
# Ver se o arquivo está ignorado
git check-ignore -v app/adms/Helpers/SlugController.php
```

---

### 4. **Problemas de Permissões no FTP**

Arquivos podem não ser enviados se houver problemas de permissões ou timeout durante o upload.

---

## ✅ Soluções

### Solução 1: Verificar se Arquivo Está no Git

```bash
# Local (no seu computador)
cd C:\wamp64\www\administrativo

# Verificar se o arquivo está no Git
git ls-files app/adms/Helpers/SlugController.php

# Se não aparecer, adicionar ao Git
git add app/adms/Helpers/SlugController.php
git commit -m "fix: Adiciona SlugController.php que estava faltando"
git push origin main
```

---

### Solução 2: Forçar Upload Manual via FTP

Se o arquivo não está no Git ou precisa ser enviado imediatamente:

**Opção A: Via cliente FTP (FileZilla, WinSCP, etc.)**
1. Conecte ao servidor novo
2. Navegue até `/home/tiaraju/www/administrativo/app/adms/Helpers/`
3. Faça upload do arquivo `SlugController.php`

**Opção B: Via Putty (criar arquivo diretamente)**
```bash
# No Putty, criar o arquivo
nano app/adms/Helpers/SlugController.php
# Cole o conteúdo e salve (Ctrl+O, Enter, Ctrl+X)
```

---

### Solução 3: Forçar Deploy Completo (Temporário)

**⚠️ CUIDADO:** Isso vai sobrescrever tudo no servidor!

Para forçar envio de todos os arquivos, você pode temporariamente alterar o `.github/workflows/deploy.yml`:

```yaml
dangerous-clean-slate: true  # MUDAR DE false PARA true
```

**Mas isso é perigoso!** Pode apagar arquivos que você quer manter no servidor.

---

### Solução 4: Adicionar Arquivo Específico ao Deploy

Criar um step específico no deploy para forçar upload de arquivos críticos:

```yaml
- name: Forçar upload - Helpers críticos
  uses: SamKirkland/FTP-Deploy-Action@v4.3.4
  with:
    server: ${{ secrets.FTP_SERVER }}
    username: ${{ secrets.FTP_USER }}
    password: ${{ secrets.FTP_PASS }}
    local-dir: app/adms/Helpers/
    server-dir: ./administrativo/app/adms/Helpers/
    dangerous-clean-slate: true
    log-level: verbose
```

---

## 🔍 Diagnóstico: Verificar o Que Está Faltando

### No Servidor (Putty):

```bash
# Verificar quais arquivos Helpers existem
ls -la app/adms/Helpers/

# Comparar com o que deveria existir (lista esperada)
# SlugController.php
# ClearUrl.php
# GenerateLog.php
# SendWhatsAppService.php
# etc.
```

### Local (Git):

```bash
# Ver todos os arquivos Helpers no Git
git ls-files app/adms/Helpers/

# Ver diferença entre local e Git
git status app/adms/Helpers/
```

---

## 📋 Checklist: Por Que Arquivo Não Foi Enviado

- [ ] Arquivo está no Git? (`git ls-files app/adms/Helpers/SlugController.php`)
- [ ] Arquivo está no `.gitignore`? (`git check-ignore -v app/adms/Helpers/SlugController.php`)
- [ ] Arquivo foi commitado? (`git log --oneline -- app/adms/Helpers/SlugController.php`)
- [ ] Deploy foi executado após commit? (Verificar GitHub Actions)
- [ ] Arquivo existe no servidor mas está vazio/corrompido? (`ls -la app/adms/Helpers/SlugController.php`)

---

## 🚀 Solução Rápida: Criar Arquivo no Servidor

Se você precisa que funcione **agora**, crie o arquivo diretamente no servidor:

```bash
# No Putty
cd /home/tiaraju/www/administrativo

# Criar diretório se não existir
mkdir -p app/adms/Helpers/

# Criar arquivo SlugController.php
cat > app/adms/Helpers/SlugController.php << 'EOF'
<?php

namespace App\adms\Helpers;

/**
 * Converter a controller enviada na URL para o formato da classe.
 */
class SlugController
{
    public static function slugController(string $slugController) : string
    {
        $slugController = strtolower($slugController);
        $slugController = str_replace("-"," ", $slugController);
        $slugController = ucwords($slugController);
        $slugController = str_replace(" ", "",  $slugController);
        return $slugController;
    }
}
EOF

# Ajustar permissões
chmod 644 app/adms/Helpers/SlugController.php
```

---

## 💡 Por Que Isso Acontece?

### Deploy Incremental vs Deploy Completo

**Deploy Incremental (atual):**
- ✅ Mais rápido
- ✅ Economiza banda
- ❌ Pode pular arquivos que "parecem" idênticos
- ❌ Pode não detectar arquivos faltando

**Deploy Completo:**
- ✅ Garante que tudo seja enviado
- ❌ Mais lento
- ❌ Pode sobrescrever arquivos do servidor

---

## 🎯 Recomendação

### Para Migração Inicial (Novo Servidor):

1. **Fazer deploy completo uma vez:**
   - Alterar temporariamente `dangerous-clean-slate: true`
   - Fazer push
   - Aguardar deploy
   - Reverter para `false`

2. **Ou fazer upload manual via FTP:**
   - Conectar via cliente FTP
   - Fazer upload de toda a pasta `app/`
   - Garantir que todos os arquivos foram enviados

3. **Depois, voltar ao deploy incremental:**
   - Mais eficiente para atualizações futuras

---

## 📝 Verificar Outros Arquivos Faltando

Execute no servidor para verificar o que mais pode estar faltando:

```bash
# Verificar estrutura de diretórios Helpers
find app/adms/Helpers/ -type f -name "*.php" | sort

# Comparar com lista esperada (você pode criar uma lista local)
# SlugController.php
# ClearUrl.php
# GenerateLog.php
# SendWhatsAppService.php
# FormatHelper.php
# etc.
```

---

## 🔧 Solução Definitiva: Garantir que Todos os Arquivos Estejam no Git

```bash
# Local (no seu computador)
cd C:\wamp64\www\administrativo

# Verificar status
git status

# Adicionar todos os arquivos Helpers
git add app/adms/Helpers/

# Verificar o que será commitado
git status

# Fazer commit
git commit -m "fix: Adiciona todos os arquivos Helpers que estavam faltando"

# Enviar para o Git
git push origin main

# Aguardar deploy automático
# Verificar no GitHub Actions se foi enviado
```

---

## ⚠️ IMPORTANTE

**Se você copiou arquivos do FTP antigo manualmente:**

1. **Verifique se estão no Git** (`git status`)
2. **Se não estiverem, adicione ao Git** (`git add`)
3. **Faça commit e push** (`git commit` e `git push`)
4. **Aguarde o deploy automático**

**Ou crie os arquivos diretamente no servidor novo** (solução rápida, mas não ideal).

---

## 📊 Resumo

**Problema:** Deploy incremental pode não enviar arquivos que já existem ou que não estão no Git.

**Soluções:**
1. ✅ Adicionar arquivos ao Git e fazer push
2. ✅ Criar arquivos diretamente no servidor (solução rápida)
3. ✅ Fazer upload manual via FTP
4. ✅ Forçar deploy completo (temporário)

**Recomendação:** Use a Solução 1 (Git) para garantir que todos os arquivos sejam versionados e enviados automaticamente.


