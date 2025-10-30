# 🔧 Manutenção de Hierarquia - Guia Completo

## ❓ **PROBLEMA IDENTIFICADO**

**Situação:** 
Um gerente com muitos subordinados é demitido. Ao contratar um novo gerente, seria necessário editar cada subordinado um por um para apontar para o novo supervisor.

**Exemplo:**
```
João (Gerente) - 15 subordinados
  ├── Maria
  ├── Carlos
  ├── Ana
  └── ... (mais 12)

João é demitido → Contratar Paula

❌ RUIM: Editar 15 usuários manualmente
✅ BOM: Transferir equipe em 1 clique
```

---

## ✅ **SOLUÇÕES IMPLEMENTADAS**

Implementamos **3 abordagens** usadas por ferramentas profissionais (Salesforce, HubSpot, SAP):

---

### **1️⃣ TRANSFERÊNCIA EM MASSA DE SUBORDINADOS**

**O que faz:** Transfere todos os subordinados de um gerente para outro em uma operação.

**Como usar via PHP:**

```php
use App\adms\Models\Services\HierarchyManagementService;

// Transferir equipe de João (ID 5) para Maria (ID 8)
$result = HierarchyManagementService::transferSubordinates(
    fromSupervisorId: 5,    // João (supervisor atual)
    toSupervisorId: 8,      // Maria (novo supervisor)
    includeIndirect: false  // false = apenas diretos, true = todos
);

if ($result['success']) {
    echo $result['message'];  // "Sucesso! 15 usuário(s) transferido(s)."
} else {
    echo $result['message'];  // Mensagem de erro
}
```

**Como usar via SQL:**

```sql
-- PASSO 1: Ver subordinados antes
SELECT id, name 
FROM adms_users 
WHERE immediate_supervisor_id = 5;  -- ID do João

-- PASSO 2: Transferir todos para Maria (ID 8)
UPDATE adms_users 
SET immediate_supervisor_id = 8 
WHERE immediate_supervisor_id = 5;

-- RESULTADO: 15 usuários atualizados em 1 comando!
```

**Validações automáticas:**
- ✅ Verifica se novo supervisor existe e está ativo
- ✅ Previne loops (não pode transferir para subordinado)
- ✅ Não permite transferir de/para o mesmo supervisor

---

### **2️⃣ PROMOÇÃO HIERÁRQUICA (SOBEM 1 NÍVEL)**

**O que faz:** Quando um gerente intermediário sai, subordinados sobem automaticamente para o "avô".

**Exemplo:**
```
ANTES:
Diretor (Carlos) - ID 10
  └── Gerente (João) - ID 5 ← Será removido
       ├── Vendedor A - ID 12
       └── Vendedor B - ID 15

DEPOIS:
Diretor (Carlos) - ID 10
  ├── Vendedor A - ID 12  ← Agora reporta ao Carlos
  └── Vendedor B - ID 15  ← Agora reporta ao Carlos
```

**Como usar via PHP:**

```php
// Promover subordinados de João para o supervisor dele (Carlos)
$result = HierarchyManagementService::promoteSubordinates(
    removedSupervisorId: 5  // ID do João
);

// Resultado:
// [
//     'success' => true,
//     'promoted_count' => 2,
//     'new_supervisor_id' => 10,  // ID do Carlos
//     'message' => 'Sucesso! 2 usuário(s) promovido(s) na hierarquia.'
// ]
```

**Como usar via SQL:**

```sql
-- PASSO 1: Ver quem é o supervisor do João
SELECT immediate_supervisor_id 
FROM adms_users 
WHERE id = 5;  -- Retorna: 10 (Carlos)

-- PASSO 2: Promover subordinados (vão para o Carlos)
UPDATE adms_users 
SET immediate_supervisor_id = (
    SELECT immediate_supervisor_id 
    FROM adms_users 
    WHERE id = 5
)
WHERE immediate_supervisor_id = 5;
```

---

### **3️⃣ VALIDAÇÃO ANTES DE REMOVER/DESATIVAR**

**O que faz:** Sistema verifica se o usuário tem subordinados ANTES de permitir desativação/exclusão.

**Fluxo de segurança:**

```
Usuário tenta desativar João
    ↓
Sistema verifica: João tem subordinados?
    ↓
    SIM (15 subordinados)
        ↓
        ❌ BLOQUEAR DESATIVAÇÃO
        ✅ Mostrar alerta:
           "João possui 15 subordinados.
            Por favor, redistribua a equipe antes."
        ✅ Botão [Transferir Equipe]
    ↓
    NÃO (0 subordinados)
        ↓
        ✅ PERMITIR DESATIVAÇÃO
```

**Como verificar via PHP:**

```php
// Verificar se João tem subordinados
$check = HierarchyManagementService::checkSubordinates(5);

if ($check['has_subordinates']) {
    // BLOQUEAR desativação
    echo "ATENÇÃO! Este usuário possui {$check['count']} subordinados:";
    foreach ($check['subordinates'] as $sub) {
        echo "- {$sub['name']}\n";
    }
    echo "Por favor, redistribua a equipe antes de desativar.";
    
} else {
    // PERMITIR desativação
    echo "OK para desativar (sem subordinados).";
}
```

**Como verificar via SQL:**

```sql
-- Ver subordinados de João (ID 5)
SELECT 
    COUNT(*) AS total,
    GROUP_CONCAT(name SEPARATOR ', ') AS nomes
FROM adms_users 
WHERE immediate_supervisor_id = 5 
AND status = 1;

-- Se total > 0, BLOQUEAR desativação!
```

---

## 📊 **COMPARAÇÃO COM OUTRAS FERRAMENTAS**

| Ferramenta | Solução Adotada | Nossa Implementação |
|------------|-----------------|---------------------|
| **Salesforce** | Transferência em massa + validação | ✅ Implementado |
| **HubSpot** | Promoção automática + alerta | ✅ Implementado |
| **SAP SuccessFactors** | Workflow de aprovação + transferência | ✅ Transferência implementada |
| **Microsoft Dynamics** | Reatribuição em massa | ✅ Implementado |
| **Zoho CRM** | Transferência + backup hierárquico | ✅ Implementado (+ SQL helpers) |

---

## 🎯 **CENÁRIOS PRÁTICOS**

### **CENÁRIO 1: Demissão de Gerente**

**Situação:** João (gerente com 15 subordinados) foi demitido.

**Solução:**
1. Contratar/designar novo gerente (Maria)
2. Transferir equipe:
```php
HierarchyManagementService::transferSubordinates(5, 8); // João → Maria
```
3. Desativar João:
```sql
UPDATE adms_users SET status = 0 WHERE id = 5;
```

**Resultado:** ✅ 15 subordinados transferidos em 1 operação!

---

### **CENÁRIO 2: Promoção de Subordinado**

**Situação:** Carlos (subordinado de João) foi promovido a gerente e vai assumir a equipe.

**Solução:**
```php
// Transferir equipe de João para Carlos
HierarchyManagementService::transferSubordinates(5, 12);

// Promover Carlos para reportar à diretoria
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 12;
```

---

### **CENÁRIO 3: Reestruturação de Departamento**

**Situação:** Departamento Comercial será reorganizado, novo gerente geral assume.

**Solução via SQL:**
```sql
-- Novo gerente (Paula) cadastrada com ID 25
-- Transferir TODOS do departamento comercial para Paula
UPDATE adms_users 
SET immediate_supervisor_id = 25 
WHERE user_department_id = 2  -- Comercial
AND id != 25                  -- Exceto a própria Paula
AND status = 1;
```

---

### **CENÁRIO 4: Gerente Intermediário Removido**

**Situação:** Estrutura com 3 níveis, remover nível intermediário.

```
Diretor → Gerente → Vendedor
   10   →    5    →    12
```

**Solução:**
```php
// Promover vendedores para o diretor
HierarchyManagementService::promoteSubordinates(5);

// Resultado:
// Diretor → Vendedor
//    10   →    12
```

---

## 🚀 **INTERFACE RECOMENDADA (FUTURO)**

### **Modal de Transferência de Equipe**

Quando um gerente for desativado/removido:

```
╔══════════════════════════════════════════════════╗
║  ⚠️ ATENÇÃO: João Silva possui 15 subordinados  ║
╠══════════════════════════════════════════════════╣
║                                                  ║
║  Antes de desativar, escolha uma opção:         ║
║                                                  ║
║  ○ Transferir equipe para outro gerente:        ║
║    [Selecionar gerente ▼]                       ║
║                                                  ║
║  ○ Promover equipe (vão para o supervisor       ║
║    de João: "Carlos Silva")                     ║
║                                                  ║
║  ○ Deixar sem supervisor (não recomendado)      ║
║                                                  ║
║  Subordinados afetados:                          ║
║  • Maria Santos                                  ║
║  • Carlos Oliveira                               ║
║  • ... (mais 13)                                 ║
║                                                  ║
║  [Ver Lista Completa]                            ║
║                                                  ║
║        [Cancelar]  [Confirmar Transferência]    ║
╚══════════════════════════════════════════════════╝
```

---

## 💡 **BOAS PRÁTICAS**

### ✅ **SEMPRE FAÇA:**

1. **Backup antes de mudanças:**
```sql
CREATE TABLE hierarchy_backup_20251030 AS
SELECT id, name, immediate_supervisor_id FROM adms_users WHERE status = 1;
```

2. **Verifique subordinados antes:**
```php
$check = HierarchyManagementService::checkSubordinates($userId);
```

3. **Use transações para múltiplas mudanças:**
```php
$conn->beginTransaction();
try {
    // ... operações ...
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack();
}
```

### ❌ **NUNCA FAÇA:**

1. **Não desative gerente com subordinados sem redistribuir**
2. **Não crie loops (A → B → A)**
3. **Não force transferência para subordinado do próprio usuário**

---

## 📝 **RESUMO**

| Problema | Solução | Ferramenta |
|----------|---------|------------|
| Gerente demitido | Transferir equipe | `transferSubordinates()` |
| Nível intermediário removido | Promover subordinados | `promoteSubordinates()` |
| Antes de desativar | Verificar subordinados | `checkSubordinates()` |
| Ver hierarquia | Informações completas | `getHierarchyInfo()` |
| Operações SQL | Scripts prontos | `scripts/hierarchy_helpers.sql` |

---

## 🎯 **PRÓXIMOS PASSOS**

Agora você pode:

1. ✅ **Executar migration:** `vendor\bin\phinx migrate`
2. ✅ **Configurar hierarquia inicial:** Usar SQL helpers
3. ✅ **Testar transferências:** Via PHP ou SQL
4. ✅ **Criar interface:** Modal de transferência (opcional)

**Quer que eu implemente a interface de transferência ou aplique as permissões nos formulários?** 🚀

