<?php
/** RH treinamentos, projetos, gestão pessoas, salas, SAC, LGPD, planejamento, relatórios, SST */
declare(strict_types=1);
function w(string $rel, string $c): void {
    $p = dirname(__DIR__) . '/docs/manual/content/' . $rel;
    if (!is_dir(dirname($p))) mkdir(dirname($p), 0775, true);
    file_put_contents($p, $c);
    echo "OK $rel\n";
}

$docs = [
'rh_treinamentos/rh-trein-visao-geral.html' => '<h1>Visão geral — Gestão de Treinamentos</h1>
<p>Catálogo corporativo de treinamentos, matrizes de necessidade, status de conclusão, avaliações e notificações.</p>
<p><strong>Quem acessa:</strong> RH, T&amp;D e gestores com permissões <em>ListTrainings</em>, <em>TrainingKpiDashboard</em>, etc.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre treinamentos no <strong>catálogo</strong> com carga horária e validade.</li>
<li>Defina necessidades por cargo/matriz.</li>
<li>Acompanhe <strong>status</strong> e dashboards de conformidade.</li>
<li>Aplique treinamentos concluídos e registre certificados.</li>
<li>Use <strong>avaliações</strong> para validar aprendizado.</li>
<li>Configure <strong>notificações</strong> de vencimento.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Pendência não aparece</dt><dd>Matriz desatualizada; execute sincronização de status.</dd>
<dt>Conflito com SST</dt><dd>Treinamentos SST são módulo separado; regularize ambos.</dd>
<dt>Notificação não enviada</dt><dd>Verifique Administração → Notificações Automáticas.</dd>
<dt>Bloqueio de login</dt><dd>Treinamento obrigatório global em Administração.</dd>
</dl>',

'rh_treinamentos/rh-trein-catalogo.html' => '<h1>Catálogo de treinamentos</h1>
<p>Cursos e conteúdos corporativos com carga horária, validade, modalidade e vínculo a avaliações.</p>
<p><strong>Quem acessa:</strong> RH com <em>ListTrainings</em>, <em>CreateTraining</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Gestão de Treinamentos → Cadastrar Treinamentos</strong>.</li>
<li>Informe nome, descrição, carga horária e validade (meses).</li>
<li>Defina modalidade (presencial, EAD, híbrido).</li>
<li>Vincule questionário de avaliação se aplicável.</li>
<li>Ative treinamento para uso em matrizes e obrigatórios.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome</strong> Título do treinamento.</div>
<div class="help-field"><strong>Validade</strong> Prazo para reciclagem.</div>
<div class="help-field"><strong>Carga horária</strong> Horas para certificado.</div>
<div class="help-field"><strong>Status</strong> Ativo/inativo.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Treinamento inativo em matriz</dt><dd>Reative ou substitua na matriz de necessidades.</dd>
<dt>Certificado sem carga horária</dt><dd>Preencha campo antes de aplicar treinamento.</dd>
<dt>Duplicidade de nome</dt><dd>Padronize códigos internos para distinguir turmas.</dd>
<dt>Avaliação não vincula</dt><dd>Crie modelo em Avaliações antes do vínculo.</dd>
</dl>',

'rh_treinamentos/rh-trein-dashboards.html' => '<h1>Dashboards de treinamentos</h1>
<p>KPIs de conformidade, necessidades pendentes e indicadores de capacitação.</p>
<p><strong>Quem acessa:</strong> RH e gestores com <em>TrainingKpiDashboard</em>, <em>TrainingComplianceDashboard</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Abra <strong>Dashboard de KPIs</strong> para visão executiva.</li>
<li>Use <strong>Dashboard de Necessidades</strong> para pendências por área.</li>
<li>Filtre por departamento, cargo ou filial.</li>
<li>Identifique treinamentos críticos vencendo.</li>
<li>Exporte ou compartilhe indicadores em reuniões de RH.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Percentual de conformidade baixo</dt><dd>Verifique matriz e aplicações pendentes.</dd>
<dt>Dashboard vazio</dt><dd>Sem treinamentos cadastrados ou filtro restritivo.</dd>
<dt>Divergência com matriz</dt><dd>Job de status pode estar atrasado; execute atualização.</dd>
<dt>Gestor não vê equipe</dt><dd>Hierarquia ou permissão gerencial ausente.</dd>
</dl>',

'rh_treinamentos/rh-trein-matrizes.html' => '<h1>Matrizes de treinamento</h1>
<p>Matriz por colaborador e matriz de treinamentos realizados — visão de necessidade vs. conclusão.</p>
<p><strong>Quem acessa:</strong> RH com <em>MatrixByUser</em>, <em>CompletedTrainingsMatrix</em>, <em>ListTrainingStatus</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Consulte <strong>Matriz por Colaborador</strong> para ver obrigatórios individuais.</li>
<li>Use <strong>Matriz de Realizados</strong> para histórico consolidado.</li>
<li>Acompanhe <strong>Status de Treinamentos</strong> para pendências.</li>
<li>Aplique conclusões ou importe turmas externas.</li>
<li>Reexecute sincronização após alterar cargos.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Colaborador sem linhas na matriz</dt><dd>Cargo sem necessidade definida.</dd>
<dt>Realizado não aparece</dt><dd>Aplicação registrada em outro treinamento equivalente.</dd>
<dt>Status desatualizado</dt><dd>Aguarde job noturno ou force atualização.</dd>
<dt>Matriz lenta</dt><dd>Filtre departamento ou exporte assíncrono.</dd>
</dl>',

'rh_treinamentos/rh-trein-avaliacoes.html' => '<h1>Avaliações e questionários</h1>
<p>Modelos de questionário, atribuição a colaboradores e correção de avaliações de treinamento.</p>
<p><strong>Quem acessa:</strong> RH (<em>ListEvaluationModels</em>) e colaboradores (<em>MyEvaluations</em>).</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Crie <strong>questionário completo</strong> com perguntas e gabarito.</li>
<li>Atribua avaliação a colaboradores ou turma.</li>
<li>Colaborador responde em <strong>Minhas Avaliações</strong>.</li>
<li>RH consulta resultados e histórico.</li>
<li>Vincule aprovação à conclusão do treinamento.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Colaborador não vê avaliação</dt><dd>Atribuição pendente ou prazo expirado.</dd>
<dt>Nota não salva</dt><dd>Sessão expirada; refaça login e envie novamente.</dd>
<dt>Modelo sem perguntas</dt><dd>Publique questionário antes de atribuir.</dd>
<dt>Certificado bloqueado</dt><dd>Nota mínima não atingida.</dd>
</dl>',

'rh_treinamentos/rh-trein-notificacoes.html' => '<h1>Notificações de treinamento</h1>
<p>Alertas de vencimento, testes de envio e integração com e-mail/push/WhatsApp.</p>
<p><strong>Quem acessa:</strong> RH com <em>TestNotification</em> e admins com <em>NotificationSettings</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Configure gatilhos em Administração → Notificações Automáticas.</li>
<li>Defina antecedência (ex.: 30, 15, 7 dias antes do vencimento).</li>
<li>Use <strong>Testar Notificações</strong> para validar envio.</li>
<li>Monitore taxa de abertura e regularize pendências.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>E-mail não chega</dt><dd>SMTP ou spam; teste com TestNotification.</dd>
<dt>Notificação duplicada</dt><dd>Múltiplos gatilhos com mesma antecedência.</dd>
<dt>Push não funciona</dt><dd>Configure Push PWA em Administração.</dd>
<dt>Colaborador sem e-mail</dt><dd>Cadastro incompleto em Usuários.</dd>
</dl>',

'projetos/proj-projetos.html' => '<h1>Gestão de projetos</h1>
<p>Projetos, etapas, grupos de etapas e acompanhamento de entregas.</p>
<p><strong>Quem acessa:</strong> PMO e gestores com <em>ListProjects</em>, <em>ListProjectStages</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Grupos de Etapas</strong> (template de fases).</li>
<li>Crie <strong>Etapas</strong> com ordem e responsável.</li>
<li>Abra <strong>Projeto</strong> com datas, escopo e equipe.</li>
<li>Vincule etapas e acompanhe percentual de conclusão.</li>
<li>Atualize status até encerramento do projeto.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Projeto</strong> Nome, patrocinador, prazo.</div>
<div class="help-field"><strong>Etapa</strong> Marco com data prevista/real.</div>
<div class="help-field"><strong>Status</strong> Planejado, em andamento, concluído, cancelado.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Etapas fora de ordem</dt><dd>Reordene numeração no cadastro de etapas.</dd>
<dt>Projeto sem responsável</dt><dd>Defina owner para notificações.</dd>
<dt>Percentual travado</dt><dd>Etapa filha não marcada concluída.</dd>
<dt>Grupo de etapas não aplica</dt><dd>Selecione template na criação do projeto.</dd>
</dl>',

'gestao_pessoas/gp-visao-geral.html' => '<h1>Visão geral — Gestão de Pessoas</h1>
<p>Portal do colaborador, políticas, folha digital, desempenho, solicitações, analytics e recrutamento.</p>
<p><strong>Quem acessa:</strong> RH, gestores e colaboradores conforme permissões <em>EmployeePortal</em>, <em>ListPolicies</em>, etc.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Publique <strong>políticas internas</strong> e comunique no portal.</li>
<li>Importe <strong>documentos de folha</strong> e acompanhe ciência.</li>
<li>Configure <strong>desempenho</strong> (metas, feedbacks, 9BOX).</li>
<li>Gerencie <strong>solicitações</strong> e aprovações.</li>
<li>Use <strong>People Analytics</strong> e <strong>recrutamento</strong> para decisões.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Colaborador não acessa portal</dt><dd>Permissão EmployeePortal ausente.</dd>
<dt>Documento folha não assina</dt><dd>Pendência de ciência; verifique tipo de documento.</dd>
<dt>Meta OKR desalinhada</dt><dd>Ciclo de desempenho não iniciado.</dd>
<dt>Solicitação parada</dt><dd>Aprovador ausente ou fluxo mal configurado.</dd>
</dl>',

'gestao_pessoas/gp-politicas.html' => '<h1>Políticas internas</h1>
<p>Publicação de políticas e normas com categorias, versões e aceite dos colaboradores.</p>
<p><strong>Quem acessa:</strong> RH com <em>ListPolicies</em>, <em>ListPolicyCategories</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre categorias de políticas.</li>
<li>Crie política com título, conteúdo e versão.</li>
<li>Exija aceite eletrônico se configurado.</li>
<li>Publique e notifique colaboradores.</li>
<li>Arquive versões anteriores.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Aceite não registrado</dt><dd>Colaborador deve rolar até o fim e confirmar.</dd>
<dt>Política antiga visível</dt><dd>Obsoletar versão anterior.</dd>
<dt>Categoria vazia</dt><dd>Associe política à categoria correta.</dd>
<dt>Notificação não enviada</dt><dd>Verifique notificações automáticas.</dd>
</dl>',

'gestao_pessoas/gp-portal.html' => '<h1>Portal do colaborador</h1>
<p>Área self-service: políticas, documentos, EPIs, treinamentos SST, solicitações e chamados.</p>
<p><strong>Quem acessa:</strong> colaboradores com <em>EmployeePortal</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Gestão de Pessoas → Portal do Colaborador</strong>.</li>
<li>Consulte pendências (políticas, folha, treinamentos).</li>
<li>Acesse atalhos para Meus EPIs, treinamentos SST, solicitações.</li>
<li>Execute ações pendentes (aceite, assinatura).</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Pendência não some após ação</dt><dd>Aguarde processamento ou refresh.</dd>
<dt>Atalho SST não aparece</dt><dd>Permissões MyEpiDeliveries / MySstTreinamentos.</dd>
<dt>Portal em branco</dt><dd>Widgets sem permissão; contate RH.</dd>
<dt>Mobile layout quebrado</dt><dd>Use PWA atualizado ou navegador recente.</dd>
</dl>',

'gestao_pessoas/gp-folha.html' => '<h1>Documentos de folha</h1>
<p>Importação de holerites/contracheques PDF, tipos de documento, ciência eletrônica e lembretes.</p>
<p><strong>Quem acessa:</strong> RH (<em>ImportPayrollDocuments</em>) e colaboradores (<em>MyPayrollDocuments</em>).</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>tipos de documento RH</strong>.</li>
<li>Importe PDFs em lote vinculando matrícula/CPF.</li>
<li>Colaborador acessa <strong>Meus documentos (folha)</strong> e assina ciência.</li>
<li>Acompanhe <strong>pendências de ciência</strong>.</li>
<li>Configure <strong>cron de lembretes</strong> com token seguro.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>PDF não vincula ao colaborador</dt><dd>Matrícula no filename ou metadados incorretos.</dd>
<dt>Ciência não registra</dt><dd>Colaborador deve abrir PDF completo.</dd>
<dt>Cron não executa</dt><dd>Token ou URL do cron inválidos.</dd>
<dt>Documento duplicado</dt><dd>Competência já importada; remova duplicata.</dd>
</dl>',

'gestao_pessoas/gp-desempenho.html' => '<h1>Desempenho</h1>
<p>Avaliações de desempenho, OKRs, feedbacks, competências, matriz 9BOX e dashboard.</p>
<p><strong>Quem acessa:</strong> RH e gestores com <em>ListPerformanceReviews</em>, <em>PerformanceDashboard</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Defina <strong>competências</strong> e matriz por cargo.</li>
<li>Abra ciclo de <strong>avaliações</strong> com período e participantes.</li>
<li>Registre <strong>metas (OKRs)</strong> e <strong>feedbacks</strong> contínuos.</li>
<li>Consolide notas e visualize <strong>9BOX</strong>.</li>
<li>Use dashboard para calibragem.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Avaliação não liberada</dt><dd>Ciclo não iniciado ou gestor não atribuído.</dd>
<dt>9BOX vazio</dt><dd>Faltam notas de desempenho e potencial.</dd>
<dt>OKR desatualizado</dt><dd>Gestor deve registrar progresso periodicamente.</dd>
<dt>Feedback anônimo exposto</dt><dd>Revise configuração de anonimato.</dd>
</dl>',

'gestao_pessoas/gp-solicitacoes.html' => '<h1>Solicitações RH</h1>
<p>Fluxos de solicitações (férias, benefícios, etc.) com aprovação em múltiplos níveis.</p>
<p><strong>Quem acessa:</strong> colaboradores (<em>ListEmployeeRequests</em>) e aprovadores (<em>PendingApprovals</em>).</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Configure <strong>tipos de solicitação</strong> e fluxo de aprovação.</li>
<li>Colaborador abre solicitação em <strong>Minhas Solicitações</strong>.</li>
<li>Gestor aprova/rejeita em <strong>Aprovações Pendentes</strong>.</li>
<li>RH finaliza se necessário.</li>
<li>Histórico fica registrado na solicitação.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Solicitação sem aprovador</dt><dd>Gestor não cadastrado no usuário.</dd>
<dt>Badge de pendências errado</dt><dd>Cache; atualize página.</dd>
<dt>Tipo não aparece</dt><dd>Inativo ou sem permissão para o colaborador.</dd>
<dt>Anexo obrigatório</dt><dd>Envie documento antes de submeter.</dd>
</dl>',

'gestao_pessoas/gp-analytics.html' => '<h1>People Analytics</h1>
<p>Dashboards e relatórios de RH: headcount, turnover, absenteísmo e indicadores customizados.</p>
<p><strong>Quem acessa:</strong> RH e diretoria com <em>PeopleAnalytics</em>, <em>PeopleReports</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>People Analytics → Dashboard</strong>.</li>
<li>Selecione período e recortes (departamento, filial).</li>
<li>Analise KPIs e tendências.</li>
<li>Exporte relatórios específicos em <strong>Relatórios de RH</strong>.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Dados desatualizados</dt><dd>ETL noturno; consulte data da última carga.</dd>
<dt>Headcount divergente</dt><dd>Filtro de ativos/inativos diferente do cadastro.</dd>
<dt>Sem permissão gerencial</dt><dd>Analytics restrito a perfis RH.</dd>
<dt>Export grande falha</dt><dd>Reduza escopo ou agende export assíncrono.</dd>
</dl>',

'gestao_pessoas/gp-recrutamento.html' => '<h1>Recrutamento e currículos</h1>
<p>Vagas, candidatos, entrevistas e dashboard de KPIs de recrutamento.</p>
<p><strong>Quem acessa:</strong> RH com <em>RhVagas</em>, <em>RhCandidatos</em>, <em>RhEntrevistas</em>, <em>RhKpiDashboard</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Publique <strong>vaga</strong> com requisitos e status.</li>
<li>Registre <strong>candidatos</strong> ou importe currículos.</li>
<li>Agende <strong>entrevistas</strong> e registre pareceres.</li>
<li>Mova candidato entre etapas até contratação ou descarte.</li>
<li>Acompanhe KPIs no dashboard de recrutamento.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Candidato duplicado</dt><dd>Busque por CPF/e-mail antes de cadastrar.</dd>
<dt>Vaga fechada ainda recebe CV</dt><dd>Altere status para encerrada.</dd>
<dt>Entrevista sem notificação</dt><dd>E-mail do candidato inválido.</dd>
<dt>Time to hire incorreto</dt><dd>Datas de abertura/fechamento da vaga inconsistentes.</dd>
</dl>',

'salas/salas-visao-geral.html' => '<h1>Visão geral — Reserva de Salas</h1>
<p>Calendário de salas, reservas, lista de espera, solicitações de serviço e administração.</p>
<p><strong>Quem acessa:</strong> colaboradores (<em>RoomCalendar</em>, <em>BookRoom</em>) e facilities (<em>AdminBookingDashboard</em>).</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Consulte <strong>Calendário</strong> de disponibilidade.</li>
<li>Crie <strong>reserva</strong> com sala, horário e participantes.</li>
<li>Solicite serviços (café, AV) se necessário.</li>
<li>Gestor de salas aprova ou ajusta conflitos.</li>
<li>Integre com calendário externo se configurado.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Conflito de horário</dt><dd>Sala já reservada; escolha outro horário ou lista de espera.</dd>
<dt>Reserva não sincroniza Outlook</dt><dd>Verifique integração calendário.</dd>
<dt>Solicitação de serviço ignorada</dt><dd>Equipe/grupo responsável não notificado.</dd>
<dt>Cancelamento tardio</dt><dd>Política de antecedência mínima pode bloquear.</dd>
</dl>',

'salas/salas-reservas.html' => '<h1>Reservas de salas</h1>
<p>Criação, edição, cancelamento de reservas e lista de espera.</p>
<p><strong>Quem acessa:</strong> usuários com <em>CreateBooking</em>, <em>ListBookings</em>, <em>BookingWaitlist</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>No calendário, clique em slot livre ou <strong>Nova reserva</strong>.</li>
<li>Selecione sala, data, hora início/fim e título da reunião.</li>
<li>Adicione participantes e descrição.</li>
<li>Confirme; convites enviados se integração ativa.</li>
<li>Para cancelar, abra reserva e use <strong>Cancelar</strong>.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Sala</strong> Capacidade e recursos (projetor, VC).</div>
<div class="help-field"><strong>Horário</strong> Início, fim e timezone.</div>
<div class="help-field"><strong>Organizador</strong> Responsável pela reserva.</div>
<div class="help-field"><strong>Lista de espera</strong> Fila quando sala ocupada.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Não consigo reservar passado</dt><dd>Política impede retroativo.</dd>
<dt>Reserva duplicada</dt><dd>Verifique calendário antes de confirmar.</dd>
<dt>Participante não recebe convite</dt><dd>Integração calendário desabilitada.</dd>
<dt>Waitlist não promove</dt><dd>Cancelamento da reserva principal libera vaga automaticamente.</dd>
</dl>',

'salas/salas-administracao.html' => '<h1>Administração de salas</h1>
<p>Cadastro de salas, dashboard administrativo, integração calendário e relatórios de uso.</p>
<p><strong>Quem acessa:</strong> facilities com <em>ListMeetingRooms</em>, <em>AdminBookingDashboard</em>, <em>BookingReports</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre salas com capacidade, recursos e localização.</li>
<li>Configure integração com Microsoft/Google Calendar.</li>
<li>Monitore ocupação no dashboard administrativo.</li>
<li>Gerencie tipos de solicitação e equipes de serviço.</li>
<li>Exporte relatórios de utilização.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Sala não aparece no calendário</dt><dd>Inativa ou sem permissão BookRoom.</dd>
<dt>Integração OAuth expirada</dt><dd>Renove token em Integração calendário.</dd>
<dt>Relatório de ocupação zerado</dt><dd>Período sem reservas confirmadas.</dd>
<dt>QR/check-in não funciona</dt><dd>Recurso depende de configuração local.</dd>
</dl>',

'sac/sac-visao-geral.html' => '<h1>Visão geral — SAC</h1>
<p>Atendimento ao cliente: chamados, clientes SAC, categorias, SLA e dashboard operacional.</p>
<p><strong>Quem acessa:</strong> equipe SAC com <em>SacDashboard</em>, <em>SacListTickets</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Configure categorias e regras de SLA.</li>
<li>Cadastre clientes SAC ou importe do CRM.</li>
<li>Abra e trate chamados conforme prioridade.</li>
<li>Monitore SLA no dashboard.</li>
<li>Encerre chamado com classificação de resolução.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>SLA estourado</dt><dd>Fila sobrecarregada; escale ou reassigne.</dd>
<dt>Cliente duplicado</dt><dd>Unifique cadastros antes de abrir ticket.</dd>
<dt>Categoria sem SLA</dt><dd>Associe regra em Regras de SLA.</dd>
<dt>Dashboard lento</dt><dd>Filtre período menor.</dd>
</dl>',

'sac/sac-atendimento.html' => '<h1>Atendimento — Chamados SAC</h1>
<p>Abertura, triagem, tratamento e encerramento de tickets com histórico e anexos.</p>
<p><strong>Quem acessa:</strong> analistas SAC com <em>SacListTickets</em>, permissões de criar/editar ticket.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Abra chamado informando cliente, categoria e descrição.</li>
<li>Classifique prioridade; SLA calculado automaticamente.</li>
<li>Atribua analista ou fila.</li>
<li>Registre interações e anexos na timeline do ticket.</li>
<li>Encerre com solução documentada.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Cliente</strong> Cadastro SAC vinculado.</div>
<div class="help-field"><strong>Categoria</strong> Define SLA e roteamento.</div>
<div class="help-field"><strong>Prioridade</strong> Urgente, alta, normal, baixa.</div>
<div class="help-field"><strong>Status</strong> Aberto, em andamento, aguardando cliente, resolvido.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>SLA não calcula</dt><dd>Categoria sem regra ou horário comercial não configurado.</dd>
<dt>Cliente não localizado</dt><dd>Cadastre em SAC → Clientes.</dd>
<dt>Anexo rejeitado</dt><dd>Tamanho ou tipo não permitido.</dd>
<dt>Reabertura indevida</dt><dd>Use status aguardando cliente antes de encerrar definitivo.</dd>
</dl>',

'lgpd/lgpd-visao-geral.html' => '<h1>Visão geral — LGPD</h1>
<p>Conformidade com a Lei Geral de Proteção de Dados: inventário, consentimentos, ROPA, AIPD e relatórios integrados.</p>
<p><strong>Quem acessa:</strong> DPO, jurídico e TI com permissões <em>Lgpd*</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Mapeie dados no <strong>Inventário</strong> e <strong>ROPA</strong>.</li>
<li>Registre <strong>consentimentos</strong> e bases legais.</li>
<li>Elabore <strong>AIPD</strong> para tratamentos de alto risco.</li>
<li>Monitore <strong>Dashboard LGPD</strong> e relatório integrado.</li>
<li>Mantenha termos e políticas atualizados.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Inventário incompleto</dt><dd>Envolva todas áreas; use data mapping.</dd>
<dt>Consentimento sem evidência</dt><dd>Registre canal, data e versão do termo.</dd>
<dt>AIPD pendente</dt><dd>Use sugestões e templates por segmento.</dd>
<dt>Relatório integrado vazio</dt><dd>Cadastros base (finalidades, bases legais) faltando.</dd>
</dl>',

'lgpd/lgpd-dashboard.html' => '<h1>Dashboard LGPD</h1>
<p>Indicadores de conformidade: tratamentos mapeados, consentimentos, AIPDs e pendências regulatórias.</p>
<p><strong>Quem acessa:</strong> DPO com <em>LgpdDashboard</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse dashboard e revise scorecards.</li>
<li>Identifique tratamentos sem base legal.</li>
<li>Priorize AIPDs vencendo revisão.</li>
<li>Exporte snapshot para comitê de privacidade.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Score baixo</dt><dd>Complete inventário e vincule bases legais.</dd>
<dt>Gráfico desatualizado</dt><dd>Dados consolidados diariamente.</dd>
<dt>Tratamento órfão</dt><dd>Responsável pelo processo não atribuído.</dd>
<dt>Sem permissão</dt><dd>Pacote LGPD completo no nível de acesso.</dd>
</dl>',

'lgpd/lgpd-consentimentos.html' => '<h1>Consentimentos</h1>
<p>Registro de consentimentos de titulares com finalidade, canal, evidência e revogação.</p>
<p><strong>Quem acessa:</strong> DPO com <em>LgpdConsentimentos</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre titular e finalidade do consentimento.</li>
<li>Registre data, canal e versão do termo aceito.</li>
<li>Anexe evidência (print, log) quando aplicável.</li>
<li>Processe revogações registrando data e motivo.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Consentimento duplicado</dt><dd>Busque titular antes de novo registro.</dd>
<dt>Revogação não propaga</dt><dd>Integre com sistemas downstream manualmente se necessário.</dd>
<dt>Termo desatualizado</dt><dd>Vincule versão correta em Lgpd Termos.</dd>
<dt>Menor de idade</dt><dd>Exige consentimento do responsável legal.</dd>
</dl>',

'lgpd/lgpd-inventario.html' => '<h1>Inventário de dados (ROPA)</h1>
<p>Registro de operações de tratamento, categorias de titulares, dados, finalidades e medidas de segurança.</p>
<p><strong>Quem acessa:</strong> DPO com <em>LgpdInventory</em>, <em>LgpdRopa</em>, <em>LgpdDataMapping</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre operação de tratamento (processo de negócio).</li>
<li>Informe titulares, tipos de dados e finalidades.</li>
<li>Vincule base legal e medidas técnicas/organizacionais.</li>
<li>Atualize ao mudar processo ou sistema.</li>
<li>Gere relatório integrado para auditoria.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Operação sem controlador</dt><dd>Defina área responsável e DPO de apoio.</dd>
<dt>Dados sensíveis não marcados</dt><dd>Classifique em Tipos/Classificações de Dados.</dd>
<dt>ROPA desatualizado</dt><dd>Revise trimestralmente ou após projetos novos.</dd>
<dt>Transferência internacional</dt><dd>Documente garantias e TIA se aplicável.</dd>
</dl>',

'lgpd/lgpd-aipd.html' => '<h1>AIPD — Avaliação de Impacto</h1>
<p>Análise de impacto à proteção de dados para tratamentos de alto risco, com templates por segmento.</p>
<p><strong>Quem acessa:</strong> DPO com <em>LgpdAipd</em>, <em>LgpdAipdSuggest</em> e templates.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Identifique tratamento que exige AIPD (dados sensíveis, larga escala).</li>
<li>Use <strong>Sugestões de AIPD</strong> ou template do segmento (RH, Saúde, etc.).</li>
<li>Preencha riscos, medidas mitigadoras e parecer DPO.</li>
<li>Aprove ou solicite revisão com stakeholders.</li>
<li>Agende revisão periódica da AIPD.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Template incompleto</dt><dd>Adapte campos à realidade local.</dd>
<dt>Risco residual alto</dt><dd>Implemente medidas antes de liberar tratamento.</dd>
<dt>AIPD sem vínculo ao inventário</dt><dd>Associe operação ROPA correspondente.</dd>
<dt>Versionamento confuso</dt><dd>Numere revisões e arquive PDF assinado.</dd>
</dl>',

'planejamento/pe-estrategico.html' => '<h1>Planejamento estratégico</h1>
<p>Planos estratégicos, indicadores (KPIs/KRIs) e dashboard de acompanhamento da execução.</p>
<p><strong>Quem acessa:</strong> diretoria e PMO com <em>StrategicDashboard</em>, <em>ListStrategicPlans</em>, <em>StrategicIndicatorsList</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Crie <strong>plano estratégico</strong> com horizonte e objetivos.</li>
<li>Defina <strong>indicadores</strong> com meta, responsável e periodicidade.</li>
<li>Lance valores realizados a cada período.</li>
<li>Acompanhe semáforo no dashboard estratégico.</li>
<li>Revise plano em ciclos anuais ou trimestrais.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Indicador sem meta</dt><dd>Defina baseline e target antes de medir.</dd>
<dt>Realizado desatualizado</dt><dd>Responsável deve lançar no prazo acordado.</dd>
<dt>Plano duplicado</dt><dd>Arquive versão anterior ao publicar nova.</dd>
<dt>Dashboard vermelho persistente</dt><dd>Plano de ação corretiva fora do módulo; documente em indicador.</dd>
</dl>',

'relatorios/rel-visao-geral.html' => '<h1>Visão geral — Relatórios</h1>
<p>Relatórios dinâmicos locais, consultas SAP via API e dashboards configuráveis.</p>
<p><strong>Quem acessa:</strong> analistas e gestores com <em>ListDynamicReports</em>, <em>ListDashboards</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Escolha fonte: relatório local, SAP ou dashboard.</li>
<li>Aplique parâmetros (data, filial, conta).</li>
<li>Execute e analise resultado.</li>
<li>Exporte ou salve layout favorito quando disponível.</li>
</ol>
<h2>Problemas comuns</h2>
<dl>
<dt>Relatório SAP timeout</dt><dd>Reduza parâmetros; verifique SapApiConfig.</dd>
<dt>Sem permissão ao relatório</dt><dd>Página vinculada ao pacote do usuário.</dd>
<dt>Dados locais inconsistentes</dt><dd>Confira origem SQL/view do relatório dinâmico.</dd>
<dt>Dashboard desatualizado</dt><dd>Cache; force refresh ou aguarde job.</dd>
</dl>',

'relatorios/rel-dinamicos.html' => '<h1>Relatórios dinâmicos</h1>
<p>Relatórios configuráveis com SQL/views locais e integração SAP Business One via API.</p>
<p><strong>Quem acessa:</strong> usuários com <em>ListDynamicReports</em>, <em>ListDynamicReportsSap</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Liste relatórios disponíveis (locais ou SAP).</li>
<li>Selecione relatório e preencha parâmetros obrigatórios.</li>
<li>Execute; aguarde processamento (SAP pode demorar).</li>
<li>Visualize tabela/gráfico e exporte Excel/PDF.</li>
<li>Para dashboards, acesse <strong>Dashboards</strong> no mesmo módulo.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Parâmetros</strong> Filtros definidos no cadastro do relatório.</div>
<div class="help-field"><strong>Fonte</strong> Banco local ou endpoint SAP.</div>
<div class="help-field"><strong>Exportação</strong> Formatos disponíveis por relatório.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Erro SQL no relatório local</dt><dd>View desatualizada; contate TI.</dd>
<dt>SAP retorna vazio</dt><dd>Parâmetros incorretos ou sem dados no ERP.</dd>
<dt>Parâmetro obrigatório</dt><dd>Preencha todos campos marcados antes de executar.</dd>
<dt>Timeout</dt><dd>Execute fora do horário de pico ou estreite filtros.</dd>
</dl>',
];

// SST UPGRADES
$sst = [
'sst/sst-cids.html' => '<h1>Catálogo CID-10</h1>
<p>Códigos da Classificação Internacional de Doenças usados em afastamentos, acidentes e relatórios analíticos SST.</p>
<p><strong>Quem acessa:</strong> equipe SST/medicina do trabalho com <em>SstListCids</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>SST → Cadastros → CIDs</strong>.</li>
<li>Busque código ou descrição; importe/atualize catálogo se disponível.</li>
<li>Mantenha códigos usados em afastamentos ativos.</li>
<li>Use autocomplete ao registrar afastamento ou acidente.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Código CID</strong> Código oficial (ex.: M54.5).</div>
<div class="help-field"><strong>Descrição</strong> Nome da doença ou condição.</div>
<div class="help-field"><strong>Capítulo</strong> Agrupamento para relatórios por capítulo CID.</div>
<div class="help-field"><strong>Status</strong> Inativos não aparecem em novos registros.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>CID não encontrado no autocomplete</dt><dd>Código inexistente ou inativo; cadastre ou corrija digitação.</dd>
<dt>Relatório por CID vazio</dt><dd>Afastamentos sem CID preenchido.</dd>
<dt>Capítulo incorreto</dt><dd>Atualize importação oficial CID-10.</dd>
<dt>Duplicidade de código</dt><dd>Mantenha um registro por código; inative duplicatas.</dd>
</dl>',

'sst/sst-epis.html' => '<h1>Catálogo de EPIs</h1>
<p>Equipamentos de Proteção Individual cadastrados para entrega, controle de estoque e vínculo a riscos.</p>
<p><strong>Quem acessa:</strong> SST com <em>SstListEpis</em>, <em>SstCreateEpi</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre EPI com nome, CA e categoria de proteção.</li>
<li>Informe validade de troca (meses) e estoque mínimo.</li>
<li>Marque <em>exige treinamento</em> se entrega depende de capacitação.</li>
<li>Vincule ao <strong>risco</strong> (aba EPIs) ou <strong>Necessidades de EPI</strong>.</li>
<li>Mantenha estoque via movimentações EPI.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome / CA</strong> Descrição e Certificado de Aprovação.</div>
<div class="help-field"><strong>Categoria</strong> Auricular, respiratória, cabeça, etc.</div>
<div class="help-field"><strong>Validade de troca</strong> Periodicidade recomendada de substituição.</div>
<div class="help-field"><strong>Estoque mínimo</strong> Alertas no dashboard SST.</div>
<div class="help-field"><strong>Exige treinamento</strong> Bloqueia entrega sem treinamento em dia.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>CA vencido</dt><dd>Atualize CA ou substitua EPI no catálogo.</dd>
<dt>EPI não aparece na ficha de entrega</dt><dd>Sem vínculo risco/necessidade para o cargo.</dd>
<dt>Alerta estoque mínimo</dt><dd>Registre entrada em Movimentações EPI.</dd>
<dt>Entrega bloqueada por treinamento</dt><dd>Aplique treinamento SST vinculado ao EPI.</dd>
</dl>',

'sst/sst-exames.html' => '<h1>Catálogo de exames</h1>
<p>Exames ocupacionais e complementares usados em ASOs e na matriz risco → exame.</p>
<p><strong>Quem acessa:</strong> medicina do trabalho com <em>SstListExames</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre exame com nome, tipo e periodicidade padrão.</li>
<li>Associe categorias ASO aplicáveis (admissional, periódico, etc.).</li>
<li>Vincule ao <strong>risco</strong> (aba Exames) como obrigatório ou recomendado.</li>
<li>Use em ASOs; complementares sugeridos automaticamente.</li>
<li>Inative exames obsoletos sem excluir histórico.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome</strong> Ex.: Audiometria tonal liminar.</div>
<div class="help-field"><strong>Código / Tipo</strong> Identificação interna.</div>
<div class="help-field"><strong>Periodicidade (meses)</strong> Intervalo entre realizações.</div>
<div class="help-field"><strong>Categoria ASO</strong> Admissional, periódico, retorno, etc.</div>
<div class="help-field"><strong>Status</strong> Inativos não entram em novas regras.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Exame não sugere no ASO</dt><dd>Sem vínculo risco/cargo ou categoria ASO incompatível.</dd>
<dt>Periodicidade errada na pendência</dt><dd>Ajuste periodicidade padrão ou validade do último ASO.</dd>
<dt>Duplicidade admissional/periódico</dt><dd>Exame em ASO pendente não gera pendência avulsa.</dd>
<dt>Exame inativo ainda obrigatório</dt><dd>Remova vínculos do risco antes de inativar.</dd>
</dl>',

'sst/sst-medicos.html' => '<h1>Médicos do trabalho</h1>
<p>Cadastro de médicos responsáveis por ASOs, programas PCMSO e encaminhamentos.</p>
<p><strong>Quem acessa:</strong> medicina do trabalho com <em>SstListMedicos</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre médico com nome, CRM/UF e contatos.</li>
<li>Associe a programas PCMSO como responsável técnico quando aplicável.</li>
<li>Selecione médico ao criar ASO ou concluir exame clínico.</li>
<li>Inative médicos desligados; histórico preservado.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Nome</strong> Nome completo do médico.</div>
<div class="help-field"><strong>CRM / UF</strong> Registro profissional.</div>
<div class="help-field"><strong>Contato</strong> E-mail e telefone para encaminhamentos.</div>
<div class="help-field"><strong>Status</strong> Inativos não aparecem em novos ASOs.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Médico não lista no ASO</dt><dd>Status inativo ou CRM não preenchido.</dd>
<dt>CRM inválido</dt><dd>Confira UF e número sem formatação extra.</dd>
<dt>PCMSO sem responsável</dt><dd>Vincule médico no cadastro do programa.</dd>
<dt>Encaminhamento sem contato</dt><dd>Preencha e-mail/clínica para laboratório.</dd>
</dl>',

'sst/sst-matriz-treinamento.html' => '<h1>Matriz cargo × treinamento</h1>
<p>Visão consolidada e edição de treinamentos obrigatórios por cargo, com origem direta, risco ou GHE.</p>
<p><strong>Quem acessa:</strong> SST com <em>SstMatrizTreinamentoCargo</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Abra matriz e localize cargo desejado.</li>
<li>Expanda linha para ver treinamentos por coluna (Total, Direto, Risco, GHE).</li>
<li>Marque/desmarque checkboxes <strong>editáveis</strong> apenas para vínculos diretos.</li>
<li>Altere regras de risco/GHE nas telas de origem (somente leitura aqui).</li>
<li>Salve; execute sincronização de vínculos se necessário.</li>
</ol>
<h2>Colunas do resumo</h2>
<div class="help-field"><strong>Total</strong> Treinamentos distintos efetivos (sem duplicar).</div>
<div class="help-field"><strong>Direto</strong> Vínculos gravados na matriz.</div>
<div class="help-field"><strong>Risco</strong> Herdados de riscos do cargo/setor.</div>
<div class="help-field"><strong>GHE</strong> Via grupos homogêneos com colaboradores ativos.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Checkbox desabilitado</dt><dd>Vínculo vem de risco/GHE; edite na origem.</dd>
<dt>Total menor que soma das colunas</dt><dd>Mesmo treinamento deduplicado no Total.</dd>
<dt>Salvar não grava risco</dt><dd>Comportamento esperado; matriz grava só diretos.</dd>
<dt>Pendência após salvar matriz</dt><dd>Execute Sincronizar vínculos em Status treinamentos SST.</dd>
</dl>',

'sst/sst-conceitos.html' => '<h1>Conceitos: Cargo, Risco, GHE e Matriz</h1>
<p>Entender estes conceitos evita cadastros duplicados e garante pendências corretas.</p>
<p><strong>Quem acessa:</strong> toda equipe SST; documento de referência conceitual.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Leia o modelo <strong>Colaborador → Cargo + Setor → Riscos → Exames/EPIs/Treinamentos</strong>.</li>
<li>Cadastre catálogos antes de vínculos (exames, EPIs, treinamentos).</li>
<li>Vincule riscos a cargos/setores; configure abas do risco.</li>
<li>Use matriz cargo × treinamento para visão consolidada.</li>
<li>Adote GHE somente quando mesmo cargo tem exposições diferentes.</li>
</ol>
<h2>Modelo principal</h2>
<pre style="background:#f8f9fa;padding:1rem;border-radius:.25rem;">Colaborador → Cargo + Setor → Riscos → Exames / EPIs / Treinamentos</pre>
<h2>GHE (opcional)</h2>
<p>Grupo Homogêneo de Exposição para colaboradores com mesma exposição em ambientes distintos. Vincule no colaborador, não no cargo.</p>
<h2>Problemas comuns</h2>
<dl>
<dt>Pendência duplicada</dt><dd>Mesmo item por cargo e GHE conta uma vez; revise regras.</dd>
<dt>Regra no lugar errado</dt><dd>Preferir risco × cargo; GHE só se necessário.</dd>
<dt>Matriz não reflete risco</dt><dd>Badges Risco/GHE são leitura; edite cadastro do risco.</dd>
<dt>Ordem de cadastro invertida</dt><dd>Sem catálogo base, vínculos ficam vazios.</dd>
</dl>',

'sst/sst-conformidade.html' => '<h1>Programas e conformidade</h1>
<p>Gestão de documentos programáticos (PGR, PCMSO, PPRA, LTCAT) e painel de conformidade regulatória.</p>
<p><strong>Quem acessa:</strong> SST e jurídico com <em>SstListProgramas</em>, <em>SstReportConformidade</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre programa com tipo, versão, vigência e responsáveis.</li>
<li>Anexe PDF assinado na aba documentos.</li>
<li>Defina escopo (cargos, departamentos ou global).</li>
<li>Monitore painel de conformidade e pendências críticas.</li>
<li>Substitua versão ao renovar PGR/PCMSO.</li>
</ol>
<h2 id="aba-programa-dados">Programa — dados</h2>
<div class="help-field"><strong>Tipo</strong> PGR, PCMSO, PPRA, LTCAT, etc.</div>
<div class="help-field"><strong>Versão / Status</strong> Vigente, substituído, em elaboração.</div>
<div class="help-field"><strong>Vigência</strong> Período de validade.</div>
<div class="help-field"><strong>Responsável / Médico</strong> Profissionais legalmente responsáveis.</div>
<h2 id="aba-programa-anexos">Programa — anexos</h2>
<p>PDF assinado do documento programático.</p>
<h2>Problemas comuns</h2>
<dl>
<dt>Programa vencido no painel</dt><dd>Cadastre nova versão e marque anterior como substituída.</dd>
<dt>Escopo não cobre colaborador</dt><dd>Ajuste cargo/departamento no escopo do programa.</dd>
<dt>Anexo ausente em auditoria</dt><dd>Exige upload na aba documentos.</dd>
<dt>Painel vermelho com programas OK</dt><dd>Pendências são EPI/exame/treinamento, não só documentos.</dd>
</dl>',

'sst/sst-esocial-ppp.html' => '<h1>eSocial e PPP</h1>
<p>Fila de eventos eSocial gerados pelo sistema e emissão do Perfil Profissiográfico Previdenciário.</p>
<p><strong>Quem acessa:</strong> SST com <em>SstListEsocialEventos</em>, <em>SstListPpp</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Registre treinamentos/acidentes que geram eventos eSocial.</li>
<li>Em <strong>Fila eSocial</strong>, gere evento ou sincronize pendentes.</li>
<li>Exporte JSON e transmita ao eSocial; marque enviado com protocolo.</li>
<li>Para PPP, selecione colaborador e <strong>Gerar PPP</strong>.</li>
<li>Exporte PDF para entrega ao trabalhador ou INSS.</li>
</ol>
<h2>Fila eSocial</h2>
<ul>
<li><strong>Gerar evento</strong> — monta JSON conforme layout.</li>
<li><strong>Exportar JSON</strong> — download para transmissão.</li>
<li><strong>Marcar enviado</strong> — registra protocolo.</li>
<li><strong>Sincronizar pendentes</strong> — geração em lote.</li>
</ul>
<h2>PPP</h2>
<div class="help-field"><strong>Gerar PPP</strong> Snapshot de exposições e histórico ocupacional.</div>
<div class="help-field"><strong>Exportar PDF</strong> Documento oficial.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Evento S-2245 não gera</dt><dd>Treinamento sem dados obrigatórios eSocial.</dd>
<dt>JSON rejeitado</dt><dd>Valide layout versão; campos CPF/matricula inconsistentes.</dd>
<dt>PPP incompleto</dt><dd>Faltam riscos/EPIs históricos no cadastro.</dd>
<dt>Duplicidade de evento</dt><dd>Verifique se já marcado enviado antes de regerar.</dd>
</dl>',

'sst/sst-equipamentos.html' => '<h1>Equipamentos e vistorias</h1>
<p>Controle de equipamentos de segurança (extintores, PA, etc.) com checklist e vistorias periódicas.</p>
<p><strong>Quem acessa:</strong> SST e responsáveis com <em>SstListEquipamentos</em>, <em>SstMinhasEquipamentoVistorias</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>tipos de equipamento</strong> com checklist de itens.</li>
<li>Registre equipamentos com identificação, QR e localização.</li>
<li>Configure periodicidade em <strong>Config. vistorias</strong>.</li>
<li>Responsável executa vistoria em <strong>Minhas vistorias</strong> ou via <strong>Ler QR</strong>.</li>
<li>Itens não conformes geram plano de ação.</li>
</ol>
<h2 id="aba-vistorias-abertas">Minhas vistorias — Abertas</h2>
<p>Pendentes ou vencidas atribuídas ao responsável.</p>
<h2 id="aba-vistorias-concluidas">Minhas vistorias — Concluídas</h2>
<p>Histórico de vistorias executadas.</p>
<h2>Problemas comuns</h2>
<dl>
<dt>QR não abre vistoria</dt><dd>Equipamento inativo ou vistoria já concluída no período.</dd>
<dt>Vistoria vencida no dashboard</dt><dd>Execute vistoria ou reassigne responsável.</dd>
<dt>Checklist vazio</dt><dd>Configure itens no tipo de equipamento.</dd>
<dt>Não conforme sem ação</dt><dd>Abra plano de correção com prazo.</dd>
</dl>',

'sst/sst-cipa-inspecoes.html' => '<h1>CIPA e inspeções</h1>
<p>Mandatos CIPA, reuniões, membros e inspeções de segurança com planos de ação.</p>
<p><strong>Quem acessa:</strong> SST com <em>SstListCipaMandatos</em>, <em>SstListInspecoes</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>mandato CIPA</strong> com período de vigência.</li>
<li>Registre membros titulares e suplentes.</li>
<li>Documente <strong>reuniões</strong> com atas e deliberações.</li>
<li>Realize <strong>inspeção</strong> informando área, data e checklist.</li>
<li>Abra planos de ação para não conformidades.</li>
</ol>
<h2>CIPA</h2>
<div class="help-field"><strong>Mandato</strong> Período da comissão.</div>
<div class="help-field"><strong>Membros</strong> Titulares e suplentes.</div>
<div class="help-field"><strong>Reuniões</strong> Atas e deliberações.</div>
<h2>Inspeções</h2>
<div class="help-field"><strong>Data / Área</strong> Quando e onde.</div>
<div class="help-field"><strong>Itens</strong> Checklist com situação.</div>
<div class="help-field"><strong>Planos de ação</strong> Correções com responsável e prazo.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Mandatos sobrepostos</dt><dd>Encerre mandato anterior ao iniciar novo.</dd>
<dt>Membro duplicado</dt><dd>Representação empregador/empregados distinta.</dd>
<dt>Inspeção sem itens</dt><dd>Use template de checklist ou copie inspeção anterior.</dd>
<dt>Plano de ação atrasado</dt><dd>Monitore prazos no dashboard SST.</dd>
</dl>',

'sst/sst-relatorios.html' => '<h1>Relatórios SST</h1>
<p>Relatórios analíticos e operacionais: pendências, EPIs, exames, treinamentos, afastamentos e CIDs.</p>
<p><strong>Quem acessa:</strong> SST com permissões <em>SstReport*</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Escolha relatório no menu SST → Relatórios.</li>
<li>Aplique filtros de setor, cargo, período.</li>
<li>Analise pendências ou indicadores.</li>
<li>Exporte PDF/Excel conforme botões disponíveis.</li>
<li>Para pendências consolidadas, veja também tópico <em>Pendências SST</em>.</li>
</ol>
<h2 id="aba-pendencias-epi">Pendências — EPIs</h2>
<p>Obrigatórios não entregues ou troca vencida.</p>
<h2 id="aba-pendencias-exame">Pendências — Exames</h2>
<p>ASOs e complementares pendentes ou vencidos.</p>
<h2 id="aba-pendencias-treinamento">Pendências — Treinamentos</h2>
<p>Pendentes, vencidos ou em reciclagem.</p>
<h2>Problemas comuns</h2>
<dl>
<dt>Relatório vazio</dt><dd>Filtros restritivos ou sem pendências reais.</dd>
<dt>Divergência com dashboard</dt><dd>Alinhe data de corte e filial.</dd>
<dt>Export falha</dt><dd>Volume grande; filtre departamento.</dd>
<dt>Colaborador inativo listado</dt><dd>Inclua filtro "somente ativos".</dd>
</dl>',

'sst/sst-acidentes-afastamentos.html' => '<h1>Acidentes e afastamentos</h1>
<p>Registro de acidentes/incidentes de trabalho e afastamentos com CID, CAT e planos de ação.</p>
<p><strong>Quem acessa:</strong> SST e RH com <em>SstListAcidentes</em>, <em>SstListAfastamentos</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Registre <strong>acidente/incidente</strong> com data, local, envolvidos e descrição.</li>
<li>Informe se houve CAT e abra planos de ação corretivos.</li>
<li>Para afastamento, vincule colaborador, CID e período.</li>
<li>Atualize retorno efetivo ao encerrar afastamento.</li>
<li>Afastamentos ativos aparecem no dashboard e relatórios.</li>
</ol>
<h2>Acidentes</h2>
<div class="help-field"><strong>Tipo</strong> Com/sem afastamento, incidente.</div>
<div class="help-field"><strong>CAT</strong> Comunicação de Acidente de Trabalho emitida.</div>
<div class="help-field"><strong>Planos de ação</strong> Correções com responsável e prazo.</div>
<h2>Afastamentos</h2>
<div class="help-field"><strong>Tipo</strong> INSS, acidentário, doença ocupacional.</div>
<div class="help-field"><strong>CID</strong> Diagnóstico (autocomplete catálogo CID).</div>
<div class="help-field"><strong>Período</strong> Início e retorno previsto/efetivo.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>CID inválido</dt><dd>Selecione do catálogo CID-10 cadastrado.</dd>
<dt>Acidente sem eSocial S-2210</dt><dd>Gere evento na fila após conclusão do registro.</dd>
<dt>Afastamento ativo após retorno</dt><dd>Informe data de retorno efetivo.</dd>
<dt>Plano de ação aberto</dt><dd>Encerre ou prorrogue com justificativa.</dd>
</dl>',
];

$docs = array_merge($docs, $sst);
foreach ($docs as $rel => $c) w($rel, $c);
echo count($docs) . " arquivos lote 5.\n";
