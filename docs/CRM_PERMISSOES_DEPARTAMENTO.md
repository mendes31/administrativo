# 🔐 Sistema de Permissões CRM por Departamento

## 📋 **RESUMO**

Sistema de controle de acesso ao CRM baseado em:
- ✅ **Departamento Comercial** (apenas usuários deste depto podem usar CRM)
- ✅ **Hierarquia** (Gerentes vs Colaboradores)
- ✅ **Permissões Automáticas** (gerentes veem todos, colaboradores só veem a si mesmos)

---

## 🎯 **COMO FUNCIONA**

### **Regras de Permissão:**

| Tipo de Usuário | O que pode ver/selecionar |
|------------------|---------------------------|
| **Super Admin** (access_level = 1) | Todos do depto comercial |
| **Gerente** (access_level = 2 ou cargo "Gerente") | Todos do depto comercial |
| **Colaborador** (demais usuários) | Apenas ele mesmo |

### **Aplicação:**

✅ **Dashboard Gerencial** - Filtra apenas usuários do depto comercial
✅ **Nova Atividade** - Dropdown "Responsável" mostra apenas usuários permitidos
✅ **Nova Oportunidade** - Dropdown "Responsável" mostra apenas usuários permitidos
✅ **Novo Parceiro** - Dropdown "Responsável" mostra apenas usuários permitidos
✅ **Pipeline/Kanban** - Filtra oportunidades dos usuários permitidos

---

## 🛠️ **INSTALAÇÃO**

### **1️⃣ Executar Seed para Criar Departamento "Comercial"**

```powershell
cd C:\wamp64\www\administrativo
vendor\bin\phinx seed:run -s AddCommercialDepartment
```

**Resultado esperado:**
```
AddCommercialDepartment: seeding
✅ Departamento "Comercial" criado!
```

---

### **2️⃣ Configurar Usuários**

**Via Interface Admin:**
1. Acesse: **Administração > Usuários**
2. Edite cada usuário do time comercial
3. Altere o **Departamento** para **"Comercial"**
4. Salve

**Via SQL (Opcional - Atualização em massa):**
```sql
-- Listar usuários e seus departamentos
SELECT id, name, user_department_id FROM adms_users;

-- Atualizar usuários para departamento Comercial (ID = 2, ajustar conforme necessário)
UPDATE adms_users 
SET user_department_id = 2 
WHERE id IN (5, 8, 12, 15); -- IDs dos usuários comerciais
```

---

### **3️⃣ Configurar Gerentes**

**Opção A: Por Access Level**
1. Acesse: **Administração > Níveis de Acesso**
2. Crie nível "Gerente Comercial" (ID = 2)
3. Atribua aos gerentes

**Opção B: Por Cargo/Posição**
1. Acesse: **Administração > Cargos**
2. Crie cargo "Gerente" ou "Gerente Comercial"
3. Atribua aos gerentes

**Cargos Reconhecidos Automaticamente:**
- Gerente
- Gerente Geral
- Manager
- Coordenador

---

## 🔧 **CONFIGURAÇÃO AVANÇADA**

### **Personalizar Cargos Gerenciais**

Edite: `app/adms/Models/Services/CrmPermissionService.php`

```php
/**
 * Cargos que são considerados gerentes
 * Adicione aqui os nomes dos cargos que são gerentes
 */
private const MANAGER_POSITIONS = [
    'Gerente', 
    'Gerente Geral', 
    'Manager', 
    'Coordenador',
    'Supervisor',        // ← Adicione aqui
    'Diretor Comercial'  // ← Adicione aqui
];
```

### **Alterar Nome do Departamento**

Se seu departamento não se chama "Comercial", altere:

```php
private const COMMERCIAL_DEPARTMENT_NAME = 'Vendas'; // ou outro nome
```

---

## 📊 **MÉTODOS DISPONÍVEIS**

### **Verificar Permissões:**

```php
use App\adms\Models\Services\CrmPermissionService;

// Verificar se usuário é do departamento comercial
$isCommercial = CrmPermissionService::isFromCommercialDepartment();

// Verificar se usuário é gerente
$isManager = CrmPermissionService::isManager();

// Obter IDs de usuários que o usuário pode visualizar
$allowedUserIds = CrmPermissionService::getAllowedUserIds();

// Obter usuários para dropdown (já filtrado)
$users = CrmPermissionService::getCommercialDepartmentUsers(!$isManager);

// Verificar se pode visualizar outro usuário
$canView = CrmPermissionService::canViewUser($targetUserId);
```

### **Filtros para Consultas:**

```php
$filters = CrmPermissionService::getCrmUserFilters();

// Retorna:
[
    'user_ids' => [5, 8, 12, 15],        // IDs permitidos
    'is_manager' => true,                 // Se é gerente
    'current_user_id' => 5,               // ID do usuário logado
    'commercial_users' => [...]           // Array completo para dropdown
]
```

---

## 🧪 **TESTES**

### **Teste 1: Colaborador**
1. Faça login com usuário colaborador (não-gerente)
2. Acesse **CRM > Nova Atividade**
3. Campo "Responsável" deve mostrar **apenas o próprio nome**
4. Deve estar **pré-selecionado** e **não editável** (ou desabilitado)

### **Teste 2: Gerente**
1. Faça login com usuário gerente
2. Acesse **CRM > Nova Atividade**
3. Campo "Responsável" deve mostrar **todos do depto comercial**
4. Pode selecionar **qualquer colaborador**

### **Teste 3: Dashboard Gerencial**
1. Faça login como gerente
2. Acesse **CRM > Dashboard Gerencial**
3. Dropdown "Colaborador" deve mostrar **apenas usuários do depto comercial**
4. Não deve aparecer usuários de outros departamentos

---

## 🚨 **TROUBLESHOOTING**

### **Problema: Todos os usuários aparecem no dropdown**

**Causa:** Departamento "Comercial" não existe no banco

**Solução:**
```powershell
vendor\bin\phinx seed:run -s AddCommercialDepartment
```

---

### **Problema: Gerente só vê ele mesmo**

**Causas possíveis:**
1. Access Level não está configurado como 2 (Gerente)
2. Nome do cargo não está na lista `MANAGER_POSITIONS`
3. Sessão não foi atualizada após mudança

**Solução:**
```sql
-- Verificar dados do usuário
SELECT 
    u.id,
    u.name,
    u.user_department_id,
    u.user_position_id,
    ual.adms_access_level_id,
    p.name AS position_name
FROM adms_users u
LEFT JOIN adms_users_access_levels ual ON u.id = ual.adms_user_id
LEFT JOIN adms_positions p ON u.user_position_id = p.id
WHERE u.id = 5; -- ID do gerente
```

- Fazer logout e login novamente
- OU adicionar cargo no `MANAGER_POSITIONS`

---

### **Problema: Colaborador consegue ver outros usuários**

**Causa:** Lógica não foi aplicada no formulário

**Solução:** Verificar se o formulário está usando `CrmPermissionService::getCommercialDepartmentUsers(!$isManager)`

---

## 📝 **PRÓXIMOS PASSOS**

Para aplicar as permissões nos formulários e dashboards:

1. ✅ **Dashboard Gerencial** → Filtrar dropdown de usuários
2. ✅ **Nova Atividade** → Campo "Responsável"
3. ✅ **Nova Oportunidade** → Campo "Responsável"
4. ✅ **Novo Parceiro** → Campo "Responsável"
5. ✅ **Pipeline/Kanban** → Filtrar oportunidades

**Você quer que eu implemente todos agora?** 🚀

---

## 💡 **DICAS**

- ✅ Super Admin (ID 1) sempre tem acesso total
- ✅ Sessão é atualizada no login (logout/login após mudanças)
- ✅ Colaboradores veem formulário com campo pré-preenchido
- ✅ Gerentes veem dropdown completo
- ✅ Validação também deve ser feita no backend (controllers)

---

**Documentação criada em:** 30/10/2025  
**Versão:** 1.0

