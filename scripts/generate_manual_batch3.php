<?php
/** Gera lotes 3-8 do manual. Uso: php scripts/generate_manual_batch3.php */
declare(strict_types=1);

function w(string $rel, string $c): void {
    $p = dirname(__DIR__) . '/docs/manual/content/' . $rel;
    $d = dirname($p);
    if (!is_dir($d)) mkdir($d, 0775, true);
    file_put_contents($p, $c);
    echo "OK $rel\n";
}

$docs = [];

// COMUNICACAO
$docs += [
'comunicacao/com-visao-geral.html' => '<h1>Visão geral — Comunicação Interna</h1>
<p>Canal corporativo de informativos, timeline social, eventos e gamificação para engajamento dos colaboradores.</p>
<p><strong>Quem acessa:</strong> todos com permissão no módulo; moderadores e RH para gestão de conteúdo.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Publique <strong>Informativos</strong> oficiais (comunicados, políticas).</li>
<li>Alimente a <strong>Timeline</strong> com posts e interações.</li>
<li>Divulgue <strong>Eventos corporativos</strong> com inscrições.</li>
<li>Configure <strong>Gamificação</strong> (regras, quizzes, ranking).</li>
<li>Modere conteúdo inadequado quando necessário.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Informativos</strong> Comunicados formais com audiência segmentada.</div>
<div class="help-field"><strong>Timeline</strong> Feed social interno com curtidas e comentários.</div>
<div class="help-field"><strong>Eventos</strong> Agenda de eventos com confirmação de presença.</div>
<div class="help-field"><strong>Gamificação</strong> Pontos, quizzes e ranking de engajamento.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Post não aparece na timeline</dt><dd>Aguardando moderação ou audiência não inclui seu perfil.</dd>
<dt>Informativo não notifica</dt><dd>Verifique canal de notificação e escopo de destinatários.</dd>
<dt>Pontos não creditados</dt><dd>Confirme regra ativa e prazo da ação na gamificação.</dd>
<dt>Erro ao comentar</dt><dd>Permissão <em>Timeline</em> ou moderação bloqueou interações.</dd>
</dl>',

'comunicacao/com-informativos.html' => '<h1>Informativos</h1>
<p>Comunicados oficiais da empresa com controle de audiência, validade e confirmação de leitura.</p>
<p><strong>Quem acessa:</strong> comunicação interna e RH com <em>ListInformativos</em>, <em>CreateInformativo</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Comunicação → Informativos → Novo</strong>.</li>
<li>Informe título, conteúdo (rich text), imagem de capa se desejado.</li>
<li>Defina audiência: todos, departamento, cargo ou lista específica.</li>
<li>Configure validade (publicação e expiração).</li>
<li>Publique e acompanhe taxa de leitura.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Título</strong> Assunto exibido na listagem e notificação.</div>
<div class="help-field"><strong>Conteúdo</strong> Corpo do comunicado com formatação.</div>
<div class="help-field"><strong>Audiência</strong> Quem deve visualizar o informativo.</div>
<div class="help-field"><strong>Exige confirmação</strong> Colaborador marca como lido.</div>
<div class="help-field"><strong>Status</strong> Rascunho, publicado, expirado.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Colaborador não recebeu</dt><dd>Audiência não inclui seu departamento; verifique filtros.</dd>
<dt>Confirmação não registra</dt><dd>Usuário deve abrir o informativo completo, não só a notificação.</dd>
<dt>Imagem não carrega</dt><dd>Formato ou tamanho excede limite; use JPG/PNG otimizado.</dd>
<dt>Informativo sumiu da listagem</dt><dd>Data de expiração atingida; republicar com nova validade.</dd>
</dl>',

'comunicacao/com-timeline.html' => '<h1>Timeline</h1>
<p>Feed social interno para posts, comentários, curtidas e compartilhamento entre colaboradores.</p>
<p><strong>Quem acessa:</strong> colaboradores com <em>Timeline</em>; moderadores com <em>TimelineModerate</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>Comunicação → Timeline</strong>.</li>
<li>Crie post com texto, imagem ou anexo conforme política da empresa.</li>
<li>Interaja curtindo ou comentando posts de colegas.</li>
<li>Moderadores revisam denúncias em <strong>Moderação da timeline</strong>.</li>
<li>Posts aprovados geram pontos se gamificação estiver ativa.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Post</strong> Texto e mídia publicados pelo colaborador.</div>
<div class="help-field"><strong>Comentário</strong> Resposta vinculada ao post.</div>
<div class="help-field"><strong>Moderação</strong> Fila de posts pendentes ou reportados.</div>
<div class="help-field"><strong>Regras de uso</strong> Política de conteúdo exibida no portal.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Post aguardando aprovação</dt><dd>Moderação prévia ativa; aguarde moderador ou contate RH.</dd>
<dt>Não consigo anexar arquivo</dt><dd>Extensão ou tamanho não permitidos pela configuração.</dd>
<dt>Comentário removido</dt><dd>Moderador excluiu por violação de política.</dd>
<dt>Feed vazio</dt><dd>Filtro de filial ou permissão restritiva; confirme escopo.</dd>
</dl>',

'comunicacao/com-eventos.html' => '<h1>Eventos corporativos</h1>
<p>Cadastro e divulgação de eventos internos com data, local, capacidade e confirmação de presença.</p>
<p><strong>Quem acessa:</strong> RH e eventos com <em>ListCompanyEvents</em>; colaboradores para inscrição.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Crie evento com título, descrição, data/hora e local.</li>
<li>Defina capacidade máxima e prazo de inscrição.</li>
<li>Selecione público-alvo ou deixe aberto a todos.</li>
<li>Publique; colaboradores confirmam presença pelo portal.</li>
<li>Exporte lista de inscritos após encerramento das inscrições.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Título / Descrição</strong> Identificação e detalhes do evento.</div>
<div class="help-field"><strong>Data e hora</strong> Início e fim; integração com calendário quando configurada.</div>
<div class="help-field"><strong>Local / Link</strong> Presencial ou URL para evento online.</div>
<div class="help-field"><strong>Capacidade</strong> Limite de inscrições.</div>
<div class="help-field"><strong>Inscritos</strong> Lista de confirmações.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Inscrição encerrada</dt><dd>Prazo ou capacidade esgotada; aumente limite se necessário.</dd>
<dt>Evento não aparece</dt><dd>Fora do período de divulgação ou audiência restrita.</dd>
<dt>Duplicidade de inscrição</dt><dd>Sistema impede segunda confirmação do mesmo usuário.</dd>
<dt>Lista de inscritos incompleta</dt><dd>Exporte após sincronização; confira filtros de status.</dd>
</dl>',

'comunicacao/com-gamificacao.html' => '<h1>Gamificação</h1>
<p>Programa de engajamento com regras de pontuação na timeline, quizzes, extrato de pontos e ranking.</p>
<p><strong>Quem acessa:</strong> RH com gestão; colaboradores para quizzes e ranking.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Configure <strong>Regras (timeline)</strong> — pontos por post, comentário, curtida.</li>
<li>Cadastre <strong>Quizzes</strong> com perguntas e pontuação.</li>
<li>Divulgue <strong>Quizzes disponíveis</strong> aos colaboradores.</li>
<li>Acompanhe <strong>Extrato de pontos</strong> e <strong>Ranking</strong>.</li>
<li>Use <strong>Dashboard RH de engajamento</strong> para KPIs.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Regra</strong> Ação (post, quiz) e pontos atribuídos.</div>
<div class="help-field"><strong>Quiz</strong> Perguntas, alternativas e nota mínima.</div>
<div class="help-field"><strong>Extrato</strong> Histórico de créditos e débitos de pontos.</div>
<div class="help-field"><strong>Ranking</strong> Classificação por período.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Pontos não aparecem no extrato</dt><dd>Regra inativa ou ação fora do período configurado.</dd>
<dt>Quiz não libera certificado/pontos</dt><dd>Nota abaixo do mínimo; permitir nova tentativa se configurado.</dd>
<dt>Ranking desatualizado</dt><dd>Processamento batch; aguarde ou execute job de consolidação.</dd>
<dt>Colaborador não vê quizzes</dt><dd>Audiência ou permissão <em>GamificationQuizCatalog</em> ausente.</dd>
</dl>',
];

// CRM
$docs += [
'crm/crm-visao-geral.html' => '<h1>Visão geral — CRM</h1>
<p>Gestão comercial: parceiros, oportunidades, pipeline Kanban, atividades, dashboards e automações.</p>
<p><strong>Quem acessa:</strong> equipe comercial e gestores com permissões <em>Crm*</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Parceiros</strong> (leads, clientes, prospects).</li>
<li>Crie <strong>Oportunidades</strong> vinculadas a parceiros e vendedores.</li>
<li>Mova cards no <strong>Pipeline</strong> conforme estágio de venda.</li>
<li>Registre <strong>Atividades</strong> (ligações, reuniões, tarefas).</li>
<li>Acompanhe <strong>Dashboards</strong> e configure tags/campos customizados.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Parceiro</strong> Conta comercial no CRM.</div>
<div class="help-field"><strong>Oportunidade</strong> Negócio em andamento com valor e previsão.</div>
<div class="help-field"><strong>Pipeline</strong> Estágios visuais do funil.</div>
<div class="help-field"><strong>Atividade</strong> Compromisso ou tarefa comercial.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Oportunidade não aparece no pipeline</dt><dd>Estágio ou filtro de vendedor oculta o card.</dd>
<dt>Valor do funil incorreto</dt><dd>Confirme moeda, probabilidade e oportunidades ganhas/perdidas.</dd>
<dt>Automação não disparou</dt><dd>Verifique gatilho, condições e se automação está ativa.</dd>
<dt>Campo customizado não salva</dt><dd>Campo pode estar inativo ou não vinculado ao tipo de registro.</dd>
</dl>',

'crm/crm-dashboards.html' => '<h1>Dashboards CRM</h1>
<p>Indicadores comerciais: funil, conversão, metas e visão gerencial consolidada.</p>
<p><strong>Quem acessa:</strong> vendedores (<em>CrmDashboard</em>) e gestores (<em>CrmManagerDashboard</em>).</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>CRM → Dashboard CRM</strong> para visão operacional.</li>
<li>Use <strong>Dashboard Gerencial</strong> para KPIs de equipe e período.</li>
<li>Aplique filtros de vendedor, período e tag.</li>
<li>Clique em indicadores para drill-down nas listagens.</li>
<li>Exporte ou imprima relatórios quando disponível.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Funil</strong> Oportunidades por estágio e valor.</div>
<div class="help-field"><strong>Conversão</strong> Taxa entre estágios ou ganho/perda.</div>
<div class="help-field"><strong>Meta vs. realizado</strong> Comparativo de vendas.</div>
<div class="help-field"><strong>Atividades pendentes</strong> Tarefas atrasadas da equipe.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Dashboard zerado</dt><dd>Sem oportunidades no período ou filtro de vendedor restritivo.</dd>
<dt>Valores divergentes do pipeline</dt><dd>Dashboard pode excluir oportunidades perdidas; alinhe filtros.</dd>
<dt>Gestor não vê equipe</dt><dd>Permissão gerencial ou hierarquia de vendedores não configurada.</dd>
<dt>Gráfico não carrega</dt><dd>Erro de permissão ou timeout; reduza intervalo de datas.</dd>
</dl>',

'crm/crm-pipeline.html' => '<h1>Pipeline de vendas</h1>
<p>Quadro Kanban das oportunidades por estágio do funil com drag-and-drop para avançar negociações.</p>
<p><strong>Quem acessa:</strong> vendedores e gestores com <em>CrmKanbanPipeline</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Abra <strong>CRM → Pipeline de Vendas</strong>.</li>
<li>Visualize colunas por estágio (Prospecção, Proposta, Negociação, Fechado).</li>
<li>Arraste o card da oportunidade para o próximo estágio.</li>
<li>Clique no card para editar valor, probabilidade e previsão de fechamento.</li>
<li>Marque como <em>Ganha</em> ou <em>Perdida</em> no estágio final.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Estágio</strong> Coluna do funil; ordem define fluxo.</div>
<div class="help-field"><strong>Card</strong> Resumo: parceiro, valor, vendedor, dias no estágio.</div>
<div class="help-field"><strong>Probabilidade</strong> Peso para forecast.</div>
<div class="help-field"><strong>Previsão de fechamento</strong> Data esperada do negócio.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Não consigo arrastar card</dt><dd>Permissão de edição ausente ou oportunidade bloqueada.</dd>
<dt>Estágio não aparece</dt><dd>Configure estágios em Configurações ou contate administrador.</dd>
<dt>Card sumiu do pipeline</dt><dd>Oportunidade ganha/perdida ou filtro de vendedor ativo.</dd>
<dt>Valor total da coluna errado</dt><dd>Verifique moeda e oportunidades arquivadas.</dd>
</dl>',

'crm/crm-parceiros.html' => '<h1>Parceiros CRM</h1>
<p>Cadastro de leads, prospects e clientes comerciais com contatos, endereço e histórico de oportunidades.</p>
<p><strong>Quem acessa:</strong> equipe comercial com <em>CrmListPartners</em>, <em>CrmCreatePartner</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>CRM → Parceiros → Listar</strong>.</li>
<li>Cadastre novo parceiro com razão social, CNPJ/CPF e segmento.</li>
<li>Adicione contatos (nome, e-mail, telefone, cargo).</li>
<li>Vincule tags e campos customizados.</li>
<li>Crie oportunidade a partir da ficha do parceiro.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Razão social / Nome</strong> Identificação do parceiro.</div>
<div class="help-field"><strong>CNPJ / CPF</strong> Documento; validação de duplicidade.</div>
<div class="help-field"><strong>Contatos</strong> Pessoas de relacionamento.</div>
<div class="help-field"><strong>Tags</strong> Classificação para filtros e automações.</div>
<div class="help-field"><strong>Responsável</strong> Vendedor owner da conta.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>CNPJ duplicado</dt><dd>Parceiro já existe; busque antes de criar ou mescle registros.</dd>
<dt>Contato não recebe e-mail do CRM</dt><dd>E-mail inválido ou integração de envio não configurada.</dd>
<dt>Parceiro não aparece na busca</dt><dd>Filtro de responsável ou tag exclui registro.</dd>
<dt>Histórico de oportunidades vazio</dt><dd>Oportunidades podem estar vinculadas a outro parceiro homônimo.</dd>
</dl>',

'crm/crm-oportunidades.html' => '<h1>Oportunidades</h1>
<p>Negócios em andamento com valor, estágio, parceiro, produtos e previsão de fechamento.</p>
<p><strong>Quem acessa:</strong> vendedores com <em>CrmListOpportunities</em>, <em>CrmCreateOpportunity</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Crie oportunidade selecionando parceiro e título do negócio.</li>
<li>Informe valor estimado, moeda e probabilidade.</li>
<li>Defina estágio inicial e vendedor responsável.</li>
<li>Adicione itens/produtos se aplicável.</li>
<li>Atualize estágio no pipeline ou na ficha até ganho/perda.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Título</strong> Nome do negócio.</div>
<div class="help-field"><strong>Parceiro</strong> Cliente ou prospect vinculado.</div>
<div class="help-field"><strong>Valor</strong> Montante estimado da venda.</div>
<div class="help-field"><strong>Estágio</strong> Posição no funil.</div>
<div class="help-field"><strong>Status</strong> Aberta, ganha, perdida.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Oportunidade sem parceiro</dt><dd>Campo obrigatório; cadastre ou selecione parceiro existente.</dd>
<dt>Forecast incorreto</dt><dd>Ajuste probabilidade e data de previsão regularmente.</dd>
<dt>Não consigo marcar como ganha</dt><dd>Permissão ou estágio final não configurado.</dd>
<dt>Produtos não somam no valor</dt><dd>Recalcule total ou use valor manual consolidado.</dd>
</dl>',

'crm/crm-atividades.html' => '<h1>Atividades CRM</h1>
<p>Agenda comercial: ligações, reuniões, e-mails e tarefas vinculadas a parceiros e oportunidades.</p>
<p><strong>Quem acessa:</strong> vendedores com <em>CrmListActivities</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Acesse <strong>CRM → Atividades → Agenda</strong>.</li>
<li>Crie atividade com tipo, data/hora e vínculo (parceiro/oportunidade).</li>
<li>Defina lembrete se disponível.</li>
<li>Execute a atividade e registre outcome (concluída, reagendada).</li>
<li>Visualize atividades atrasadas no dashboard.</li>
</ol>
<h2>Campos e telas</h2>
<div class="help-field"><strong>Tipo</strong> Ligação, reunião, e-mail, tarefa.</div>
<div class="help-field"><strong>Data/hora</strong> Agendamento e duração.</div>
<div class="help-field"><strong>Vínculo</strong> Parceiro ou oportunidade relacionada.</div>
<div class="help-field"><strong>Status</strong> Pendente, concluída, cancelada.</div>
<div class="help-field"><strong>Notas</strong> Registro do que foi tratado.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Atividade não aparece na agenda</dt><dd>Filtro de vendedor ou data; expanda intervalo.</dd>
<dt>Lembrete não chegou</dt><dd>Notificações desabilitadas ou e-mail incorreto.</dd>
<dt>Não consigo vincular oportunidade</dt><dd>Oportunidade deve pertencer ao mesmo parceiro.</dd>
<dt>Atividades atrasadas acumuladas</dt><dd>Conclua ou reagende em lote; revise carga do vendedor.</dd>
</dl>',

'crm/crm-configuracoes.html' => '<h1>Configurações CRM</h1>
<p>Tags, campos customizáveis e automações para adaptar o CRM ao processo comercial.</p>
<p><strong>Quem acessa:</strong> administradores CRM com <em>CrmListTags</em>, <em>CrmListCustomFields</em>, <em>CrmListAutomations</em>.</p>
<h2>Fluxo principal (passo a passo)</h2>
<ol>
<li>Cadastre <strong>Tags</strong> para segmentação de parceiros e oportunidades.</li>
<li>Crie <strong>Campos customizados</strong> (texto, número, lista) por entidade.</li>
<li>Configure <strong>Automações</strong>: gatilho (estágio, tag) → ação (e-mail, tarefa).</li>
<li>Teste automação com registro de homologação.</li>
<li>Documente tags e campos para a equipe comercial.</li>
</ol>
<h2 id="cfg-tags">Tags</h2>
<div class="help-field"><strong>Nome / Cor</strong> Identificação visual nos cards e listagens.</div>
<h2 id="cfg-campos">Campos customizados</h2>
<div class="help-field"><strong>Entidade</strong> Parceiro, oportunidade ou atividade.</div>
<div class="help-field"><strong>Tipo</strong> Texto, número, data, lista de opções.</div>
<div class="help-field"><strong>Obrigatório</strong> Exige preenchimento ao salvar.</div>
<h2 id="cfg-automacoes">Automações</h2>
<div class="help-field"><strong>Gatilho</strong> Evento que inicia (mudança de estágio, nova oportunidade).</div>
<div class="help-field"><strong>Ação</strong> Criar tarefa, enviar notificação, atribuir tag.</div>
<h2>Problemas comuns</h2>
<dl>
<dt>Campo custom não aparece na tela</dt><dd>Entidade ou ordem de exibição incorreta; reative campo.</dd>
<dt>Automação em loop</dt><dd>Gatilho dispara na própria ação; revise condições.</dd>
<dt>Tag não filtra pipeline</dt><dd>Aplique filtro de tag no Kanban; confirme vínculo no registro.</dd>
<dt>Exclusão de campo com dados</dt><dd>Dados históricos podem permanecer no banco; prefira inativar.</dd>
</dl>',
];

foreach ($docs as $rel => $html) w($rel, $html);
echo count($docs) . " arquivos.\n";
