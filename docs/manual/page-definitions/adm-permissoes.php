<?php

declare(strict_types=1);

/**
 * Definições detalhadas por tela — Administração → Permissões (ACL).
 * @return array<string, array{title: string, html: string}>
 */
return [
    'list-groups-pages' => [
        'title' => 'Grupos de Páginas — Listar',
        'html' => manual_doc(
            'Grupos de Páginas — Listar',
            'Lista os <strong>grupos de páginas</strong>, que organizam o menu de configuração ACL e agrupam páginas na matriz de permissões por área (Financeiro, SST, CRM, etc.).',
            'administradores com permissão <em>ListGroupsPages</em>; ações de cadastro/edição exigem <em>CreateGroupPage</em>, <em>UpdateGroupPage</em>, <em>DeleteGroupPage</em>.',
            [
                'Acesse <strong>Administração → Páginas → Grupos de Páginas</strong>.',
                'Filtre por <strong>Nome</strong> se houver muitos grupos.',
                'Use <strong>Cadastrar</strong> para criar um novo grupo antes de cadastrar páginas.',
                'Em cada linha, use <strong>Visualizar</strong>, <strong>Editar</strong> ou <strong>Apagar</strong> conforme sua permissão.',
                'Após criar grupos, prossiga para <strong>Pacotes</strong> e <strong>Páginas</strong> para montar o ACL.',
            ],
            [
                ['Nome (filtro)', 'Busca parcial no nome do grupo; não altera cadastros.'],
                ['Mostrar N registros', 'Paginação da listagem (10–100).'],
                ['ID', 'Identificador interno usado em vínculos com páginas.'],
                ['Nome', 'Rótulo exibido na matriz de permissões e agrupamento do menu ACL.'],
            ],
            [
                ['id' => '', 'title' => 'Relação com o ACL', 'body' => '<p>Grupo → Pacote → Página → Nível de acesso. Sem grupo, a página aparece como <em>Sem Grupo</em> na matriz.</p>'],
            ],
            [
                'Não consigo apagar grupo' => 'Pode haver páginas vinculadas; remova ou reassocie as páginas antes.',
                'Grupo não aparece na matriz' => 'Confirme se há páginas ativas vinculadas ao grupo.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'create-group-page' => [
        'title' => 'Grupos de Páginas — Cadastrar',
        'html' => manual_doc(
            'Grupos de Páginas — Cadastrar',
            'Cria um novo grupo lógico para classificar páginas do sistema na estrutura de permissões.',
            '<em>CreateGroupPage</em> (e <em>ListGroupsPages</em> para acessar o formulário).',
            [
                'Em <strong>Grupos de Páginas</strong>, clique em <strong>Cadastrar</strong>.',
                'Informe um <strong>Nome</strong> claro (ex.: <em>Financeiro</em>, <em>Segurança e Medicina</em>).',
                'Opcionalmente preencha <strong>Observação</strong> para documentar o propósito do grupo.',
                'Salve e verifique o grupo na listagem.',
                'Ao cadastrar páginas, selecione este grupo no campo <strong>Grupo</strong>.',
            ],
            [
                ['Nome', 'Obrigatório. Aparece na matriz de permissões agrupando as páginas.'],
                ['Observação', 'Texto interno para administradores; não afeta permissões.'],
            ],
            [],
            [
                'Nome duplicado ou vazio' => 'O sistema valida campos obrigatórios; use nome único e descritivo.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'update-group-page' => [
        'title' => 'Grupos de Páginas — Editar',
        'html' => manual_doc(
            'Grupos de Páginas — Editar',
            'Altera nome e observação de um grupo existente. O ID permanece o mesmo; páginas vinculadas mantêm o vínculo.',
            '<em>UpdateGroupPage</em>.',
            [
                'Na listagem, clique em <strong>Editar</strong> no grupo desejado.',
                'Ajuste <strong>Nome</strong> e/ou <strong>Observação</strong>.',
                'Salve; a matriz de permissões passará a exibir o novo nome do grupo.',
            ],
            [
                ['Nome', 'Atualiza o rótulo em toda a matriz ACL para páginas deste grupo.'],
                ['Observação', 'Apenas documentação administrativa.'],
            ],
            [],
            [
                'Alteração não reflete para usuários' => 'Nomes de grupo são cosméticos na matriz; permissões não mudam até alterar pacotes/níveis.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'view-group-page' => [
        'title' => 'Grupos de Páginas — Visualizar',
        'html' => manual_doc(
            'Grupos de Páginas — Visualizar',
            'Consulta dados do grupo em modo leitura, incluindo datas de cadastro e edição.',
            '<em>ViewGroupPage</em>.',
            [
                'Na listagem, clique em <strong>Visualizar</strong>.',
                'Confira ID, nome, observação e auditoria.',
                'Use <strong>Editar</strong> no cabeçalho se precisar alterar.',
            ],
            [
                ['ID', 'Chave usada no vínculo <code>adms_groups_page_id</code> das páginas.'],
                ['Cadastrado / Editado', 'Auditoria de criação e última alteração.'],
            ],
            [],
            [],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'list-packages' => [
        'title' => 'Pacotes — Listar',
        'html' => manual_doc(
            'Pacotes — Listar',
            'Lista <strong>pacotes de páginas</strong>: conjuntos de permissões que serão atribuídos aos níveis de acesso (perfis).',
            '<em>ListPackages</em>; cadastro/edição com <em>CreatePackage</em>, <em>UpdatePackage</em>, <em>DeletePackage</em>.',
            [
                'Acesse <strong>Administração → Páginas → Pacotes</strong>.',
                'Filtre por nome se necessário.',
                'Crie pacotes por perfil funcional (ex.: <em>CRM — Vendedor</em>, <em>Financeiro — Analista</em>).',
                'Associe páginas a cada pacote ao cadastrar/editar em <strong>Listar Páginas</strong>.',
                'Marque os pacotes nos <strong>Níveis de Acesso</strong> (Cadastro).',
            ],
            [
                ['Nome (filtro)', 'Busca na listagem sem alterar dados.'],
                ['Nome do pacote', 'Identificação usada ao vincular páginas e níveis de acesso.'],
            ],
            [
                ['id' => '', 'title' => 'Pacote vs. Grupo', 'body' => '<p><strong>Grupo</strong> organiza visualmente na matriz ACL. <strong>Pacote</strong> define quais páginas um perfil pode receber em bloco via nível de acesso.</p>'],
            ],
            [
                'Pacote vazio (sem páginas)' => 'Atribuir pacote a um nível não libera menu até haver páginas vinculadas ao pacote.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'create-package' => [
        'title' => 'Pacotes — Cadastrar',
        'html' => manual_doc(
            'Pacotes — Cadastrar',
            'Registra um novo pacote para agrupar páginas que serão liberadas juntas a um nível de acesso.',
            '<em>CreatePackage</em>.',
            [
                'Em <strong>Pacotes</strong>, clique em <strong>Cadastrar</strong>.',
                'Defina <strong>Nome</strong> alinhado ao perfil (ex.: <em>RH — Gestor</em>).',
                'Documente em <strong>Observação</strong> quais áreas o pacote cobre.',
                'Salve e vincule páginas em <strong>Cadastrar/Editar Página</strong> (campo Pacote).',
            ],
            [
                ['Nome', 'Exibido na seleção de pacotes ao editar páginas e níveis de acesso.'],
                ['Observação', 'Referência interna para equipe de TI.'],
            ],
            [],
            [],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'update-package' => [
        'title' => 'Pacotes — Editar',
        'html' => manual_doc(
            'Pacotes — Editar',
            'Altera nome e observação do pacote. Páginas já vinculadas permanecem associadas.',
            '<em>UpdatePackage</em>.',
            [
                'Na listagem, clique em <strong>Editar</strong>.',
                'Atualize os campos e salve.',
            ],
            [
                ['Nome', 'Renomear não altera permissões já concedidas; apenas o rótulo do pacote.'],
            ],
            [],
            [],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'view-package' => [
        'title' => 'Pacotes — Visualizar',
        'html' => manual_doc(
            'Pacotes — Visualizar',
            'Consulta dados do pacote e histórico de alterações (log disponível no cabeçalho).',
            '<em>ViewPackage</em>.',
            [
                'Abra <strong>Visualizar</strong> na listagem de pacotes.',
                'Use o botão de log para ver quem alterou o registro.',
            ],
            [],
            [],
            [],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'list-pages' => [
        'title' => 'Páginas (ACL) — Listar',
        'html' => manual_doc(
            'Páginas (ACL) — Listar',
            'Lista todas as <strong>páginas</strong> registradas no ACL: cada entrada mapeia um controller PHP a uma URL e controla aparecimento no menu e na matriz de permissões.',
            '<em>ListPages</em>; cadastro com <em>CreatePage</em>, edição <em>UpdatePage</em>.',
            [
                'Acesse <strong>Administração → Páginas → Páginas</strong>.',
                'Use filtros: ID, Nome, Controller, Grupo, Status, Pública, Padrão.',
                'Localize a tela desejada e edite para corrigir slug, pacote ou status.',
                'Cadastre novas páginas após criar controllers no código-fonte.',
                'Inclua a página no pacote do perfil e marque na matriz do nível de acesso.',
            ],
            [
                ['ID', 'Identificador interno da página no banco.'],
                ['Nome', 'Rótulo na matriz de permissões.'],
                ['Controller (filtro)', 'Busca pela classe PHP (ex.: <em>ListUsers</em>).'],
                ['Grupo (filtro)', 'Restringe às páginas de um grupo de páginas.'],
                ['Status', 'Ativa (1) ou Inativa (0) — inativa some do menu e da matriz ativa.'],
                ['Pública', 'Sim = rota acessível sem login (login, recuperar senha, etc.).'],
                ['Padrão', 'Sim = novos níveis de acesso recebem esta permissão automaticamente na matriz.'],
            ],
            [
                ['id' => '', 'title' => 'Impacto de Status e Pública', 'body' => '<p><strong>Inativa:</strong> página não é oferecida em novas configurações. <strong>Pública:</strong> ignora autenticação. <strong>Padrão:</strong> facilita bootstrap de novos perfis, mas não remove permissões já dadas.</p>'],
            ],
            [
                'Nova tela no código não aparece no menu' => 'Cadastre a página aqui com <code>controller_url</code> igual ao slug da rota.',
                'Controller_url errado' => 'Deve ser kebab-case minúsculo, igual à URL (ex.: <em>list-users</em>).',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'create-page' => [
        'title' => 'Páginas (ACL) — Cadastrar',
        'html' => manual_doc(
            'Páginas (ACL) — Cadastrar',
            'Registra uma nova rota/controller no ACL para que possa receber permissões e aparecer no menu.',
            '<em>CreatePage</em>.',
            [
                'Após implementar o controller em <code>app/adms/Controllers/</code>, abra <strong>Cadastrar Página</strong>.',
                'Preencha <strong>Nome</strong> descritivo para a matriz.',
                'Informe <strong>Classe (controller)</strong> em PascalCase (ex.: <em>ListUsers</em>).',
                'Informe <strong>URL (slug)</strong> em kebab-case (ex.: <em>list-users</em>) — deve coincidir com a rota.',
                'Informe <strong>Diretório</strong> da pasta do controller (sensível a maiúsculas no Linux).',
                'Selecione <strong>Pacote</strong> e <strong>Grupo</strong> adequados.',
                'Defina <strong>Status</strong>, <strong>Pública</strong> e <strong>Página Padrão</strong> conforme regras abaixo.',
                'Salve e inclua a página no pacote do nível de acesso; teste com usuário de homologação.',
            ],
            [
                ['Classe (controller PHP)', 'Nome exato da classe; o sistema resolve o arquivo em Controllers/{diretório}/.'],
                ['URL (slug)', 'Primeiro segmento da URL; usado em F1, menu e roteamento.'],
                ['Diretório', 'Subpasta em Controllers (ex.: users, accessLevels).'],
                ['Status — Ativa', 'Página disponível para permissões e menu.'],
                ['Status — Inativa', 'Página oculta; útil para descontinuar rotas.'],
                ['Pública — Sim', 'Rota sem login; sistema tende a conceder permissão na matriz (exceto super admin).'],
                ['Pública — Não', 'Exige autenticação e permissão explícita.'],
                ['Página Padrão — Sim', 'Novos níveis de acesso ganham esta permissão automaticamente; não remove permissões já existentes.'],
                ['Pacote', 'Vincula a página a um pacote para atribuição em massa via nível de acesso.'],
                ['Grupo', 'Agrupa visualmente na matriz de permissões.'],
                ['Observação', 'Documentação interna na matriz ACL.'],
            ],
            [
                ['id' => '', 'title' => 'Ordem recomendada', 'body' => '<ol><li>Deploy do controller</li><li>Cadastro da página ACL</li><li>Inclusão no pacote</li><li>Marcação no nível de acesso</li><li>Logout/login do usuário teste</li></ol>'],
            ],
            [
                'Erro 404 após cadastrar' => 'Slug ou diretório não bate com o controller real; confira case do diretório no servidor Linux.',
                'Pública marcada por engano' => 'Risco de segurança — rotas administrativas devem ser privadas.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'update-page' => [
        'title' => 'Páginas (ACL) — Editar',
        'html' => manual_doc(
            'Páginas (ACL) — Editar',
            'Altera metadados da página ACL. Mudar slug ou controller afeta roteamento e F1; mudar status afeta menu imediato após recarregar permissões.',
            '<em>UpdatePage</em>.',
            [
                'Localize a página em <strong>Listar Páginas</strong> e clique em <strong>Editar</strong>.',
                'Ajuste pacote, grupo, status ou observação.',
                'Evite alterar slug em produção sem atualizar links e bookmarks.',
                'Salve e valide com usuário de teste (logout/login).',
            ],
            [
                ['Status → Inativa', 'Remove a página das seleções ativas; usuários perdem acesso após recarregar sessão.'],
                ['Pública / Padrão', 'Mesmas regras do cadastro; desmarcar padrão não revoga permissões já concedidas.'],
                ['Pacote / Grupo', 'Reorganiza onde a página aparece na matriz e quais perfis a recebem via pacote.'],
            ],
            [],
            [
                'Alterei slug e F1 quebrou' => 'Atualize também o mapeamento se necessário; slug é a chave da rota e da ajuda contextual.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'view-page' => [
        'title' => 'Páginas (ACL) — Visualizar',
        'html' => manual_doc(
            'Páginas (ACL) — Visualizar',
            'Exibe todos os metadados da página ACL: controller, URL, pacote, grupo, flags e auditoria.',
            '<em>ViewPage</em>.',
            [
                'Use para auditar configuração antes de alterar permissões em produção.',
                'Confira se <code>controller_url</code> corresponde à URL real da tela.',
            ],
            [],
            [],
            [],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'list-permission' => [
        'title' => 'Matriz de permissões — Listar',
        'html' => manual_doc(
            'Matriz de permissões — Listar',
            'Exibe a <strong>matriz de permissões</strong> de um nível de acesso: todas as páginas agrupadas por grupo, com checkboxes para autorizar ou negar cada tela.',
            '<em>ListPermission</em> (acesso via <strong>Cadastro → Níveis de Acesso → Permissões</strong>).',
            [
                'Em <strong>Níveis de Acesso</strong>, abra o nível desejado e clique em <strong>Permissões</strong>.',
                'Use a busca <em>Buscar por grupo</em> para localizar áreas (Financeiro, SST…).',
                'Expanda/colapse grupos com os botões ou clique no cabeçalho do grupo.',
                'Marque ou desmarque checkboxes das páginas que o perfil deve acessar.',
                'Clique em <strong>Salvar</strong> para gravar a matriz (exceto nível Super Administrador, somente leitura).',
                'Exporte PDF para documentação de auditoria se necessário.',
                'Peça aos usuários <strong>logout/login</strong> para recarregar permissões da sessão.',
            ],
            [
                ['Buscar por grupo', 'Filtra visualmente grupos na matriz; não altera dados até salvar.'],
                ['Checkbox da página', 'Autoriza (marcado) ou remove permissão explícita (desmarcado) para aquele nível.'],
                ['Status / Tipo', 'Indica se a página está ativa e se é pública ou padrão.'],
                ['Expandir / Colapsar todos', 'Facilita navegação em matrizes grandes.'],
                ['Salvar', 'Persiste todas as alterações da matriz para o nível de acesso atual.'],
            ],
            [
                ['id' => '', 'title' => 'Super Administrador', 'body' => '<p>O nível Super Administrador tem matriz bloqueada (somente leitura) com acesso total implícito.</p>'],
                ['id' => '', 'title' => 'Erro 004', 'body' => '<p>Usuário com página de listagem mas sem página de <em>create/update/delete</em> recebe erro 004 na ação. Inclua todas as páginas do fluxo no pacote.</p>'],
            ],
            [
                'Salvei mas usuário não vê menu' => 'Confirme pacote no nível de acesso, página ativa e logout/login.',
                'Checkbox não salva' => 'Nível Super Admin é somente leitura; use outro nível de teste.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
    'update-permission' => [
        'title' => 'Matriz de permissões — Salvar',
        'html' => manual_doc(
            'Matriz de permissões — Salvar alterações',
            'Processa o envio do formulário da matriz de permissões ao clicar em <strong>Salvar</strong> na tela de permissões do nível de acesso.',
            '<em>UpdatePermission</em> (ação POST da matriz).',
            [
                'Altere os checkboxes desejados na matriz.',
                'Clique em <strong>Salvar</strong> uma única vez e aguarde confirmação.',
                'Valide com usuário vinculado ao nível (logout/login).',
            ],
            [
                ['adms_access_level_id', 'Identifica qual nível de acesso está sendo alterado.'],
                ['Permissões marcadas', 'Gravadas como autorizadas (permission = 1) para cada página.'],
                ['Permissões desmarcadas', 'Removem autorização explícita; comportamento depende de pacotes e páginas padrão.'],
            ],
            [],
            [
                'Alteração parcial não aplicada' => 'Verifique mensagem de erro CSRF ou sessão expirada; recarregue a página.',
            ],
            'adm-permissoes',
            'Permissões — visão geral'
        ),
    ],
];
