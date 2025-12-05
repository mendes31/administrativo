# 📊 Análise de Viabilidade - Módulo de Gestão de Pessoas

## 🎯 Resumo Executivo

**Status:** ✅ **VIÁVEL** com implementação em fases

**Complexidade Geral:** 🔴 **ALTA** (módulo extenso com múltiplas integrações)

**Tempo Estimado:** 4-6 meses (desenvolvimento completo) ou 1-2 meses por fase

**Recomendação:** Implementar em **fases incrementais**, priorizando funcionalidades de maior valor e menor complexidade.

---

## 📋 1. ANÁLISE DO QUE JÁ EXISTE NO SISTEMA

### ✅ **Módulos Já Implementados (Base Sólida)**

#### 1.1 **Estrutura de Usuários e Organização**
- ✅ `adms_users` - Cadastro completo de usuários/colaboradores
- ✅ `adms_positions` - Cargos e funções
- ✅ `adms_departments` - Departamentos
- ✅ `adms_branches` - Filiais
- ✅ `adms_users_departments` - Relacionamento usuário-departamento
- ✅ `adms_users_access_levels` - Níveis de acesso e permissões
- ✅ Hierarquia (immediate_supervisor) já implementada
- ✅ Organograma básico (`OrganizationChart` controller)

**Impacto:** Base sólida para módulo de Gestão de Pessoas ✅

#### 1.2 **Módulo de Treinamentos (T&D)**
- ✅ `adms_trainings` - Cadastro de treinamentos
- ✅ `adms_training_positions` - Treinamentos por cargo
- ✅ `adms_training_users` - Inscrições e aplicações
- ✅ `adms_training_applications` - Controle de presença
- ✅ Matriz de treinamentos
- ✅ Dashboard de treinamentos
- ✅ Notificações automáticas
- ✅ Histórico de treinamentos

**Impacto:** **80% do módulo T&D já está pronto!** ✅

#### 1.3 **Módulo de Avaliações**
- ✅ `adms_evaluation_models` - Modelos de avaliação
- ✅ `adms_evaluation_questions` - Perguntas
- ✅ `adms_evaluation_answers` - Respostas
- ✅ `adms_evaluation_assignments` - Atribuições
- ✅ `adms_evaluation_attempts` - Tentativas
- ✅ Avaliações 360° (estrutura suporta)
- ✅ Histórico de avaliações

**Impacto:** **Base para Gestão de Desempenho já existe!** ✅

#### 1.4 **Módulo de PDI (Recém Criado)**
- ✅ `adms_pdi_plans` - Planos PDI
- ✅ `adms_pdi_actions` - Ações de desenvolvimento
- ✅ `adms_pdi_competencies` - Competências
- ✅ `adms_pdi_goals` - Metas
- ✅ `adms_pdi_feedbacks` - Feedbacks

**Impacto:** **PDI já está estruturado!** ✅

#### 1.5 **Sistema de Permissões**
- ✅ `adms_access_levels` - Níveis de acesso
- ✅ `adms_access_levels_pages` - Permissões por página
- ✅ `adms_pages` - Sistema de rotas
- ✅ `MenuPermissionUserRepository` - Verificação de permissões
- ✅ `ButtonPermissionUserRepository` - Permissões de botões
- ✅ Suporte a Super Admin (nível 1)
- ✅ Permissões por departamento (exemplo: CRM)

**Impacto:** **Sistema de permissões robusto!** ✅

#### 1.6 **Infraestrutura Técnica**
- ✅ Sistema de rotas (`PageController`, `LoadPageAdmAccessLevel`)
- ✅ Padrão MVC bem definido
- ✅ Repositories para acesso a dados
- ✅ Services para lógica de negócio
- ✅ Migrations (Phinx) para banco de dados
- ✅ Seeds para dados iniciais
- ✅ Sistema de notificações
- ✅ Upload de arquivos
- ✅ Geração de PDFs (mPDF, DomPDF)
- ✅ Integração com WhatsApp
- ✅ Dashboard builder (pode ser usado para People Analytics)

**Impacto:** **Infraestrutura pronta para expansão!** ✅

---

## 🆕 2. O QUE PRECISA SER CRIADO

### 2.1 **Medicina & Segurança do Trabalho (SST)** 🔴 ALTA COMPLEXIDADE

#### Tabelas Necessárias:
```sql
- adms_sst_risks (riscos por cargo/setor)
- adms_sst_exams (exames médicos)
- adms_sst_asos (ASO - Admissional, Periódico, etc)
- adms_sst_ppra_pgr (PPRA/PGR/PCMSO)
- adms_sst_epis (EPIs)
- adms_sst_epi_deliveries (entrega/devolução)
- adms_sst_doctors (médicos)
- adms_sst_occupational_programs (programas ocupacionais)
- adms_sst_esocial_events (integração eSocial)
```

#### Controllers Necessários:
- `SstController` (dashboard)
- `SstRiskController` (riscos)
- `SstExamController` (exames)
- `SstAsoController` (ASOs)
- `SstEpiController` (EPIs)
- `SstEpiDeliveryController` (entregas)
- `SstDoctorController` (médicos)
- `SstProgramController` (PPRA/PGR/PCMSO)
- `SstEsocialController` (integração eSocial)

#### Complexidade:
- 🔴 **ALTA** - Requer conhecimento de legislação trabalhista
- 🔴 **ALTA** - Integração eSocial (S-2210, S-2220, S-2240) é complexa
- 🟡 **MÉDIA** - Controle de EPIs e entregas
- 🟢 **BAIXA** - Cadastros básicos (médicos, riscos)

**Estimativa:** 3-4 semanas

---

### 2.2 **Gestão de Desempenho** 🟢 BAIXA COMPLEXIDADE (Base Existe)

#### Tabelas Necessárias:
```sql
- adms_performance_reviews (avaliações 90°, 180°, 360°)
- adms_competencies (competências)
- adms_competency_matrix (matriz por cargo)
- adms_goals (metas/OKRs)
- adms_feedbacks (feedback contínuo)
```

**Nota:** PDI já tem estrutura similar, pode reutilizar!

#### Controllers Necessários:
- `PerformanceController` (avaliações)
- `CompetencyController` (competências)
- `GoalController` (metas/OKRs)
- `FeedbackController` (feedbacks)
- `PerformanceDashboardController` (dashboard)
- `NineBoxMatrixController` (matriz 9BOX)

#### Complexidade:
- 🟢 **BAIXA** - Base de avaliações já existe
- 🟢 **BAIXA** - PDI já tem estrutura de competências e metas
- 🟡 **MÉDIA** - Matriz 9BOX (visualização)
- 🟡 **MÉDIA** - Cálculo de avaliações 360°

**Estimativa:** 2-3 semanas

---

### 2.3 **Treinamentos (T&D)** ✅ JÁ EXISTE (80%)

#### O Que Falta:
- 🟡 Trilhas de aprendizagem (estrutura existe, precisa melhorar)
- 🟡 E-learning/LMS (não existe)
- 🟢 Certificados (estrutura existe, precisa melhorar)
- 🟢 Indicadores (existe, pode melhorar)

**Estimativa:** 1-2 semanas (aprimoramentos)

---

### 2.4 **Cargos & Salários / Estrutura Organizacional** 🟡 MÉDIA COMPLEXIDADE

#### Tabelas Necessárias:
```sql
- adms_job_roles (cargos - já existe como positions)
- adms_salary_ranges (faixas salariais)
- adms_career_tracks (trilhas de carreira)
- adms_career_levels (níveis de carreira)
- adms_promotions (promoções)
- adms_salary_history (histórico salarial)
```

#### Controllers Necessários:
- `JobRoleController` (pode reutilizar Positions)
- `SalaryController` (faixas salariais)
- `CareerTrackController` (trilhas)
- `PromotionController` (promoções)
- `OrgStructureController` (melhorar organograma existente)

#### Complexidade:
- 🟢 **BAIXA** - Cargos já existem (positions)
- 🟡 **MÉDIA** - Faixas salariais e histórico
- 🟡 **MÉDIA** - Trilhas de carreira
- 🟢 **BAIXA** - Organograma (já existe básico)

**Estimativa:** 2-3 semanas

---

### 2.5 **Clima Organizacional & Engajamento** 🟡 MÉDIA COMPLEXIDADE

#### Tabelas Necessárias:
```sql
- adms_surveys (pesquisas)
- adms_survey_questions (perguntas)
- adms_survey_responses (respostas)
- adms_survey_campaigns (campanhas)
- adms_engagement_indicators (indicadores)
- adms_pulse_surveys (pulse surveys)
```

#### Controllers Necessários:
- `SurveyController` (pesquisas)
- `SurveyResponseController` (respostas)
- `EngagementController` (engajamento)
- `EngagementDashboardController` (dashboard)
- `NpsController` (NPS interno)

#### Complexidade:
- 🟡 **MÉDIA** - Estrutura similar a avaliações (pode reutilizar)
- 🟡 **MÉDIA** - Cálculo de NPS e indicadores
- 🟢 **BAIXA** - Pulse surveys (simples)
- 🟡 **MÉDIA** - Mapa de engajamento (visualização)

**Estimativa:** 2-3 semanas

---

### 2.6 **Portal do Colaborador** 🟢 BAIXA COMPLEXIDADE

#### Funcionalidades:
- 🟢 Solicitação de férias (estrutura existe)
- 🟡 Consultar holerite (integração com folha)
- 🟢 Alteração cadastral (já existe em users)
- 🟡 Abertura de chamados (novo)
- 🟡 Registro de ponto (novo, opcional)
- 🟢 Comunicações internas (informativos já existe)

#### Controllers Necessários:
- `PortalController` (dashboard do colaborador)
- `RequestController` (solicitações)
- `TicketController` (chamados)
- `TimeClockController` (ponto - opcional)

#### Complexidade:
- 🟢 **BAIXA** - Maioria já existe ou é simples
- 🟡 **MÉDIA** - Integração com folha de pagamento
- 🔴 **ALTA** - Registro de ponto (se implementar)

**Estimativa:** 2-3 semanas (sem ponto)

---

### 2.7 **People Analytics / BI** 🟢 BAIXA COMPLEXIDADE (Dashboard Existe)

#### Funcionalidades:
- 🟢 Dashboard builder já existe!
- 🟢 KPIs podem ser criados no dashboard
- 🟡 Relatórios avançados (estrutura existe)
- 🟡 Previsões de turnover (IA - opcional)

#### Controllers Necessários:
- `PeopleAnalyticsController` (dashboard)
- `PeopleReportsController` (relatórios)
- `TurnoverPredictionController` (IA - opcional)

#### Complexidade:
- 🟢 **BAIXA** - Dashboard builder já existe
- 🟡 **MÉDIA** - Cálculo de KPIs específicos
- 🔴 **ALTA** - IA para previsões (opcional)

**Estimativa:** 1-2 semanas (sem IA)

---

### 2.8 **Gestão de Benefícios** 🟡 MÉDIA COMPLEXIDADE

#### Tabelas Necessárias:
```sql
- adms_benefits (benefícios)
- adms_benefit_categories (categorias)
- adms_benefit_assignments (atribuições)
- adms_benefit_providers (fornecedores)
- adms_benefit_enrollments (inscrições)
- adms_benefit_reimbursements (reembolsos)
```

#### Controllers Necessários:
- `BenefitController` (benefícios)
- `BenefitAssignmentController` (atribuições)
- `BenefitProviderController` (fornecedores)
- `BenefitEnrollmentController` (inscrições)
- `BenefitReimbursementController` (reembolsos)

#### Complexidade:
- 🟡 **MÉDIA** - Estrutura de cadastros
- 🟡 **MÉDIA** - Integração com fornecedores
- 🟡 **MÉDIA** - Open Enrollment (período de escolha)
- 🟢 **BAIXA** - Relatórios de custos

**Estimativa:** 2-3 semanas

---

## 📊 3. MATRIZ DE VIABILIDADE

| Módulo | Complexidade | Base Existente | Prioridade | Tempo Estimado |
|--------|-------------|----------------|------------|----------------|
| **SST** | 🔴 Alta | 🟢 Boa (users, positions) | 🟡 Média | 3-4 semanas |
| **Desempenho** | 🟢 Baixa | ✅ Excelente (avaliações, PDI) | 🔴 Alta | 2-3 semanas |
| **T&D** | 🟢 Baixa | ✅ 80% Pronto | 🟡 Média | 1-2 semanas |
| **Cargos & Salários** | 🟡 Média | ✅ Boa (positions) | 🟡 Média | 2-3 semanas |
| **Clima** | 🟡 Média | ✅ Boa (avaliações) | 🟡 Média | 2-3 semanas |
| **Portal** | 🟢 Baixa | ✅ Boa (users, informativos) | 🔴 Alta | 2-3 semanas |
| **Analytics** | 🟢 Baixa | ✅ Excelente (dashboards) | 🟡 Média | 1-2 semanas |
| **Benefícios** | 🟡 Média | 🟢 Boa (users) | 🟡 Média | 2-3 semanas |

---

## 🎯 4. RECOMENDAÇÕES DE IMPLEMENTAÇÃO

### **FASE 1: Fundação (4-6 semanas)** 🔴 PRIORIDADE ALTA

**Objetivo:** Criar base sólida e funcionalidades de maior valor

1. **Gestão de Desempenho** (2-3 semanas)
   - ✅ Base já existe (avaliações, PDI)
   - ✅ Alto valor para RH
   - ✅ Baixa complexidade

2. **Portal do Colaborador** (2-3 semanas)
   - ✅ Melhora experiência do usuário
   - ✅ Maioria já existe
   - ✅ Alto impacto

**Resultado:** Sistema funcional com alto valor

---

### **FASE 2: Expansão (4-6 semanas)** 🟡 PRIORIDADE MÉDIA

3. **People Analytics** (1-2 semanas)
   - ✅ Dashboard builder já existe
   - ✅ Alto valor estratégico
   - ✅ Baixa complexidade

4. **Cargos & Salários** (2-3 semanas)
   - ✅ Base existe (positions)
   - ✅ Importante para RH
   - ✅ Média complexidade

5. **Clima Organizacional** (2-3 semanas)
   - ✅ Base existe (avaliações)
   - ✅ Valor estratégico
   - ✅ Média complexidade

**Resultado:** Sistema completo de gestão de pessoas

---

### **FASE 3: Especialização (4-6 semanas)** 🟢 PRIORIDADE BAIXA

6. **SST** (3-4 semanas)
   - ⚠️ Requer conhecimento técnico
   - ⚠️ Integração eSocial complexa
   - ✅ Alto valor legal/compliance

7. **Benefícios** (2-3 semanas)
   - ✅ Importante para RH
   - ✅ Média complexidade

8. **Aprimoramentos T&D** (1-2 semanas)
   - ✅ Já está 80% pronto
   - ✅ Melhorias incrementais

**Resultado:** Sistema completo e especializado

---

## ⚠️ 5. RISCOS E DESAFIOS

### 🔴 **Riscos Altos**

1. **Integração eSocial (SST)**
   - Complexidade técnica alta
   - Requer conhecimento de legislação
   - Pode precisar de consultoria externa
   - **Mitigação:** Implementar sem integração inicial, adicionar depois

2. **Registro de Ponto (Portal)**
   - Requer integração com hardware/biometria
   - Complexidade alta
   - **Mitigação:** Deixar para fase futura ou integrar com sistema externo

3. **IA para Previsões (Analytics)**
   - Requer conhecimento de ML/IA
   - Complexidade muito alta
   - **Mitigação:** Deixar para fase futura ou usar APIs externas

### 🟡 **Riscos Médios**

1. **Volume de Dados**
   - Muitas tabelas e relacionamentos
   - **Mitigação:** Usar índices adequados, otimizar queries

2. **Permissões Complexas**
   - Muitos níveis de acesso
   - **Mitigação:** Sistema de permissões já existe e é robusto

3. **Integrações Externas**
   - Fornecedores de benefícios
   - **Mitigação:** Criar estrutura flexível para integrações

---

## ✅ 6. PONTOS FORTES DO PROJETO

1. ✅ **Base Sólida:** 60-80% da estrutura já existe
2. ✅ **Padrão Consistente:** MVC bem definido
3. ✅ **Sistema de Permissões:** Robusto e flexível
4. ✅ **Infraestrutura:** Pronta para expansão
5. ✅ **Dashboard Builder:** Pode ser usado para Analytics
6. ✅ **Módulos Existentes:** Treinamentos, Avaliações, PDI já funcionam

---

## 📈 7. ESTIMATIVA TOTAL

### **Cenário Otimista (Fases 1 e 2):**
- **Tempo:** 8-12 semanas (2-3 meses)
- **Funcionalidades:** 80% do módulo completo
- **Valor:** Alto (cobre principais necessidades)

### **Cenário Realista (Todas as Fases):**
- **Tempo:** 12-18 semanas (3-4.5 meses)
- **Funcionalidades:** 100% do módulo completo
- **Valor:** Máximo (sistema completo)

### **Cenário com IA e Integrações Avançadas:**
- **Tempo:** 20-24 semanas (5-6 meses)
- **Funcionalidades:** 100% + IA + Integrações
- **Valor:** Máximo + Diferenciais

---

## 🎯 8. CONCLUSÃO E RECOMENDAÇÃO FINAL

### ✅ **VIABILIDADE: ALTA**

O projeto é **totalmente viável** porque:

1. ✅ **60-80% da base já existe** (usuários, treinamentos, avaliações, PDI)
2. ✅ **Padrão arquitetural consistente** facilita expansão
3. ✅ **Sistema de permissões robusto** suporta complexidade
4. ✅ **Infraestrutura pronta** (rotas, repositories, services)
5. ✅ **Dashboard builder** pode ser usado para Analytics

### 📋 **RECOMENDAÇÃO:**

**Implementar em 3 fases incrementais:**

1. **FASE 1 (2-3 meses):** Desempenho + Portal + Analytics
2. **FASE 2 (2-3 meses):** Cargos & Salários + Clima + Benefícios
3. **FASE 3 (2-3 meses):** SST + Aprimoramentos + Integrações

**Total:** 6-9 meses para sistema completo

**Alternativa Rápida:** Fases 1 e 2 (4-6 meses) cobrem 90% das necessidades

---

## 📝 9. PRÓXIMOS PASSOS SUGERIDOS

1. ✅ **Validar prioridades** com stakeholders
2. ✅ **Definir escopo da Fase 1**
3. ✅ **Criar migrations** para novas tabelas
4. ✅ **Criar repositories** base
5. ✅ **Implementar controllers** seguindo padrão existente
6. ✅ **Criar views** reutilizando componentes existentes
7. ✅ **Testes incrementais** por módulo
8. ✅ **Documentação** de cada funcionalidade

---

**Documento criado em:** 02/12/2025  
**Última atualização:** 02/12/2025

