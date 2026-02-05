# 🔍 Diagnóstico: Por que o Deploy Parou de Funcionar no Novo FTP?

## 📊 **Situação**

O workflow `deploy.yml` **funcionava perfeitamente** no servidor FTP antigo, mas **parou de funcionar** no novo servidor FTP da Kinghost.

---

## 🔴 **Possíveis Causas**

### **1. Proteções Anti-DDoS Mais Rígidas**

O novo servidor FTP pode ter:
- ✅ **Firewall mais restritivo** (bloqueia muitas conexões rápidas)
- ✅ **Limite de conexões simultâneas menor**
- ✅ **Timeout mais curto** (fecha conexões inativas rapidamente)
- ✅ **Rate limiting** (bloqueia após X tentativas em Y segundos)

**Sintoma:** `Server sent FIN packet unexpectedly, closing connection`

---

### **2. Configurações de Rede Diferentes**

- ✅ **Modo passivo/ativo** diferente
- ✅ **Portas FTP bloqueadas**
- ✅ **IP do GitHub Actions bloqueado** (whitelist)

---

### **3. Credenciais/Secrets Incorretos**

- ✅ **FTP_SERVER_PROD** apontando para servidor errado
- ✅ **FTP_USER_PROD** ou **FTP_PASS_PROD** incorretos
- ✅ **Permissões FTP diferentes** (usuário sem permissão de escrita)

---

### **4. Estrutura de Diretórios Diferente**

- ✅ **Caminho base diferente** (`/files/` vs `/www/`)
- ✅ **Permissões de diretório** diferentes
- ✅ **Pasta `administrativo` não existe** no novo servidor

---

## ✅ **Soluções Aplicadas**

### **1. Correção de Sintaxe**
- ❌ Removido `exclude:` duplicado (linha 37-38)
- ❌ Removido `dry-run: false` desnecessário

### **2. Simplificação do Workflow**
- ✅ Removidos delays entre tentativas (podem estar causando timeout)
- ✅ Timeout padronizado para 5 minutos (300000ms) em todas as tentativas
- ✅ Configurações lftp voltadas para valores padrão que funcionavam

### **3. Fallback Robusto**
- ✅ lftp configurado para executar mesmo se todas as tentativas falharem
- ✅ Timeout e retries ajustados para conexões instáveis

---

## 🔧 **Próximos Passos para Diagnóstico**

### **1. Verificar Secrets no GitHub**

No GitHub → **Settings → Secrets and variables → Actions**, verifique:

```bash
FTP_SERVER_PROD = ftp.novohost.kinghost.com.br  # ou IP
FTP_USER_PROD = seu_usuario
FTP_PASS_PROD = sua_senha
```

**⚠️ IMPORTANTE:** 
- O `FTP_SERVER_PROD` deve ser apenas o **hostname ou IP**, **SEM** `ftp://` ou `http://`
- Exemplo correto: `ftp.kinghost.com.br` ou `200.123.45.67`
- Exemplo errado: `ftp://ftp.kinghost.com.br`

---

### **2. Testar Conexão FTP Manualmente**

**No seu computador (PowerShell/CMD):**

```bash
# Testar conexão básica
ftp ftp.novohost.kinghost.com.br

# Ou usar FileZilla para testar:
# - Host: ftp.novohost.kinghost.com.br
# - Usuário: seu_usuario
# - Senha: sua_senha
# - Porta: 21
```

**Se conectar manualmente mas falhar no GitHub Actions:**
- Problema pode ser **IP do GitHub bloqueado** (precisa whitelist)

---

### **3. Verificar Logs do GitHub Actions**

No GitHub Actions, expanda o step que falhou e procure por:

```
✅ "Connecting to ftp..." → Conexão OK
❌ "Connection refused" → Firewall bloqueando
❌ "Authentication failed" → Credenciais erradas
❌ "FIN packet" → Servidor fechando conexão (proteção anti-DDoS)
```

---

### **4. Contatar Suporte Kinghost**

Se o problema persistir, pergunte ao suporte:

1. **"O IP do GitHub Actions está bloqueado?"**
   - GitHub Actions usa IPs dinâmicos
   - Pode precisar desabilitar proteção anti-DDoS temporariamente

2. **"Há limite de conexões FTP simultâneas?"**
   - Alguns servidores limitam a 1-2 conexões por usuário

3. **"O modo passivo FTP está habilitado?"**
   - GitHub Actions precisa de modo passivo

4. **"Há rate limiting no FTP?"**
   - Muitas tentativas rápidas podem ser bloqueadas

---

## 🚀 **Solução Alternativa: Usar Apenas lftp**

Se o `FTP-Deploy-Action` continuar falhando, podemos **remover todas as tentativas** e usar **apenas lftp**:

```yaml
# Remover steps deploy1 até deploy5
# Manter apenas o step "Deploy via lftp (Fallback Incremental)"
# Mas mudar a condição de:
#   if: always() && (steps.deploy5.outcome == 'failure' || steps.deploy5.outcome == 'skipped')
# Para:
#   if: always()
```

**Vantagens do lftp:**
- ✅ Mais robusto com conexões instáveis
- ✅ Reconexão automática
- ✅ Menos bloqueado por firewalls

**Desvantagens:**
- ⚠️ Logs menos detalhados
- ⚠️ Pode ser mais lento

---

## 📋 **Checklist de Verificação**

- [ ] Secrets do GitHub estão corretos?
- [ ] FTP_SERVER_PROD não tem `ftp://` ou `http://`?
- [ ] Conexão FTP funciona manualmente (FileZilla)?
- [ ] Pasta `administrativo` existe no novo servidor?
- [ ] Usuário FTP tem permissão de escrita?
- [ ] Suporte Kinghost foi contatado sobre bloqueios?

---

## 💡 **Recomendação Final**

1. **Teste o deploy novamente** com as correções aplicadas
2. **Se ainda falhar**, verifique os logs detalhados do GitHub Actions
3. **Se o erro for "FIN packet"**, o problema é proteção anti-DDoS do servidor
4. **Solução:** Contatar suporte Kinghost ou usar apenas lftp

---

## 🔗 **Arquivos Relacionados**

- `.github/workflows/deploy.yml` - Workflow de deploy
- `docs/SOLUCAO_UPLOAD_FTP_FILEZILLA.md` - Soluções para upload manual

