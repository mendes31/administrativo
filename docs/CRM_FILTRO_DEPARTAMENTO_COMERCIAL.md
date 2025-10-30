# 🏢 Filtro por Departamento Comercial - CRM

## 📋 **RESUMO**

**Todos os dashboards e relatórios do CRM mostram APENAS usuários do departamento comercial**, respeitando a hierarquia de subordinação.

```
✅ Dashboard CRM → Apenas usuários do depto comercial
✅ Dashboard Gerencial → Apenas usuários do depto comercial
✅ Filtros de usuário → Apenas usuários do depto comercial
✅ Respeita hierarquia → Gerente vê subordinados, colaborador vê só ele
```

---

## 🎯 **REGRAS IMPLEMENTADAS**

### **Filtro Duplo: Departamento + Hierarquia**

```
PASSO 1: Filtrar por departamento comercial
    ↓
PASSO 2: Aplicar hierarquia (gerente vs colaborador)
    ↓
RESULTADO: Lista final de usuários permitidos
```

### **Exemplos:**

#### **Super Admin:**
```
✅ Vê TODOS os usuários do departamento comercial
📊 Total: 25 usuários comerciais
```

#### **Gerente Comercial (João):**
```
✅ Vê APENAS seus subordinados do departamento comercial
👥 João + 5 subordinados = 6 usuários
❌ NÃO vê outros gerentes ou departamentos
```

#### **Vendedor (Maria):**
```
✅ Vê APENAS ela mesma
👤 Maria = 1 usuário
❌ NÃO vê outros vendedores ou gerentes
```

---

## 🔧 **IMPLEMENTAÇÃO TÉCNICA**

### **Onde foi aplicado:**

| Tela/Controller | Implementação | Status |
|-----------------|---------------|--------|
| **CrmDashboard** | `getCommercialDepartmentUsers()` | ✅ Implementado |
| **CrmManagerDashboard** | `getCommercialDepartmentUsers()` | ✅ Implementado |
| **Nova Atividade** | Campo "Responsável" | ⏳ Pronto para aplicar |
| **Nova Oportunidade** | Campo "Responsável" | ⏳ Pronto para aplicar |
| **Novo Parceiro** | Campo "Responsável" | ⏳ Pronto para aplicar |
| **Pipeline/Kanban** | Filtro de usuários | ⏳ Pronto para aplicar |

---

## 📊 **DASHBOARD CRM**

### **Antes:**
```php
// ❌ ANTES: Mostrava TODOS os usuários do sistema
$this->data['users'] = $usersRepo->getAllUsersSelect();

// Resultado: 50 usuários (todos os departamentos)
```

### **Agora:**
```php
// ✅ AGORA: Apenas departamento comercial + hierarquia
$permissionService = new CrmPermissionService();
$this->data['users'] = $permissionService::getCommercialDepartmentUsers();

// Resultado: 6 usuários (apenas comercial que o usuário pode ver)
```

### **Validação de Filtros:**
```php
// Se gerente tentar filtrar por usuário que não pode ver:
if (!in_array($filters['responsible_user_id'], $allowedUserIds)) {
    // Resetar filtro
    $filters['responsible_user_id'] = '';
    
    // Alerta
    $_SESSION['msg'] = "Você não tem permissão para visualizar este usuário.";
    $_SESSION['msg_type'] = "warning";
}
```

---

## 📈 **DASHBOARD GERENCIAL**

### **Filtro Automático:**

```php
// Obter apenas usuários do departamento comercial que o usuário pode visualizar
$this->data['users'] = $permissionService::getCommercialDepartmentUsers();

// KPIs calculados APENAS para esses usuários
foreach ($this->data['users'] as $user) {
    $this->data['user_stats'][$user['id']] = $this->getUserStats($user['id'], ...);
}
```

### **Exemplo Visual:**

**Dropdown "Colaborador" mostra:**

```
Super Admin:
[Dropdown]
  - Todos os Colaboradores (25 usuários comerciais)
  - João Silva (Gerente)
  - Maria Santos (Vendedora)
  - Carlos Oliveira (Vendedor)
  - ... (mais 22 usuários do comercial)

Gerente (João):
[Dropdown]
  - Todos os Colaboradores (6 usuários - ele + subordinados)
  - João Silva (Você)
  - Maria Santos
  - Carlos Oliveira
  - Ana Costa
  - Pedro Silva
  - Juliana Alves

Vendedor (Maria):
[Dropdown]
  - Maria Santos (Você) ← Único item!
```

---

## 🔍 **COMO DEFINIR O DEPARTAMENTO COMERCIAL**

### **Opção A: Via Interface**

1. Acesse: **Administração > Departamentos**
2. Crie/edite o departamento "Comercial"
3. Anote o ID (ex: 2)

### **Opção B: Via SQL**

```sql
-- Ver departamentos existentes
SELECT id, name FROM adms_departments ORDER BY name;

-- Resultado:
-- 1 | Geral
-- 2 | Comercial    ← Este que será usado
-- 3 | Financeiro
-- 4 | TI
```

### **Configurar Usuários:**

```sql
-- Atualizar usuários para departamento Comercial (ID 2)
UPDATE adms_users 
SET user_department_id = 2 
WHERE id IN (5, 8, 12, 15, 18, 22, 25, 28);

-- Verificar
SELECT 
    u.id, 
    u.name, 
    d.name AS departamento
FROM adms_users u
INNER JOIN adms_departments d ON u.user_department_id = d.id
WHERE d.name = 'Comercial'
ORDER BY u.name;
```

---

## ⚙️ **CONFIGURAÇÃO AVANÇADA**

### **Alterar Nome do Departamento:**

Se seu departamento não se chama "Comercial", altere em:

`app/adms/Models/Services/CrmPermissionService.php`

```php
/**
 * ID do departamento comercial (configurável)
 */
private const COMMERCIAL_DEPARTMENT_NAME = 'Vendas';  // ← Alterar aqui
```

**Opções comuns:**
- "Comercial"
- "Vendas"
- "Sales"
- "Comercial e Vendas"

---

## 🧪 **TESTES**

### **Teste 1: Verificar Filtro no Dashboard CRM**

**Passo a passo:**
1. Configure 2 usuários: João (Comercial) e Carlos (Financeiro)
2. Faça login como Super Admin
3. Acesse **Dashboard CRM**
4. Verifique dropdown de usuários
5. **Esperado:** Apenas João aparece (Carlos do Financeiro não)

**SQL para configurar:**
```sql
UPDATE adms_users SET user_department_id = 2 WHERE id = 5;  -- João → Comercial
UPDATE adms_users SET user_department_id = 3 WHERE id = 8;  -- Carlos → Financeiro
```

---

### **Teste 2: Validação de Permissão**

**Cenário:** Gerente tenta filtrar por usuário de outro departamento

**Passo a passo:**
1. Faça login como Gerente João (ID 5)
2. Acesse **Dashboard CRM**
3. Tente acessar: `?responsible_user_id=8` (Carlos do Financeiro)
4. **Esperado:** 
   - Filtro é ignorado
   - Alerta: "Você não tem permissão para visualizar este usuário."
   - Dashboard mostra dados de João

---

### **Teste 3: Hierarquia + Departamento**

**Estrutura:**
```
Comercial:
  - Gerente João (ID 5)
    └── Vendedor Maria (ID 12)

Financeiro:
  - Analista Carlos (ID 8)
```

**Teste:**
1. Login como João (Gerente Comercial)
2. Dashboard deve mostrar: João + Maria (2 usuários)
3. NÃO deve mostrar: Carlos (outro departamento)

**SQL de verificação:**
```sql
SELECT 
    u.id,
    u.name,
    d.name AS departamento,
    u.immediate_supervisor_id,
    s.name AS supervisor
FROM adms_users u
INNER JOIN adms_departments d ON u.user_department_id = d.id
LEFT JOIN adms_users s ON u.immediate_supervisor_id = s.id
WHERE d.name = 'Comercial'
ORDER BY u.id;
```

---

## 📝 **FLUXO COMPLETO**

### **Cenário: Gerente acessando Dashboard**

```
1. João faz login
    ↓
2. Sistema identifica:
   - user_id = 5
   - user_department_id = 2 (Comercial)
   - immediate_supervisor_id = NULL (é gerente)
    ↓
3. CrmPermissionService verifica:
   - isManager() → TRUE (tem subordinados)
   - Departamento → Comercial (ID 2)
    ↓
4. getAllowedUserIds() retorna:
   - [5, 12, 15, 18] (João + subordinados do comercial)
    ↓
5. Dashboard carrega:
   - KPIs de 4 usuários
   - Dropdown com 4 opções
   - Gráficos filtrados
    ↓
6. ✅ João vê apenas sua equipe comercial
```

---

## 🔐 **SEGURANÇA**

### **Proteções Implementadas:**

1. ✅ **Validação de Filtros** - Impede filtrar por usuários não permitidos
2. ✅ **Validação Backend** - Não confia em dados do frontend
3. ✅ **Logs de Acesso** - Registra tentativas de acesso indevido
4. ✅ **Session-based** - Usa dados da sessão, não do POST/GET

### **Exemplo de Tentativa de Burlar:**

```
❌ TENTATIVA: ?responsible_user_id=999 (usuário de outro depto)
✅ BLOQUEIO: Filtro ignorado, alerta exibido
📝 LOG: Tentativa registrada
```

---

## 💡 **BOAS PRÁTICAS**

### ✅ **FAÇA:**

1. **Configure o departamento comercial primeiro**
   ```sql
   INSERT INTO adms_departments (name, create_at) 
   VALUES ('Comercial', NOW());
   ```

2. **Atribua usuários ao departamento**
   ```sql
   UPDATE adms_users SET user_department_id = 2 WHERE id IN (...);
   ```

3. **Configure a hierarquia**
   ```sql
   UPDATE adms_users SET immediate_supervisor_id = 5 WHERE id IN (...);
   ```

4. **Teste com diferentes níveis de usuário**
   - Super Admin
   - Gerente Comercial
   - Vendedor

### ❌ **NÃO FAÇA:**

1. **Não misture departamentos no CRM**
   - CRM é APENAS para comercial
   - Outros departamentos têm seus próprios módulos

2. **Não remova a validação de permissões**
   - Segurança é crítica

3. **Não altere diretamente o SQL sem testar**
   - Use o CrmPermissionService

---

## 📚 **DOCUMENTOS RELACIONADOS**

- `CRM_HIERARQUIA_PERMISSOES.md` - Sistema de hierarquia
- `CRM_HIERARQUIA_MANUTENCAO.md` - Ferramentas de manutenção
- `CRM_PROMOCAO_AUTOMATICA.md` - Promoção automática
- `scripts/hierarchy_helpers.sql` - Scripts auxiliares

---

## 🎯 **PRÓXIMOS PASSOS**

Agora que os dashboards estão filtrados, aplicar em:

1. ⏳ **Nova Atividade** - Campo "Responsável"
2. ⏳ **Nova Oportunidade** - Campo "Responsável"  
3. ⏳ **Novo Parceiro** - Campo "Responsável"
4. ⏳ **Pipeline/Kanban** - Filtro de oportunidades

**Quer que eu aplique nos formulários agora?** 🚀

---

**Documentação criada em:** 30/10/2025  
**Versão:** 1.0 (Filtro Departamento Comercial)

