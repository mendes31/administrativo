<?php
/**
 * Gera arquivos HTML do manual (módulos não-SST + upgrade SST esqueleto).
 * Uso: php scripts/generate_manual_module_docs.php
 */
declare(strict_types=1);

$base = dirname(__DIR__) . '/docs/manual/content';

function writeHtml(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    file_put_contents($path, $content);
    echo "OK: $path\n";
}

function doc(string $h1, string $objetivo, string $quem, array $passos, array $campos = [], array $secoes = [], array $problemas = []): string
{
    $html = "<h1>{$h1}</h1>\n<p>{$objetivo}</p>\n\n";
    $html .= "<p><strong>Quem acessa:</strong> {$quem}</p>\n\n";
    $html .= "<h2>Fluxo principal (passo a passo)</h2>\n<ol>\n";
    foreach ($passos as $p) {
        $html .= "    <li>{$p}</li>\n";
    }
    $html .= "</ol>\n\n";
    foreach ($secoes as $sec) {
        $html .= "<h2{$sec['id']}>{$sec['title']}</h2>\n{$sec['body']}\n\n";
    }
    if ($campos) {
        $html .= "<h2>Campos e telas</h2>\n";
        foreach ($campos as $c) {
            $html .= "<div class=\"help-field\"><strong>{$c[0]}</strong> {$c[1]}</div>\n";
        }
        $html .= "\n";
    }
    $html .= "<h2>Problemas comuns</h2>\n<dl>\n";
    foreach ($problemas as $dt => $dd) {
        $html .= "    <dt>{$dt}</dt>\n    <dd>{$dd}</dd>\n";
    }
    $html .= "</dl>\n";
    return $html;
}

$files = [];

// ========== DASHBOARD ==========
$files["{$base}/dashboard/dashboard-visao-geral.html"] = doc(
    'Visão geral — Dashboard',
    'Painel inicial do sistema com indicadores consolidados, atalhos e widgets conforme permissões do usuário. É a primeira tela após o login para quem possui acesso.',
    'todos os usuários com permissão <em>Dashboard</em>; gestores veem KPIs ampliados conforme pacote de páginas.',
    [
        'Faça login e aguarde o carregamento do dashboard principal.',
        'Revise os cartões de indicadores (pendências, treinamentos, financeiro, etc.) disponíveis no seu perfil.',
        'Use os atalhos ou links nos widgets para ir direto ao módulo relacionado.',
        'Aplique filtros globais (filial, período) quando disponíveis no topo da página.',
        'Pressione <kbd>F1</kbd> em qualquer widget com ajuda contextual para abrir o tópico correspondente.',
    ],
    [
        ['Widgets', 'Blocos configuráveis com gráficos, contadores e listas resumidas.'],
        ['Filtro global', 'Restringe dados por filial ou unidade organizacional.'],
        ['Atalhos rápidos', 'Links para telas frequentes (cadastros, pendências, relatórios).'],
        ['Indicadores SST/RH', 'Aparecem se o usuário tiver permissão nos respectivos módulos.'],
    ],
    [],
    [
        'Dashboard vazio ou sem widgets' => 'Verifique se seu nível de acesso inclui a página <em>Dashboard</em> e se há dados no período filtrado.',
        'Indicador diverge do relatório detalhado' => 'Confirme filtros de filial/período; widgets podem usar cache ou janela de tempo diferente.',
        'Não consigo acessar o dashboard' => 'Permissão <em>Dashboard</em> ausente no nível de acesso. Solicite ao administrador.',
        'F1 abre documentação em desenvolvimento' => 'Alguns widgets ainda não têm tópico dedicado; consulte a visão geral do módulo relacionado.',
    ]
);

// ========== ADMINISTRAÇÃO ==========
$files["{$base}/administracao/adm-visao-geral.html"] = doc(
    'Visão geral — Administração',
    'Módulo central de governança do sistema: configurações de integração, logs de auditoria, controle de permissões (páginas, pacotes, grupos) e treinamentos obrigatórios globais.',
    'administradores de TI, superusuários e perfis com permissões em <em>Administração</em>.',
    [
        'Revise <strong>Configurações</strong> (e-mail, SAP, WhatsApp, senha, filiais) antes de liberar o sistema a novos usuários.',
        'Configure <strong>Logs</strong> e retenção para auditoria e segurança.',
        'Cadastre <strong>Grupos de Páginas → Pacotes → Páginas</strong> e associe aos níveis de acesso.',
        'Defina <strong>Treinamentos Obrigatórios</strong> que bloqueiam login quando vencidos.',
        'Monitore usuários conectados e últimos acessos periodicamente.',
    ],
    [
        ['Configurações', 'Parâmetros globais de e-mail, notificações, integrações e filiais.'],
        ['Logs', 'Rastreio de acessos, alterações e sessões ativas.'],
        ['Páginas / ACL', 'Estrutura de permissões do sistema.'],
        ['Treinamentos obrigatórios', 'Regras de bloqueio de acesso por pendência de treinamento.'],
    ],
    [
        ['id' => '', 'title' => 'Ordem recomendada de implantação', 'body' => '<ol><li>Filiais e e-mail</li><li>Pacotes e níveis de acesso</li><li>Política de senha</li><li>Integrações (SAP, WhatsApp)</li><li>Logs e notificações</li></ol>'],
    ],
    [
        'Usuário não vê menu esperado' => 'Confira pacote no nível de acesso e se a página está ativa em <em>Listar Páginas</em>.',
        'E-mail não envia' => 'Valide SMTP em Configuração de E-mail e teste com notificação automática.',
        'Integração SAP falha' => 'Revise URL, credenciais e permissão <em>SapApiConfig</em>; consulte logs de alteração.',
        'Treinamento obrigatório não bloqueia' => 'Verifique se o treinamento está marcado como obrigatório e se o job de status foi executado.',
    ]
);

$files["{$base}/administracao/adm-configuracoes.html"] = doc(
    'Configurações do sistema',
    'Centraliza parâmetros de e-mail, notificações automáticas, calendário, integrações (SAP, MCP, Push PWA), WhatsApp, política de senha e cadastro de filiais.',
    'administradores com permissões <em>EmailConfig</em>, <em>NotificationSettings</em>, <em>SapApiConfig</em>, <em>WhatsAppConfig</em>, <em>PasswordPolicy</em>, <em>ListBranches</em>, etc.',
    [
        'Acesse <strong>Administração → Configurações</strong> e escolha a tela desejada.',
        'Em <strong>E-mail</strong>, informe servidor SMTP, porta, usuário, senha e remetente padrão; salve e teste envio.',
        'Em <strong>Notificações Automáticas</strong>, ative/desative gatilhos (treinamentos, folha, SST) e canais.',
        'Configure <strong>SAP API</strong> com endpoint, autenticação e timeout conforme documentação da integração.',
        'Ajuste <strong>WhatsApp</strong> (token, número, webhook) se usar mensagens transacionais.',
        'Defina <strong>Política de Senha</strong> (complexidade, expiração, histórico) e cadastre <strong>Filiais</strong> usadas no filtro global.',
    ],
    [
        ['Servidor SMTP / Porta', 'Host e porta do servidor de e-mail (TLS recomendado).'],
        ['Usuário / Senha SMTP', 'Credenciais de autenticação do remetente.'],
        ['Remetente padrão', 'Nome e e-mail exibidos nas mensagens automáticas.'],
        ['SAP — URL base', 'Endpoint da API SAP Business One ou integração configurada.'],
        ['WhatsApp — Token', 'Token da API oficial ou provedor homologado.'],
        ['Política — Complexidade', 'Tamanho mínimo, caracteres especiais, validade em dias.'],
        ['Filial — Código / Nome', 'Identificação usada em filtros e relatórios multi-unidade.'],
    ],
    [
        ['id' => ' id="cfg-email"', 'title' => 'Configuração de E-mail', 'body' => '<p>Teste sempre após alterar credenciais. Falhas de SMTP aparecem nos logs de acesso/alteração.</p>'],
        ['id' => ' id="cfg-notificacoes"', 'title' => 'Notificações automáticas', 'body' => '<p>Cada evento (treinamento vencendo, documento folha, pendência SST) pode disparar e-mail, push ou WhatsApp conforme habilitado.</p>'],
    ],
    [
        'E-mails caem em spam' => 'Configure SPF/DKIM no domínio do remetente; use endereço corporativo válido.',
        'SAP retorna 401/403' => 'Credenciais expiradas ou usuário sem permissão na API; regenere token.',
        'WhatsApp não entrega' => 'Verifique template aprovado, número verificado e limite da API.',
        'Política de senha não aplica' => 'Usuários existentes podem precisar trocar senha no próximo login; confirme se a política está ativa.',
        'Filial não aparece no filtro', 'Cadastro inativo ou usuário sem vínculo à filial no perfil.',
    ]
);

$files["{$base}/administracao/adm-logs.html"] = doc(
    'Logs e auditoria',
    'Consulta de log de acessos, alterações em registros, usuários conectados, último acesso e configurações de retenção de logs.',
    'administradores e auditores com permissões <em>ListLogAcessos</em>, <em>ListLogAlteracoes</em>, <em>ListConnectedUsers</em>, <em>LogSettings</em>.',
    [
        'Em <strong>Log de Acessos</strong>, filtre por usuário, IP, data e ação (login, logout, falha).',
        'Em <strong>Log de Alterações</strong>, busque por tabela, registro ou usuário que modificou dados.',
        'Use <strong>Usuários conectados</strong> para ver sessões ativas e encerrar se necessário.',
        'Consulte <strong>Último acesso</strong> para identificar contas inativas.',
        'Ajuste retenção e nível de detalhe em <strong>Configurações de Log</strong>.',
    ],
    [
        ['Data/hora', 'Momento do evento registrado.'],
        ['Usuário', 'Conta que executou a ação.'],
        ['IP / User-Agent', 'Origem da requisição (acessos).'],
        ['Tabela / Registro', 'Entidade alterada (log de alterações).'],
        ['Ação', 'INSERT, UPDATE, DELETE ou tipo de acesso.'],
        ['Retenção (dias)', 'Período de armazenamento configurável.'],
    ],
    [],
    [
        'Log de alteração vazio' => 'Confirme se auditoria está habilitada em Configurações de Log para a tabela.',
        'Muitos registros — consulta lenta' => 'Use filtros de data estreitos; considere exportar e arquivar.',
        'Usuário aparece conectado após logout' => 'Sessão pode expirar por timeout; verifique configuração de sessão PHP.',
        'Não vejo menu de Logs' => 'Permissões específicas por sub-tela; solicite pacote de auditoria ao administrador.',
    ]
);

$files["{$base}/administracao/adm-permissoes.html"] = doc(
    'Permissões — Grupos, Pacotes e Páginas',
    'Estrutura ACL do sistema: grupos organizam pacotes de funcionalidades; pacotes agrupam páginas (controllers); níveis de acesso recebem pacotes.',
    'administradores de segurança com <em>ListGroupsPages</em>, <em>ListPackages</em>, <em>ListPages</em> e <em>ListAccessLevels</em>.',
    [
        'Cadastre <strong>Grupos de Páginas</strong> por área (ex.: Financeiro, SST).',
        'Crie <strong>Pacotes</strong> vinculados a grupos; cada pacote representa um perfil funcional.',
        'Registre <strong>Páginas</strong> (slug do controller) e associe aos pacotes corretos.',
        'Em <strong>Cadastro → Níveis de Acesso</strong>, marque os pacotes permitidos para cada perfil.',
        'Teste com usuário de homologação antes de aplicar em produção.',
    ],
    [
        ['Grupo', 'Agrupamento lógico de pacotes no menu de configuração.'],
        ['Pacote', 'Conjunto de páginas liberadas juntas (ex.: CRM — Vendedor).'],
        ['Página', 'Entrada ACL mapeada ao controller (ex.: <em>CrmListOpportunities</em>).'],
        ['Nível de acesso', 'Perfil atribuído ao usuário; herda pacotes marcados.'],
        ['Permissão vs. Página', 'Menu usa permissão da página; negar página oculta o item.'],
    ],
    [
        ['id' => '', 'title' => 'Modelo ACL', 'body' => '<pre style="background:#f8f9fa;padding:1rem;border-radius:.25rem;">Usuário → Nível de Acesso → Pacotes → Páginas (controllers)</pre><p>Erro <strong>004</strong> indica falta de permissão na ação específica dentro da página.</p>'],
    ],
    [
        'Menu aparece mas ação retorna erro 004' => 'Usuário tem a página de listagem, mas não a de criar/editar; inclua páginas filhas no pacote.',
        'Nova tela não aparece no menu' => 'Cadastre a página em <em>Listar Páginas</em> e inclua no pacote do nível de acesso.',
        'Permissão duplicada confusa' => 'Evite pacotes sobrepostos; documente qual perfil usa qual pacote.',
        'Alteração não reflete imediatamente' => 'Usuário precisa logout/login para recarregar permissões da sessão.',
    ]
);

$files["{$base}/administracao/adm-treinamentos-obrigatorios.html"] = doc(
    'Treinamentos obrigatórios (bloqueio de acesso)',
    'Define treinamentos corporativos que impedem login ou exibem aviso até conclusão, independente do módulo SST ou RH.',
    'RH, compliance e administradores com <em>ListMandatoryTrainings</em>.',
    [
        'Acesse <strong>Administração → Treinamentos Obrigatórios</strong>.',
        'Cadastre ou selecione treinamento, validade e público-alvo (todos, cargo, departamento).',
        'Marque como <em>obrigatório</em> e defina se bloqueia login ou apenas exibe banner.',
        'Vincule ao catálogo de treinamentos RH quando aplicável.',
        'Comunique colaboradores antes de ativar bloqueio em produção.',
    ],
    [
        ['Treinamento', 'Curso ou conteúdo que deve ser concluído.'],
        ['Validade (dias)', 'Prazo para reciclagem após conclusão.'],
        ['Bloquear login', 'Impede acesso ao sistema até regularização.'],
        ['Escopo', 'Todos, cargo específico, departamento ou filial.'],
        ['Status', 'Ativo/inativo controla se a regra é avaliada.'],
    ],
    [],
    [
        'Colaborador bloqueado indevidamente' => 'Verifique conclusão registrada em Status de Treinamentos e data de validade.',
        'Bloqueio não ocorre' => 'Treinamento pode não estar marcado como obrigatório ou job de status não rodou.',
        'Conflito com treinamento SST' => 'SST e obrigatório global são regras distintas; regularize ambos se aplicável.',
        'Gestor precisa acessar para liberar exceção' => 'Use perfil com permissão de aplicar treinamento ou desative bloqueio temporariamente.',
    ]
);

// Continue in part 2 - file too large, split generation

foreach ($files as $path => $content) {
    writeHtml($path, $content);
}

echo count($files) . " arquivos gerados (lote 1).\n";
