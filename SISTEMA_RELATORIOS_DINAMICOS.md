# 📊 SISTEMA DE RELATÓRIOS DINÂMICOS

## ✅ ARQUIVOS CRIADOS

### Backend:
- ✅ `database/migrations/20251104000001_create_dynamic_reports.php`
- ✅ `app/adms/Models/Repository/DynamicReportsRepository.php`
- ✅ `app/adms/Models/Services/DynamicQueryBuilderService.php`
- ✅ `app/adms/Models/Services/SapB1HanaConnection.php`

### Controllers:
- ✅ `app/adms/Controllers/reports/DynamicReportBuilder.php`
- ✅ `app/adms/Controllers/reports/ListDynamicReports.php`
- ✅ `app/adms/Controllers/reports/ViewDynamicReport.php`
- ✅ `app/adms/Controllers/reports/SaveDynamicReport.php`
- ✅ `app/adms/Controllers/reports/ExecuteDynamicReport.php`

### Scripts:
- ✅ `scripts/test_sap_b1_connection.php`

---

## 🚀 INSTALAÇÃO

### 1. Executar Migration

```bash
cd C:\wamp64\www\administrativo
vendor\bin\phinx migrate -c database/phinx.php
```

### 2. Adicionar Rotas

Editar `routes/LoadPageAdm.php`, adicionar no array `$listPgPrivate`:

```php
"DynamicReportBuilder", "ListDynamicReports", "ViewDynamicReport", 
"SaveDynamicReport", "ExecuteDynamicReport", "DeleteDynamicReport",
"ExportDynamicReport"
```

### 3. Adicionar no Menu

Editar `app/adms/Views/partials/menu.php`, adicionar:

```php
[
    'id' => 'relatorios',
    'icon' => 'fa-solid fa-chart-bar',
    'label' => 'Relatórios Dinâmicos',
    'submenu' => [
        [
            'label' => 'Meus Relatórios',
            'url' => $_ENV['URL_ADM'] . 'list-dynamic-reports',
            'permission' => 'ListDynamicReports'
        ],
        [
            'label' => 'Criar Relatório',
            'url' => $_ENV['URL_ADM'] . 'dynamic-report-builder',
            'permission' => 'DynamicReportBuilder'
        ]
    ]
]
```

### 4. Atualizar Composer

```bash
composer dump-autoload
```

---

## 📋 FUNCIONALIDADES

### ✅ Relatórios Locais:
- Usuários
- Treinamentos
- CRM (Parceiros, Oportunidades)
- Financeiro
- Estoque

### ✅ Relatórios SAP B1:
- Clientes (OCRD)
- Notas Fiscais (OINV)
- Pedidos de Venda (ORDR)
- Itens (OITM)
- Estoque (OITW)

### ✅ Recursos:
- Construtor visual
- Filtros dinâmicos
- Agregações (COUNT, SUM, AVG, MIN, MAX)
- Agrupamento (GROUP BY)
- Ordenação (ORDER BY)
- Visualizações (Tabela e Gráficos)
- Atualização em tempo real
- Exportação (Excel, PDF, CSV)
- Compartilhamento
- Favoritos

---

## 🎯 COMO USAR

### 1. Criar Relatório

1. Acessar: `http://localhost/administrativo/dynamic-report-builder`
2. Selecionar fonte de dados
3. Adicionar campos
4. Adicionar filtros (opcional)
5. Clicar "Visualizar Prévia" → **Dados aparecem em tempo real!**
6. Salvar

### 2. Visualizar Relatórios

1. Acessar: `http://localhost/administrativo/list-dynamic-reports`
2. Clicar em um relatório
3. Ver dados atualizados

---

## 📊 EXEMPLOS

### Exemplo 1: Top 10 Clientes SAP B1
- **Fonte:** SAP B1 - Clientes (OCRD)
- **Campos:** CardName, Balance (SUM)
- **Filtro:** CardType = 'C'
- **Agrupar:** CardName
- **Ordenar:** Balance DESC
- **Limite:** 10
- **Visualização:** Gráfico de Barras

### Exemplo 2: Treinamentos por Status
- **Fonte:** Status de Treinamentos
- **Campos:** status, id (COUNT)
- **Agrupar:** status
- **Visualização:** Gráfico de Pizza

---

## 🔧 TROUBLESHOOTING

### Erro: Tabela não existe
```bash
# Executar migration
vendor\bin\phinx migrate -c database/phinx.php
```

### Erro: Classe não encontrada
```bash
# Atualizar autoload
composer dump-autoload
```

### Erro conexão SAP B1
```bash
# Testar conexão
php scripts/test_sap_b1_connection.php
```

---

✅ **Sistema pronto para uso!** 🎉

