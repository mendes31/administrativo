# 🔧 TROUBLESHOOTING - Apply Training em Produção

## 🐛 PROBLEMA REPORTADO

Sistema **funciona localmente** mas **NÃO salva em produção** após deploy.

---

## 📊 LOGS DE DEBUG ADICIONADOS

Foram adicionados logs detalhados em **3 pontos críticos**:

### 1️⃣ **Início do Método `apply()`**
```
=== APPLY TRAINING CHAMADO ===
POST recebido: Array(...)
SESSION user_id: 1
Training ID: 8, User ID: 6
```

### 2️⃣ **Preparação e Salvamento**
```
=== PREPARANDO DADOS PARA SALVAR ===
Dados a salvar: Array(...)
=== ATUALIZANDO VÍNCULO PRINCIPAL ===
Resultado vínculo: SUCESSO
=== MODO INSERÇÃO ===
Novo ID retornado: 94
```

### 3️⃣ **Sucesso ou Erro**
```
=== SUCESSO - Salvando mensagem e redirecionando ===
Mensagem: Treinamento registrado como realizado!
Redirecionando para: http://...
```

**OU** em caso de erro:
```
=== ERRO CAPTURADO ===
Mensagem de erro: ...
Stack trace: ...
Arquivo: ... - Linha: ...
```

---

## 🔍 COMO VERIFICAR EM PRODUÇÃO

### **PASSO 1: Localizar os Logs de Erro do PHP**

Em produção, os logs do PHP podem estar em diferentes locais dependendo da configuração do servidor:

#### **Apache (Linux/cPanel)**
```bash
# Log padrão do Apache
tail -f /var/log/apache2/error.log

# Log PHP (pode variar)
tail -f /var/log/php_errors.log

# cPanel
tail -f ~/public_html/error_log
```

#### **Apache (Windows/WAMP)**
```
C:\wamp64\logs\php_error.log
C:\wamp64\logs\apache_error.log
```

#### **Verificar configuração PHP**
```php
<?php
echo ini_get('error_log');
echo ini_get('display_errors');
echo ini_get('log_errors');
```

---

### **PASSO 2: Reproduzir o Erro em Produção**

1. ✅ Acesse **apply-training** em produção
2. ✅ Preencha o formulário
3. ✅ Clique em **Salvar**
4. ✅ **IMEDIATAMENTE** após clicar, verifique os logs

---

### **PASSO 3: Analisar os Logs**

#### **Se aparecer `=== APPLY TRAINING CHAMADO ===`**
✅ O método está sendo executado
➡️ Continue verificando os próximos logs

#### **Se NÃO aparecer nada**
❌ O método `apply()` **não está sendo chamado**
➡️ **Problema de rota ou formulário**

**Possíveis causas:**
- Action do formulário está errado
- POST não está chegando ao servidor
- Redirecionamento incorreto

**Solução:**
Verificar se o `action` do formulário está correto:
```php
<form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>apply-training">
```

---

### **PASSO 4: Verificar Erros Comuns**

#### **ERRO: "Falha ao inserir aplicação"**
```
=== MODO INSERÇÃO ===
Novo ID retornado: FALHA
=== ERRO CAPTURADO ===
Mensagem de erro: Falha ao inserir aplicação
```

**Possíveis causas:**
1. Problema de conexão com o banco de dados
2. Campos obrigatórios faltando no banco
3. Foreign keys inválidas
4. Permissões de banco insuficientes

**Verificar:**
```sql
-- Verificar se a tabela existe
SHOW TABLES LIKE 'adms_training_applications';

-- Verificar estrutura da tabela
DESCRIBE adms_training_applications;

-- Tentar inserir manualmente
INSERT INTO adms_training_applications (
    adms_user_id, adms_training_id, data_realizacao, 
    instrutor_nome, instrutor_email, aplicado_por, 
    nota, status, created_at
) VALUES (
    6, 8, '2025-10-28', 
    'Teste', 'teste@teste.com', 1, 
    8.5, 'concluido', NOW()
);
```

---

#### **ERRO: Foreign Key Constraint**
```
SQLSTATE[23000]: Integrity constraint violation: 
1452 Cannot add or update a child row: a foreign key constraint fails
```

**Causa:**
- `adms_user_id` não existe na tabela `adms_users`
- `adms_training_id` não existe na tabela `adms_trainings`
- `instructor_user_id` não existe

**Solução:**
```sql
-- Verificar se os IDs existem
SELECT id, name FROM adms_users WHERE id = 6;
SELECT id, nome FROM adms_trainings WHERE id = 8;
```

---

#### **ERRO: Column not found**
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'xxx' in 'field list'
```

**Causa:**
- Estrutura da tabela em produção está **diferente** do local
- Migration não foi executada em produção

**Solução:**
```bash
# Executar migrations em produção
cd database
php ../vendor/bin/phinx status
php ../vendor/bin/phinx migrate
```

---

#### **ERRO: Sessão não persiste**
```
=== SUCESSO - Salvando mensagem e redirecionando ===
Mensagem: Treinamento registrado como realizado!
```
Mas a mensagem **não aparece** na tela seguinte.

**Causa:**
- Sessões não estão configuradas corretamente em produção
- `session_save_path` sem permissão de escrita

**Solução:**
```php
// Verificar configuração de sessão
<?php
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session save path: " . session_save_path() . "<br>";
echo "Session status: " . session_status() . "<br>";
$_SESSION['teste'] = 'funciona';
echo "Teste salvo: " . $_SESSION['teste'];
```

---

## 🔧 CHECKLIST DE DIAGNÓSTICO

### **Ambiente**
- [ ] Versão do PHP em produção: `php -v`
- [ ] Extensões PHP instaladas: `php -m | grep pdo`
- [ ] Logs de erro habilitados: `ini_get('log_errors')`
- [ ] Permissões de escrita em `app/logs/`

### **Banco de Dados**
- [ ] Tabela `adms_training_applications` existe
- [ ] Todas as colunas necessárias existem
- [ ] Foreign keys estão corretas
- [ ] Usuário do banco tem permissão de INSERT

### **Código**
- [ ] Arquivo `ApplyTraining.php` foi atualizado em produção
- [ ] Arquivo `applyTraining.php` (View) foi atualizado
- [ ] Sem cache de PHP (opcache) ou browser
- [ ] `.env` configurado corretamente

### **Logs**
- [ ] Logs do PHP aparecem após submit
- [ ] Mensagem `=== APPLY TRAINING CHAMADO ===` aparece
- [ ] Mensagem `=== MODO INSERÇÃO ===` aparece
- [ ] Novo ID retornado é um número válido

---

## 🚀 SOLUÇÃO RÁPIDA - FORÇAR EXIBIÇÃO DE ERROS

Se os logs não estiverem aparecendo, adicione **temporariamente** no início do arquivo `ApplyTraining.php`:

```php
<?php
// APENAS PARA DEBUG - REMOVER APÓS RESOLVER
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../logs/apply_training_debug.log');

namespace App\adms\Controllers\trainings;
```

Isso forçará a exibição de erros **na tela** e salvará em um log específico.

---

## 📋 DIFERENÇAS COMUNS LOCAL vs PRODUÇÃO

| Item | Local | Produção (Comum) |
|------|-------|------------------|
| **PHP** | 8.2+ | 7.4 - 8.1 |
| **MySQL** | 8.0 | 5.7 |
| **Timezone** | America/Sao_Paulo | UTC |
| **Display Errors** | ON | OFF |
| **Error Reporting** | E_ALL | E_ERROR |
| **Session Path** | /tmp | /var/lib/php/sessions |
| **Max Execution** | 300s | 30s |
| **Memory Limit** | 512M | 128M |

---

## 🎯 PRÓXIMOS PASSOS

1. **Fazer deploy da versão atualizada** com os logs
2. **Tentar salvar** uma aplicação em produção
3. **Verificar os logs** (error.log do PHP)
4. **Enviar os logs** para análise
5. **Identificar o erro específico**
6. **Aplicar correção**

---

## 📞 INFORMAÇÕES NECESSÁRIAS

Para eu ajudar melhor, você pode me enviar:

1. **Logs do PHP** após tentar salvar
2. **Mensagem de erro** que aparece na tela (se houver)
3. **Versão do PHP** em produção: `php -v`
4. **Estrutura da tabela** em produção:
   ```sql
   DESCRIBE adms_training_applications;
   ```
5. **Último registro** inserido com sucesso (se houver):
   ```sql
   SELECT * FROM adms_training_applications ORDER BY id DESC LIMIT 1;
   ```

---

**Com essas informações, posso identificar e corrigir o problema específico da produção!** 🎯

