# 📢 SISTEMA DE MENSAGENS DE ALERTA

## 🎨 **TIPOS DE MENSAGENS**

### **1️⃣ SUCESSO (Verde)**
```php
$_SESSION['msg'] = "Treinamento registrado com sucesso!";
$_SESSION['msg_type'] = "success";
```
**Visual:** 🟢 Caixa verde com ícone de check

---

### **2️⃣ ERRO (Vermelho)**
```php
$_SESSION['msg'] = "Erro ao salvar!";
$_SESSION['msg_type'] = "danger";
```
**Visual:** 🔴 Caixa vermelha com ícone de X

---

### **3️⃣ AVISO (Amarelo) - NOVO!**
```php
$_SESSION['msg_warning'] = "⚠️ Atenção: Lançamento retroativo!";
```
**Visual:** 🟡 Caixa amarela com ícone de alerta

---

## 💡 **CENÁRIO: Lançamento Retroativo**

### **Quando acontece:**
- Data de realização é **ANTERIOR** à criação do vínculo
- Exemplo: Vínculo criado em 20/10, mas treinamento foi em 15/09

### **O que o sistema faz:**

1. ✅ **PERMITE** salvar (não bloqueia)
2. ⚠️ **MOSTRA aviso** na tela em amarelo
3. 📝 **REGISTRA** em log de auditoria
4. ✅ **SALVA** normalmente no banco

### **Mensagens exibidas:**

```
┌────────────────────────────────────────────────────┐
│ ✓ Treinamento registrado como realizado!          │ ← Verde (sucesso)
└────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────┐
│ ⚠️ Atenção: Data de realização (15/09/2025) é     │ ← Amarelo (aviso)
│ anterior à criação do vínculo (20/10/2025).       │
│ Lançamento retroativo registrado.                 │
└────────────────────────────────────────────────────┘
```

**Resultado:**
- ✅ Salvo com sucesso
- ⚠️ Usuário foi avisado sobre a data retroativa
- 📝 Registrado em `app/logs/lancamentos_retroativos.log`

---

## 📊 **EXEMPLO DE USO**

### **Caso Real:**

**Situação:**
- Colaborador fez treinamento em **Setembro**
- Vínculo só foi criado no sistema em **Outubro**
- RH precisa registrar o treinamento retroativamente

**Antes (BLOQUEAVA):**
```
❌ ERRO: Data de realização não pode ser anterior à data de criação do vínculo (20/10/2025).
```
Não salvava!

**Agora (FLEXIBILIZADO):**
```
✅ Treinamento registrado como realizado!
⚠️ Atenção: Data de realização (15/09/2025) é anterior à criação do vínculo (20/10/2025). Lançamento retroativo registrado.
```
Salva normalmente e avisa!

---

## 🔍 **LOG DE AUDITORIA**

Cada lançamento retroativo gera uma entrada em:
```
app/logs/lancamentos_retroativos.log
```

**Formato:**
```
2025-10-28 17:45:30 | LANÇAMENTO RETROATIVO | User: 6 | Training: 8 | Data Realização: 2025-09-15 | Vínculo criado em: 2025-10-20 | Aplicado por: 1 (Manager)
```

**Campos registrados:**
- Data/Hora do lançamento
- ID do usuário
- ID do treinamento
- Data de realização informada
- Data de criação do vínculo
- Quem fez o lançamento

---

## 📋 **ARQUIVOS ATUALIZADOS**

```
✅ app/adms/Views/partials/alerts.php
   - Linhas 17-19: Suporte para msg_warning
   - Linha 30: Cleanup de msg_warning

✅ app/adms/Controllers/trainings/ApplyTraining.php
   - Linhas 337-340: Criação da mensagem de aviso
   - Linha 335: Salvamento em log de auditoria
```

---

## 🎯 **COMO VAI FUNCIONAR**

### **Lançamento Normal (data >= criação vínculo):**
```
✅ Treinamento registrado como realizado!
```
Apenas mensagem de sucesso verde.

### **Lançamento Retroativo (data < criação vínculo):**
```
✅ Treinamento registrado como realizado!

⚠️ Atenção: Data de realização (15/09/2025) é anterior à criação 
do vínculo (20/10/2025). Lançamento retroativo registrado.
```
Mensagem de sucesso **+** aviso amarelo.

---

## 📊 **RELATÓRIOS FUTUROS**

Com o log de lançamentos retroativos, você pode:

✅ **Auditar** quem faz lançamentos retroativos
✅ **Identificar** padrões suspeitos
✅ **Gerar relatórios** gerenciais
✅ **Compliance** e governança

---

**Faça o deploy do `alerts.php` atualizado e teste! Agora vai mostrar o aviso!** 🎯

