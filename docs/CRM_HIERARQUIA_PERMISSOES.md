# 🔐 Sistema de Hierarquia e Permissões CRM

## 📋 **RESUMO**

Sistema de controle de acesso ao CRM baseado em **hierarquia real de subordinação**:
- ✅ **Campo `immediate_supervisor_id`** - Cada usuário tem um supervisor definido
- ✅ **Hierarquia Recursiva** - Gerentes veem subordinados diretos E indiretos
- ✅ **Super Admin** - Vê todos os usuários
- ✅ **Permissões Automáticas** - Baseadas no organograma real da empresa

---

## 🎯 **COMO FUNCIONA**

### **Estrutura de Hierarquia:**

```
Super Admin (access_level = 1)
    ├── Todos os usuários
    │
Gerente Comercial (tem subordinados)
    ├── Vendedor A (subordinado direto)
    ├── Vendedor B (subordinado direto)
    └── Supervisor A (subordinado direto)
            ├── Vendedor C (subordinado indireto)
            └── Vendedor D (subordinado indireto)
```

### **Regras de Visualização:**

| Tipo de Usuário | O que pode ver/selecionar |
|------------------|---------------------------|
| **Super Admin** (access_level = 1) | 👑 **TODOS** os usuários |
| **Gerente** (tem subordinados) | 👥 **Seus subordinados** (diretos + indiretos) + ele mesmo |
| **Supervisor** (tem subordinados) | 👥 **Seus subordinados** (diretos + indiretos) + ele mesmo |
| **Colaborador** (sem subordinados) | 👤 **Apenas ele mesmo** |

---

## 🛠️ **INSTALAÇÃO**

### **1️⃣ Executar Migration**

```powershell
cd C:\wamp64\www\administrativo
vendor\bin\phinx migrate -e development
```

**Resultado esperado:**
```
== 20251030150000 AddImmediateSupervisorToUsers: migrating
== 20251030150000 AddImmediateSupervisorToUsers: migrated 0.1234s
✅ Campo immediate_supervisor_id criado!
```

---

### **2️⃣ Configurar Hierarquia de Usuários**

**Opção A: Via Interface (RECOMENDADO)**

1. Acesse: **Administração > Usuários**
2. Edite cada usuário
3. **Selecione o "Supervisor Imediato"** no dropdown
4. Salve

**Exemplo de configuração:**
```
João Silva (Gerente Geral) 
  → immediate_supervisor_id = NULL (ele é o topo)

Maria Santos (Vendedora)
  → immediate_supervisor_id = ID do João Silva

Carlos Oliveira (Supervisor)
  → immediate_supervisor_id = ID do João Silva

Pedro Costa (Vendedor)
  → immediate_supervisor_id = ID do Carlos Oliveira
```

**Opção B: Via SQL (Atualização em massa)**

```sql
-- Ver estrutura atual
SELECT 
    id, 
    name, 
    immediate_supervisor_id,
    (SELECT name FROM adms_users WHERE id = u.immediate_supervisor_id) AS supervisor_name
FROM adms_users u
ORDER BY name;

-- Definir supervisor para um usuário
UPDATE adms_users 
SET immediate_supervisor_id = 5  -- ID do gerente
WHERE id = 12;                    -- ID do subordinado

-- Definir supervisor para vários usuários de uma vez
UPDATE adms_users 
SET immediate_supervisor_id = 5 
WHERE id IN (12, 15, 18, 22);
```

---

## 📊 **EXEMPLOS PRÁTICOS**

### **Exemplo 1: Equipe Comercial Simples**

```
Gerente Comercial (João) - ID 5
   ├── Vendedor A (Maria) - ID 12
   ├── Vendedor B (Carlos) - ID 15
   └── Vendedor C (Ana) - ID 18
```

**Configuração:**
```sql
UPDATE adms_users SET immediate_supervisor_id = NULL WHERE id = 5;  -- João não tem supervisor
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 12;    -- Maria → João
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 15;    -- Carlos → João
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 18;    -- Ana → João
```

**Permissões:**
- João vê: Maria, Carlos, Ana + ele mesmo
- Maria vê: apenas ela mesma
- Carlos vê: apenas ele mesmo
- Ana vê: apenas ela mesma

---

### **Exemplo 2: Hierarquia com Supervisor Intermediário**

```
Gerente Geral (João) - ID 5
   └── Supervisor (Pedro) - ID 10
          ├── Vendedor A (Maria) - ID 12
          └── Vendedor B (Carlos) - ID 15
```

**Configuração:**
```sql
UPDATE adms_users SET immediate_supervisor_id = NULL WHERE id = 5;   -- João (topo)
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 10;     -- Pedro → João
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 12;    -- Maria → Pedro
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 15;    -- Carlos → Pedro
```

**Permissões:**
- João vê: Pedro, Maria, Carlos + ele mesmo (todos subordinados diretos E indiretos)
- Pedro vê: Maria, Carlos + ele mesmo
- Maria vê: apenas ela mesma
- Carlos vê: apenas ele mesmo

---

## 🔍 **COMO O SISTEMA IDENTIFICA GERENTES**

O sistema verifica **automaticamente** se um usuário é gerente através de:

### **Critério Principal (Mais Confiável):**
✅ **Tem subordinados?** → Se SIM, é gerente!

```sql
-- Verifica se o usuário ID 5 tem subordinados
SELECT COUNT(*) 
FROM adms_users 
WHERE immediate_supervisor_id = 5 
AND status = 1;

-- Se COUNT > 0, é gerente!
```

### **Critérios Alternativos (Fallback):**
1. **Access Level = 1** (Super Admin) → Sempre gerente
2. **Access Level = 2** (Gerente) → Gerente
3. **Cargo contém:** "Gerente", "Manager", "Coordenador", "Supervisor" → Gerente

---

## 📝 **MÉTODOS DISPONÍVEIS NA API**

### **`CrmPermissionService::isManager()`**
Verifica se o usuário logado é gerente

```php
use App\adms\Models\Services\CrmPermissionService;

$isManager = CrmPermissionService::isManager();

if ($isManager) {
    echo "Você é gerente!";
}
```

### **`CrmPermissionService::getAllowedUserIds()`**
Obtém IDs de usuários que o usuário pode visualizar

```php
$allowedIds = CrmPermissionService::getAllowedUserIds();
// Retorna: [5, 12, 15, 18] (gerente + subordinados)
// ou [12] (apenas o próprio ID se for colaborador)
```

### **`CrmPermissionService::getAllSubordinates($userId)`**
Obtém todos os subordinados (diretos + indiretos) de um usuário

```php
$subordinates = CrmPermissionService::getAllSubordinates(5);
// Retorna: [10, 12, 15] (todos abaixo do João na hierarquia)
```

### **`CrmPermissionService::getCommercialDepartmentUsers()`**
Obtém usuários para dropdown (já filtrado pela hierarquia)

```php
$users = CrmPermissionService::getCommercialDepartmentUsers();
// Retorna: [
//   ['id' => 5, 'name' => 'João Silva'],
//   ['id' => 12, 'name' => 'Maria Santos'],
//   ...
// ]
```

### **`CrmPermissionService::canViewUser($targetUserId)`**
Verifica se pode visualizar dados de outro usuário

```php
$canView = CrmPermissionService::canViewUser(12);

if ($canView) {
    // Pode visualizar relatórios de Maria
}
```

### **`CrmPermissionService::getImmediateSupervisor()`**
Obtém o supervisor imediato do usuário logado

```php
$supervisorId = CrmPermissionService::getImmediateSupervisor();

if ($supervisorId) {
    echo "Seu supervisor é: ID " . $supervisorId;
}
```

---

## 🧪 **TESTES**

### **Teste 1: Colaborador sem subordinados**
```sql
-- Configurar
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 12; -- Maria → João

-- Fazer login como Maria (ID 12)
```

**Esperado:**
- ✅ Dashboard Gerencial: Dropdown mostra **apenas "Maria Santos"**
- ✅ Nova Atividade: Campo "Responsável" = **"Maria Santos" (pré-selecionado, disabled)**
- ✅ Pipeline: Mostra **apenas oportunidades de Maria**

---

### **Teste 2: Supervisor com subordinados**
```sql
-- Configurar
UPDATE adms_users SET immediate_supervisor_id = NULL WHERE id = 5;  -- João (topo)
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 10;    -- Pedro → João
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 12;   -- Maria → Pedro

-- Fazer login como Pedro (ID 10)
```

**Esperado:**
- ✅ Dashboard Gerencial: Dropdown mostra **"Pedro", "Maria"**
- ✅ Nova Atividade: Campo "Responsável" pode selecionar **Pedro OU Maria**
- ✅ Pipeline: Mostra **oportunidades de Pedro E Maria**

---

### **Teste 3: Gerente Geral (múltiplos níveis)**
```sql
-- Configurar hierarquia de 3 níveis
UPDATE adms_users SET immediate_supervisor_id = NULL WHERE id = 5;  -- João (topo)
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 10;    -- Pedro → João
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 12;   -- Maria → Pedro
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 15;   -- Carlos → Pedro

-- Fazer login como João (ID 5)
```

**Esperado:**
- ✅ Dashboard Gerencial: Dropdown mostra **"João", "Pedro", "Maria", "Carlos"**
- ✅ João vê **TODOS** (subordinados diretos E indiretos)
- ✅ Pipeline: Mostra **todas as oportunidades da equipe**

---

### **Teste 4: Super Admin**
```sql
-- Fazer login com usuário access_level = 1
```

**Esperado:**
- ✅ Vê **TODOS** os usuários (ignora hierarquia)
- ✅ Acesso total ao sistema

---

## 🚨 **TROUBLESHOOTING**

### **Problema: Gerente só vê ele mesmo**

**Causa:** Subordinados não apontam para ele

**Solução:**
```sql
-- Verificar hierarquia
SELECT 
    u1.id,
    u1.name,
    u1.immediate_supervisor_id,
    u2.name AS supervisor_name
FROM adms_users u1
LEFT JOIN adms_users u2 ON u1.immediate_supervisor_id = u2.id
WHERE u1.status = 1
ORDER BY u1.name;

-- Configurar subordinados
UPDATE adms_users 
SET immediate_supervisor_id = 5  -- ID do gerente
WHERE id IN (12, 15, 18);         -- IDs dos subordinados
```

---

### **Problema: Loop infinito na hierarquia**

**Causa:** Usuário A aponta para B, e B aponta para A

**Solução:** O sistema tem proteção contra loops, mas evite criar:
```sql
-- ❌ ERRADO (loop)
UPDATE adms_users SET immediate_supervisor_id = 10 WHERE id = 5;
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 10;

-- ✅ CORRETO (hierarquia linear)
UPDATE adms_users SET immediate_supervisor_id = NULL WHERE id = 5;  -- João (topo)
UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id = 10;    -- Pedro → João
```

---

### **Problema: Colaborador virou gerente mas ainda vê só ele**

**Causa:** Cache de sessão ou subordinados não configurados

**Solução:**
1. Fazer **logout e login novamente**
2. Verificar se tem subordinados:
```sql
SELECT * FROM adms_users WHERE immediate_supervisor_id = 12; -- ID do novo gerente
```

---

## 💡 **BOAS PRÁTICAS**

✅ **Defina sempre um supervisor** para usuários que não são o topo da hierarquia
✅ **Mantenha hierarquia linear** (evite loops)
✅ **Super Admin não precisa de supervisor** (immediate_supervisor_id = NULL)
✅ **Logout/Login** após alterar hierarquia para atualizar sessão
✅ **Documente seu organograma** antes de configurar no sistema

---

## 📈 **PRÓXIMOS PASSOS**

Agora que o sistema de hierarquia está pronto, podemos aplicar em:

1. ✅ **Dashboard Gerencial** → Filtrar usuários por hierarquia
2. ✅ **Nova Atividade** → Campo "Responsável" filtrado
3. ✅ **Nova Oportunidade** → Campo "Responsável" filtrado
4. ✅ **Novo Parceiro** → Campo "Responsável" filtrado
5. ✅ **Pipeline/Kanban** → Filtrar por subordinados
6. ✅ **Relatórios** → Dados apenas de usuários permitidos

**Quer que eu aplique as permissões em todos os formulários agora?** 🚀

---

**Documentação criada em:** 30/10/2025  
**Versão:** 2.0 (Sistema de Hierarquia Real)

