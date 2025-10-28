# 🧪 COMO TESTAR O ALERTA RETROATIVO

## 📋 **PRÉ-REQUISITOS PARA VER O ALERTA**

Para a mensagem de aviso aparecer, **TODAS** estas condições devem ser verdadeiras:

### ✅ **Condição 1: Existe vínculo na tabela `adms_training_users`**
```sql
SELECT * FROM adms_training_users 
WHERE adms_user_id = [ID_USUARIO] 
AND adms_training_id = [ID_TREINAMENTO];
```

### ✅ **Condição 2: O vínculo tem `created_at` preenchido**
```sql
-- Verificar se created_at existe e não é NULL
SELECT id, created_at 
FROM adms_training_users 
WHERE adms_user_id = [ID_USUARIO] 
AND adms_training_id = [ID_TREINAMENTO];
```

### ✅ **Condição 3: Data de realização < Data de criação do vínculo**
```
Data de Realização: 15/09/2025
created_at do vínculo: 2025-10-20
15/09/2025 < 20/10/2025 ← RETROATIVO!
```

---

## 🔍 **VERIFICAR SE AS CONDIÇÕES SÃO ATENDIDAS**

### **PASSO 1: Identificar o user_id e training_id que você está usando**

Exemplo:
- User: **6** (Rafaela)
- Training: **8** (Clicksign)

### **PASSO 2: Verificar se existe vínculo**

Execute no banco de dados de **produção**:

```sql
SELECT 
    id,
    adms_user_id,
    adms_training_id,
    created_at,
    DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as created_at_formatado
FROM adms_training_users 
WHERE adms_user_id = 6 
AND adms_training_id = 8;
```

**Resultado esperado:**
```
id: 123
adms_user_id: 6
adms_training_id: 8
created_at: 2025-10-20 14:30:00
created_at_formatado: 20/10/2025 14:30
```

### **PASSO 3: Escolher uma data de realização ANTERIOR**

Se `created_at = 2025-10-20`, então escolha:
- ✅ **15/09/2025** (anterior)
- ✅ **01/10/2025** (anterior)
- ✅ **19/10/2025** (anterior)
- ❌ **20/10/2025** (igual - não mostra aviso)
- ❌ **21/10/2025** (posterior - não mostra aviso)

---

## 🎯 **TESTE PASSO A PASSO**

### **1. Verificar no Banco (Produção)**

```sql
-- Listar todos os vínculos
SELECT 
    u.name as usuario,
    t.nome as treinamento,
    tu.created_at,
    DATE(tu.created_at) as data_criacao_vinculo
FROM adms_training_users tu
INNER JOIN adms_users u ON u.id = tu.adms_user_id
INNER JOIN adms_trainings t ON t.id = tu.adms_training_id
ORDER BY tu.id DESC
LIMIT 10;
```

**Escolha um registro e anote:**
- User ID: _____
- Training ID: _____
- Data criação vínculo: _____

### **2. Acessar apply-training com esses IDs**

```
http://www.administrativotiaraju.kinghost.net/administrativo/apply-training?user_id=[ID_USER]&training_id=[ID_TRAINING]
```

### **3. Preencher com data RETROATIVA**

- **Data de Realização:** [DATA ANTERIOR ao vínculo]
- **Data de Avaliação:** [MESMA DATA]
- **Nota:** 8,5
- **Tipo Instrutor:** Interno
- **Instrutor:** Manager

### **4. Clicar em Salvar**

### **5. Verificar as mensagens**

Devem aparecer **DUAS mensagens**:

```
┌────────────────────────────────────────────┐
│ ✓ Treinamento registrado como realizado!  │ ← Verde
└────────────────────────────────────────────┘

┌────────────────────────────────────────────┐
│ ⚠️ Atenção: Data de realização (15/09/2025)│ ← Amarelo
│ é anterior à criação do vínculo            │
│ (20/10/2025). Lançamento retroativo        │
│ registrado.                                │
└────────────────────────────────────────────┘
```

---

## 🔍 **SE O ALERTA NÃO APARECER**

### **Verifique os logs:**

Acesse:
```
http://www.administrativotiaraju.kinghost.net/administrativo/scripts/diagnostico_producao.php
```

Na seção "8. Logs de Erro Recentes", procure por:

```
VALIDAÇÃO 8B: Verificando data_realizacao vs created_at do vínculo (FLEXÍVEL)
Data criação vínculo: 2025-10-20
Data realização: 2025-09-15
⚠️ AVISO: Lançamento RETROATIVO detectado!
⚠️ MENSAGEM DE AVISO ATIVA: ⚠️ Atenção: Data de realização...
```

---

## 🎯 **POSSÍVEIS CAUSAS**

### **Causa 1: Data NÃO é retroativa**
```
Data realização: 2025-10-28
Data criação vínculo: 2025-10-20
2025-10-28 >= 2025-10-20 ← Não é retroativa, não mostra aviso
```

**Solução:** Use uma data anterior ao vínculo

---

### **Causa 2: Vínculo não existe**
```
Log mostra: ℹ Vínculo não encontrado ou sem created_at
```

**Solução:** Verificar no banco se o vínculo existe

---

### **Causa 3: Mensagem sendo limpa**

A mensagem warning pode estar sendo limpa em outro lugar do código.

**Solução:** Vou adicionar proteção

---

## 📞 **ME ENVIE:**

1. **User ID e Training ID** que você está usando
2. **Data de realização** que você está colocando
3. **Logs** da seção 8 do diagnóstico após salvar
4. **Print da tela** mostrando as mensagens que aparecem

Com isso vou identificar por que o aviso não aparece! 🔍

