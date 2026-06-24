<?php

declare(strict_types=1);

/**
 * Funções compartilhadas para geração de HTML do manual.
 */

function manual_doc(
    string $h1,
    string $objetivo,
    string $quem,
    array $passos,
    array $campos = [],
    array $secoes = [],
    array $problemas = [],
    ?string $parentTopic = null,
    ?string $parentLabel = null
): string {
    $html = "<h1>{$h1}</h1>\n<p>{$objetivo}</p>\n\n";

    if ($parentTopic !== null && $parentLabel !== null) {
        $html .= '<p class="help-note"><strong>Visão geral do módulo:</strong> '
            . "consulte também o tópico <em>{$parentLabel}</em> "
            . "(<code>{$parentTopic}</code>) para o fluxo completo da área.</p>\n\n";
    }

    $html .= "<p><strong>Quem acessa:</strong> {$quem}</p>\n\n";
    $html .= "<h2>Fluxo principal (passo a passo)</h2>\n<ol>\n";
    foreach ($passos as $p) {
        $html .= "    <li>{$p}</li>\n";
    }
    $html .= "</ol>\n\n";

    foreach ($secoes as $sec) {
        $idAttr = $sec['id'] ?? '';
        $html .= "<h2{$idAttr}>{$sec['title']}</h2>\n{$sec['body']}\n\n";
    }

    if ($campos !== []) {
        $html .= "<h2>Campos e parâmetros</h2>\n";
        $html .= '<p class="text-muted small">O que cada campo afeta no sistema.</p>' . "\n";
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

function manual_slug_to_permission(string $slug): string
{
    return str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
}

function manual_humanize_slug(string $slug): string
{
    return ucwords(str_replace('-', ' ', $slug));
}

/** @return 'list'|'create'|'update'|'view'|'other' */
function manual_infer_action(string $slug): string
{
    if (str_starts_with($slug, 'list-')) {
        return 'list';
    }
    if (str_starts_with($slug, 'create-')) {
        return 'create';
    }
    if (str_starts_with($slug, 'update-')) {
        return 'update';
    }
    if (str_starts_with($slug, 'view-')) {
        return 'view';
    }

    return 'other';
}

function manual_skeleton_doc(
    string $slug,
    string $screenTitle,
    string $permission,
    string $moduleTitle,
    ?string $parentTopic = null,
    ?string $parentLabel = null
): string {
    $action = manual_infer_action($slug);
    $perm = "<em>{$permission}</em>";

    $objetivos = [
        'list' => "Lista e filtra registros de <strong>{$screenTitle}</strong> no módulo {$moduleTitle}. "
            . 'Use esta tela para localizar itens, acessar visualização/edição e iniciar novos cadastros.',
        'create' => "Cadastra um novo registro em <strong>{$screenTitle}</strong>. "
            . 'Preencha os campos obrigatórios e salve; o registro passará a aparecer nas listagens e poderá receber permissões conforme o pacote.',
        'update' => "Altera um registro existente de <strong>{$screenTitle}</strong>. "
            . 'Mudanças em campos de status ou vínculos podem afetar menu, permissões ou integrações.',
        'view' => "Exibe os dados de um registro de <strong>{$screenTitle}</strong> em modo somente leitura, "
            . 'com atalhos para editar ou excluir quando permitido.',
        'other' => "Tela <strong>{$screenTitle}</strong> do módulo {$moduleTitle}. "
            . 'Consulte os campos disponíveis na interface e o fluxo abaixo.',
    ];

    $passosList = [
        'list' => [
            "Acesse o menu <strong>{$moduleTitle}</strong> e abra <strong>{$screenTitle}</strong>.",
            'Use os filtros no topo para restringir por nome, status ou outros critérios.',
            'Ajuste <em>Mostrar N registros</em> se precisar ver mais linhas por página.',
            'Clique em <strong>Visualizar</strong>, <strong>Editar</strong> ou <strong>Cadastrar</strong> conforme sua permissão.',
            'Pressione <kbd>F1</kbd> nesta tela para reabrir esta ajuda contextual.',
        ],
        'create' => [
            "Na listagem, clique em <strong>Cadastrar</strong> (ou acesse diretamente <code>{$slug}</code>).",
            'Preencha todos os campos obrigatórios marcados no formulário.',
            'Revise o impacto de status, vínculos e flags (ativo, público, padrão) antes de salvar.',
            'Confirme o cadastro; o sistema exibirá mensagem de sucesso ou erro de validação.',
            'Volte à listagem e verifique se o novo registro aparece com os filtros corretos.',
        ],
        'update' => [
            'Na listagem, localize o registro e clique em <strong>Editar</strong>.',
            'Altere apenas os campos necessários; observe campos que afetam permissões ou integrações.',
            'Salve as alterações e confira a mensagem de retorno.',
            'Teste com um usuário do perfil impactado (logout/login) se mudou permissões ou status.',
        ],
        'view' => [
            'Na listagem, clique em <strong>Visualizar</strong> no registro desejado.',
            'Revise ID, datas de cadastro/edição e observações.',
            'Use os botões do cabeçalho para ir à listagem, editar ou consultar log de alterações.',
        ],
        'other' => [
            "Acesse <strong>{$screenTitle}</strong> pelo menu {$moduleTitle}.",
            'Identifique a ação principal da tela (consulta, processamento, relatório).',
            'Preencha filtros ou parâmetros antes de executar.',
            'Pressione <kbd>F1</kbd> para contextualizar a ajuda.',
        ],
    ];

    $campos = [
        'list' => [
            ['Filtros de busca', 'Restringem os registros exibidos na grade; não alteram dados, apenas a consulta.'],
            ['Mostrar N registros', 'Controla paginação (10, 20, 50 ou 100 por página).'],
            ['Ações (Visualizar / Editar / Apagar)', 'Dependem das permissões do seu nível de acesso além da listagem.'],
        ],
        'create' => [
            ['Campos do formulário', 'Cada campo do formulário grava um atributo do registro; campos vazios podem impedir o salvamento.'],
            ['Status / Ativo', 'Quando existir, registro inativo costuma sumir de seleções e do menu.'],
        ],
        'update' => [
            ['Campos editáveis', 'Somente campos exibidos podem ser alterados; IDs e auditoria são somente leitura.'],
        ],
        'view' => [
            ['Dados exibidos', 'Espelham o estado atual do registro no banco; use Editar para modificar.'],
        ],
        'other' => [
            ['Parâmetros da tela', 'Consulte os rótulos na interface; cada filtro ou campo altera o resultado exibido.'],
        ],
    ];

    $problemas = [
        'Não vejo o menu desta tela' => "Permissão {$perm} ausente no seu nível de acesso. Solicite inclusão no pacote correto.",
        'Erro 004 ao executar ação' => 'Você tem a listagem, mas não a permissão da ação (criar, editar, excluir). Inclua a página filha no pacote.',
        'F1 abre documentação genérica' => 'Este tópico ainda está em expansão; consulte a visão geral do módulo para o fluxo completo.',
    ];

    return manual_doc(
        $screenTitle,
        $objetivos[$action],
        "usuários com permissão {$perm}.",
        $passosList[$action],
        $campos[$action],
        [],
        $problemas,
        $parentTopic,
        $parentLabel
    );
}
