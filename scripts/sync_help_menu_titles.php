<?php

declare(strict_types=1);

/**
 * Sincroniza títulos do help-menu.json com o manifest.json (sem reconstruir a árvore).
 * Uso: php scripts/sync_help_menu_titles.php
 */

$root = dirname(__DIR__);
$manifest = json_decode((string) file_get_contents($root . '/docs/manual/manifest.json'), true);
$menuPath = $root . '/docs/manual/help-menu.json';
$menu = json_decode((string) file_get_contents($menuPath), true);

if (!is_array($manifest) || !is_array($menu)) {
    fwrite(STDERR, "manifest ou help-menu inválido.\n");
    exit(1);
}

$titles = [];
foreach ($manifest['modules'] ?? [] as $module) {
    foreach ($module['topics'] ?? [] as $topic) {
        $id = (string) ($topic['id'] ?? '');
        if ($id !== '') {
            $titles[$id] = (string) ($topic['title'] ?? $id);
        }
    }
}

$updated = 0;
$walk = static function (array &$nodes) use (&$walk, $titles, &$updated): void {
    foreach ($nodes as &$node) {
        if (($node['type'] ?? '') === 'topic') {
            $id = (string) ($node['topic_id'] ?? '');
            if ($id !== '' && isset($titles[$id]) && ($node['title'] ?? '') !== $titles[$id]) {
                $node['title'] = $titles[$id];
                $updated++;
            }
        } elseif (($node['type'] ?? '') === 'group' && isset($node['children'])) {
            $walk($node['children']);
        }
    }
};

foreach ($menu['modules'] ?? [] as &$module) {
    if (isset($module['children'])) {
        $walk($module['children']);
    }
}
unset($module);

$menu['generated_at'] = date('c');
file_put_contents(
    $menuPath,
    json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
);

echo "help-menu.json: {$updated} título(s) sincronizado(s) com manifest.\n";
