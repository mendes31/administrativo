# 🚀 COMO EXECUTAR O SCRIPT DE DIAGNÓSTICO

## 📍 LOCALIZAÇÃO DO ARQUIVO

```
administrativo/
└── scripts/
    └── diagnostico_producao.php  ← Este arquivo
```

---

## 💻 **EXECUTAR LOCALMENTE (WAMP)**

### **PASSO 1: Verificar se o WAMP está rodando**
- Olhe o ícone do WAMP na bandeja do sistema
- Deve estar **VERDE** ✅
- Se estiver laranja/vermelho, clique e escolha "Start All Services"

### **PASSO 2: Abrir no navegador**

Cole uma destas URLs no seu navegador:

#### **Opção A (padrão):**
```
http://localhost/administrativo/scripts/diagnostico_producao.php
```

#### **Opção B (se usa porta 8080):**
```
http://localhost:8080/administrativo/scripts/diagnostico_producao.php
```

#### **Opção C (se configurou virtual host):**
```
http://administrativo.local/scripts/diagnostico_producao.php
```

### **PASSO 3: Visualizar o resultado**

Você verá uma página com várias seções mostrando:
- ✅ Versão do PHP
- ✅ Extensões instaladas
- ✅ Conexão com banco de dados
- ✅ Estrutura da tabela
- ✅ Último registro inserido
- ✅ Configuração de sessões
- ✅ Permissões de diretórios

---

## 🌐 **EXECUTAR EM PRODUÇÃO**

### **PASSO 1: Fazer Deploy do Arquivo**

#### **Via FTP/FileZilla:**
1. Conecte-se ao servidor via FTP
2. Navegue até a pasta `administrativo/scripts/`
3. Faça upload do arquivo `diagnostico_producao.php`

#### **Via Painel de Controle (cPanel/Plesk):**
1. Acesse o gerenciador de arquivos
2. Navegue até `administrativo/scripts/`
3. Faça upload do arquivo

#### **Via Git (se usar):**
```bash
git add scripts/diagnostico_producao.php
git commit -m "Add: script de diagnóstico"
git push
```
Depois faça pull no servidor.

### **PASSO 2: Acessar no navegador**

```
https://seu-dominio.com/administrativo/scripts/diagnostico_producao.php
```

**Exemplos:**
```
https://tiaraju.com.br/administrativo/scripts/diagnostico_producao.php
https://sistema.tiaraju.com.br/administrativo/scripts/diagnostico_producao.php
https://www.administrativotiaraju.kinghost.net/administrativo/scripts/diagnostico_producao.php
```

### **PASSO 3: Analisar o resultado**

Procure por itens em **VERMELHO** (erro) ou **AMARELO** (aviso):

✅ **Se tudo estiver verde**: Configuração OK, problema pode ser no código
❌ **Se houver vermelho**: Anote os erros e corrija primeiro
⚠️ **Se houver amarelo**: Anote os avisos (podem impactar)

---

## 🔍 **O QUE PROCURAR**

### **Seção 2: Extensões PHP**
```
✓ pdo - Instalada
✓ pdo_mysql - Instalada
✓ mysqli - Instalada
```
Se alguma estiver **NÃO INSTALADA**: contate o suporte do hosting

### **Seção 3: Banco de Dados**
```
✓ Conexão com banco de dados: OK
✓ Tabela adms_training_applications: EXISTE
```
Se aparecer **NÃO EXISTE**: execute as migrations

### **Seção 4: Sessões**
```
Session Status: ✓ ATIVA
Save Path Writable: ✓ SIM
```
Se aparecer **NÃO**: problema de permissões

### **Seção 7: Teste de POST**
```
Formulário → Digite algo → Clique "Testar POST"
```
Deve mostrar os dados que você digitou
Se não mostrar: problema de configuração do servidor

---

## 🎯 **APÓS EXECUTAR**

### **1. Copie as Informações Importantes**

- Versão do PHP
- Extensões (se alguma faltar)
- Status da conexão com banco
- Estrutura da tabela
- Último registro inserido
- Qualquer erro em vermelho

### **2. Teste Salvar uma Aplicação**

1. Vá em `apply-training`
2. Preencha o formulário
3. Salve
4. Volte no script de diagnóstico
5. Atualize a página (F5)
6. Veja a seção "8. Logs de Erro Recentes"

### **3. Me Envie**

- Print da tela do diagnóstico
- Logs que aparecerem
- Mensagem de erro (se houver)

---

## ⚠️ **IMPORTANTE - REMOVER APÓS USO**

Este script mostra informações sensíveis do servidor!

**APÓS O DIAGNÓSTICO, DELETE O ARQUIVO:**

### **Via FTP:**
- Conecte no servidor
- Vá em `administrativo/scripts/`
- Delete `diagnostico_producao.php`

### **Via SSH:**
```bash
cd /caminho/para/administrativo
rm scripts/diagnostico_producao.php
```

### **Via Painel:**
- Gerenciador de arquivos
- Navegue até a pasta
- Selecione o arquivo
- Clique em "Excluir"

---

## 🆘 **PROBLEMAS COMUNS**

### **Erro 404 ao acessar o script**

**Causa:** Rewrite rules do `.htaccess` redirecionando

**Solução:** Adicione no `.htaccess`:
```apache
# Permitir acesso direto a scripts de diagnóstico
RewriteCond %{REQUEST_URI} !^/administrativo/scripts/diagnostico_producao\.php$
```

### **Tela em branco**

**Causa:** Erro fatal no PHP

**Solução:** Verifique o log de erros do servidor

### **Erro de permissão**

**Causa:** Arquivo sem permissão de execução

**Solução:**
```bash
chmod 644 scripts/diagnostico_producao.php
```

---

**Pronto! Agora é só acessar a URL no navegador!** 🎯

**Local:** `http://localhost/administrativo/scripts/diagnostico_producao.php`
**Produção:** `https://seu-dominio.com/administrativo/scripts/diagnostico_producao.php`

