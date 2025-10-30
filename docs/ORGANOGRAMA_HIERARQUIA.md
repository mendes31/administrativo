# 🌳 Organograma da Empresa - Visualização Hierárquica

## 📋 **RESUMO**

**Organograma visual interativo** mostrando toda a estrutura hierárquica da empresa baseada no campo `immediate_supervisor_id`.

```
CEO/Direção (sem supervisor)
  └── Gerente A (tem subordinados)
       ├── Vendedor 1
       ├── Vendedor 2
       └── Supervisor B
            ├── Vendedor 3
            └── Vendedor 4
```

---

## 🎯 **FUNCIONALIDADES**

### **1️⃣ Visualização em Árvore**
- ✅ Estrutura hierárquica clara e visual
- ✅ Linhas verdes conectando supervisor → subordinado
- ✅ Cores diferentes para cada tipo de cargo

### **2️⃣ Identificação por Cores**

| Tipo | Borda | Background | Significado |
|------|-------|------------|-------------|
| **CEO/Direção** | 🔴 Vermelha | Gradiente vermelho claro | Topo da hierarquia (sem supervisor) |
| **Gerente/Supervisor** | 🟡 Dourada | Gradiente amarelo claro | Tem subordinados |
| **Colaborador** | 🟢 Verde | Branco | Sem subordinados |

### **3️⃣ Informações Exibidas**

Cada card mostra:
- 📷 **Avatar** do usuário
- 👤 **Nome** completo
- 💼 **Cargo** (position_name)
- 🏢 **Departamento** (badge verde)
- 👥 **Subordinados** (se for gerente)
  - Diretos: quantidade imediata
  - Total: incluindo indiretos

### **4️⃣ Estatísticas**

Dashboard no topo mostra:
- 👥 **Total de Colaboradores**
- 👔 **Total de Gerentes/Supervisores**
- 📊 **Níveis Hierárquicos** (profundidade da árvore)
- 👑 **Maior Equipe** (gerente com mais subordinados)

### **5️⃣ Interatividade**

- 🖱️ **Hover**: Destaca o card (zoom e sombra)
- 👆 **Clique**: Destaca permanentemente (borda verde)
- 🖨️ **Imprimir**: Gera PDF do organograma
- 📱 **Responsivo**: Funciona em mobile (scroll horizontal)

---

## 📍 **COMO ACESSAR**

### **Via Menu:**
```
Administração > Usuários > Organograma
```

### **Via URL:**
```
http://192.168.2.180/administrativo/organization-chart
```

---

## 🔧 **INSTALAÇÃO**

### **PASSO 1: Executar Seed**

```powershell
cd C:\wamp64\www\administrativo
vendor\bin\phinx seed:run -s AddOrganizationChartPage
```

**Resultado:**
```
AddOrganizationChartPage: seeding
✅ Página "Organograma" adicionada ao sistema
```

---

### **PASSO 2: Dar Permissão**

**Via Interface:**
1. Acesse: **Administração > Níveis de Acesso**
2. Edite o nível "Super Admin" (ou outro)
3. Marque a permissão **"Organograma"**
4. Salve

**Via SQL:**
```sql
-- Ver ID da página
SELECT id, name, controller FROM adms_pages WHERE controller = 'OrganizationChart';

-- Dar permissão para Super Admin (access_level_id = 1)
INSERT INTO adms_access_levels_pages (adms_access_level_id, adms_page_id, permission)
VALUES (1, (SELECT id FROM adms_pages WHERE controller = 'OrganizationChart'), 1);
```

---

### **PASSO 3: Acessar**

1. Faça logout e login novamente
2. Acesse: **Administração > Usuários > Organograma**
3. ✅ Deve exibir a árvore hierárquica!

---

## 📊 **EXEMPLO VISUAL**

### **Estrutura Simples:**

```
┌─────────────────────┐
│   João Silva        │  ← CEO (borda vermelha)
│   CEO               │
│   Direção           │
│   👥 2 diretos      │
└─────────────────────┘
          │
    ┌─────┴─────┐
    │           │
┌────────┐  ┌────────┐
│ Maria  │  │ Carlos │  ← Colaboradores (borda verde)
│ Vended.│  │ Vended.│
│ Comerc.│  │ Comerc.│
└────────┘  └────────┘
```

### **Estrutura Complexa (3 Níveis):**

```
┌─────────────────────┐
│   Diretor Geral     │  ← CEO (borda vermelha)
│   CEO               │
│   Direção           │
│   👥 2 diretos      │
└─────────────────────┘
          │
    ┌─────┴──────┐
    │            │
┌───────────┐ ┌───────────┐
│ Gerente A │ │ Gerente B │  ← Gerentes (borda dourada)
│ Gerente   │ │ Gerente   │
│ Comercial │ │ TI        │
│ 👥 3 dir. │ │ 👥 2 dir. │
└───────────┘ └───────────┘
     │             │
  ┌──┼──┐       ┌──┴──┐
  │  │  │       │     │
┌──┐┌──┐┌──┐  ┌──┐  ┌──┐
│V1││V2││V3│  │D1│  │D2│  ← Colaboradores (borda verde)
└──┘└──┘└──┘  └──┘  └──┘
```

---

## 🎨 **PERSONALIZAÇÃO**

### **Alterar Cores:**

Edite: `app/adms/Views/users/organization-chart.php`

```css
/* CEO/Direção */
.org-chart-node.is-ceo {
    border-color: #dc3545;  /* Vermelho */
    background: linear-gradient(135deg, #fff 0%, #ffe6e6 100%);
}

/* Gerente/Supervisor */
.org-chart-node.is-manager {
    border-color: #ffc107;  /* Dourado */
    background: linear-gradient(135deg, #fff 0%, #fff9e6 100%);
}

/* Colaborador */
.org-chart-node {
    border: 2px solid #2E9263;  /* Verde */
    background: white;
}
```

---

## 📱 **RECURSOS**

### **Impressão/PDF:**

1. Clique no botão **"Imprimir"**
2. Navegador abre diálogo de impressão
3. Escolha **"Salvar como PDF"**
4. ✅ Organograma em PDF gerado!

**Ajustes automáticos para impressão:**
- ❌ Esconde botões e filtros
- ✅ Remove background colorido
- ✅ Otimiza para página branca

---

### **Responsividade:**

**Desktop:**
- ✅ Árvore horizontal completa
- ✅ Scroll se necessário

**Tablet:**
- ✅ Scroll horizontal
- ✅ Cards redimensionados

**Mobile:**
- ✅ Árvore compacta
- ✅ Scroll em ambas direções
- ✅ Cards menores

---

## 📊 **ESTATÍSTICAS EXIBIDAS**

### **Total de Colaboradores:**
```
Conta TODOS os usuários ativos
Status = 1
```

### **Total de Gerentes/Supervisores:**
```
Conta usuários que TÊM subordinados
hasSubordinates() = true
```

### **Níveis Hierárquicos:**
```
Profundidade máxima da árvore
Exemplo: CEO → Gerente → Supervisor → Vendedor = 4 níveis
```

### **Maior Equipe:**
```
Gerente com MAIS subordinados DIRETOS
Exemplo: "João Silva - 8 subordinados"
```

---

## 🔍 **DETALHES TÉCNICOS**

### **Algoritmo Recursivo:**

```php
function buildHierarchy($users, $parentId = null) {
    $branch = [];
    
    foreach ($users as $user) {
        if ($user['immediate_supervisor_id'] == $parentId) {
            $children = buildHierarchy($users, $user['id']);
            
            $branch[] = [
                'user' => $user,
                'children' => $children,
                'children_count' => count($children),
                'total_subordinates' => countTotal($children)
            ];
        }
    }
    
    return $branch;
}
```

### **Renderização HTML:**

```php
function renderHierarchyTree($hierarchy) {
    $html = '<ul>';
    
    foreach ($hierarchy as $node) {
        $html .= '<li>';
        $html .= renderNode($node['user']);
        
        if (!empty($node['children'])) {
            $html .= renderHierarchyTree($node['children']); // Recursão
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    return $html;
}
```

---

## 🧪 **EXEMPLOS DE USO**

### **Cenário 1: Startup Pequena**

```
CEO (1)
  ├── Desenvolvedor A
  ├── Desenvolvedor B
  └── Designer

Total: 4 pessoas
Níveis: 2
Gerentes: 1
```

### **Cenário 2: Empresa Média**

```
Diretor Geral (1)
  ├── Gerente Comercial (3 subordinados)
  │    ├── Vendedor A
  │    ├── Vendedor B
  │    └── Vendedor C
  └── Gerente TI (2 subordinados)
       ├── Desenvolvedor A
       └── Desenvolvedor B

Total: 8 pessoas
Níveis: 3
Gerentes: 3 (Diretor + 2 Gerentes)
Maior equipe: Gerente Comercial (3)
```

### **Cenário 3: Empresa Grande**

```
CEO (1)
  ├── Diretor Comercial (10 subordinados)
  │    ├── Gerente Regional Sul (5)
  │    │    ├── Supervisor A (2)
  │    │    │    ├── Vendedor 1
  │    │    │    └── Vendedor 2
  │    │    └── ...
  │    └── Gerente Regional Norte (5)
  │         └── ...
  └── Diretor TI (8 subordinados)
       └── ...

Total: 50+ pessoas
Níveis: 5
Gerentes: 12
Maior equipe: Gerente Regional Sul (5)
```

---

## 💡 **BENEFÍCIOS**

### **Para RH:**
- ✅ Visualizar estrutura organizacional completa
- ✅ Identificar lacunas na hierarquia
- ✅ Planejar sucessões
- ✅ Onboarding de novos funcionários

### **Para Gestores:**
- ✅ Entender quem reporta para quem
- ✅ Visualizar tamanho das equipes
- ✅ Identificar desequilíbrios (equipes muito grandes)
- ✅ Comunicação mais eficiente

### **Para Administradores:**
- ✅ Validar hierarquia está correta
- ✅ Detectar loops ou erros
- ✅ Gerar documentação visual
- ✅ Apresentações para diretoria

---

## 🚨 **TROUBLESHOOTING**

### **Problema: Organograma vazio**

**Causa:** Nenhum usuário sem supervisor (sem CEO/topo)

**Solução:**
```sql
-- Definir um CEO
UPDATE adms_users 
SET immediate_supervisor_id = NULL 
WHERE id = 1;  -- ID do CEO
```

---

### **Problema: Alguns usuários não aparecem**

**Causa:** Usuários inativos ou sem departamento/cargo

**Solução:**
```sql
-- Verificar usuários ativos
SELECT id, name, status, user_department_id, user_position_id 
FROM adms_users 
WHERE status = 1;

-- Ativar usuário
UPDATE adms_users SET status = 1 WHERE id = X;
```

---

### **Problema: Hierarquia muito larga**

**Causa:** Muitos subordinados diretos (ex: 20 vendedores para 1 gerente)

**Solução:** 
- Adicionar supervisores intermediários
- Dividir em equipes menores
- Usar scroll horizontal (já implementado)

---

## 📝 **PRÓXIMOS PASSOS**

Após visualizar o organograma, você pode:

1. ✅ **Editar hierarquia**: Clicar em usuário → Editar → Alterar supervisor
2. ✅ **Transferir equipes**: Usar `HierarchyManagementService`
3. ✅ **Exportar**: Imprimir para PDF
4. ✅ **Compartilhar**: Enviar PDF para equipe

---

## 🎯 **FUNCIONALIDADES FUTURAS (Opcional)**

- [ ] Busca por nome no organograma
- [ ] Filtro por departamento
- [ ] Zoom in/out
- [ ] Exportar para PNG
- [ ] Visualização horizontal vs vertical
- [ ] Informações adicionais ao clicar (email, telefone)
- [ ] Edição inline (arrastar e soltar para mudar supervisor)

---

**Documentação criada em:** 30/10/2025  
**Versão:** 1.0 (Organograma Visual)

