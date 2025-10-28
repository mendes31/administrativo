# 📊 COMO VERIFICAR OS LOGS EM PRODUÇÃO

## 🎯 **OBJETIVO**

Identificar **exatamente** qual validação está bloqueando o salvamento em produção.

---

## 📁 **ONDE ESTÃO OS LOGS**

### **Log Principal (apply_training_debug.log):**
```
/home/administrativotiaraju/www/administrativo/app/logs/apply_training_debug.log
```

### **Log do PHP (php_errors.log) - Se .user.ini foi configurado:**
```
/home/administrativotiaraju/www/administrativo/logs/php_errors.log
```

### **Log do Repository (debug_training_applications.log):**
```
/home/administrativotiaraju/www/administrativo/app/logs/debug_training_applications.log
```

---

## 🔍 **COMO ACESSAR OS LOGS**

### **OPÇÃO 1: Via Script de Diagnóstico (MAIS FÁCIL)**

1. Acesse:
```
http://www.administrativotiaraju.kinghost.net/administrativo/scripts/diagnostico_producao.php
```

2. Role até a seção **"8. Logs de Erro Recentes"**

3. Você verá as últimas 50 linhas de cada log

---

### **OPÇÃO 2: Via FTP/FileZilla**

1. Conecte no servidor FTP

2. Navegue até:
```
/www/administrativo/app/logs/
```

3. Baixe os arquivos:
   - `apply_training_debug.log`
   - `debug_training_applications.log`

4. Abra no Notepad++ ou VSCode

---

### **OPÇÃO 3: Via Painel KingHost**

1. Acesse o painel da KingHost

2. Vá em **"Gerenciador de Arquivos"**

3. Navegue até:
```
www/administrativo/app/logs/
```

4. Clique com botão direito no arquivo

5. Escolha **"Editar"** ou **"Visualizar"**

---

## 📋 **O QUE PROCURAR NOS LOGS**

### **CENÁRIO 1: Método NÃO foi chamado**

**O que procurar:**
```
❌ NÃO TEM: === APPLY TRAINING CHAMADO ===
```

**Significa:**
- Formulário não está enviando POST
- Action do form está errado
- Rota não está funcionando

**Solução:**
- Verificar URL no `action` do formulário
- Verificar se `.htaccess` está correto em produção

---

### **CENÁRIO 2: POST está vazio**

**O que procurar:**
```
✓ TEM: === APPLY TRAINING CHAMADO ===
✓ TEM: POST count: 0
✓ TEM: ⚠️ ALERTA: POST VAZIO
```

**Significa:**
- Configuração do servidor bloqueando POST
- `post_max_size` muito baixo
- ModSecurity bloqueando

**Solução:**
- Aumentar `post_max_size` no `.user.ini`
- Contatar suporte KingHost

---

### **CENÁRIO 3: Parou em alguma validação**

**O que procurar:**
```
✓ TEM: VALIDAÇÃO 1: Verificando training_id e user_id
✓ TEM: ✓ PASSOU: training_id=1, user_id=1

✓ TEM: VALIDAÇÃO 2: Verificando nota
❌ TEM: ❌ FALHOU: nota inválida ou vazia  ← PAROU AQUI!

❌ NÃO TEM: VALIDAÇÃO 3
❌ NÃO TEM: === PREPARANDO DADOS PARA SALVAR ===
```

**Significa:**
- A validação X está falhando
- Dados não chegam como esperado

**Solução:**
- Ver qual validação falhou
- Verificar por que o dado está vazio/inválido

---

### **CENÁRIO 4: Passou todas validações mas não salvou**

**O que procurar:**
```
✓ TEM: VALIDAÇÃO 1 ✓ PASSOU
✓ TEM: VALIDAÇÃO 2 ✓ PASSOU
✓ TEM: VALIDAÇÃO 3 ✓ PASSOU
...
✓ TEM: ✅✅✅ TODAS AS VALIDAÇÕES PASSARAM
✓ TEM: === PREPARANDO DADOS PARA SALVAR ===
✓ TEM: === MODO INSERÇÃO ===
❌ TEM: Novo ID retornado: FALHA  ← ERRO NO REPOSITORY
```

**Significa:**
- Erro no Repository/SQL
- Problema de foreign key
- Erro de banco de dados

**Solução:**
- Ver o erro SQL no log
- Verificar se IDs existem nas tabelas relacionadas

---

### **CENÁRIO 5: Salvou mas não redireciona**

**O que procurar:**
```
✓ TEM: Novo ID retornado: 123  ← SALVOU!
✓ TEM: === SUCESSO - Salvando mensagem ===
✓ TEM: Redirecionando para: http://...
```

**Mas a mensagem não aparece na tela.**

**Significa:**
- Sessão não está persistindo
- Problema no redirecionamento

**Solução:**
- Verificar configuração de sessões
- Verificar se URL_ADM está correta

---

## 🚀 **PASSO A PASSO PARA DIAGNÓSTICO**

### **1. Fazer deploy dos arquivos atualizados**

Arquivos que precisam estar em produção:
- ✅ `app/adms/Controllers/trainings/ApplyTraining.php` (com logs)
- ✅ `app/adms/Models/Repository/TrainingApplicationsRepository.php` (com logs)
- ✅ `app/adms/Views/trainings/applyTraining.php` (campos required)
- ✅ `.htaccess` (permitir scripts)
- ✅ `.user.ini` (ativar log_errors) - **na raiz!**

### **2. Aguardar 5 minutos**

Para o `.user.ini` fazer efeito.

### **3. Limpar logs antigos (opcional)**

Via FTP, delete ou renomeie:
```
app/logs/apply_training_debug.log
app/logs/debug_training_applications.log
```

Isso garante que você verá apenas os novos logs.

### **4. Tentar salvar uma aplicação**

1. Acesse apply-training em produção
2. Preencha:
   - Data de Realização: Hoje
   - Data de Avaliação: Hoje
   - Nota: 8.5
   - Tipo Instrutor: Interno
   - Instrutor: Manager
3. Clique em **Salvar**

### **5. IMEDIATAMENTE verificar os logs**

Acesse:
```
http://www.administrativotiaraju.kinghost.net/administrativo/scripts/diagnostico_producao.php
```

Vá direto na seção **"8. Logs de Erro Recentes"**

### **6. Analisar o que aparece**

Copie TODO o conteúdo dos logs e me envie.

Procure especialmente por:
- `=== APPLY TRAINING CHAMADO ===`
- `VALIDAÇÃO 1` até `VALIDAÇÃO 7`
- `✓ PASSOU` ou `❌ FALHOU`
- `=== PREPARANDO DADOS PARA SALVAR ===`
- `Novo ID retornado:`
- Qualquer mensagem de `Exception` ou `Error`

---

## 🎯 **FORMATO DO LOG ESPERADO (SUCESSO)**

```
=== APPLY TRAINING CHAMADO ===
Data/Hora: 2025-10-28 17:30:00
REQUEST_METHOD: POST
POST count: 10
POST recebido: Array(...)
SESSION user_id: 1

VALIDAÇÃO 1: Verificando training_id e user_id
✓ PASSOU: training_id=1, user_id=1

VALIDAÇÃO 2: Verificando nota (valor=8.5)
✓ PASSOU: nota=8.5

VALIDAÇÃO 3: Verificando data_avaliacao (valor=2025-10-28)
✓ PASSOU: data_avaliacao=2025-10-28

VALIDAÇÃO 4: Verificando limites da data_avaliacao
✓ PASSOU: data_avaliacao válida

VALIDAÇÃO 5: Verificando se tem data_realizacao OU data_agendada
✓ PASSOU: data_realizacao=2025-10-28, data_agendada=

VALIDAÇÃO 6: Verificando instructor_type (valor=internal)
✓ PASSOU: instructor_type=internal

VALIDAÇÃO 7: Verificando dados do instrutor
✓ PASSOU: instructor_user_id=1 (interno)

✅✅✅ TODAS AS VALIDAÇÕES PASSARAM - INICIANDO SALVAMENTO ✅✅✅

=== PREPARANDO DADOS PARA SALVAR ===
Dados a salvar: Array(...)

=== ATUALIZANDO VÍNCULO PRINCIPAL ===
Resultado vínculo: SUCESSO

=== MODO INSERÇÃO ===
Novo ID retornado: 123  ← SUCESSO!

=== SUCESSO - Salvando mensagem e redirecionando ===
```

---

## ⚠️ **SE O LOG ESTIVER VAZIO**

Significa que `log_errors` ainda está OFF.

**Verifique:**

1. O arquivo `.user.ini` está na **raiz** do projeto?
   ```
   /www/administrativo/.user.ini  ← CORRETO
   ```
   
   **NÃO:**
   ```
   /www/administrativo/scripts/.user.ini  ← ERRADO!
   ```

2. Aguardou 5 minutos após upload?

3. Execute o diagnóstico e veja se `log_errors` mudou para ON

---

## 📞 **ME ENVIE**

Após tentar salvar, me envie:

1. **Print da seção "8. Logs de Erro Recentes"** do script de diagnóstico
2. **Mensagem** que aparece na tela (se houver)
3. **Se salvou ou não** no banco

Com isso identifico exatamente o problema! 🎯

---

**Execute agora e me envie os logs!** 🚀

