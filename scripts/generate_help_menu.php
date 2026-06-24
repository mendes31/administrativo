<?php

declare(strict_types=1);

/**
 * Gera docs/manual/help-menu.json — navegação do manual espelhando o menu do sistema.
 * Uso: php scripts/generate_help_menu.php
 */

require_once __DIR__ . '/load_adms_menu_tree.php';
require_once __DIR__ . '/manual_doc_lib.php';

function manual_short_topic_title(string $title): string
{
    if (preg_match('/ — (.+)$/u', $title, $m)) {
        return $m[1];
    }

    return $title;
}

$manifestPath = dirname(__DIR__) . '/docs/manual/manifest.json';
$outputPath = dirname(__DIR__) . '/docs/manual/help-menu.json';
$pageTopicMapPath = dirname(__DIR__) . '/docs/manual/page-topic-map.json';

$manifest = json_decode((string) file_get_contents($manifestPath), true);
$pageTopicMap = json_decode((string) file_get_contents($pageTopicMapPath), true);
if (!is_array($manifest) || !is_array($pageTopicMap)) {
    fwrite(STDERR, "manifest ou page-topic-map inválido.\n");
    exit(1);
}

/** @var array<string, array{id: string, title: string, module_id: string}> */
$topicsById = [];
foreach ($manifest['modules'] ?? [] as $module) {
    $moduleId = (string) ($module['id'] ?? '');
    foreach ($module['topics'] ?? [] as $topic) {
        $id = (string) ($topic['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $topicsById[$id] = [
            'id' => $id,
            'title' => (string) ($topic['title'] ?? $id),
            'module_id' => $moduleId,
        ];
    }
}

/** Visão geral por módulo do manifest */
$moduleOverviewTopic = [
    'geral' => 'index',
    'dashboard' => 'dashboard-visao-geral',
    'administracao' => 'adm-visao-geral',
    'cadastro' => 'cad-visao-geral',
    'comunicacao' => 'com-visao-geral',
    'crm' => 'crm-visao-geral',
    'estoque' => 'est-visao-geral',
    'financeiro' => 'fin-visao-geral',
    'parceiros' => null,
    'qualidade' => null,
    'rh_treinamentos' => 'rh-trein-visao-geral',
    'projetos' => null,
    'gestao_pessoas' => 'gp-visao-geral',
    'salas' => 'salas-visao-geral',
    'sac' => 'sac-visao-geral',
    'sst' => 'sst-visao-geral',
    'lgpd' => 'lgpd-visao-geral',
    'planejamento' => null,
    'relatorios' => 'rel-visao-geral',
];

/** Mapeia id do menu → id do módulo no manifest */
$menuIdToModuleId = [
    'dashboard' => 'dashboard',
    'administracao' => 'administracao',
    'cadastro' => 'cadastro',
    'comunicacao' => 'comunicacao',
    'crm' => 'crm',
    'estoque' => 'estoque',
    'financeiro' => 'financeiro',
    'parceiros' => 'parceiros',
    'qualidade' => 'qualidade',
    'rh_treinamentos' => 'rh_treinamentos',
    'projetos' => 'projetos',
    'gestao_pessoas' => 'gestao_pessoas',
    'salas' => 'salas',
    'sac' => 'sac',
    'sst' => 'sst',
    'lgpd' => 'lgpd',
    'planejamento' => 'planejamento',
    'relatorios' => 'relatorios',
];

function extractSlugFromUrl(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }
    $path = parse_url($url, PHP_URL_PATH);
    if (is_string($path) && $path !== '') {
        $slug = trim($path, '/');
        return $slug !== '' ? $slug : null;
    }
    $slug = trim($url, '/');
    return $slug !== '' ? $slug : null;
}

function resolveTopicId(string $slug, array $pageTopicMap, array $topicsById): ?string
{
    $slug = strtolower(trim($slug));
    if ($slug === '') {
        return null;
    }
    if (isset($topicsById[$slug])) {
        return $slug;
    }
    $mapped = $pageTopicMap[$slug] ?? null;
    if (is_string($mapped) && isset($topicsById[$mapped])) {
        return $mapped;
    }

    return null;
}

function crudResourceStem(string $slug): string
{
    $slug = strtolower(trim($slug));
    $slug = (string) preg_replace('/^(crm-|sac-|sst-)?(list|create|update|view)-/', '', $slug);

    $aliases = [
        'groups-pages' => 'group-page',
        'groups-page' => 'group-page',
        'mandatory-trainings' => 'mandatory-training',
        'log-acessos' => 'log-acesso',
        'log-alteracoes' => 'log-alteracao',
        'connected-users' => 'connected-user',
        'users-last-access' => 'user-last-access',
        'access-levels' => 'access-level',
        'work-shifts' => 'work-shift',
        'cost-centers' => 'cost-center',
        'payment-methods' => 'payment-method',
        'accounts-plan' => 'account-plan',
        'mov-between-accounts' => 'mov-between-account',
    ];
    if (isset($aliases[$slug])) {
        return $aliases[$slug];
    }

    if (str_ends_with($slug, 'ies')) {
        $slug = substr($slug, 0, -3) . 'y';
    } elseif (str_ends_with($slug, 's') && !str_ends_with($slug, 'ss')) {
        $slug = substr($slug, 0, -1);
    }

    return $aliases[$slug] ?? $slug;
}

function crudTopicPrefix(string $slug): string
{
    if (preg_match('/^(crm-|sac-|sst-)/', $slug, $m)) {
        return $m[1];
    }

    return '';
}

/**
 * Expande item de menu (listagem) em subitens Listar/Cadastrar/Editar/Visualizar quando existirem no manifest.
 *
 * @return list<array{type: string, topic_id: string, title: string}>
 */
function expandCrudTopics(string $listSlug, array $topicsById): array
{
    $listSlug = strtolower(trim($listSlug));
    if (!isset($topicsById[$listSlug])) {
        return [];
    }

    $stem = crudResourceStem($listSlug);
    $prefix = crudTopicPrefix($listSlug);
    $actionOrder = ['list' => 0, 'create' => 1, 'update' => 2, 'view' => 3, 'other' => 4];
    $items = [];

    foreach ($topicsById as $topicId => $meta) {
        if (crudTopicPrefix($topicId) !== $prefix || crudResourceStem($topicId) !== $stem) {
            continue;
        }
        $action = manual_infer_action($topicId);
        if (!in_array($action, ['list', 'create', 'update', 'view'], true)) {
            continue;
        }
        $items[] = [
            'type' => 'topic',
            'topic_id' => $topicId,
            'title' => manual_short_topic_title($meta['title']),
            '_order' => $actionOrder[$action],
        ];
    }

    if ($items === []) {
        return [[
            'type' => 'topic',
            'topic_id' => $listSlug,
            'title' => $topicsById[$listSlug]['title'],
        ]];
    }

    usort($items, static fn (array $a, array $b): int => $a['_order'] <=> $b['_order']);
    foreach ($items as &$item) {
        unset($item['_order']);
    }

    return count($items) > 1 ? $items : [[
        'type' => 'topic',
        'topic_id' => $listSlug,
        'title' => $topicsById[$listSlug]['title'],
    ]];
}

/**
 * @param array<string, mixed> $item
 * @return array<string, mixed>|null
 */
function buildHelpNavNode(array $item, array $pageTopicMap, array $topicsById): ?array
{
    $label = trim((string) ($item['label'] ?? ''));
    if ($label === '' || ($item['id'] ?? '') === 'logout') {
        return null;
    }

    $submenu = $item['submenu'] ?? null;
    if (is_array($submenu) && $submenu !== []) {
        $children = [];
        foreach ($submenu as $child) {
            if (!is_array($child)) {
                continue;
            }
            $node = buildHelpNavNode($child, $pageTopicMap, $topicsById);
            if ($node !== null) {
                $children[] = $node;
            }
        }
        if ($children === []) {
            return null;
        }

        return [
            'type' => 'group',
            'label' => $label,
            'children' => $children,
        ];
    }

    $slug = extractSlugFromUrl(isset($item['url']) ? (string) $item['url'] : null);
    if ($slug === null) {
        return null;
    }

    $topicId = resolveTopicId($slug, $pageTopicMap, $topicsById);
    if ($topicId === null) {
        return null;
    }

    $crud = expandCrudTopics($slug, $topicsById);
    if (count($crud) > 1) {
        return [
            'type' => 'group',
            'label' => $label,
            'children' => $crud,
        ];
    }

    return [
        'type' => 'topic',
        'topic_id' => $topicId,
        'title' => $topicsById[$topicId]['title'],
    ];
}

$menus = loadAdmsMenuTree();
$modules = [];

foreach ($menus as $menu) {
    $menuId = (string) ($menu['id'] ?? '');
    if ($menuId === '' || $menuId === 'logout') {
        continue;
    }

    $moduleId = $menuIdToModuleId[$menuId] ?? $menuId;
    $moduleTitle = trim((string) ($menu['label'] ?? $menuId));
    $children = [];

    $overviewId = $moduleOverviewTopic[$moduleId] ?? null;
    if ($overviewId !== null && isset($topicsById[$overviewId])) {
        $children[] = [
            'type' => 'topic',
            'topic_id' => $overviewId,
            'title' => $topicsById[$overviewId]['title'],
        ];
    }

    $submenu = $menu['submenu'] ?? [];
    if (is_array($submenu) && $submenu !== []) {
        foreach ($submenu as $child) {
            if (!is_array($child)) {
                continue;
            }
            $node = buildHelpNavNode($child, $pageTopicMap, $topicsById);
            if ($node !== null) {
                $children[] = $node;
            }
        }
    } else {
        $slug = extractSlugFromUrl(isset($menu['url']) ? (string) $menu['url'] : null);
        if ($slug !== null) {
            $topicId = resolveTopicId($slug, $pageTopicMap, $topicsById);
            if ($topicId !== null && ($overviewId === null || $topicId !== $overviewId)) {
                $children[] = [
                    'type' => 'topic',
                    'topic_id' => $topicId,
                    'title' => $topicsById[$topicId]['title'],
                ];
            }
        }
    }

    if ($children === []) {
        continue;
    }

    $modules[] = [
        'id' => $moduleId,
        'title' => $moduleTitle,
        'children' => $children,
    ];
}

// Geral (início + em desenvolvimento)
array_unshift($modules, [
    'id' => 'geral',
    'title' => 'Geral',
    'children' => [
        ['type' => 'topic', 'topic_id' => 'index', 'title' => $topicsById['index']['title'] ?? 'Início do manual'],
        ['type' => 'topic', 'topic_id' => 'em-desenvolvimento', 'title' => $topicsById['em-desenvolvimento']['title'] ?? 'Em desenvolvimento'],
    ],
]);

$payload = [
    'version' => 1,
    'generated_at' => date('c'),
    'modules' => $modules,
];

file_put_contents(
    $outputPath,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

$topicCount = 0;
$groupCount = 0;
$countNodes = static function (array $nodes) use (&$countNodes, &$topicCount, &$groupCount): void {
    foreach ($nodes as $node) {
        if (($node['type'] ?? '') === 'topic') {
            $topicCount++;
        } elseif (($node['type'] ?? '') === 'group') {
            $groupCount++;
            $countNodes($node['children'] ?? []);
        }
    }
};
foreach ($modules as $mod) {
    $countNodes($mod['children'] ?? []);
}

echo "help-menu.json: " . count($modules) . " módulos, {$groupCount} grupos, {$topicCount} tópicos linkados.\n";
