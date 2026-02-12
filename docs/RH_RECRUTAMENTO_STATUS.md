# 📊 Status do Módulo de Recrutamento/Currículos - Gestão de Pessoas

**Última atualização:** 12/02/2026

---

## ✅ **O QUE JÁ FOI IMPLEMENTADO**

### 1. **Banco de Dados (Migrations)** ✅

#### Tabelas Criadas:
- ✅ `rh_candidatos` - Cadastro de candidatos/currículos
  - Dados pessoais (nome, email, telefone, cidade, estado)
  - Origem (email, whatsapp, form_trabalhe_conosco, manual)
  - Status do processo (recebido, em_entrevista, reprovado, banco_talentos, contratado, anonimizado)
  - Campos LGPD (termo_id, consentimento_id, status, data_consentimento, data_expiracao, motivo_anonimizacao)
  - Auditoria (created_at, updated_at)

- ✅ `rh_candidatos_anexos` - Anexos/currículos em PDF/DOC/DOCX
  - Relacionamento com `rh_candidatos`
  - Tipo de anexo (curriculo, documento, etc.)
  - Caminho do arquivo físico
  - Nome original

- ✅ `lgpd_politicas_retencao` - Políticas de retenção de dados
  - Contexto (curriculo_nao_aproveitado, banco_talentos)
  - Prazo em meses (6 para não aproveitados, 12 para banco de talentos)
  - Status (Ativo/Inativo)

**Status:** ✅ Migrations criadas e rodadas

---

### 2. **Repositories (Models)** ✅

#### `RhCandidatosRepository.php` - CRUD Completo
- ✅ `create(array $data)` - Cria candidato com cálculo automático de `lgpd_data_expiracao`
- ✅ `update(int $id, array $data)` - Atualiza candidato e recalcula retenção se `status_processo` mudar
- ✅ `delete(int $id)` - Deleta candidato e arquivos físicos
- ✅ `getById(int $id)` - Busca candidato por ID
- ✅ `getAll(array $filters, int $page, int $perPage)` - Lista paginada com filtros
- ✅ `addAnexo(int $candidatoId, array $data)` - Adiciona anexo
- ✅ `getAnexosByCandidato(int $candidatoId)` - Lista anexos de um candidato
- ✅ `aplicarPoliticaRetencao()` - Aplica política de retenção/anomização
- ✅ `anonimizarCandidato(array $candidato)` - Anonimiza dados pessoais
- ✅ `getDashboardStats()` - Estatísticas para dashboard
- ✅ `resolveContextoRetencao(string $statusProcesso)` - Mapeia status para contexto LGPD
- ✅ `buscarPrazoMesesPorContexto(string $contexto)` - Busca prazo de retenção

**Status:** ✅ Repository completo com todas as funcionalidades

---

### 3. **Controllers** ✅

#### CRUD Completo:
- ✅ `RhCandidatos.php` - Listagem com filtros e paginação
- ✅ `RhCandidatosCreate.php` - Cadastro de candidato + upload de currículo
- ✅ `RhCandidatosView.php` - Visualização detalhada
- ✅ `RhCandidatosEdit.php` - Edição (com validação de senha + justificativa)
- ✅ `RhCandidatosDelete.php` - Exclusão (com validação de senha + justificativa)
- ✅ `RhKpiDashboard.php` - Dashboard de KPIs de recrutamento

**Status:** ✅ Todos os controllers implementados

---

### 4. **Views** ✅

#### Telas Implementadas:
- ✅ `list.php` - Listagem com filtros (nome, email, origem, status_processo)
  - Badges de status do processo
  - Badges de status LGPD (Ativo, Próx. expiração, Vencido, Anonimizado)
  - Paginação
  - Modal de exclusão com senha + justificativa

- ✅ `create.php` - Formulário de cadastro
  - Campos: nome, email, telefone, cidade, estado, origem, status_processo, observações
  - Upload de currículo (PDF, DOC, DOCX)
  - Validação de campos obrigatórios

- ✅ `edit.php` - Formulário de edição
  - Mesmos campos do create
  - Upload de novo anexo (adiciona, não substitui)
  - Lista de anexos existentes
  - Validação de senha + justificativa

- ✅ `view.php` - Visualização detalhada
  - Todos os dados do candidato
  - Informações LGPD (status, datas, motivo de anonimização)
  - Lista de anexos com download
  - Botão "Log de Alterações" (se houver logs)
  - Botões de ação (Editar, Excluir)

- ✅ `kpiDashboard.php` - Dashboard de KPIs
  - Cards com totais (candidatos, por status, por origem, LGPD)
  - Gráficos Chart.js (distribuição por status, distribuição por origem)

**Status:** ✅ Todas as views implementadas

---

### 5. **Services** ✅

#### `CandidateRetentionService.php`
- ✅ `ensureUpdated(bool $force, ?int $minIntervalSeconds)` - Executa política de retenção
  - Cache em `storage/cache/system/candidate_retention_last_run.json`
  - Intervalo padrão: 24 horas
  - Integrado no `Dashboard.php` (executa no primeiro acesso do dia)

**Status:** ✅ Service implementado e integrado

---

### 6. **Integrações** ✅

#### Log de Alterações:
- ✅ `LogAlteracaoService` - Registra INSERT, UPDATE, DELETE
- ✅ `LogResumoService` - Exibe botão "Log de Alterações" nas views
- ✅ `LogJustificativasRepository` - Vincula justificativas a logs de ações sensíveis

#### Ações Sensíveis:
- ✅ `SensitiveActionService` - Valida senha + justificativa para edição/exclusão

#### Sistema de Arquivos:
- ✅ Upload de currículos em `public/adms/uploads/rh_candidatos/{candidato_id}/`
- ✅ Exclusão física de arquivos durante anonimização
- ✅ Download seguro via `FileServer` (se implementado)

**Status:** ✅ Integrações completas

---

### 7. **Rotas e Menu** ✅

#### Rotas:
- ✅ `rh-candidatos` - Listagem
- ✅ `rh-candidatos-create` - Cadastro
- ✅ `rh-candidatos-view/{id}` - Visualização
- ✅ `rh-candidatos-edit/{id}` - Edição
- ✅ `rh-candidatos-delete` - Exclusão (AJAX)
- ✅ `rh-kpi-dashboard` - Dashboard

#### Menu:
- ✅ Páginas adicionadas ao seed `AddAdmsPages.php` (grupo 30: "Gestão de Pessoas / Currículos")
- ✅ Permissões sincronizadas via `SyncAccessLevelsPages`

**Status:** ✅ Rotas e menu configurados

---

## ⏳ **O QUE ESTÁ FALTANDO / PRÓXIMOS PASSOS**

### 🔴 **PRIORIDADE ALTA**

#### 1. **Gestão de Vagas** (2-3 semanas)
**Status:** Não implementado

**O que fazer:**
- [ ] Criar migration `rh_vagas`
  - Campos: titulo, descricao, area, cargo_id, tipo_contrato, salario_min, salario_max, status (aberta/fechada), data_abertura, data_fechamento, requisitos, beneficios
- [ ] Criar migration `rh_candidatos_vagas` (relacionamento N:N)
  - Campos: rh_candidato_id, rh_vaga_id, status (candidatado, em_analise, aprovado, reprovado), data_candidatura, observacoes
- [ ] Repository `RhVagasRepository.php`
- [ ] Controllers: `RhVagas`, `RhVagasCreate`, `RhVagasView`, `RhVagasEdit`, `RhVagasDelete`
- [ ] Views: list, create, edit, view
- [ ] Vincular candidatos a vagas na view do candidato
- [ ] Filtro de candidatos por vaga na listagem

**Complexidade:** 🟡 Média  
**Valor:** 🔴 Alto

---

#### 2. **Gestão de Entrevistas** (2-3 semanas)
**Status:** Não implementado

**O que fazer:**
- [ ] Criar migration `rh_entrevistas`
  - Campos: rh_candidato_id, rh_vaga_id (opcional), tipo (presencial, online, telefone), entrevistador_id (adms_user_id), data_hora, local, observacoes, resultado (aprovado, reprovado, pendente), feedback
- [ ] Repository `RhEntrevistasRepository.php`
- [ ] Controllers: `RhEntrevistas`, `RhEntrevistasCreate`, `RhEntrevistasView`, `RhEntrevistasEdit`, `RhEntrevistasDelete`
- [ ] Views: list, create, edit, view
- [ ] Calendário de entrevistas (pode usar biblioteca JS)
- [ ] Notificações para entrevistador e candidato
- [ ] Histórico de entrevistas por candidato na view

**Complexidade:** 🟡 Média  
**Valor:** 🔴 Alto

---

#### 3. **Banco de Talentos** (1-2 semanas)
**Status:** Parcialmente implementado (status `banco_talentos` existe)

**O que fazer:**
- [ ] Melhorar visualização de candidatos em banco de talentos
- [ ] Adicionar campos de classificação:
  - [ ] Área de interesse
  - [ ] Competências técnicas
  - [ ] Nível técnico (junior, pleno, senior)
  - [ ] Potencial (baixo, médio, alto)
  - [ ] Score/nota geral
- [ ] Filtros avançados no banco de talentos
- [ ] Busca inteligente (por competências, área, etc.)
- [ ] Alertas automáticos quando nova vaga corresponde ao perfil

**Complexidade:** 🟡 Média  
**Valor:** 🔴 Alto

---

### 🟡 **PRIORIDADE MÉDIA**

#### 4. **Integração com n8n** (1 semana)
**Status:** Planejado, não implementado

**O que fazer:**
- [ ] Criar endpoint API `api/rh/candidatos/receive`
  - Recebe dados de currículo via POST (JSON)
  - Valida dados
  - Cria candidato automaticamente
  - Retorna status (sucesso/erro)
- [ ] Autenticação via token (API key)
- [ ] Log de recebimentos via n8n
- [ ] Webhook para notificar n8n de status de candidato

**Complexidade:** 🟢 Baixa  
**Valor:** 🟡 Médio (automatização)

---

#### 5. **Melhorias no Dashboard** (1 semana)
**Status:** Dashboard básico existe

**O que fazer:**
- [ ] Adicionar gráfico de evolução temporal (candidatos por mês)
- [ ] Taxa de conversão (candidatos -> entrevistas -> aprovados -> contratados)
- [ ] Tempo médio de processo seletivo
- [ ] Origem mais eficaz (qual origem traz mais candidatos aprovados)
- [ ] Filtros por período (mês, trimestre, ano)
- [ ] Exportação de relatórios (PDF/Excel)

**Complexidade:** 🟢 Baixa  
**Valor:** 🟡 Médio

---

#### 6. **Auto-matching de Candidatos e Vagas** (2 semanas)
**Status:** Não implementado

**O que fazer:**
- [ ] Algoritmo de matching baseado em:
  - [ ] Competências do candidato vs requisitos da vaga
  - [ ] Experiência vs experiência requerida
  - [ ] Localização vs localização da vaga
  - [ ] Faixa salarial
- [ ] Score de compatibilidade (0-100%)
- [ ] Sugestões automáticas na view da vaga
- [ ] Sugestões automáticas na view do candidato
- [ ] Notificações para RH quando há match alto

**Complexidade:** 🔴 Alta (algoritmo de matching)  
**Valor:** 🟡 Médio (pode ser feito depois)

---

### 🟢 **PRIORIDADE BAIXA / FUTURO**

#### 7. **IA para Leitura de Currículos** (Futuro)
**Status:** Não implementado

**O que fazer:**
- [ ] Integração com API de IA (OpenAI, Google Gemini, etc.)
- [ ] Extração automática de dados do PDF/DOC
- [ ] Preenchimento automático do formulário
- [ ] Classificação automática (área, nível, competências)
- [ ] Sugestão de score inicial

**Complexidade:** 🔴 Alta  
**Valor:** 🟢 Baixo (nice to have)

---

#### 8. **Auto-ranking de Candidatos** (Futuro)
**Status:** Não implementado

**O que fazer:**
- [ ] Algoritmo de ranking baseado em múltiplos fatores
- [ ] Peso para cada fator (experiência, educação, competências, entrevistas)
- [ ] Ranking automático por vaga
- [ ] Visualização de ranking na view da vaga

**Complexidade:** 🔴 Alta  
**Valor:** 🟢 Baixo (pode ser feito depois)

---

## 📊 **RESUMO DO STATUS ATUAL**

### ✅ **Implementado (100%):**
- ✅ Estrutura de banco de dados (migrations)
- ✅ Repository completo com LGPD
- ✅ CRUD completo (Create, Read, Update, Delete)
- ✅ Dashboard básico de KPIs
- ✅ Sistema de anexos (upload/download)
- ✅ Política de retenção/anomização automática
- ✅ Log de alterações
- ✅ Validação de ações sensíveis
- ✅ Rotas e menu

### ⏳ **Faltando (Próximas Fases):**
- ⏳ Gestão de Vagas
- ⏳ Gestão de Entrevistas
- ⏳ Melhorias no Banco de Talentos
- ⏳ Integração n8n
- ⏳ Melhorias no Dashboard
- ⏳ Auto-matching
- ⏳ IA para leitura de currículos (futuro)
- ⏳ Auto-ranking (futuro)

---

## 🎯 **RECOMENDAÇÃO DE PRÓXIMOS PASSOS**

### **FASE 2: Gestão de Vagas e Entrevistas** (4-6 semanas)

**Ordem sugerida:**

1. **Gestão de Vagas** (2-3 semanas) 🔴
   - Base para todo o processo seletivo
   - Permite vincular candidatos a vagas
   - Alta prioridade estratégica

2. **Gestão de Entrevistas** (2-3 semanas) 🔴
   - Complementa o processo seletivo
   - Permite rastrear todo o ciclo do candidato
   - Alto valor para RH

3. **Melhorias no Banco de Talentos** (1-2 semanas) 🟡
   - Aproveita candidatos já cadastrados
   - Melhora a experiência de busca
   - Valor médio-alto

---

## 📝 **NOTAS IMPORTANTES**

1. **LGPD:** O sistema já está preparado para retenção/anomização automática. A política roda diariamente via `CandidateRetentionService`.

2. **Logs:** Todas as ações sensíveis (criar, editar, excluir) são registradas no log de alterações.

3. **Anexos:** Os arquivos são armazenados fisicamente e são deletados durante a anonimização.

4. **Permissões:** O sistema usa o mesmo padrão de permissões do resto do sistema (páginas e botões).

5. **Padrão MVC:** Todo o código segue o padrão MVC já estabelecido no projeto.

---

**Última atualização:** 12/02/2026

