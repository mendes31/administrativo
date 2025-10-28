# 📊 MÓDULO CRM - PLANO COMPLETO DE DESENVOLVIMENTO

## 🎯 VISÃO GERAL

Sistema CRM (Customer Relationship Management) completo integrado ao sistema administrativo Tiaraju, seguindo os padrões arquiteturais e visuais do projeto existente.

---

## 📋 ÍNDICE

1. [Análise de Mercado e Funcionalidades](#análise-de-mercado)
2. [Arquitetura do Módulo](#arquitetura)
3. [Estrutura de Banco de Dados](#banco-de-dados)
4. [Controllers](#controllers)
5. [Models e Repositories](#models)
6. [Views](#views)
7. [Rotas e Permissões](#rotas)
8. [Identidade Visual](#identidade-visual)
9. [Roadmap de Desenvolvimento](#roadmap)

---

## 🔍 ANÁLISE DE MERCADO E FUNCIONALIDADES

### Ferramentas de Referência
- **Pipedrive**: Pipeline Kanban, gestão de atividades
- **HubSpot CRM**: Dashboard analítico, automações
- **RD Station CRM**: Funil de vendas, gestão de leads
- **Salesforce**: Relatórios avançados, forecasting

### ✅ FUNCIONALIDADES ESSENCIAIS

#### 1️⃣ **GESTÃO DE PARCEIROS (Leads/Clientes)**
- ✅ Cadastro completo de parceiros
- ✅ Segmentação (Farma, Suplementos, Ambos)
- ✅ Histórico de interações
- ✅ Documentos anexados
- ✅ Tags personalizadas
- ✅ Pontuação (Lead Scoring)

#### 2️⃣ **PIPELINE KANBAN**
- ✅ Visualização em colunas (estágios)
- ✅ Drag & Drop entre estágios
- ✅ Valor total por estágio
- ✅ Contador de oportunidades
- ✅ Cores personalizadas por estágio
- ✅ Tempo médio em cada estágio

**Estágios Padrão:**
1. **Prospecção** (Azul) - 20%
2. **Qualificação** (Roxo) - 40%
3. **Proposta** (Laranja) - 60%
4. **Negociação** (Laranja Escuro) - 80%
5. **Fechamento** (Verde) - 95%
6. **Ganho** (Verde Escuro) - 100%
7. **Perdido** (Vermelho) - 0%

#### 3️⃣ **GESTÃO DE OPORTUNIDADES**
- ✅ Valor estimado
- ✅ Data de fechamento prevista
- ✅ Probabilidade de conversão
- ✅ Produtos/Serviços vinculados
- ✅ Responsável pela oportunidade
- ✅ Próxima ação
- ✅ Tempo na etapa atual

#### 4️⃣ **ATIVIDADES E TAREFAS**
- ✅ Ligações (com registro)
- ✅ E-mails (integração)
- ✅ Reuniões (agendamento)
- ✅ Tarefas genéricas
- ✅ Follow-ups automáticos
- ✅ Notificações por e-mail
- ✅ Histórico completo

#### 5️⃣ **DASHBOARD GERENCIAL**
- ✅ **Visão Geral** (Gestor):
  - Total de leads
  - Oportunidades ativas
  - Taxa de conversão
  - Receita prevista
  - Funil de vendas
  - Atividades recentes
  - Performance por usuário
  - Tendências mensais
  
- ✅ **Visão Individual** (Usuário):
  - Minhas oportunidades
  - Minhas tarefas pendentes
  - Minhas atividades hoje
  - Minha meta vs realizado
  - Próximos follow-ups

#### 6️⃣ **RELATÓRIOS E ANÁLISES**
- ✅ Funil de vendas
- ✅ Taxa de conversão por estágio
- ✅ Tempo médio no pipeline
- ✅ Motivos de perda
- ✅ Previsão de vendas (forecast)
- ✅ Performance por vendedor
- ✅ ROI de campanhas
- ✅ Exportação Excel/PDF

#### 7️⃣ **AUTOMAÇÕES**
- ✅ Rotação de leads (round-robin)
- ✅ E-mails automáticos por estágio
- ✅ Lembretes de follow-up
- ✅ Alertas de oportunidades paradas
- ✅ Criação automática de tarefas
- ✅ Notificações Slack/E-mail

#### 8️⃣ **INTEGRAÇÕES**
- ✅ E-mail (SMTP configurável)
- ✅ WhatsApp Business API
- ✅ Calendário (Google Calendar)
- ✅ Telefonia (CTI)

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Pastas

```
app/adms/
├── Controllers/
│   └── crm/
│       ├── partners/
│       │   ├── ListPartners.php
│       │   ├── ViewPartner.php
│       │   ├── CreatePartner.php
│       │   ├── UpdatePartner.php
│       │   ├── DeletePartner.php
│       │   └── ImportPartners.php
│       ├── opportunities/
│       │   ├── ListOpportunities.php
│       │   ├── ViewOpportunity.php
│       │   ├── CreateOpportunity.php
│       │   ├── UpdateOpportunity.php
│       │   ├── DeleteOpportunity.php
│       │   ├── KanbanPipeline.php
│       │   ├── MoveOpportunity.php
│       │   └── CloneOpportunity.php
│       ├── activities/
│       │   ├── ListActivities.php
│       │   ├── CreateActivity.php
│       │   ├── UpdateActivity.php
│       │   ├── DeleteActivity.php
│       │   └── CompleteActivity.php
│       ├── dashboard/
│       │   ├── CrmDashboard.php
│       │   ├── CrmDashboardGestor.php
│       │   └── CrmDashboardUsuario.php
│       ├── reports/
│       │   ├── FunnelReport.php
│       │   ├── ConversionReport.php
│       │   ├── ForecastReport.php
│       │   └── PerformanceReport.php
│       └── settings/
│           ├── PipelineStages.php
│           ├── OpportunityTypes.php
│           └── ActivityTypes.php
│
├── Models/
│   └── Repository/
│       ├── CrmPartnersRepository.php
│       ├── CrmOpportunitiesRepository.php
│       ├── CrmActivitiesRepository.php
│       ├── CrmPipelineStagesRepository.php
│       ├── CrmTagsRepository.php
│       └── CrmNotesRepository.php
│
└── Views/
    └── crm/
        ├── partners/
        │   ├── list.php
        │   ├── view.php
        │   ├── form.php
        │   └── import.php
        ├── opportunities/
        │   ├── list.php
        │   ├── kanban.php
        │   ├── view.php
        │   └── form.php
        ├── activities/
        │   ├── list.php
        │   └── form.php
        ├── dashboard/
        │   ├── gestor.php
        │   └── usuario.php
        └── reports/
            ├── funnel.php
            └── performance.php
```

---

## 💾 ESTRUTURA DE BANCO DE DADOS

### 1. **crm_partners** (Parceiros - Leads/Clientes)

```sql
CREATE TABLE crm_partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificação
    code VARCHAR(20) UNIQUE NOT NULL,           -- Código único (P00001)
    name VARCHAR(255) NOT NULL,                 -- Nome/Razão Social
    trading_name VARCHAR(255),                  -- Nome Fantasia
    type_person ENUM('PF', 'PJ') NOT NULL,      -- Pessoa Física ou Jurídica
    document VARCHAR(20) UNIQUE,                -- CPF/CNPJ
    
    -- Contato
    email VARCHAR(255),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    website VARCHAR(255),
    
    -- Endereço
    zip_code VARCHAR(10),
    address VARCHAR(255),
    number VARCHAR(20),
    complement VARCHAR(100),
    neighborhood VARCHAR(100),
    city VARCHAR(100),
    state VARCHAR(2),
    
    -- Segmentação
    segment ENUM('Farma', 'Suplementos', 'Ambos') NOT NULL,
    partner_type ENUM('Lead', 'Cliente', 'Prospect') DEFAULT 'Lead',
    source VARCHAR(100),                        -- Origem (Site, Indicação, etc)
    
    -- Classificação
    lead_score INT DEFAULT 0,                   -- Pontuação do Lead (0-100)
    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média',
    status ENUM('Ativo', 'Inativo', 'Bloqueado') DEFAULT 'Ativo',
    
    -- Relacionamento
    responsible_user_id INT,                    -- Usuário responsável
    department_id INT,                          -- Departamento responsável
    
    -- Datas importantes
    first_contact_date DATETIME,
    last_contact_date DATETIME,
    next_contact_date DATETIME,
    
    -- Financeiro
    estimated_revenue DECIMAL(15,2) DEFAULT 0,  -- Receita estimada total
    
    -- Observações
    notes TEXT,
    tags VARCHAR(500),                          -- Tags separadas por vírgula
    
    -- Auditoria
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_code (code),
    INDEX idx_name (name),
    INDEX idx_document (document),
    INDEX idx_responsible (responsible_user_id),
    INDEX idx_segment (segment),
    INDEX idx_partner_type (partner_type),
    INDEX idx_status (status),
    
    -- Foreign Keys
    FOREIGN KEY (responsible_user_id) REFERENCES adms_users(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES adms_departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES adms_users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. **crm_pipeline_stages** (Etapas do Pipeline)

```sql
CREATE TABLE crm_pipeline_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    display_order INT NOT NULL,
    color VARCHAR(20) DEFAULT '#6c757d',        -- Cor hexadecimal
    conversion_probability INT DEFAULT 0,        -- % de conversão (0-100)
    is_active TINYINT(1) DEFAULT 1,
    is_final_stage TINYINT(1) DEFAULT 0,        -- Indica se é estágio final (ganho/perdido)
    stage_type ENUM('active', 'won', 'lost') DEFAULT 'active',
    
    -- Auditoria
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_order (display_order),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. **crm_opportunities** (Oportunidades)

```sql
CREATE TABLE crm_opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificação
    code VARCHAR(20) UNIQUE NOT NULL,           -- Código único (OPP00001)
    title VARCHAR(255) NOT NULL,                -- Título da oportunidade
    description TEXT,
    
    -- Relacionamento
    partner_id INT NOT NULL,                    -- Parceiro vinculado
    responsible_user_id INT NOT NULL,           -- Responsável
    
    -- Pipeline
    stage_id INT NOT NULL,                      -- Etapa atual
    previous_stage_id INT,                      -- Etapa anterior
    stage_entered_at DATETIME,                  -- Data de entrada na etapa atual
    
    -- Valores
    value DECIMAL(15,2) NOT NULL,               -- Valor da oportunidade
    currency VARCHAR(3) DEFAULT 'BRL',
    
    -- Probabilidade e Previsão
    probability INT DEFAULT 50,                 -- % de chance de fechar (0-100)
    expected_close_date DATE,                   -- Data prevista de fechamento
    actual_close_date DATE,                     -- Data real de fechamento
    
    -- Produtos/Serviços
    products_services TEXT,                     -- JSON com produtos
    
    -- Próxima Ação
    next_action VARCHAR(255),
    next_action_date DATETIME,
    
    -- Status
    status ENUM('Aberta', 'Ganha', 'Perdida', 'Cancelada') DEFAULT 'Aberta',
    lost_reason VARCHAR(255),                   -- Motivo de perda
    
    -- Origem
    source VARCHAR(100),                        -- Origem da oportunidade
    campaign_id INT,                            -- Campanha de marketing
    
    -- Observações
    notes TEXT,
    tags VARCHAR(500),
    
    -- Auditoria
    created_by INT,
    updated_by INT,
    closed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_code (code),
    INDEX idx_partner (partner_id),
    INDEX idx_responsible (responsible_user_id),
    INDEX idx_stage (stage_id),
    INDEX idx_status (status),
    INDEX idx_close_date (expected_close_date),
    INDEX idx_value (value),
    
    -- Foreign Keys
    FOREIGN KEY (partner_id) REFERENCES crm_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_user_id) REFERENCES adms_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (stage_id) REFERENCES crm_pipeline_stages(id) ON DELETE RESTRICT,
    FOREIGN KEY (previous_stage_id) REFERENCES crm_pipeline_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES adms_users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES adms_users(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. **crm_activities** (Atividades)

```sql
CREATE TABLE crm_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Tipo de Atividade
    type ENUM('call', 'email', 'meeting', 'task', 'note') NOT NULL,
    
    -- Relacionamento
    partner_id INT,                             -- Parceiro relacionado
    opportunity_id INT,                         -- Oportunidade relacionada
    responsible_user_id INT NOT NULL,           -- Responsável
    
    -- Detalhes
    title VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Agendamento
    scheduled_date DATETIME,
    completed_date DATETIME,
    duration_minutes INT,                       -- Duração em minutos
    
    -- Status
    status ENUM('Pendente', 'Concluída', 'Cancelada') DEFAULT 'Pendente',
    priority ENUM('Baixa', 'Média', 'Alta', 'Urgente') DEFAULT 'Média',
    
    -- Resultado (para ligações/reuniões)
    outcome VARCHAR(255),
    outcome_notes TEXT,
    
    -- Lembrete
    reminder_date DATETIME,
    reminder_sent TINYINT(1) DEFAULT 0,
    
    -- Auditoria
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_type (type),
    INDEX idx_partner (partner_id),
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_responsible (responsible_user_id),
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_date),
    INDEX idx_completed (completed_date),
    
    -- Foreign Keys
    FOREIGN KEY (partner_id) REFERENCES crm_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (responsible_user_id) REFERENCES adms_users(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES adms_users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. **crm_stage_history** (Histórico de Movimentação no Pipeline)

```sql
CREATE TABLE crm_stage_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    from_stage_id INT,
    to_stage_id INT NOT NULL,
    days_in_stage INT DEFAULT 0,               -- Dias que ficou na etapa anterior
    moved_by INT NOT NULL,
    moved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_moved_at (moved_at),
    
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (from_stage_id) REFERENCES crm_pipeline_stages(id) ON DELETE SET NULL,
    FOREIGN KEY (to_stage_id) REFERENCES crm_pipeline_stages(id) ON DELETE RESTRICT,
    FOREIGN KEY (moved_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. **crm_notes** (Observações/Notas)

```sql
CREATE TABLE crm_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT,
    opportunity_id INT,
    content TEXT NOT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_partner (partner_id),
    INDEX idx_opportunity (opportunity_id),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (partner_id) REFERENCES crm_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. **crm_documents** (Documentos Anexados)

```sql
CREATE TABLE crm_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT,
    opportunity_id INT,
    
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    description VARCHAR(255),
    
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_partner (partner_id),
    INDEX idx_opportunity (opportunity_id),
    
    FOREIGN KEY (partner_id) REFERENCES crm_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES adms_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. **crm_tags** (Tags Personalizadas)

```sql
CREATE TABLE crm_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(20) DEFAULT '#6c757d',
    description VARCHAR(255),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 9. **crm_partner_tags** (Relacionamento Parceiro-Tags)

```sql
CREATE TABLE crm_partner_tags (
    partner_id INT,
    tag_id INT,
    
    PRIMARY KEY (partner_id, tag_id),
    
    FOREIGN KEY (partner_id) REFERENCES crm_partners(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES crm_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🎨 IDENTIDADE VISUAL

### Paleta de Cores do Projeto

```css
/* Cores Principais */
--primary-green: #2E9263;       /* Verde Tiaraju */
--primary-dark: #258556;        /* Verde Escuro */
--primary-light: #4CAF7A;       /* Verde Claro */

/* Cores de Status */
--status-success: #28a745;
--status-warning: #ffc107;
--status-danger: #dc3545;
--status-info: #17a2b8;

/* Cores do Pipeline */
--stage-prospection: #0d6efd;   /* Azul */
--stage-qualification: #6f42c1;  /* Roxo */
--stage-proposal: #fd7e14;       /* Laranja */
--stage-negotiation: #d63384;    /* Rosa/Laranja Escuro */
--stage-closing: #198754;        /* Verde */
--stage-won: #157347;            /* Verde Escuro */
--stage-lost: #dc3545;           /* Vermelho */

/* Neutros */
--gray-100: #f8f9fa;
--gray-200: #e9ecef;
--gray-300: #dee2e6;
--gray-400: #ced4da;
--gray-500: #adb5bd;
--gray-600: #6c757d;
--gray-700: #495057;
--gray-800: #343a40;
--gray-900: #212529;
```

### Componentes Visuais

#### Card de Oportunidade (Kanban)
```html
<div class="opportunity-card">
    <div class="card-header">
        <span class="partner-name">Nome do Parceiro</span>
        <span class="probability">80%</span>
    </div>
    <div class="card-body">
        <h6>Título da Oportunidade</h6>
        <p class="value">R$ 45.000</p>
        <div class="meta">
            <span>Responsável</span>
            <span>6 dias nesta etapa</span>
        </div>
        <div class="next-action">
            <i class="fas fa-tasks"></i> Próxima ação
        </div>
    </div>
</div>
```

#### Dashboard KPI Card
```html
<div class="kpi-card">
    <div class="kpi-icon">
        <i class="fas fa-users"></i>
    </div>
    <div class="kpi-content">
        <h6>Total de Leads</h6>
        <h3>1,247</h3>
        <span class="trend positive">
            <i class="fas fa-arrow-up"></i> +12% vs mês anterior
        </span>
    </div>
</div>
```

---

## 📝 CONTROLLERS (Exemplos)

### CrmPartnersController - ListPartners.php

```php
<?php

namespace App\adms\Controllers\crm\partners;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Controllers\Services\PaginationService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Views\Services\LoadViewService;

class ListPartners
{
    private array|string|null $data = null;
    private int $limitResult = 10;

    public function index(string|int $page = 1): void
    {
        // Capturar parâmetros de filtro
        $filters = [
            'search' => $_GET['search'] ?? '',
            'segment' => $_GET['segment'] ?? '',
            'partner_type' => $_GET['partner_type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'responsible_user_id' => $_GET['responsible_user_id'] ?? ''
        ];

        // Paginação
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int)$_GET['page'];
        }
        
        if (isset($_GET['per_page']) && in_array((int)$_GET['per_page'], [10, 20, 50, 100])) {
            $this->limitResult = (int)$_GET['per_page'];
        }

        // Repository
        $repository = new CrmPartnersRepository();
        $totalPartners = $repository->getAmountPartners($filters);
        $this->data['partners'] = $repository->getAllPartners((int)$page, (int)$this->limitResult, $filters);
        
        // Paginação
        $pagination = PaginationService::generatePagination(
            (int)$totalPartners,
            (int)$this->limitResult,
            (int)$page,
            'crm-list-partners',
            array_merge(['per_page' => $this->limitResult], $filters)
        );
        
        $this->data['pagination'] = $pagination;
        $this->data['per_page'] = $this->limitResult;
        $this->data['filters'] = $filters;

        // Dados adicionais para os filtros
        $this->data['segments'] = ['Farma', 'Suplementos', 'Ambos'];
        $this->data['partner_types'] = ['Lead', 'Cliente', 'Prospect'];
        $this->data['statuses'] = ['Ativo', 'Inativo', 'Bloqueado'];

        // Layout da página
        $pageElements = [
            'title_head' => 'Gestão de Parceiros',
            'menu' => 'crm-list-partners',
            'buttonPermission' => ['CrmCreatePartner', 'CrmViewPartner', 'CrmUpdatePartner', 'CrmDeletePartner'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar VIEW
        $loadView = new LoadViewService("adms/Views/crm/partners/list", $this->data);
        $loadView->loadView();
    }
}
```

### CrmOpportunitiesController - KanbanPipeline.php

```php
<?php

namespace App\adms\Controllers\crm\opportunities;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPipelineStagesRepository;
use App\adms\Views\Services\LoadViewService;

class KanbanPipeline
{
    private array|string|null $data = null;

    public function index(): void
    {
        // Filtros
        $filters = [
            'responsible_user_id' => $_GET['responsible_user_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];

        // Repositories
        $stagesRepo = new CrmPipelineStagesRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();

        // Buscar etapas ativas
        $this->data['stages'] = $stagesRepo->getActiveStages();

        // Buscar oportunidades por etapa
        foreach ($this->data['stages'] as &$stage) {
            $stage['opportunities'] = $opportunitiesRepo->getOpportunitiesByStage($stage['id'], $filters);
            $stage['total_value'] = $opportunitiesRepo->getTotalValueByStage($stage['id'], $filters);
            $stage['count'] = count($stage['opportunities']);
        }

        // Valor total do pipeline
        $this->data['total_pipeline_value'] = $opportunitiesRepo->getTotalPipelineValue($filters);

        // Filtros para a view
        $this->data['filters'] = $filters;

        // Layout
        $pageElements = [
            'title_head' => 'Pipeline de Vendas',
            'menu' => 'crm-kanban-pipeline',
            'buttonPermission' => ['CrmCreateOpportunity', 'CrmMoveOpportunity'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar VIEW
        $loadView = new LoadViewService("adms/Views/crm/opportunities/kanban", $this->data);
        $loadView->loadView();
    }
}
```

### CrmDashboardController - CrmDashboard.php

```php
<?php

namespace App\adms\Controllers\crm\dashboard;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmActivitiesRepository;
use App\adms\Views\Services\LoadViewService;

class CrmDashboard
{
    private array|string|null $data = null;

    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        $isGestor = $_SESSION['is_gestor'] ?? false; // Verificar se é gestor

        // Repositories
        $partnersRepo = new CrmPartnersRepository();
        $opportunitiesRepo = new CrmOpportunitiesRepository();
        $activitiesRepo = new CrmActivitiesRepository();

        if ($isGestor) {
            // Dashboard Gerencial
            $this->data['total_leads'] = $partnersRepo->getTotalLeads();
            $this->data['total_leads_change'] = $partnersRepo->getLeadsChangePercent();
            
            $this->data['active_opportunities'] = $opportunitiesRepo->getActiveOpportunitiesCount();
            $this->data['active_opportunities_change'] = $opportunitiesRepo->getActiveOpportunitiesChangePercent();
            
            $this->data['conversion_rate'] = $opportunitiesRepo->getConversionRate();
            $this->data['conversion_rate_change'] = $opportunitiesRepo->getConversionRateChangePercent();
            
            $this->data['projected_revenue'] = $opportunitiesRepo->getProjectedRevenue();
            $this->data['projected_revenue_change'] = $opportunitiesRepo->getProjectedRevenueChangePercent();
            
            $this->data['funnel_data'] = $opportunitiesRepo->getFunnelData();
            $this->data['recent_activities'] = $activitiesRepo->getRecentActivities(10);
            
            $view = "adms/Views/crm/dashboard/gestor";
        } else {
            // Dashboard do Usuário
            $this->data['my_opportunities'] = $opportunitiesRepo->getUserOpportunities($userId);
            $this->data['my_pending_tasks'] = $activitiesRepo->getUserPendingTasks($userId);
            $this->data['my_today_activities'] = $activitiesRepo->getUserTodayActivities($userId);
            $this->data['my_performance'] = $opportunitiesRepo->getUserPerformance($userId);
            $this->data['my_next_followups'] = $activitiesRepo->getUserNextFollowups($userId, 5);
            
            $view = "adms/Views/crm/dashboard/usuario";
        }

        // Layout
        $pageElements = [
            'title_head' => 'CRM Dashboard',
            'menu' => 'crm-dashboard',
            'buttonPermission' => ['CrmDashboard'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar VIEW
        $loadView = new LoadViewService($view, $this->data);
        $loadView->loadView();
    }
}
```

---

## 🗺️ ROADMAP DE DESENVOLVIMENTO

### 📦 FASE 1: FUNDAÇÃO (2 semanas)
- ✅ Criar migrations do banco de dados
- ✅ Criar seeds com dados de exemplo
- ✅ Criar Repositories básicos
- ✅ Implementar cadastro de Parceiros (CRUD completo)
- ✅ Implementar cadastro de Etapas do Pipeline
- ✅ Adicionar permissões no sistema
- ✅ Adicionar menu CRM

### 📦 FASE 2: OPORTUNIDADES (2 semanas)
- ✅ Implementar CRUD de Oportunidades
- ✅ Criar tela Kanban (Pipeline)
- ✅ Implementar Drag & Drop entre estágios
- ✅ Criar histórico de movimentação
- ✅ Implementar cálculo de tempo em cada etapa

### 📦 FASE 3: ATIVIDADES (1 semana)
- ✅ Implementar CRUD de Atividades
- ✅ Criar agenda de atividades
- ✅ Implementar lembretes
- ✅ Criar timeline de atividades no parceiro/oportunidade

### 📦 FASE 4: DASHBOARD (1 semana)
- ✅ Criar Dashboard Gerencial
- ✅ Criar Dashboard do Usuário
- ✅ Implementar KPIs principais
- ✅ Criar gráficos e métricas

### 📦 FASE 5: RELATÓRIOS (1 semana)
- ✅ Relatório de Funil
- ✅ Relatório de Conversão
- ✅ Relatório de Performance
- ✅ Relatório de Forecast
- ✅ Exportação Excel/PDF

### 📦 FASE 6: MELHORIAS (1 semana)
- ✅ Implementar tags
- ✅ Implementar anexos de documentos
- ✅ Implementar notas/observações
- ✅ Implementar busca avançada
- ✅ Implementar filtros salvos

### 📦 FASE 7: AUTOMAÇÕES (2 semanas)
- ✅ Criação automática de tarefas
- ✅ E-mails automáticos por estágio
- ✅ Alertas de oportunidades paradas
- ✅ Rotação de leads (round-robin)
- ✅ Notificações por e-mail

### 📦 FASE 8: INTEGRAÇÕES (2 semanas)
- ✅ Integração com e-mail (SMTP)
- ✅ Integração com WhatsApp Business API
- ✅ Integração com Google Calendar
- ✅ Webhooks para sistemas externos

---

## 🔐 PERMISSÕES

### Lista Completa de Permissões do Módulo CRM

```php
// Parceiros
'CrmListPartners'
'CrmViewPartner'
'CrmCreatePartner'
'CrmUpdatePartner'
'CrmDeletePartner'
'CrmImportPartners'

// Oportunidades
'CrmListOpportunities'
'CrmViewOpportunity'
'CrmCreateOpportunity'
'CrmUpdateOpportunity'
'CrmDeleteOpportunity'
'CrmKanbanPipeline'
'CrmMoveOpportunity'
'CrmCloneOpportunity'

// Atividades
'CrmListActivities'
'CrmCreateActivity'
'CrmUpdateActivity'
'CrmDeleteActivity'
'CrmCompleteActivity'

// Dashboard
'CrmDashboard'
'CrmDashboardGestor'     // Apenas gestores
'CrmDashboardUsuario'    // Todos os usuários

// Relatórios
'CrmFunnelReport'
'CrmConversionReport'
'CrmForecastReport'
'CrmPerformanceReport'
'CrmExportReports'

// Configurações
'CrmManageStages'
'CrmManageTags'
'CrmManageSettings'
```

---

## 📋 MENU

### Estrutura do Menu CRM

```php
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
            'icon' => 'fa-solid fa-users',
            'submenu' => [
                [
                    'label' => 'Listar Parceiros',
                    'url' => $_ENV['URL_ADM'] . 'crm-list-partners',
                    'permission' => 'CrmListPartners'
                ],
                [
                    'label' => 'Novo Parceiro',
                    'url' => $_ENV['URL_ADM'] . 'crm-create-partner',
                    'permission' => 'CrmCreatePartner'
                ],
                [
                    'label' => 'Importar Parceiros',
                    'url' => $_ENV['URL_ADM'] . 'crm-import-partners',
                    'permission' => 'CrmImportPartners'
                ]
            ]
        ],
        [
            'label' => 'Oportunidades',
            'icon' => 'fa-solid fa-bullseye',
            'submenu' => [
                [
                    'label' => 'Listar Oportunidades',
                    'url' => $_ENV['URL_ADM'] . 'crm-list-opportunities',
                    'permission' => 'CrmListOpportunities'
                ],
                [
                    'label' => 'Nova Oportunidade',
                    'url' => $_ENV['URL_ADM'] . 'crm-create-opportunity',
                    'permission' => 'CrmCreateOpportunity'
                ]
            ]
        ],
        [
            'label' => 'Atividades',
            'url' => $_ENV['URL_ADM'] . 'crm-list-activities',
            'permission' => 'CrmListActivities'
        ],
        [
            'label' => 'Relatórios',
            'icon' => 'fa-solid fa-chart-bar',
            'submenu' => [
                [
                    'label' => 'Funil de Vendas',
                    'url' => $_ENV['URL_ADM'] . 'crm-funnel-report',
                    'permission' => 'CrmFunnelReport'
                ],
                [
                    'label' => 'Taxa de Conversão',
                    'url' => $_ENV['URL_ADM'] . 'crm-conversion-report',
                    'permission' => 'CrmConversionReport'
                ],
                [
                    'label' => 'Previsão de Vendas',
                    'url' => $_ENV['URL_ADM'] . 'crm-forecast-report',
                    'permission' => 'CrmForecastReport'
                ],
                [
                    'label' => 'Performance',
                    'url' => $_ENV['URL_ADM'] . 'crm-performance-report',
                    'permission' => 'CrmPerformanceReport'
                ]
            ]
        ],
        [
            'label' => 'Configurações',
            'url' => $_ENV['URL_ADM'] . 'crm-settings',
            'permission' => 'CrmManageSettings'
        ]
    ]
]
```

---

## 🎯 MÉTRICAS E KPIs

### Dashboard Gerencial

1. **Total de Leads**
   - Valor absoluto
   - Comparação com mês anterior (%)
   - Tendência (gráfico de linha)

2. **Oportunidades Ativas**
   - Por estágio
   - Valor total
   - Taxa de conversão esperada

3. **Taxa de Conversão Geral**
   - % de leads que viraram clientes
   - Comparação histórica
   - Meta vs realizado

4. **Receita Prevista**
   - Soma de oportunidades abertas × probabilidade
   - Divisão por mês de fechamento
   - Forecast do trimestre

5. **Funil de Vendas**
   - Quantidade em cada estágio
   - Visualização gráfica
   - Taxa de conversão por estágio

6. **Atividades Recentes**
   - Últimas 10 atividades
   - Status e responsável
   - Timestamp

### Dashboard do Usuário

1. **Minhas Oportunidades**
   - Total de oportunidades abertas
   - Valor total
   - Próximas a vencer

2. **Minhas Tarefas**
   - Pendentes hoje
   - Atrasadas
   - Desta semana

3. **Meta vs Realizado**
   - Meta mensal
   - Realizado até o momento
   - % de atingimento

4. **Próximos Follow-ups**
   - 5 próximas atividades agendadas
   - Parceiro/Oportunidade
   - Data e hora

---

## 🚀 TECNOLOGIAS E BIBLIOTECAS

### Front-end
- **Bootstrap 5.3.3** (já utilizado no projeto)
- **Font Awesome** (ícones)
- **SortableJS** (drag & drop para Kanban)
- **Chart.js** (gráficos do dashboard)
- **DataTables** (tabelas com paginação e busca)
- **Select2** (selects avançados)
- **Flatpickr** (date picker)

### Back-end
- **PHP 8.x** (padrão do projeto)
- **PDO** (acesso ao banco de dados)
- **PHPMailer** (envio de e-mails)
- **FPDF ou TCPDF** (geração de PDFs)
- **PhpSpreadsheet** (exportação Excel)

### Banco de Dados
- **MySQL 8.0**
- **Phinx** (migrations - já utilizado)

---

## 📚 PRÓXIMOS PASSOS

1. **Revisar e aprovar este plano**
2. **Criar as migrations do banco de dados**
3. **Criar os seeds com dados de exemplo**
4. **Desenvolver os Repositories**
5. **Desenvolver os Controllers**
6. **Desenvolver as Views**
7. **Adicionar rotas**
8. **Adicionar permissões**
9. **Adicionar ao menu**
10. **Testes e validações**

---

## ✅ CHECKLIST DE DESENVOLVIMENTO

### Banco de Dados
- [ ] Migration: crm_partners
- [ ] Migration: crm_pipeline_stages
- [ ] Migration: crm_opportunities
- [ ] Migration: crm_activities
- [ ] Migration: crm_stage_history
- [ ] Migration: crm_notes
- [ ] Migration: crm_documents
- [ ] Migration: crm_tags
- [ ] Migration: crm_partner_tags
- [ ] Seed: Etapas padrão do pipeline
- [ ] Seed: Tipos de atividade
- [ ] Seed: Dados de exemplo

### Repositories
- [ ] CrmPartnersRepository
- [ ] CrmPipelineStagesRepository
- [ ] CrmOpportunitiesRepository
- [ ] CrmActivitiesRepository
- [ ] CrmStageHistoryRepository
- [ ] CrmNotesRepository
- [ ] CrmDocumentsRepository
- [ ] CrmTagsRepository

### Controllers - Parceiros
- [ ] ListPartners
- [ ] ViewPartner
- [ ] CreatePartner
- [ ] UpdatePartner
- [ ] DeletePartner
- [ ] ImportPartners

### Controllers - Oportunidades
- [ ] ListOpportunities
- [ ] ViewOpportunity
- [ ] CreateOpportunity
- [ ] UpdateOpportunity
- [ ] DeleteOpportunity
- [ ] KanbanPipeline
- [ ] MoveOpportunity
- [ ] CloneOpportunity

### Controllers - Atividades
- [ ] ListActivities
- [ ] CreateActivity
- [ ] UpdateActivity
- [ ] DeleteActivity
- [ ] CompleteActivity

### Controllers - Dashboard
- [ ] CrmDashboard
- [ ] CrmDashboardGestor
- [ ] CrmDashboardUsuario

### Controllers - Relatórios
- [ ] FunnelReport
- [ ] ConversionReport
- [ ] ForecastReport
- [ ] PerformanceReport

### Views - Parceiros
- [ ] list.php
- [ ] view.php
- [ ] form.php
- [ ] import.php

### Views - Oportunidades
- [ ] list.php
- [ ] kanban.php
- [ ] view.php
- [ ] form.php

### Views - Atividades
- [ ] list.php
- [ ] form.php

### Views - Dashboard
- [ ] gestor.php
- [ ] usuario.php

### Views - Relatórios
- [ ] funnel.php
- [ ] conversion.php
- [ ] forecast.php
- [ ] performance.php

### CSS/JS
- [ ] crm.css (estilos específicos)
- [ ] kanban.js (drag & drop)
- [ ] dashboard-charts.js (gráficos)
- [ ] activities.js (gestão de atividades)

### Rotas e Permissões
- [ ] Adicionar rotas no LoadPageAdm.php
- [ ] Criar permissões no sistema
- [ ] Adicionar páginas na tabela adms_pages
- [ ] Vincular permissões aos níveis de acesso

### Menu
- [ ] Adicionar menu CRM
- [ ] Configurar ícones
- [ ] Testar navegação

### Testes
- [ ] Testar CRUD de Parceiros
- [ ] Testar CRUD de Oportunidades
- [ ] Testar movimentação no Kanban
- [ ] Testar criação de atividades
- [ ] Testar dashboard gerencial
- [ ] Testar dashboard usuário
- [ ] Testar relatórios
- [ ] Testar permissões

### Documentação
- [ ] Documentar API interna
- [ ] Criar manual do usuário
- [ ] Documentar processos de negócio

---

**Desenvolvido por:** Rafael Mendes
**Data:** 28/10/2025
**Versão:** 1.0

---

🎯 **Este documento serve como guia completo para o desenvolvimento do Módulo CRM no sistema administrativo Tiaraju.**

