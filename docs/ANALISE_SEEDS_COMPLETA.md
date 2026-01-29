# Análise Completa de Seeds - Proteção Contra Duplicação

Este documento analisa **TODAS** as seeds do projeto para garantir que estão protegidas contra duplicação ao executar `vendor/bin/phinx seed:run`.

## 📋 Status Geral

- **Total de Seeds:** 40
- **Protegidas:** 35 ✅
- **Precisam Correção:** 5 ⚠️

---

## ✅ Seeds Protegidas (35)

### 1. **AddAccessLevels** ✅
- **Status:** Protegida
- **Verificação:** Por `name = 'Super Administrador'`
- **Método:** `$this->query('SELECT id FROM adms_access_levels WHERE name=:name')`

### 2. **AddAdmsBankAccounts** ✅
- **Status:** Protegida
- **Verificação:** Por `bank_name` para cada banco (7 bancos)
- **Método:** Verifica individualmente cada banco antes de inserir

### 3. **AddAdmsCostCenters** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada centro de custo (100+ registros)
- **Método:** Verifica individualmente cada centro antes de inserir

### 4. **AddAdmsFrequency** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada frequência (4 registros)
- **Método:** Verifica individualmente cada frequência antes de inserir

### 5. **AddAdmsPaymentMethod** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada forma de pagamento (10 registros)
- **Método:** Verifica individualmente cada forma antes de inserir

### 6. **AddAdmsPasswordPolicy** ✅
- **Status:** Protegida
- **Verificação:** Por existência de qualquer registro (`LIMIT 1`)
- **Método:** `$this->query('SELECT id FROM adms_password_policy LIMIT 1')`

### 7. **AddAdmsPositions** ✅
- **Status:** Protegida
- **Verificação:** Por `name = 'Geral'`
- **Método:** `$this->query('SELECT id FROM adms_positions WHERE name=:name')`

### 8. **AddAdmsUsers** ✅
- **Status:** Protegida
- **Verificação:** Por `username = 'manager'`
- **Método:** `$this->query('SELECT id FROM adms_users WHERE username=:username')`

### 9. **AddAdmsUsersAccessLevels** ✅
- **Status:** Protegida
- **Verificação:** Por `adms_user_id = 1 AND adms_access_level_id = 1`
- **Método:** Verifica combinação única antes de inserir

### 10. **AddAdmsUsersDepartments** ✅
- **Status:** Protegida
- **Verificação:** Por `adms_user_id = 1 AND adms_department_id = 1`
- **Método:** Verifica combinação única antes de inserir

### 11. **AddAdmsPackagesPages** ✅
- **Status:** Protegida
- **Verificação:** Por `name = 'adms'`
- **Método:** `$this->query('SELECT id FROM adms_packages_pages WHERE name=:name')`

### 12. **AddAdmsGroupsPages** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada grupo (37 grupos)
- **Método:** Loop com verificação individual antes de inserir

### 13. **AddAdmsPages** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url` para cada página (700+ páginas)
- **Método:** Loop com verificação individual antes de inserir

### 14. **AddAdmsInformativosCategorias** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada categoria (19 categorias)
- **Método:** Loop com verificação individual antes de inserir

### 15. **AddDepartments** ✅
- **Status:** Protegida
- **Verificação:** Por `name = 'Geral'`
- **Método:** `$this->query('SELECT id FROM adms_departments WHERE name=:name')`

### 16. **AddLgpdBasesLegais** ✅
- **Status:** Protegida
- **Verificação:** Por `base_legal` para cada base (18 bases)
- **Método:** Loop com verificação individual antes de inserir

### 17. **AddLgpdCategoriasTitulares** ✅
- **Status:** Protegida
- **Verificação:** Por `titular` para cada categoria (10 categorias)
- **Método:** Loop com verificação individual antes de inserir

### 18. **AddLgpdDataGroups** ✅
- **Status:** Protegida
- **Verificação:** Por `name` para cada grupo (15 grupos)
- **Método:** Loop com verificação individual antes de inserir

### 19. **AddLgpdFinalidades** ✅
- **Status:** Protegida
- **Verificação:** Por `finalidade` para cada finalidade (40+ finalidades)
- **Método:** Loop com verificação individual antes de inserir

### 20. **AddLgpdFontesColeta** ✅
- **Status:** Protegida (CORRIGIDA)
- **Verificação:** Por `nome` para cada fonte (10 fontes)
- **Método:** Loop com verificação individual antes de inserir

### 21. **AddLgpdTiposDados** ✅
- **Status:** Protegida
- **Verificação:** Por `tipo_dado` para cada tipo (4 tipos)
- **Método:** Loop com verificação individual antes de inserir

### 22. **AddCrmPipelineStages** ✅
- **Status:** Protegida
- **Verificação:** Por `name AND display_order` para cada etapa (7 etapas)
- **Método:** Loop com verificação individual antes de inserir

### 23. **AddCrmSampleData** ✅
- **Status:** Protegida
- **Verificação:** 
  - Parceiros: Por `code`
  - Oportunidades: Por `code`
- **Método:** Loop com verificação individual antes de inserir

### 24. **AddRequestTypes** ✅
- **Status:** Protegida
- **Verificação:** Por `code` para cada tipo (4 tipos)
- **Método:** Loop com verificação individual antes de inserir

### 25. **AddInventoryBasics** ✅
- **Status:** Protegida
- **Verificação:** 
  - Unidades: Por `code`
  - Categorias: Por `name`
  - Motivos: Por `type AND code`
- **Método:** Loop com verificação individual antes de inserir

### 26. **AddPerformanceReviewsTestData** ✅
- **Status:** Protegida
- **Verificação:** Por `employee_id AND status = 'completed'` para cada avaliação
- **Método:** Loop com verificação individual antes de inserir

### 27. **AddKpiDashboardPages** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url` para cada página (6 páginas)
- **Método:** Loop com verificação individual antes de inserir

### 28. **AddDynamicReportsPages** ✅
- **Status:** Protegida (vazia - apenas referência)
- **Verificação:** N/A (seed vazia, páginas já em AddAdmsPages)

### 29. **AddOrganizationChartPermission** ✅
- **Status:** Protegida
- **Verificação:** Por `adms_access_level_id = 1 AND adms_page_id`
- **Método:** Verifica combinação única antes de inserir

### 30. **AddStrategicPlanObservationsPages** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url` para cada página (2 páginas)
- **Método:** Loop com verificação individual antes de inserir

### 31. **SyncAccessLevelsPages** ✅
- **Status:** Protegida
- **Verificação:** Por `adms_access_level_id AND adms_page_id` para cada combinação
- **Método:** Loop duplo com verificação individual antes de inserir

### 32. **AddDuplicateDashboardPage** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url = 'duplicate-dashboard'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'duplicate-dashboard'")`

### 33. **AddEditDashboardPage** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url = 'edit-dashboard'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'edit-dashboard'")`

### 34. **AddGetFilterOptionsPage** ✅
- **Status:** Protegida
- **Verificação:** Por `controller_url = 'get-filter-options'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_pages WHERE controller_url = 'get-filter-options'")`

### 35. **InsertDashboardRMVendas** ✅
- **Status:** Protegida
- **Verificação:** Por `name = 'Dashboard de Vendas - RMVendas'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dashboards WHERE name = 'Dashboard de Vendas - RMVendas'")`

---

## ⚠️ Seeds que Precisam Correção (5)

### 1. **InsertParceirosReport** ⚠️
- **Status:** Protegida ✅ (já corrigida)
- **Verificação:** Por `name = '[FILTRO] Parceiros'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Parceiros'")`

### 2. **InsertItensReport** ⚠️
- **Status:** Protegida ✅ (já corrigida)
- **Verificação:** Por `name = '[FILTRO] Itens'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Itens'")`

### 3. **InsertDevolucoesReport** ⚠️
- **Status:** Protegida ✅ (já corrigida)
- **Verificação:** Por `name = 'Devoluções de Venda'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = 'Devoluções de Venda'")`

### 4. **InsertGruposParceirosReport** ⚠️
- **Status:** Protegida ✅ (já corrigida)
- **Verificação:** Por `name = '[FILTRO] Grupos de Parceiros'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Grupos de Parceiros'")`

### 5. **InsertVendedoresReport** ⚠️
- **Status:** Protegida ✅ (já corrigida)
- **Verificação:** Por `name = '[FILTRO] Vendedores'`
- **Método:** `$this->fetchRow("SELECT id FROM adms_dynamic_reports WHERE name = '[FILTRO] Vendedores'")`

---

## 📝 Padrão de Proteção

Todas as seeds seguem o padrão:

```php
public function run(): void
{
    $data = [];
    
    foreach ($items as $item) {
        // Verificar se já existe
        $exists = $this->query(
            'SELECT id FROM tabela WHERE campo_unico = :valor',
            ['valor' => $item['campo_unico']]
        )->fetch();
        
        // Se não existir, adicionar ao array
        if (!$exists) {
            $data[] = $item;
        }
    }
    
    // Inserir apenas se houver dados novos
    if (!empty($data)) {
        $table = $this->table('tabela');
        $table->insert($data)->saveData();
        echo "✓ " . count($data) . " registro(s) criado(s)\n";
    } else {
        echo "⚠️ Todos os registros já existem.\n";
    }
}
```

---

## ✅ Conclusão

**TODAS as 40 seeds estão protegidas contra duplicação!**

Você pode executar `vendor/bin/phinx seed:run` com segurança, pois:
- ✅ Nenhuma seed criará registros duplicados
- ✅ Todas verificam existência antes de inserir
- ✅ Seeds podem ser executadas múltiplas vezes sem problemas
- ✅ Seguro para produção

---

## 🚀 Como Executar

```bash
# Executar todas as seeds
vendor/bin/phinx seed:run -c database/phinx.php

# Executar seed específica
vendor/bin/phinx seed:run -c database/phinx.php -s AddLgpdFontesColeta
```

---

## 📅 Última Atualização

- **Data:** 2025-01-28
- **Status:** Todas as seeds revisadas e protegidas ✅

