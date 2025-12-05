# Módulo de PDI (Plano de Desenvolvimento Individual) - Proposta

## 📋 Análise de Ferramentas de Mercado

### Ferramentas Analisadas:
1. **Feedz** - Plataforma de gestão de pessoas com PDIs personalizados
2. **MarQ HR** - Software de gestão de desempenho com PDIs integrados
3. **LG lugar de gente** - Módulo Gen.te Desenvolve - PDI

### Funcionalidades Principais Identificadas:
- ✅ Criação de PDIs personalizados
- ✅ Definição de metas claras e mensuráveis
- ✅ Acompanhamento do progresso em tempo real
- ✅ Integração com avaliações de desempenho
- ✅ Feedback contínuo entre gestor e colaborador
- ✅ Ações de desenvolvimento (treinamentos, cursos, mentoring)
- ✅ Competências técnicas e comportamentais
- ✅ Histórico e relatórios de evolução

---

## 🏗️ Estrutura do Módulo Proposto

### 1. **Tabelas do Banco de Dados**

#### `adms_pdi_plans` (Planos PDI)
- Informações principais do plano
- Vinculação com colaborador e gestor
- Período de vigência
- Status (rascunho, ativo, concluído, cancelado)
- Integração com avaliações

#### `adms_pdi_actions` (Ações de Desenvolvimento)
- Ações específicas para desenvolvimento
- Tipos: treinamento, curso, mentoring, projeto, leitura, outros
- Controle de horas e progresso
- Integração com módulo de treinamentos

#### `adms_pdi_competencies` (Competências)
- Competências técnicas e comportamentais
- Níveis atual e alvo (1-5)
- Categorização por tipo

#### `adms_pdi_goals` (Metas)
- Metas mensuráveis e com prazos
- Acompanhamento de valores atual vs. alvo
- Status de alcance

#### `adms_pdi_feedbacks` (Feedbacks)
- Feedback contínuo entre gestor e colaborador
- Tipos: geral, por ação, marco, final
- Histórico completo

---

## 📁 Estrutura de Arquivos

### Controllers (`app/adms/Controllers/pdi/`)
```
- CreatePdiPlan.php          # Criar novo PDI
- UpdatePdiPlan.php          # Editar PDI
- ViewPdiPlan.php            # Visualizar PDI completo
- ListPdiPlans.php            # Listar PDIs (gestor/colaborador)
- MyPdiPlans.php              # Meus PDIs (colaborador)
- DeletePdiPlan.php           # Excluir PDI
- ApprovePdiPlan.php          # Aprovar PDI (gestor)
- AddPdiAction.php            # Adicionar ação
- UpdatePdiAction.php         # Atualizar ação
- DeletePdiAction.php         # Excluir ação
- AddPdiCompetency.php        # Adicionar competência
- UpdatePdiCompetency.php     # Atualizar competência
- AddPdiGoal.php              # Adicionar meta
- UpdatePdiGoal.php           # Atualizar meta
- AddPdiFeedback.php          # Adicionar feedback
- PdiDashboard.php            # Dashboard de acompanhamento
- PdiReports.php              # Relatórios e análises
```

### Models/Repository (`app/adms/Models/Repository/`)
```
- PdiPlansRepository.php       # CRUD de planos
- PdiActionsRepository.php     # CRUD de ações
- PdiCompetenciesRepository.php # CRUD de competências
- PdiGoalsRepository.php       # CRUD de metas
- PdiFeedbacksRepository.php   # CRUD de feedbacks
```

### Views (`app/adms/Views/pdi/`)
```
- create.php                   # Formulário de criação
- edit.php                     # Formulário de edição
- view.php                     # Visualização completa
- list.php                     # Listagem (gestor)
- my-plans.php                 # Meus PDIs (colaborador)
- dashboard.php                # Dashboard de acompanhamento
- reports.php                  # Relatórios
```

---

## 🎯 Funcionalidades Principais

### 1. **Criação de PDI**
- Formulário completo com:
  - Informações básicas (título, período, descrição)
  - Objetivo de carreira
  - Nível atual vs. alvo
  - Vinculação com avaliação de desempenho (opcional)
  - Seleção de gestor responsável

### 2. **Gestão de Ações**
- Adicionar ações de desenvolvimento:
  - **Treinamentos**: Integração com módulo de treinamentos
  - **Cursos**: Cursos externos ou internos
  - **Mentoring**: Programa de mentoria
  - **Projetos**: Projetos específicos
  - **Leituras**: Materiais de estudo
  - **Outros**: Ações personalizadas
- Controle de:
  - Datas (início e término)
  - Horas (esperadas vs. realizadas)
  - Progresso percentual
  - Status
  - Observações

### 3. **Competências**
- Definir competências a desenvolver:
  - Técnicas
  - Comportamentais
  - Liderança
- Níveis de 1 a 5 (atual e alvo)
- Descrição detalhada

### 4. **Metas**
- Metas mensuráveis com:
  - Título e descrição
  - Valor alvo e atual
  - Unidade de medida
  - Prazo
  - Status de alcance

### 5. **Feedbacks**
- Sistema de feedback contínuo:
  - Feedback geral do plano
  - Feedback por ação específica
  - Feedback em marcos importantes
  - Feedback final
- Histórico completo de interações

### 6. **Acompanhamento**
- Dashboard com:
  - Progresso geral do PDI
  - Status das ações
  - Competências desenvolvidas
  - Metas alcançadas
  - Timeline visual
  - Gráficos de evolução

### 7. **Relatórios**
- Relatórios disponíveis:
  - PDIs por colaborador
  - PDIs por gestor
  - Taxa de conclusão de ações
  - Competências mais desenvolvidas
  - Tempo médio de desenvolvimento
  - Análise comparativa

---

## 🔗 Integrações

### 1. **Módulo de Avaliações**
- Vincular PDI a avaliações de desempenho
- Importar competências avaliadas
- Usar resultados como base para o PDI

### 2. **Módulo de Treinamentos**
- Vincular ações a treinamentos cadastrados
- Sincronizar conclusão de treinamentos
- Contabilizar horas automaticamente

### 3. **Módulo de Usuários**
- Seleção de colaboradores
- Hierarquia gestor-colaborador
- Permissões por nível de acesso

---

## 📊 Fluxo de Trabalho

1. **Colaborador ou Gestor cria o PDI**
   - Status: `draft` (rascunho)

2. **Preenchimento do PDI**
   - Adicionar ações
   - Definir competências
   - Estabelecer metas
   - Adicionar observações

3. **Aprovação**
   - Gestor revisa e aprova
   - Status muda para `active` (ativo)

4. **Execução**
   - Colaborador executa ações
   - Atualiza progresso
   - Registra horas realizadas
   - Adiciona feedbacks

5. **Acompanhamento**
   - Reuniões periódicas
   - Feedbacks contínuos
   - Ajustes quando necessário

6. **Conclusão**
   - Status muda para `completed` (concluído)
   - Avaliação final
   - Criação de novo PDI para próximo período

---

## 🎨 Interface do Usuário

### Página Principal (Colaborador)
- Cards com PDIs ativos
- Progresso visual (barras de progresso)
- Próximas ações pendentes
- Feedbacks recentes

### Página Principal (Gestor)
- Lista de PDIs da equipe
- Filtros por status, período, colaborador
- Indicadores de progresso
- Alertas de ações atrasadas

### Visualização de PDI
- Abas organizadas:
  - **Visão Geral**: Informações principais
  - **Ações**: Lista de ações com progresso
  - **Competências**: Matriz de competências
  - **Metas**: Metas e status
  - **Feedbacks**: Histórico de feedbacks
  - **Timeline**: Linha do tempo visual

---

## 📈 Métricas e KPIs

- Taxa de conclusão de PDIs
- Tempo médio de desenvolvimento
- Número de ações concluídas
- Competências desenvolvidas
- Satisfação com feedbacks
- Adesão ao programa de PDI

---

## 🔐 Permissões

- **Colaborador**: Ver e editar próprios PDIs
- **Gestor**: Ver e gerenciar PDIs da equipe
- **RH**: Acesso total para relatórios
- **Super Admin**: Acesso completo

---

## 🚀 Próximos Passos

1. ✅ Criar migration das tabelas
2. ⏳ Criar repositories
3. ⏳ Criar controllers
4. ⏳ Criar views
5. ⏳ Integrar com módulo de treinamentos
6. ⏳ Integrar com módulo de avaliações
7. ⏳ Criar dashboard
8. ⏳ Criar relatórios
9. ⏳ Testes

---

## 📝 Observações

- O módulo segue o padrão arquitetural do sistema existente
- Utiliza o mesmo sistema de permissões
- Integra-se com módulos existentes (avaliações, treinamentos)
- Interface responsiva e moderna
- Suporte a notificações para lembretes e atualizações

