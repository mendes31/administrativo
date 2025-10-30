# 🚀 Promoção Automática de Subordinados

## 📋 **RESUMO**

**Funcionamento Automático:**
Quando um usuário com subordinados é **inativado** ou **excluído**, o sistema **automaticamente promove** seus subordinados para o supervisor de nível superior.

```
ANTES:                      DEPOIS:
Diretor                     Diretor
  └── Gerente ← INATIVADO     ├── Vendedor A
       ├── Vendedor A         ├── Vendedor B
       ├── Vendedor B         └── Vendedor C
       └── Vendedor C
```

---

## ✅ **COMPORTAMENTO AUTOMÁTICO**

### **Cenário 1: Inativar Usuário (status = 0)**

```php
// Usuário: Gerente (ID 5) com 3 subordinados
// Supervisor do Gerente: Diretor (ID 10)

// Ao inativar:
UPDATE adms_users SET status = 0 WHERE id = 5;

// O sistema AUTOMATICAMENTE:
// 1. Detecta que o Gerente (ID 5) tem subordinados
// 2. Busca o supervisor do Gerente → Diretor (ID 10)
// 3. Promove os 3 subordinados para o Diretor

// Resultado:
// ✅ Gerente inativado
// ✅ Subordinados agora reportam ao Diretor
// ✅ Hierarquia mantida intacta
```

### **Cenário 2: Excluir Usuário (DELETE)**

```php
// Ao excluir fisicamente:
DELETE FROM adms_users WHERE id = 5;

// O sistema AUTOMATICAMENTE:
// 1. ANTES de excluir, promove os subordinados
// 2. Depois executa a exclusão

// Resultado:
// ✅ Usuário excluído
// ✅ Subordinados preservados e promovidos
// ✅ Sem órfãos na hierarquia
```

---

## 🔍 **COMO FUNCIONA INTERNAMENTE**

### **Fluxo de Inativação:**

```
1. updateUser() chamado com status = 0
    ↓
2. Sistema detecta mudança de status
    ↓
3. Verifica: usuário tem subordinados?
    ↓
    SIM → Buscar supervisor do usuário
        ↓
        Promover subordinados para o supervisor
        ↓
        Log: "✅ 3 subordinados promovidos"
        ↓
        Mensagem na sessão para o admin
    ↓
4. Executa UPDATE status = 0
    ↓
5. ✅ Concluído
```

### **Código Implementado:**

```php
// Em UsersRepository::updateUser()

// Se usuário está sendo inativado E tem subordinados
if (isset($data['status']) && $data['status'] == 0 && $dadosAntes['status'] == 1) {
    
    // Verificar se tem subordinados
    $checkResult = HierarchyManagementService::checkSubordinates($data['id']);
    
    if ($checkResult['has_subordinates']) {
        // Promover subordinados automaticamente
        $promoteResult = HierarchyManagementService::promoteSubordinates($data['id']);
        
        if ($promoteResult['success']) {
            $_SESSION['hierarchy_message'] = $promoteResult['message'];
            // Ex: "Sucesso! 3 usuário(s) promovido(s) na hierarquia."
        }
    }
}
```

---

## 📊 **EXEMPLOS PRÁTICOS**

### **Exemplo 1: Gerente com Equipe Direta**

```
ESTRUTURA INICIAL:
CEO (ID 1) - NULL
  └── Gerente Comercial (ID 5) - supervisor_id = 1
       ├── Vendedor A (ID 12) - supervisor_id = 5
       ├── Vendedor B (ID 15) - supervisor_id = 5
       └── Vendedor C (ID 18) - supervisor_id = 5
```

**Ação:** Inativar Gerente Comercial (ID 5)

```sql
UPDATE adms_users SET status = 0 WHERE id = 5;
```

**Resultado Automático:**

```
ESTRUTURA APÓS INATIVAÇÃO:
CEO (ID 1) - NULL
  ├── Gerente Comercial (ID 5) - supervisor_id = 1 [INATIVO]
  ├── Vendedor A (ID 12) - supervisor_id = 1  ← PROMOVIDO!
  ├── Vendedor B (ID 15) - supervisor_id = 1  ← PROMOVIDO!
  └── Vendedor C (ID 18) - supervisor_id = 1  ← PROMOVIDO!
```

**Logs Gerados:**
```
=== HIERARQUIA: Usuário 5 está sendo inativado ===
HIERARQUIA: Usuário tem 3 subordinados - promovendo automaticamente...
HIERARQUIA: ✅ 3 subordinados promovidos com sucesso
```

**Mensagem para o Admin:**
```
✅ Gerente Comercial inativado com sucesso!
ℹ️ Sucesso! 3 usuário(s) promovido(s) na hierarquia.
```

---

### **Exemplo 2: Hierarquia de 3 Níveis**

```
ESTRUTURA INICIAL:
Diretor (ID 10) - NULL
  └── Gerente (ID 5) - supervisor_id = 10
       └── Supervisor (ID 8) - supervisor_id = 5
            ├── Vendedor A (ID 12) - supervisor_id = 8
            └── Vendedor B (ID 15) - supervisor_id = 8
```

**Ação:** Inativar Gerente (ID 5)

```sql
UPDATE adms_users SET status = 0 WHERE id = 5;
```

**Resultado Automático:**

```
ESTRUTURA APÓS INATIVAÇÃO:
Diretor (ID 10) - NULL
  ├── Gerente (ID 5) - supervisor_id = 10 [INATIVO]
  └── Supervisor (ID 8) - supervisor_id = 10  ← PROMOVIDO!
       ├── Vendedor A (ID 12) - supervisor_id = 8  (mantido)
       └── Vendedor B (ID 15) - supervisor_id = 8  (mantido)
```

**Observação:** 
- Apenas subordinados **DIRETOS** são promovidos
- Subordinados indiretos (Vendedor A e B) **mantêm** o Supervisor (ID 8)
- Hierarquia preservada, apenas sobe 1 nível

---

### **Exemplo 3: Usuário no Topo (Sem Supervisor)**

```
ESTRUTURA INICIAL:
CEO (ID 1) - NULL ← Topo da hierarquia
  ├── Gerente A (ID 5) - supervisor_id = 1
  ├── Gerente B (ID 8) - supervisor_id = 1
  └── Gerente C (ID 12) - supervisor_id = 1
```

**Ação:** Inativar CEO (ID 1)

```sql
UPDATE adms_users SET status = 0 WHERE id = 1;
```

**Resultado Automático:**

```
ESTRUTURA APÓS INATIVAÇÃO:
CEO (ID 1) - NULL [INATIVO]
├── Gerente A (ID 5) - supervisor_id = NULL  ← Sem supervisor
├── Gerente B (ID 8) - supervisor_id = NULL  ← Sem supervisor
└── Gerente C (ID 12) - supervisor_id = NULL ← Sem supervisor
```

**Mensagem:**
```
ℹ️ Sucesso! 3 usuário(s) ficaram sem supervisor.
(CEO estava no topo da hierarquia)
```

---

## 🛡️ **PROTEÇÕES E VALIDAÇÕES**

### **1. Evitar Loops**

O sistema impede criar loops na hierarquia:

```php
// ❌ NÃO PERMITIDO
A → B → A

// Validação:
if (isSubordinateOf($toSupervisorId, $fromSupervisorId)) {
    return "Não é possível transferir para um subordinado.";
}
```

### **2. Verificar Supervisor Ativo**

```php
// Só permite transferir para supervisor ativo
if (!isValidSupervisor($toSupervisorId)) {
    return "Supervisor de destino está inativo.";
}
```

### **3. Transações Atômicas**

```php
$conn->beginTransaction();
try {
    // Promover subordinados
    // Inativar usuário
    $conn->commit();
} catch (Exception $e) {
    $conn->rollBack(); // Reverte tudo em caso de erro
}
```

---

## 📝 **LOGS E AUDITORIA**

### **Logs Gerados Automaticamente:**

**Arquivo:** `C:\wamp64\logs\php_error.log`

```
[30-Oct-2025 15:30:45] === HIERARQUIA: Usuário 5 está sendo inativado ===
[30-Oct-2025 15:30:45] HIERARQUIA: Usuário tem 3 subordinados - promovendo automaticamente...
[30-Oct-2025 15:30:45] HIERARQUIA: ✅ 3 subordinados promovidos com sucesso
```

### **Logs de Alteração (Tabela `adms_logs_alteracoes`):**

```sql
SELECT * FROM adms_logs_alteracoes 
WHERE tabela = 'adms_users' 
AND registro_id IN (12, 15, 18)
AND tipo_operacao = 'UPDATE'
ORDER BY data_hora DESC;

-- Resultado:
-- Registro das promoções de cada subordinado
-- Antes: supervisor_id = 5
-- Depois: supervisor_id = 10
```

---

## 💡 **MENSAGENS PARA O USUÁRIO**

### **Interface Recomendada:**

Quando admin inativa usuário com subordinados:

```
╔══════════════════════════════════════════════════╗
║  ✅ Gerente Comercial inativado com sucesso!    ║
╠══════════════════════════════════════════════════╣
║                                                  ║
║  ℹ️ HIERARQUIA AJUSTADA AUTOMATICAMENTE          ║
║                                                  ║
║  3 subordinados foram promovidos e agora        ║
║  reportam para: Diretor Geral                   ║
║                                                  ║
║  • Vendedor A → agora reporta ao Diretor        ║
║  • Vendedor B → agora reporta ao Diretor        ║
║  • Vendedor C → agora reporta ao Diretor        ║
║                                                  ║
║  [Ver Hierarquia Atualizada] [OK]               ║
╚══════════════════════════════════════════════════╝
```

---

## 🔄 **COMO REVERTER (SE NECESSÁRIO)**

Se precisar **reverter** uma promoção automática:

```sql
-- Ver quem foi promovido
SELECT id, name, immediate_supervisor_id 
FROM adms_users 
WHERE immediate_supervisor_id = 10  -- Novo supervisor
AND id IN (12, 15, 18);              -- IDs dos promovidos

-- Reverter manualmente
UPDATE adms_users 
SET immediate_supervisor_id = 5      -- Voltar para o supervisor original
WHERE id IN (12, 15, 18);
```

---

## 🎯 **QUANDO A PROMOÇÃO AUTOMÁTICA ACONTECE**

| Ação | Promoção Automática | Observação |
|------|---------------------|------------|
| **Inativar usuário** (status = 0) | ✅ SIM | Subordinados sobem 1 nível |
| **Excluir usuário** (DELETE) | ✅ SIM | Antes de excluir |
| **Editar departamento** | ❌ NÃO | Não afeta hierarquia |
| **Editar cargo** | ❌ NÃO | Não afeta hierarquia |
| **Bloquear usuário** | ❌ NÃO | Apenas bloqueio temporário |
| **Mudar supervisor** manualmente | ❌ NÃO | Não é promoção, é reatribuição |

---

## ✅ **VANTAGENS DA PROMOÇÃO AUTOMÁTICA**

1. ✅ **Sem Intervenção Manual** - Admin não precisa redistribuir equipe
2. ✅ **Hierarquia Sempre Íntegra** - Sem "órfãos" no sistema
3. ✅ **Processo Transparente** - Logs e mensagens claras
4. ✅ **Seguro** - Transações atômicas, rollback em caso de erro
5. ✅ **Auditável** - Tudo registrado nos logs
6. ✅ **Rápido** - Promoção instantânea, sem demora

---

## 🚨 **EXCEÇÕES E CASOS ESPECIAIS**

### **Caso 1: Inativar Usuário Sem Subordinados**

```
✅ Inativação normal
❌ Nenhuma promoção necessária
ℹ️ Processo simplificado
```

### **Caso 2: Inativar Usuário no Topo**

```
✅ Subordinados ficam sem supervisor (NULL)
⚠️ Mensagem: "X usuário(s) ficaram sem supervisor"
💡 Admin pode redistribuir manualmente depois
```

### **Caso 3: Erro na Promoção**

```
❌ Erro ao promover (ex: constraint violation)
✅ Inativação AINDA ACONTECE
⚠️ Admin é notificado do erro
📝 Log registra o erro para análise
```

---

## 📚 **DOCUMENTOS RELACIONADOS**

- `CRM_HIERARQUIA_PERMISSOES.md` - Sistema de hierarquia completo
- `CRM_HIERARQUIA_MANUTENCAO.md` - Ferramentas de manutenção
- `scripts/hierarchy_helpers.sql` - Scripts SQL auxiliares

---

**Documentação criada em:** 30/10/2025  
**Versão:** 1.0 (Promoção Automática)

