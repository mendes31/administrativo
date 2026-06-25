<?php

declare(strict_types=1);

/**
 * Gera docs/manual/manifest.json a partir dos HTML em docs/manual/content/.
 * Uso: php scripts/generate_manual_manifest.php
 */

$contentRoot = dirname(__DIR__) . '/docs/manual/content';
$manifestPath = dirname(__DIR__) . '/docs/manual/manifest.json';
$definitionsDir = dirname(__DIR__) . '/docs/manual/page-definitions';
require_once __DIR__ . '/manual_doc_lib.php';
require_once __DIR__ . '/manual_coverage_lib.php';

/** id do tópico → título exibido no manual */
$titleOverrides = [
    'index' => 'Início do manual',
    'em-desenvolvimento' => 'Documentação em desenvolvimento',
    'dashboard-visao-geral' => 'Dashboard — visão geral',
    'adm-visao-geral' => 'Administração — visão geral',
    'adm-configuracoes' => 'Configurações do sistema',
    'adm-logs' => 'Logs e auditoria',
    'adm-permissoes' => 'Permissões (grupos, pacotes, páginas)',
    'adm-treinamentos-obrigatorios' => 'Treinamentos obrigatórios',
    'cad-visao-geral' => 'Cadastro — visão geral',
    'cad-estrutura' => 'Estrutura organizacional',
    'cad-usuarios' => 'Cadastro de usuários',
    'cad-organograma' => 'Organograma',
    'cad-niveis-acesso' => 'Níveis de acesso',
    'com-visao-geral' => 'Comunicação interna — visão geral',
    'com-informativos' => 'Informativos',
    'com-timeline' => 'Timeline e moderação',
    'com-eventos' => 'Eventos corporativos',
    'com-gamificacao' => 'Gamificação',
    'crm-visao-geral' => 'CRM — visão geral',
    'crm-dashboards' => 'Dashboards CRM',
    'crm-pipeline' => 'Pipeline de vendas (Kanban)',
    'crm-parceiros' => 'Parceiros CRM',
    'crm-oportunidades' => 'Oportunidades',
    'crm-atividades' => 'Atividades comerciais',
    'crm-configuracoes' => 'Configurações CRM (tags, campos, automações)',
    'est-visao-geral' => 'Estoque — visão geral',
    'est-cadastros' => 'Cadastros de estoque',
    'est-movimentacoes' => 'Movimentações de estoque',
    'est-custeio' => 'Custeio e produção',
    'est-relatorios' => 'Relatórios de estoque',
    'fin-visao-geral' => 'Financeiro — visão geral',
    'fin-cadastros' => 'Cadastros financeiros',
    'fin-pagar-receber' => 'Contas a pagar e receber',
    'fin-relatorios' => 'Relatórios financeiros',
    'parceiros-negocio' => 'Clientes e fornecedores',
    'qualidade-documentos' => 'Documentos (qualidade)',
    'rh-trein-visao-geral' => 'Gestão de Treinamentos — visão geral',
    'rh-trein-catalogo' => 'Catálogo de treinamentos',
    'rh-trein-dashboards' => 'Dashboards de treinamentos',
    'rh-trein-matrizes' => 'Matrizes de treinamentos',
    'rh-trein-avaliacoes' => 'Avaliações e questionários',
    'rh-trein-notificacoes' => 'Notificações de treinamento',
    'proj-projetos' => 'Gestão de projetos',
    'gp-visao-geral' => 'Gestão de Pessoas — visão geral',
    'gp-politicas' => 'Políticas internas',
    'gp-portal' => 'Portal do colaborador',
    'gp-folha' => 'Documentos de folha (RH)',
    'gp-desempenho' => 'Desempenho e competências',
    'gp-solicitacoes' => 'Solicitações e chamados',
    'gp-analytics' => 'People Analytics',
    'gp-recrutamento' => 'Recrutamento e currículos',
    'salas-visao-geral' => 'Reserva de Salas — visão geral',
    'salas-reservas' => 'Calendário e reservas',
    'salas-administracao' => 'Administração de salas',
    'sac-visao-geral' => 'SAC — visão geral',
    'sac-atendimento' => 'Chamados e SLA',
    'lgpd-visao-geral' => 'LGPD — visão geral',
    'lgpd-dashboard' => 'Dashboard LGPD',
    'lgpd-consentimentos' => 'Consentimentos e termos',
    'lgpd-inventario' => 'Inventário, ROPA e mapeamento',
    'lgpd-aipd' => 'AIPD, RIPD e TIA',
    'pe-estrategico' => 'Planejamento estratégico',
    'rel-visao-geral' => 'Relatórios — visão geral',
    'rel-dinamicos' => 'Relatórios dinâmicos e dashboards',
    'sst-visao-geral' => 'Visão geral do módulo SST',
    'sst-conceitos' => 'Conceitos: Cargo, Risco, GHE e Matriz',
    'sst-dashboard' => 'Dashboard SST',
    'sst-riscos' => 'Riscos ocupacionais',
    'sst-exames' => 'Catálogo de exames',
    'sst-asos' => 'ASOs e encaminhamentos',
    'sst-epis' => 'Catálogo de EPIs',
    'sst-epi-fichas' => 'Fichas, estoque e movimentações EPI',
    'sst-necessidades' => 'Necessidades (EPI, exame, treinamento)',
    'sst-treinamentos' => 'Treinamentos SST',
    'sst-matriz-treinamento' => 'Matriz cargo × treinamento',
    'sst-ghe' => 'GHE — Grupos Homogêneos de Exposição',
    'sst-medicos' => 'Médicos do trabalho',
    'sst-cids' => 'Catálogo CID-10',
    'sst-acidentes-afastamentos' => 'Acidentes e afastamentos',
    'sst-conformidade' => 'Programas e conformidade',
    'sst-esocial-ppp' => 'eSocial e PPP',
    'sst-equipamentos' => 'Equipamentos e vistorias',
    'sst-cipa-inspecoes' => 'CIPA e inspeções',
    'sst-relatorios' => 'Relatórios SST',
    'sst-report-pendencias' => 'Pendências SST',
    'sst-perfil-colaborador' => 'Perfil SST do colaborador',
];

foreach (glob($definitionsDir . '/*.php') ?: [] as $defFile) {
    $chunk = require $defFile;
    if (!is_array($chunk)) {
        continue;
    }
    foreach ($chunk as $slug => $def) {
        if (is_array($def) && !empty($def['title'])) {
            $titleOverrides[$slug] = (string) $def['title'];
        }
    }
}

$modules = [
    'geral' => ['title' => 'Geral', 'dirs' => [''], 'files' => ['index.html', 'em-desenvolvimento.html']],
    'dashboard' => ['title' => 'Dashboard', 'dirs' => ['dashboard']],
    'administracao' => ['title' => 'Administração', 'dirs' => ['administracao']],
    'cadastro' => ['title' => 'Cadastro', 'dirs' => ['cadastro']],
    'comunicacao' => ['title' => 'Comunicação Interna', 'dirs' => ['comunicacao']],
    'crm' => ['title' => 'CRM', 'dirs' => ['crm']],
    'estoque' => ['title' => 'Estoque', 'dirs' => ['estoque']],
    'financeiro' => ['title' => 'Financeiro', 'dirs' => ['financeiro']],
    'parceiros' => ['title' => 'Parceiros de Negócio', 'dirs' => ['parceiros']],
    'qualidade' => ['title' => 'Garantia da Qualidade', 'dirs' => ['qualidade']],
    'rh_treinamentos' => ['title' => 'Gestão de Treinamentos', 'dirs' => ['rh_treinamentos']],
    'projetos' => ['title' => 'Gestão de Projetos', 'dirs' => ['projetos']],
    'gestao_pessoas' => ['title' => 'Gestão de Pessoas', 'dirs' => ['gestao_pessoas']],
    'salas' => ['title' => 'Reserva de Salas', 'dirs' => ['salas']],
    'sac' => ['title' => 'SAC', 'dirs' => ['sac']],
    'sst' => ['title' => 'Segurança e Medicina', 'dirs' => ['sst']],
    'lgpd' => ['title' => 'LGPD', 'dirs' => ['lgpd']],
    'planejamento' => ['title' => 'Planejamento Estratégico', 'dirs' => ['planejamento']],
    'relatorios' => ['title' => 'Relatórios', 'dirs' => ['relatorios']],
];

function topicTitle(string $id, array $overrides, array $pageTitles): string
{
    if (isset($overrides[$id])) {
        return $overrides[$id];
    }

    if (isset($pageTitles[$id])) {
        return $pageTitles[$id];
    }

    return manual_legacy_title_from_slug($id);
}

function sortTopics(array $topics): array
{
    usort($topics, static function (array $a, array $b): int {
        $aVisao = str_contains($a['id'], 'visao-geral') ? 0 : 1;
        $bVisao = str_contains($b['id'], 'visao-geral') ? 0 : 1;
        if ($aVisao !== $bVisao) {
            return $aVisao <=> $bVisao;
        }

        $aAgg = !str_contains($a['id'], '-') || preg_match('/^(adm|cad|com|crm|est|fin|gp|rh-trein|salas|sac|lgpd|pe|rel|proj|parceiros|qualidade|dashboard)-/', $a['id']) ? 0 : 1;
        $bAgg = !str_contains($b['id'], '-') || preg_match('/^(adm|cad|com|crm|est|fin|gp|rh-trein|salas|sac|lgpd|pe|rel|proj|parceiros|qualidade|dashboard)-/', $b['id']) ? 0 : 1;
        if ($aAgg !== $bAgg) {
            return $aAgg <=> $bAgg;
        }

        return strcmp($a['title'], $b['title']);
    });

    return $topics;
}

$outputModules = [];
$pageTitles = manual_page_titles_by_slug();

foreach ($modules as $moduleId => $meta) {
    $topics = [];

    if ($moduleId === 'geral') {
        foreach ($meta['files'] as $file) {
            $id = pathinfo($file, PATHINFO_FILENAME);
            $topics[] = [
                'id' => $id,
                'title' => topicTitle($id, $titleOverrides, $pageTitles),
                'file' => $file,
            ];
        }
    } else {
        foreach ($meta['dirs'] as $dir) {
            $scan = $contentRoot . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($scan)) {
                continue;
            }
            foreach (glob($scan . '/*.html') ?: [] as $abs) {
                $basename = basename($abs);
                $id = pathinfo($basename, PATHINFO_FILENAME);
                $topics[] = [
                    'id' => $id,
                    'title' => topicTitle($id, $titleOverrides, $pageTitles),
                    'file' => str_replace('\\', '/', $dir . '/' . $basename),
                ];
            }
        }
    }

    $outputModules[] = [
        'id' => $moduleId,
        'title' => $meta['title'],
        'topics' => sortTopics($topics),
    ];
}

$manifest = [
    'version' => 1,
    'modules' => $outputModules,
];

file_put_contents(
    $manifestPath,
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

$total = 0;
foreach ($outputModules as $m) {
    $total += count($m['topics']);
}

echo "manifest.json gerado: {$total} tópicos em " . count($outputModules) . " módulos.\n";
