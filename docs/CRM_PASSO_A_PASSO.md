# 🚀 MÓDULO CRM - GUIA PASSO A PASSO

## 📊 **ONDE ESTAMOS**

### ✅ **CONCLUÍDO (100%):**
- ✅ Documentação completa
- ✅ **9 Migrations criadas** (todas as tabelas)
- ✅ 1 Seed (etapas do pipeline)
- ✅ 1 Repository exemplo

### ⏳ **A FAZER:**
- Executar migrations
- Criar repositories
- Criar controllers
- Criar views
- Adicionar rotas
- Adicionar menu
- Testar

---

## 🗂️ **ARQUIVOS CRIADOS**

### **Migrations (9 tabelas - TODAS PRONTAS):**
```
✅ 20251028100000_create_crm_partners.php           - Parceiros
✅ 20251028100001_create_crm_pipeline_stages.php    - Etapas do Pipeline
✅ 20251028100002_create_crm_opportunities.php      - Oportunidades
✅ 20251028100003_create_crm_activities.php         - Atividades
✅ 20251028100004_create_crm_stage_history.php      - Histórico
✅ 20251028100005_create_crm_notes.php              - Notas
✅ 20251028100006_create_crm_documents.php          - Documentos
✅ 20251028100007_create_crm_tags.php               - Tags
✅ 20251028100008_create_crm_partner_tags.php       - Relação Parceiro-Tags
```

### **Seeds:**
```
✅ AddCrmPipelineStages.php  - 7 etapas padrão
```

### **Repositories:**
```
✅ CrmOpportunitiesRepository.php  - Exemplo completo
```

---

## 🚀 **PASSO A PASSO PARA DESENVOLVER**

### **📦 PASSO 1: EXECUTAR AS MIGRATIONS** (5 minutos)

#### **1.1 Abrir terminal no diretório do projeto:**
```powershell
cd C:\wamp64\www\administrativo
```

#### **1.2 Verificar status das migrations:**
```powershell
cd database
php ..\vendor\bin\phinx status
```

Você verá algo como:
```
Status  Migration ID    Migration Name
--------------------------------------
  down  20251028100000  CreateCrmPartners
  down  20251028100001  CreateCrmPipelineStages
  down  20251028100002  CreateCrmOpportunities
  ...
```

#### **1.3 Executar TODAS as migrations:**
```powershell
php ..\vendor\bin\phinx migrate
```

Deve mostrar:
```
✓ migrating 20251028100000_create_crm_partners
✓ migrating 20251028100001_create_crm_pipeline_stages
...
All Done.
```

#### **1.4 Executar o SEED (etapas do pipeline):**
```powershell
php ..\vendor\bin\phinx seed:run -s AddCrmPipelineStages
```

#### **1.5 Verificar no banco:**
```sql
SHOW TABLES LIKE 'crm_%';
-- Deve mostrar 9 tabelas

SELECT * FROM crm_pipeline_stages;
-- Deve mostrar 7 etapas (Prospecção, Qualificação, etc)
```

---

### **📁 PASSO 2: CRIAR REPOSITORIES** (2-3 horas)

Criar **6 arquivos** em `app/adms/Models/Repository/`:

#### **2.1 CrmPartnersRepository.php**
- `getAllPartners()` - Listar com paginação e filtros
- `getPartner()` - Buscar um específico
- `createPartner()` - Criar novo
- `updatePartner()` - Atualizar
- `deletePartner()` - Deletar
- `getNextPartnerCode()` - Gerar código (P00001)

#### **2.2 CrmPipelineStagesRepository.php**
- `getActiveStages()` - Listar etapas ativas
- `getStage()` - Buscar uma etapa
- `updateStageOrder()` - Reordenar

#### **2.3 CrmActivitiesRepository.php**
- `getAllActivities()` - Listar atividades
- `createActivity()` - Criar
- `completeActivity()` - Marcar como concluída
- `getUserPendingTasks()` - Tarefas pendentes do usuário

#### **2.4 CrmNotesRepository.php**
- `getNotesByPartner()` - Notas de um parceiro
- `getNotesByOpportunity()` - Notas de uma oportunidade
- `createNote()` - Criar nota

#### **2.5 CrmDocumentsRepository.php**
- `getDocumentsByPartner()` - Documentos de um parceiro
- `uploadDocument()` - Fazer upload

#### **2.6 CrmTagsRepository.php**
- `getAllTags()` - Listar tags
- `createTag()` - Criar tag

**Posso criar todos esses repositories para você!**

---

### **🎨 PASSO 3: CRIAR CONTROLLERS** (3-4 horas)

#### **3.1 Criar estrutura de pastas:**
```
app/adms/Controllers/crm/
├── partners/
│   ├── ListPartners.php
│   ├── ViewPartner.php
│   ├── CreatePartner.php
│   ├── UpdatePartner.php
│   └── DeletePartner.php
├── opportunities/
│   ├── KanbanPipeline.php  ← Tela principal!
│   ├── ListOpportunities.php
│   ├── CreateOpportunity.php
│   └── MoveOpportunity.php
├── activities/
│   ├── ListActivities.php
│   └── CreateActivity.php
└── dashboard/
    └── CrmDashboard.php
```

**Posso criar todos esses controllers para você!**

---

### **🖼️ PASSO 4: CRIAR VIEWS** (4-5 horas)

#### **4.1 Criar estrutura de pastas:**
```
app/adms/Views/crm/
├── partners/
│   ├── list.php      - Listagem de parceiros
│   ├── view.php      - Detalhes do parceiro
│   └── form.php      - Formulário de cadastro
├── opportunities/
│   ├── kanban.php    - PIPELINE KANBAN ← Tela principal!
│   ├── list.php      - Listagem
│   └── form.php      - Formulário
├── activities/
│   └── list.php      - Agenda de atividades
└── dashboard/
    ├── gestor.php    - Dashboard gerencial
    └── usuario.php   - Dashboard do usuário
```

**A view mais importante é `kanban.php` - a tela do pipeline!**

---

### **🔗 PASSO 5: ADICIONAR ROTAS** (30 minutos)

Editar `routes/LoadPageAdm.php` e adicionar:

```php
private array $listPgPrivate = [
    // ... rotas existentes ...
    
    // CRM - Parceiros
    "CrmListPartners", "CrmViewPartner", "CrmCreatePartner", 
    "CrmUpdatePartner", "CrmDeletePartner",
    
    // CRM - Oportunidades
    "CrmListOpportunities", "CrmViewOpportunity", "CrmCreateOpportunity",
    "CrmUpdateOpportunity", "CrmKanbanPipeline", "CrmMoveOpportunity",
    
    // CRM - Atividades
    "CrmListActivities", "CrmCreateActivity",
    
    // CRM - Dashboard
    "CrmDashboard",
];
```

---

### **🍔 PASSO 6: ADICIONAR MENU** (15 minutos)

Editar `app/adms/Views/partials/menu.php` e adicionar:

```php
$menus = [
    // ... menus existentes ...
    
    [
        'id' => 'crm',
        'icon' => 'fa-solid fa-chart-line',
        'label' => 'CRM',
        'submenu' => [
            [
                'label' => 'Dashboard',
                'url' => $_ENV['URL_ADM'] . 'crm-dashboard',
                'permission' => 'CrmDashboard'
            ],
            [
                'label' => 'Pipeline Kanban',
                'url' => $_ENV['URL_ADM'] . 'crm-kanban-pipeline',
                'permission' => 'CrmKanbanPipeline'
            ],
            [
                'label' => 'Parceiros',
                'url' => $_ENV['URL_ADM'] . 'crm-list-partners',
                'permission' => 'CrmListPartners'
            ],
            [
                'label' => 'Oportunidades',
                'url' => $_ENV['URL_ADM'] . 'crm-list-opportunities',
                'permission' => 'CrmListOpportunities'
            ],
            [
                'label' => 'Atividades',
                'url' => $_ENV['URL_ADM'] . 'crm-list-activities',
                'permission' => 'CrmListActivities'
            ]
        ]
    ],
    
    // ... resto dos menus ...
];
```

---

## 🎯 **QUAL PARTE VOCÊ QUER QUE EU DESENVOLVA AGORA?**

Posso criar para você:

### **OPÇÃO A: COMEÇAR PELO KANBAN** (Tela mais importante)
1. Controller: `KanbanPipeline.php`
2. View: `kanban.php` com drag & drop
3. CSS/JS para o Kanban
4. API para mover cards

### **OPÇÃO B: COMEÇAR PELOS PARCEIROS** (Base do CRM)
1. Todos os Controllers de Parceiros
2. Todas as Views de Parceiros
3. CRUD completo funcionando

### **OPÇÃO C: COMEÇAR PELO DASHBOARD** (Visão geral)
1. Controller do Dashboard
2. Views (gestor e usuário)
3. KPIs e gráficos

### **OPÇÃO D: FAZER TUDO DE UMA VEZ** (Desenvolvimento completo)
1. Todos os Repositories
2. Todos os Controllers
3. Todas as Views
4. Rotas e Menu
5. Pronto para usar

---

## ⏱️ **TEMPO ESTIMADO POR OPÇÃO**

| Opção | Tempo | Resultado |
|-------|-------|-----------|
| A - Kanban | 2-3 horas | Pipeline visual funcionando |
| B - Parceiros | 3-4 horas | CRUD de parceiros completo |
| C - Dashboard | 2-3 horas | Visão gerencial funcionando |
| D - Completo | 1-2 dias | Módulo CRM 100% funcional |

---

## 📋 **MEU OBJETIVO PARA VOCÊ**

Quero entregar um CRM:
- ✅ **Profissional** (como Pipedrive)
- ✅ **Integrado** ao sistema Tiaraju
- ✅ **Funcional** (drag & drop, dashboards, relatórios)
- ✅ **Bonito** (mantendo a identidade visual verde)
- ✅ **Mobile-first** (responsivo)

---

## 🎯 **ESCOLHA AGORA**

**O que você quer que eu desenvolva primeiro?**

Digite:
- **"KANBAN"** - Para começar pelo pipeline visual
- **"PARCEIROS"** - Para começar pela base
- **"DASHBOARD"** - Para começar pela visão geral
- **"TUDO"** - Para eu desenvolver o módulo completo

**Qual sua escolha?** 🚀
