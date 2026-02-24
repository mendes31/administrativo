# Análise do Módulo de Recrutamento - Sugestões de Melhorias

**Data:** 13/02/2026  
**Baseado em:** ATS de mercado (Greenhouse, Lever, Workable, BambooHR, Recruitee)

---

## 📊 Status Atual do Desenvolvimento

### ✅ Funcionalidades Implementadas

#### 1. **Gestão de Candidatos**
- ✅ Cadastro manual de candidatos/currículos
- ✅ Listagem com filtros (nome, email, origem, status)
- ✅ Visualização detalhada do candidato
- ✅ Edição de dados do candidato
- ✅ Sistema de score/classificação
- ✅ Histórico de movimentações (log de alterações)
- ✅ LGPD: gestão de consentimento e anonimização
- ✅ Múltiplas origens (manual, email, WhatsApp, formulário, LinkedIn, Indeed)

#### 2. **Gestão de Vagas**
- ✅ Cadastro de vagas (título, descrição, requisitos, benefícios)
- ✅ Vinculação com área e cargo
- ✅ Informações de salário (min/max, mostrar ou não)
- ✅ Status (aberta, fechada, cancelada)
- ✅ Data limite de inscrição
- ✅ Quantidade de vagas
- ✅ Local de trabalho e jornada

#### 3. **Pipeline Kanban**
- ✅ Visualização em colunas (Candidatado, Em Entrevista, Aprovado, Reprovado, Desistiu)
- ✅ Drag & drop para movimentar candidatos
- ✅ Atualização de status via AJAX
- ✅ Contadores por coluna
- ✅ Permissões por vaga

#### 4. **Entrevistas**
- ✅ Cadastro de entrevistas
- ✅ Tipos (Presencial, Online)
- ✅ Agendamento (data/hora, local, entrevistador)
- ✅ Resultados (Pendente, Agendado, Aprovado, Reprovado)
- ✅ Feedback e observações
- ✅ Sincronização bidirecional com pipeline
- ✅ Listagem com filtros

#### 5. **Dashboard e KPIs**
- ✅ Dashboard de recrutamento
- ✅ Estatísticas por status
- ✅ Estatísticas por origem
- ✅ Resumo LGPD
- ✅ Indicadores de entrevistas

#### 6. **Integrações e Segurança**
- ✅ Sistema de permissões por vaga
- ✅ CSRF protection
- ✅ Logs de alterações
- ✅ LGPD compliance

---

## 🚀 Melhorias Sugeridas (Baseadas em ATS de Mercado)

### 🔴 **PRIORIDADE ALTA** (Impacto imediato na eficiência)

#### 1. **Upload e Parsing de Currículos**
**Status:** ❌ Não implementado  
**Benefício:** Reduz tempo de cadastro manual em 80%

**Sugestão:**
- Upload de PDF/DOC/DOCX
- Extração automática de dados (nome, email, telefone, experiência, formação)
- Sugestão de preenchimento do formulário
- Integração com APIs de parsing (ex: Affinda, ResumeParser.io)

**Implementação:**
```php
// Novo controller: RhCandidatosUpload.php
// Novo serviço: RhResumeParserService.php
// Campo no formulário: upload de arquivo
```

---

#### 2. **Notificações Automáticas por Email**
**Status:** ⚠️ Parcial (sistema de email existe, mas não há notificações automáticas)  
**Benefício:** Melhora comunicação e reduz perda de candidatos

**Sugestões:**
- **Para candidatos:**
  - Confirmação de recebimento do currículo
  - Convite para entrevista (com link de confirmação)
  - Feedback após entrevista
  - Rejeição educada (com feedback opcional)
  
- **Para recrutadores:**
  - Novo candidato aplicou na vaga
  - Entrevista agendada (lembrete 24h antes)
  - Candidato não respondeu há X dias
  - Vaga próxima do prazo de fechamento

**Implementação:**
```php
// Novo serviço: RhNotificationService.php
// Templates de email em: app/adms/Views/emails/rh/
// Eventos: após criar candidato, agendar entrevista, atualizar status
```

---

#### 3. **Avaliação e Scorecard de Candidatos**
**Status:** ⚠️ Parcial (existe campo score, mas sem estrutura de avaliação)  
**Benefício:** Padroniza avaliação e facilita comparação

**Sugestão:**
- Scorecard por etapa (telefone, técnica, cultural)
- Critérios customizáveis por vaga
- Múltiplos avaliadores por candidato
- Notas e comentários por critério
- Cálculo automático de score médio

**Estrutura proposta:**
```sql
-- Nova tabela: rh_scorecards
-- Campos: vaga_id, candidato_id, avaliador_id, etapa, criterios (JSON), nota_total, observacoes
```

---

#### 4. **Banco de Talentos (Talent Pool)**
**Status:** ⚠️ Parcial (existe status "banco_talentos", mas sem gestão dedicada)  
**Benefício:** Reutiliza candidatos qualificados para novas vagas

**Sugestão:**
- Tela dedicada ao banco de talentos
- Tags/categorização por área, senioridade, skills
- Busca avançada no banco
- Sugestão automática de candidatos para novas vagas
- Histórico de candidaturas anteriores

---

#### 5. **Formulário de Candidatura Público**
**Status:** ❌ Não implementado  
**Benefício:** Permite candidatos se candidatarem diretamente

**Sugestão:**
- Página pública (fora do admin)
- Upload de currículo
- Seleção de vaga
- Captcha anti-spam
- Confirmação por email

**Implementação:**
```php
// Nova rota pública: /candidatar-se/{vaga_id}
// Controller: Public\RhCandidatarSe.php
// View: app/adms/Views/public/rh/candidatar-se.php
```

---

### 🟡 **PRIORIDADE MÉDIA** (Melhora experiência e produtividade)

#### 6. **Comentários e Notas por Candidato**
**Status:** ⚠️ Parcial (existe campo observações, mas sem timeline)  
**Benefício:** Facilita comunicação entre recrutadores

**Sugestão:**
- Timeline de comentários (estilo chat)
- @menções para notificar outros recrutadores
- Tags privadas/públicas
- Anexos em comentários

---

#### 7. **Calendário de Entrevistas**
**Status:** ❌ Não implementado  
**Benefício:** Visualização centralizada e evita conflitos

**Sugestão:**
- Calendário mensal/semanal
- Integração com Google Calendar / Outlook
- Bloqueio de horários do entrevistador
- Lembrete automático (email/SMS)

---

#### 8. **Relatórios e Analytics Avançados**
**Status:** ⚠️ Parcial (existe dashboard básico)  
**Benefício:** Insights para melhorar processo de recrutamento

**Sugestões:**
- Tempo médio por etapa do pipeline
- Taxa de conversão por origem
- Custo por contratação
- Taxa de desistência
- Fonte de melhor qualidade
- Exportação para Excel/PDF

---

#### 9. **Templates de Email e Mensagens**
**Status:** ❌ Não implementado  
**Benefício:** Padroniza comunicação e economiza tempo

**Sugestão:**
- Templates por tipo (convite, rejeição, feedback)
- Variáveis dinâmicas ({nome_candidato}, {nome_vaga}, {data_entrevista})
- Editor WYSIWYG
- Preview antes de enviar

---

#### 10. **Integração com LinkedIn**
**Status:** ❌ Não implementado  
**Benefício:** Importa perfil completo e facilita sourcing

**Sugestão:**
- Botão "Importar do LinkedIn" no cadastro
- OAuth LinkedIn
- Importação de experiência, formação, skills
- Link para perfil do LinkedIn no cadastro

---

### 🟢 **PRIORIDADE BAIXA** (Nice to have)

#### 11. **Avaliação de Fit Cultural**
- Questionário customizável por vaga
- Respostas do candidato vs. perfil da empresa
- Score de fit cultural

#### 12. **Referências**
- Cadastro de referências do candidato
- Envio automático de formulário para referências
- Consolidação de respostas

#### 13. **Onboarding Integrado**
- Após aprovação, criar usuário automaticamente
- Enviar credenciais e materiais de onboarding
- Checklist de documentos

#### 14. **Mobile App / PWA**
- Acesso mobile otimizado
- Notificações push
- Aprovação rápida de candidatos

#### 15. **Integração com Job Boards**
- Publicação automática em sites de emprego
- Sincronização de candidaturas
- Análise de performance por canal

---

## 📋 Plano de Implementação Sugerido

### **Fase 1 - Fundação (1-2 meses)**
1. ✅ Upload e parsing de currículos
2. ✅ Notificações automáticas por email
3. ✅ Formulário público de candidatura

### **Fase 2 - Qualidade (2-3 meses)**
4. ✅ Scorecard de avaliação
5. ✅ Comentários e timeline
6. ✅ Templates de email

### **Fase 3 - Eficiência (3-4 meses)**
7. ✅ Calendário de entrevistas
8. ✅ Banco de talentos aprimorado
9. ✅ Relatórios avançados

### **Fase 4 - Integração (4-6 meses)**
10. ✅ Integração LinkedIn
11. ✅ Integração com job boards
12. ✅ Mobile/PWA

---

## 🎯 Métricas de Sucesso

Após implementação, medir:
- **Tempo médio de preenchimento de vaga** (reduzir em 30%)
- **Taxa de resposta de candidatos** (aumentar em 40%)
- **Satisfação dos recrutadores** (pesquisa interna)
- **Taxa de contratação por origem** (identificar melhores canais)
- **Tempo médio por etapa** (otimizar gargalos)

---

## 🔧 Considerações Técnicas

### **Arquitetura Sugerida**
- Manter padrão MVC atual
- Criar serviços específicos para novas funcionalidades
- Usar eventos/listeners para notificações
- Queue system para emails (Redis/Beanstalkd)

### **Banco de Dados**
- Novas tabelas conforme necessário
- Índices para performance em buscas
- Migrations versionadas

### **Segurança**
- Validação rigorosa de uploads
- Rate limiting em formulário público
- Sanitização de dados de parsing

---

## 📚 Referências

- **Greenhouse:** https://www.greenhouse.io/
- **Lever:** https://www.lever.co/
- **Workable:** https://www.workable.com/
- **BambooHR:** https://www.bamboohr.com/
- **Recruitee:** https://recruitee.com/

---

**Documento gerado em:** 13/02/2026  
**Próxima revisão sugerida:** Após implementação da Fase 1
