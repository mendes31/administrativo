<?php

declare(strict_types=1);

/**
 * Biblioteca compartilhada: cobertura do manual vs páginas do sistema.
 */

require_once __DIR__ . '/manual_doc_lib.php';
require_once __DIR__ . '/manual_aggregate_topic_map.php';

const MANUAL_SKELETON_MARKERS = [
    'Este tópico ainda está em expansão',
    'F1 abre documentação genérica',
    'Identifique a ação principal da tela (consulta, processamento, relatório)',
    'Consulte os campos disponíveis na interface e o fluxo abaixo',
];

/**
 * @return array<int, array{name: string, controller: string, controller_url: string, directory: string, obs: string, page_status: int}>
 */
function manual_load_pages_from_seeds(): array
{
    $seedFile = dirname(__DIR__) . '/database/seeds/AddAdmsPages.php';
    if (!is_readable($seedFile)) {
        return [];
    }

    $content = (string) file_get_contents($seedFile);
    $pages = [];

    if (preg_match_all(
        "/\['name'\s*=>\s*'([^']*)',\s*'controller'\s*=>\s*'([^']*)',\s*'controller_url'\s*=>\s*'([^']*)',\s*'directory'\s*=>\s*'([^']*)',\s*'obs'\s*=>\s*'([^']*)'[^]]*'page_status'\s*=>\s*(\d+)/s",
        $content,
        $matches,
        PREG_SET_ORDER
    )) {
        foreach ($matches as $m) {
            $pages[] = [
                'name' => $m[1],
                'controller' => $m[2],
                'controller_url' => $m[3],
                'directory' => $m[4],
                'obs' => $m[5],
                'page_status' => (int) $m[6],
            ];
        }
    }

    return $pages;
}

/**
 * @return array<int, array{controller: string, controller_url: string, name: string}>
 */
function manual_load_pages_from_migrations(): array
{
    $dir = dirname(__DIR__) . '/database/migrations';
    $found = [];
    $files = glob($dir . '/*register*page*.php') ?: [];

    foreach ($files as $file) {
        $content = (string) file_get_contents($file);
        if (preg_match_all(
            "/'controller'\s*=>\s*'([^']+)'.*?'controller_url'\s*=>\s*'([^']+)'.*?'name'\s*=>\s*'([^']+)'/s",
            $content,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $m) {
                $found[$m[2]] = [
                    'controller' => $m[1],
                    'controller_url' => $m[2],
                    'name' => $m[3],
                ];
            }
        }
    }

    return array_values($found);
}

/**
 * Nome oficial da página (menu do sistema) por controller_url.
 *
 * @return array<string, string>
 */
function manual_page_titles_by_slug(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $titles = [];
    foreach (manual_load_pages_from_seeds() as $page) {
        $slug = (string) ($page['controller_url'] ?? '');
        $name = trim((string) ($page['name'] ?? ''));
        if ($slug !== '' && $name !== '') {
            $titles[$slug] = $name;
        }
    }
    foreach (manual_load_pages_from_migrations() as $page) {
        $slug = (string) ($page['controller_url'] ?? '');
        $name = trim((string) ($page['name'] ?? ''));
        if ($slug !== '' && $name !== '' && !isset($titles[$slug])) {
            $titles[$slug] = $name;
        }
    }

    $cache = $titles;

    return $titles;
}

/** Título legado gerado a partir do slug (antes do seed). */
function manual_legacy_title_from_slug(string $slug): string
{
    $actionLabels = [
        'list-' => 'Listar — ',
        'create-' => 'Cadastrar — ',
        'update-' => 'Editar — ',
        'view-' => 'Visualizar — ',
    ];
    foreach ($actionLabels as $prefix => $label) {
        if (str_starts_with($slug, $prefix)) {
            $rest = substr($slug, strlen($prefix));

            return $label . ucwords(str_replace('-', ' ', $rest));
        }
    }

    return manual_humanize_slug($slug);
}

function manual_is_permission_only_page(array $page): bool
{
    $obs = mb_strtolower((string) ($page['obs'] ?? ''));
    $controller = (string) ($page['controller'] ?? '');

    if (str_contains($obs, 'permissão para') || str_contains($obs, 'permissão ')) {
        return true;
    }
    if (str_contains($obs, 'controla visibilidade')) {
        return true;
    }
    if (str_starts_with($controller, 'DashboardCard')) {
        return true;
    }

    return false;
}

function manual_should_document_slug(array $page): bool
{
    if ((int) ($page['page_status'] ?? 0) !== 1) {
        return false;
    }

    $slug = (string) ($page['controller_url'] ?? '');
    if ($slug === '' || $slug === 'login' || $slug === 'logout') {
        return false;
    }

    $skipPrefixes = ['save-', 'export-', 'import-', 'test-', 'send-', 'sync-', 'update-training-matrix'];
    foreach ($skipPrefixes as $prefix) {
        if (str_starts_with($slug, $prefix)) {
            return false;
        }
    }

    return true;
}

/** Telas principais que devem entrar no mapa agregado quando novas. */
function manual_is_primary_screen_slug(string $slug): bool
{
    if (preg_match('/^(list|create|view|update|dashboard|apply-|schedule-|book-|matrix-|.*-matrix|.*-dashboard|employee-portal|notificacoes|profile|organization-chart)/', $slug)) {
        return true;
    }

    return false;
}

function manual_is_tracked_slug(string $slug, array $aggregateMap, array $sstMap): bool
{
    return isset($aggregateMap[$slug]) || isset($sstMap[$slug]);
}

/** @return array<string, string> */
function manual_load_page_topic_map(): array
{
    $path = dirname(__DIR__) . '/docs/manual/page-topic-map.json';
    $json = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;

    return is_array($json) ? $json : [];
}

/** @return array<string, true> */
function manual_manifest_topic_ids(): array
{
    $path = dirname(__DIR__) . '/docs/manual/manifest.json';
    $json = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
    $ids = [];

    if (!is_array($json) || !isset($json['modules'])) {
        return $ids;
    }

    foreach ($json['modules'] as $module) {
        foreach ($module['topics'] ?? [] as $topic) {
            if (!empty($topic['id'])) {
                $ids[(string) $topic['id']] = true;
            }
        }
    }

    return $ids;
}

function manual_topic_html_path(string $topicId): ?string
{
    $root = dirname(__DIR__) . '/docs/manual/content';
    $topicToDir = manualTopicToContentDir();

    $candidates = [];
    if (isset($topicToDir[$topicId])) {
        $candidates[] = $root . '/' . $topicToDir[$topicId] . '/' . $topicId . '.html';
    }

    foreach (glob($root . '/*/' . $topicId . '.html') ?: [] as $file) {
        $candidates[] = $file;
    }

    $candidates[] = $root . '/' . $topicId . '.html';

    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function manual_is_skeleton_content(string $html): bool
{
    foreach (MANUAL_SKELETON_MARKERS as $marker) {
        if (str_contains($html, $marker)) {
            return true;
        }
    }

    return false;
}

function manual_guess_parent_topic_for_permission(string $controller, string $directory, string $controllerUrl): ?string
{
    static $explicit = [
        'EditCompletedTraining' => 'completed-trainings-matrix',
        'DeleteCompletedTraining' => 'completed-trainings-matrix',
    ];

    if (isset($explicit[$controller])) {
        return $explicit[$controller];
    }

    $aggregate = buildManualAggregateTopicMap();
    $slug = preg_replace('/^(edit|delete|update|create|view|save)-/', '', $controllerUrl) ?? $controllerUrl;
    foreach ($aggregate as $mapSlug => $topic) {
        if (str_contains($mapSlug, $slug) || str_contains($slug, $mapSlug)) {
            return $topic;
        }
    }

    $dirTopics = [
        'trainings' => 'rh-trein-visao-geral',
        'users' => 'cad-usuarios',
        'lgpd' => 'lgpd-visao-geral',
        'crm' => 'crm-visao-geral',
        'sst' => 'sst-visao-geral',
    ];

    return $dirTopics[$directory] ?? null;
}

/** @return array<string, mixed> */
function manual_run_coverage_audit(): array
{
    $pages = manual_load_pages_from_seeds();
    $migrationPages = manual_load_pages_from_migrations();
    $knownSlugs = [];
    foreach ($pages as $p) {
        $knownSlugs[$p['controller_url']] = $p;
    }
    foreach ($migrationPages as $p) {
        if (!isset($knownSlugs[$p['controller_url']])) {
            $knownSlugs[$p['controller_url']] = array_merge($p, [
                'directory' => '',
                'obs' => 'Registrado em migration',
                'page_status' => 1,
            ]);
        }
    }

    $pageTopicMap = manual_load_page_topic_map();
    $aggregateMap = buildManualAggregateTopicMap();
    $sstPath = dirname(__DIR__) . '/docs/manual/page-topic-map.sst.json';
    $sstMap = is_readable($sstPath) ? json_decode((string) file_get_contents($sstPath), true) : [];
    $sstMap = is_array($sstMap) ? $sstMap : [];
    $manifestIds = manual_manifest_topic_ids();
    $aggregateLabels = manualAggregateTopicLabels();

    $result = [
        'missing_map' => [],
        'missing_aggregate' => [],
        'new_primary_unmapped' => [],
        'missing_html' => [],
        'skeleton' => [],
        'permission_undocumented' => [],
        'orphan_html' => [],
        'ok_count' => 0,
    ];

    foreach ($knownSlugs as $slug => $page) {
        if (!manual_should_document_slug($page)) {
            continue;
        }

        if (manual_is_permission_only_page($page)) {
            $parent = manual_guess_parent_topic_for_permission(
                (string) $page['controller'],
                (string) ($page['directory'] ?? ''),
                $slug
            );
            $parentPath = $parent !== null ? manual_topic_html_path($parent) : null;
            $controller = (string) $page['controller'];
            $documented = false;
            if ($parentPath !== null) {
                $html = (string) file_get_contents($parentPath);
                $documented = str_contains($html, $controller);
            }
            if (!$documented) {
                $result['permission_undocumented'][] = [
                    'controller' => $controller,
                    'name' => $page['name'] ?? $controller,
                    'parent_topic' => $parent,
                    'parent_file' => $parentPath,
                ];
            } else {
                $result['ok_count']++;
            }
            continue;
        }

        if (!manual_is_tracked_slug($slug, $aggregateMap, $sstMap)) {
            if (manual_is_primary_screen_slug($slug)) {
                $result['new_primary_unmapped'][] = [
                    'slug' => $slug,
                    'name' => $page['name'] ?? $slug,
                    'directory' => $page['directory'] ?? '',
                ];
            }
            continue;
        }

        if (!isset($pageTopicMap[$slug]) && !isset($sstMap[$slug])) {
            $result['missing_map'][] = [
                'slug' => $slug,
                'name' => $page['name'] ?? $slug,
                'controller' => $page['controller'] ?? '',
            ];
            continue;
        }

        if (!isset($aggregateMap[$slug]) && !isset($sstMap[$slug])) {
            $result['missing_aggregate'][] = [
                'slug' => $slug,
                'name' => $page['name'] ?? $slug,
                'directory' => $page['directory'] ?? '',
            ];
        }

        $topicId = $pageTopicMap[$slug] ?? $sstMap[$slug] ?? $slug;
        $htmlPath = manual_topic_html_path($topicId);

        if ($htmlPath === null) {
            $result['missing_html'][] = [
                'slug' => $slug,
                'topic_id' => $topicId,
            ];
            continue;
        }

        $html = (string) file_get_contents($htmlPath);
        if (manual_is_skeleton_content($html)) {
            $result['skeleton'][] = [
                'slug' => $slug,
                'topic_id' => $topicId,
                'file' => $htmlPath,
            ];
            continue;
        }

        if (!isset($manifestIds[$topicId])) {
            $result['missing_html'][] = [
                'slug' => $slug,
                'topic_id' => $topicId,
                'reason' => 'fora do manifest.json',
            ];
            continue;
        }

        $result['ok_count']++;
    }

    $contentRoot = dirname(__DIR__) . '/docs/manual/content';
    foreach (glob($contentRoot . '/*/*.html') ?: [] as $file) {
        $id = pathinfo($file, PATHINFO_FILENAME);
        if (in_array($id, ['index', 'em-desenvolvimento'], true)) {
            continue;
        }
        $hasPage = false;
        foreach ($knownSlugs as $slug => $page) {
            $topicId = $pageTopicMap[$slug] ?? null;
            if ($slug === $id || $topicId === $id) {
                $hasPage = true;
                break;
            }
        }
        if (!$hasPage && !isset($aggregateLabels[$id])) {
            $result['orphan_html'][] = str_replace(dirname(__DIR__) . '/', '', $file);
        }
    }

    return $result;
}

/** @return list<string> */
function manual_fix_missing_skeletons(array $missingHtml, array $skeleton): array
{
    $aggregateMap = buildManualAggregateTopicMap();
    $topicToDir = manualTopicToContentDir();
    $aggregateLabels = manualAggregateTopicLabels();
    $moduleTitles = manualModuleTitlesByDir();
    $created = [];

    $targets = array_merge($missingHtml, $skeleton);
    foreach ($targets as $item) {
        $slug = (string) ($item['slug'] ?? $item['topic_id'] ?? '');
        $topicId = (string) ($item['topic_id'] ?? $slug);
        if ($slug === '') {
            continue;
        }

        $aggregateTopic = $aggregateMap[$slug] ?? null;
        $dir = $topicToDir[$aggregateTopic] ?? null;
        if ($dir === null) {
            continue;
        }

        $targetFile = dirname(__DIR__) . "/docs/manual/content/{$dir}/{$slug}.html";
        if (is_file($targetFile) && !manual_is_skeleton_content((string) file_get_contents($targetFile))) {
            continue;
        }

        $moduleTitle = $moduleTitles[$dir] ?? ucwords(str_replace('_', ' ', $dir));
        $parentLabel = $aggregateLabels[$aggregateTopic] ?? $aggregateTopic;
        $screenTitle = manual_page_titles_by_slug()[$slug] ?? manual_humanize_slug($slug);
        $permission = manual_slug_to_permission($slug);

        $html = manual_skeleton_doc(
            $slug,
            $screenTitle,
            $permission,
            $moduleTitle,
            $aggregateTopic,
            $parentLabel
        );

        $folder = dirname($targetFile);
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        file_put_contents($targetFile, $html);
        $created[] = $targetFile;
    }

    return $created;
}

function manual_write_pending_aggregate(array $missingAggregate): void
{
    if ($missingAggregate === []) {
        return;
    }

    $path = dirname(__DIR__) . '/docs/manual/pending-aggregate-slugs.json';
    $existing = [];
    if (is_readable($path)) {
        $decoded = json_decode((string) file_get_contents($path), true);
        $existing = is_array($decoded) ? $decoded : [];
    }

    foreach ($missingAggregate as $row) {
        $existing[$row['slug']] = [
            'name' => $row['name'],
            'directory' => $row['directory'],
            'detected_at' => date('c'),
        ];
    }

    file_put_contents(
        $path,
        json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
    );
}

function manual_regenerate_manual_artifacts(): void
{
    $php = PHP_BINARY ?: 'php';
    $root = dirname(__DIR__);

    passthru("{$php} \"{$root}/scripts/generate_manual_page_topic_map.php\"");
    passthru("{$php} \"{$root}/scripts/generate_manual_manifest.php\"");
    passthru("{$php} \"{$root}/scripts/generate_help_menu.php\"");
}

function manual_camel_case_to_slug(string $name): string
{
    $name = preg_replace('/([a-z\d])([A-Z])/', '$1-$2', $name) ?? $name;

    return strtolower($name);
}

function manual_git_cmd(string ...$args): array
{
    $root = dirname(__DIR__);

    return array_merge(['git', '-C', $root, '-c', 'core.safecrlf=false'], $args);
}

function manual_git_shell_exec(array $cmd): string
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));
    if (!is_resource($process)) {
        return '';
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    proc_close($process);

    return is_string($stdout) ? $stdout : '';
}

function manual_git_ref_exists(string $ref): bool
{
    $out = manual_git_shell_exec(manual_git_cmd('rev-parse', '--verify', "{$ref}^{commit}"));

    return trim($out) !== '';
}

/**
 * Resolve ref de comparação (ex.: origin/main inexistente → origin/dev-master).
 *
 * @return array{ref: ?string, requested: ?string, warning: ?string}
 */
function manual_git_resolve_base_ref(?string $requested): array
{
    if ($requested === null || $requested === '') {
        return ['ref' => null, 'requested' => null, 'warning' => null];
    }

    $candidates = [$requested];
    if ($requested === 'auto') {
        $candidates = [];
    } elseif (!str_contains($requested, '/')) {
        $candidates[] = "origin/{$requested}";
    }

    $defaultRemote = trim(manual_git_shell_exec(manual_git_cmd('symbolic-ref', 'refs/remotes/origin/HEAD')));
    if ($defaultRemote !== '') {
        $candidates[] = str_replace('refs/remotes/', '', $defaultRemote);
    }
    foreach (['origin/dev-master', 'origin/main', 'origin/master', 'dev-master', 'main', 'master'] as $fallback) {
        $candidates[] = $fallback;
    }

    $seen = [];
    foreach ($candidates as $candidate) {
        if ($candidate === '' || isset($seen[$candidate])) {
            continue;
        }
        $seen[$candidate] = true;
        if (manual_git_ref_exists($candidate)) {
            $warning = null;
            if ($requested !== 'auto' && $candidate !== $requested) {
                $warning = "Ref '{$requested}' não existe; usando '{$candidate}'.";
            }

            return ['ref' => $candidate, 'requested' => $requested, 'warning' => $warning];
        }
    }

    return [
        'ref' => null,
        'requested' => $requested,
        'warning' => "Ref '{$requested}' não encontrado no repositório.",
    ];
}

/**
 * Arquivos alterados no git.
 *
 * @param bool $stagedOnly Se true, considera apenas o índice (git add). Ideal para pre-commit.
 * @return list<string> caminhos relativos ao repositório, barras /
 */
function manual_git_list_changed_files(?string $baseRef = null, bool $stagedOnly = false): array
{
    $commands = [];

    if ($baseRef !== null && $baseRef !== '') {
        $commands[] = manual_git_cmd('diff', '--name-only', '--diff-filter=ACMRT', "{$baseRef}...HEAD");
    } elseif ($stagedOnly) {
        $commands[] = manual_git_cmd('diff', '--cached', '--name-only', '--diff-filter=ACMRT');
    } else {
        $commands[] = manual_git_cmd('diff', '--cached', '--name-only', '--diff-filter=ACMRT');
        $commands[] = manual_git_cmd('diff', '--name-only', '--diff-filter=ACMRT');
    }

    $files = [];
    foreach ($commands as $cmd) {
        $out = manual_git_shell_exec($cmd);
        if (trim($out) === '') {
            continue;
        }
        foreach (preg_split('/\R/', trim($out)) ?: [] as $line) {
            $line = str_replace('\\', '/', trim($line));
            if ($line !== '') {
                $files[$line] = true;
            }
        }
    }

    return array_keys($files);
}

function manual_git_diff_text_for_file(string $rel, ?string $baseRef = null): string
{
    $rel = str_replace('\\', '/', $rel);
    $commands = [];

    if ($baseRef !== null && $baseRef !== '') {
        $commands[] = manual_git_cmd('diff', $baseRef . '...HEAD', '--', $rel);
    } else {
        $commands[] = manual_git_cmd('diff', '--cached', '--', $rel);
        $commands[] = manual_git_cmd('diff', '--', $rel);
    }

    $chunks = [];
    foreach ($commands as $cmd) {
        $out = manual_git_shell_exec($cmd);
        if (trim($out) !== '') {
            $chunks[] = $out;
        }
    }

    return implode("\n", $chunks);
}

/**
 * Sinais de alteração que podem exigir atualização do manual.
 *
 * @param list<string> $changedFiles
 * @return array{slugs: array<string, true>, controllers: array<string, true>, topic_ids: array<string, true>, manual_paths: array<string, true>}
 */
function manual_collect_touch_signals_from_changed_files(array $changedFiles, ?string $baseRef = null): array
{
    $root = dirname(__DIR__);
    $signals = [
        'slugs' => [],
        'controllers' => [],
        'topic_ids' => [],
        'manual_paths' => [],
    ];

    foreach ($changedFiles as $rel) {
        $rel = str_replace('\\', '/', $rel);

        if (str_starts_with($rel, 'docs/manual/content/') && str_ends_with($rel, '.html')) {
            $signals['manual_paths'][$rel] = true;
            $signals['topic_ids'][pathinfo($rel, PATHINFO_FILENAME)] = true;
        }

        if (preg_match('#database/migrations/.+register.+page.+\.php$#i', $rel)) {
            $path = "{$root}/{$rel}";
            if (is_readable($path)) {
                $content = (string) file_get_contents($path);
                if (preg_match_all("/'controller_url'\s*=>\s*'([^']+)'/", $content, $m)) {
                    foreach ($m[1] as $slug) {
                        $signals['slugs'][$slug] = true;
                    }
                }
                if (preg_match_all("/'controller'\s*=>\s*'([^']+)'/", $content, $m)) {
                    foreach ($m[1] as $controller) {
                        $signals['controllers'][$controller] = true;
                    }
                }
            }
        }

        if (str_ends_with($rel, 'database/seeds/AddAdmsPages.php')) {
            $patch = manual_git_diff_text_for_file($rel, $baseRef);
            if (preg_match_all("/^\+[^+].*'controller_url'\s*=>\s*'([^']+)'/m", $patch, $m)) {
                foreach ($m[1] as $slug) {
                    $signals['slugs'][$slug] = true;
                }
            }
            if (preg_match_all("/^\+[^+].*'controller'\s*=>\s*'([^']+)'/m", $patch, $m)) {
                foreach ($m[1] as $controller) {
                    $signals['controllers'][$controller] = true;
                }
            }
        }

        if (preg_match('#app/adms/Controllers/.+/([A-Za-z0-9]+)\.php$#', $rel, $m)) {
            $class = $m[1];
            $signals['controllers'][$class] = true;
            $signals['slugs'][manual_camel_case_to_slug($class)] = true;
        }

        if (preg_match('#app/adms/Views/.+/([A-Za-z0-9]+)\.php$#', $rel, $m)) {
            $signals['slugs'][manual_camel_case_to_slug($m[1])] = true;
        }
    }

    return $signals;
}

/** @param array<string, mixed> $audit */
function manual_filter_audit_by_touch(array $audit, array $signals): array
{
    $slugHit = static function (string $slug) use ($signals): bool {
        return isset($signals['slugs'][$slug]);
    };

    $controllerHit = static function (string $controller) use ($signals): bool {
        return isset($signals['controllers'][$controller]);
    };

    $topicHit = static function (?string $topicId) use ($signals): bool {
        return $topicId !== null && $topicId !== '' && isset($signals['topic_ids'][$topicId]);
    };

    $filtered = $audit;
    foreach ([
        'missing_map',
        'missing_aggregate',
        'new_primary_unmapped',
        'missing_html',
        'skeleton',
    ] as $key) {
        $filtered[$key] = array_values(array_filter(
            $audit[$key] ?? [],
            static function (array $row) use ($slugHit, $topicHit): bool {
                $slug = (string) ($row['slug'] ?? '');
                $topic = isset($row['topic_id']) ? (string) $row['topic_id'] : '';

                return $slugHit($slug) || $topicHit($topic);
            }
        ));
    }

    $filtered['permission_undocumented'] = array_values(array_filter(
        $audit['permission_undocumented'] ?? [],
        static function (array $row) use ($controllerHit, $topicHit): bool {
            return $controllerHit((string) ($row['controller'] ?? ''))
                || $topicHit(isset($row['parent_topic']) ? (string) $row['parent_topic'] : null);
        }
    ));

    $filtered['orphan_html'] = array_values(array_filter(
        $audit['orphan_html'] ?? [],
        static function (string $path) use ($signals): bool {
            foreach (array_keys($signals['manual_paths']) as $manualPath) {
                if (str_contains($path, $manualPath) || str_contains($manualPath, $path)) {
                    return true;
                }
            }

            return false;
        }
    ));

    return $filtered;
}

/**
 * @return array{audit: array<string, mixed>, changed_files: list<string>, touch: array<string, mixed>}
 */
function manual_run_changed_coverage_audit(?string $baseRef = null, bool $stagedOnly = false): array
{
    $changedFiles = manual_git_list_changed_files($baseRef, $stagedOnly);
    $touch = manual_collect_touch_signals_from_changed_files($changedFiles, $baseRef);
    $audit = manual_run_coverage_audit();
    $audit = manual_filter_audit_by_touch($audit, $touch);

    return [
        'audit' => $audit,
        'changed_files' => $changedFiles,
        'touch' => $touch,
    ];
}

/** @return array<string, string> controller => controller_url */
function manual_controller_to_slug_map(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $map = [];
    foreach (array_merge(manual_load_pages_from_seeds(), manual_load_pages_from_migrations()) as $page) {
        $controller = (string) ($page['controller'] ?? '');
        $slug = (string) ($page['controller_url'] ?? '');
        if ($controller !== '' && $slug !== '') {
            $map[$controller] = $slug;
        }
    }

    $cache = $map;

    return $map;
}

function manual_classify_changed_file(string $rel): ?string
{
    $rel = str_replace('\\', '/', $rel);

    if (preg_match('#^app/adms/Controllers/.+\.php$#', $rel)) {
        return 'controller';
    }
    if (preg_match('#^app/adms/Views/.+\.php$#', $rel)) {
        return 'view';
    }
    if (preg_match('#^database/migrations/.+register.+page.+\.php$#i', $rel)) {
        return 'migration';
    }
    if ($rel === 'database/seeds/AddAdmsPages.php') {
        return 'seed';
    }
    if (str_starts_with($rel, 'docs/manual/content/') && str_ends_with($rel, '.html')) {
        return 'manual';
    }
    if (str_starts_with($rel, 'docs/manual/')) {
        return 'manual_meta';
    }

    return null;
}

/**
 * @param array<string, true> $slugs
 * @return list<array{slug: string, name: string, topic_id: ?string, html_path: ?string, html_rel: ?string, status: string}>
 */
function manual_resolve_topics_for_slugs(array $slugs): array
{
    $topicMap = manual_load_page_topic_map();
    $titles = manual_page_titles_by_slug();
    $root = dirname(__DIR__);
    $topics = [];

    foreach (array_keys($slugs) as $slug) {
        $topicId = $topicMap[$slug] ?? null;
        $htmlPath = $topicId !== null ? manual_topic_html_path($topicId) : null;
        $htmlRel = $htmlPath !== null ? str_replace('\\', '/', substr($htmlPath, strlen($root) + 1)) : null;

        $status = 'sem_mapa';
        if ($topicId === null) {
            $status = 'sem_topic_id';
        } elseif ($htmlPath === null) {
            $status = 'html_ausente';
        } elseif (!is_readable($htmlPath)) {
            $status = 'html_ausente';
        } elseif (manual_is_skeleton_content((string) file_get_contents($htmlPath))) {
            $status = 'esqueleto';
        } else {
            $status = 'documentado';
        }

        $topics[] = [
            'slug' => $slug,
            'name' => $titles[$slug] ?? manual_legacy_title_from_slug($slug),
            'topic_id' => $topicId,
            'html_path' => $htmlPath,
            'html_rel' => $htmlRel,
            'status' => $status,
        ];
    }

    usort($topics, static fn (array $a, array $b): int => strcmp($a['slug'], $b['slug']));

    return $topics;
}

/**
 * Relatório para Cursor/CI: alterações git → tópicos do manual → pendências.
 *
 * @return array<string, mixed>
 */
function manual_build_change_report(?string $baseRef = null, bool $stagedOnly = false): array
{
    $baseResolved = manual_git_resolve_base_ref($baseRef);
    $effectiveBase = $baseResolved['ref'];

    $changedMeta = manual_run_changed_coverage_audit($effectiveBase, $stagedOnly);
    $changedFiles = $changedMeta['changed_files'];
    $touch = $changedMeta['touch'];
    $audit = $changedMeta['audit'];

    $controllerSlugMap = manual_controller_to_slug_map();
    $slugs = $touch['slugs'];
    foreach (array_keys($touch['controllers']) as $controller) {
        if (isset($controllerSlugMap[$controller])) {
            $slugs[$controllerSlugMap[$controller]] = true;
        }
    }

    $codeChanges = [];
    $manualChanges = [];
    $manualMetaChanges = [];
    $otherChanges = [];
    foreach ($changedFiles as $rel) {
        $kind = manual_classify_changed_file($rel);
        $row = ['path' => $rel, 'kind' => $kind];
        if ($kind === 'manual') {
            $manualChanges[] = $row;
        } elseif ($kind === 'manual_meta') {
            $manualMetaChanges[] = $row;
        } elseif ($kind !== null) {
            $codeChanges[] = $row;
        } else {
            $otherChanges[] = $row;
        }
    }

    $affectedTopics = manual_resolve_topics_for_slugs($slugs);
    $needsManualUpdate = array_values(array_filter(
        $affectedTopics,
        static fn (array $t): bool => $t['status'] !== 'documentado'
    ));

    $issueCount = count($audit['missing_map'])
        + count($audit['missing_aggregate'])
        + count($audit['new_primary_unmapped'])
        + count($audit['missing_html'])
        + count($audit['skeleton'])
        + count($audit['permission_undocumented']);

    $actions = [];
    foreach ($needsManualUpdate as $topic) {
        if ($topic['html_rel'] !== null) {
            $actions[] = "Revisar ou criar conteúdo em {$topic['html_rel']} ({$topic['name']})";
        } elseif ($topic['topic_id'] !== null) {
            $actions[] = "Criar HTML para o tópico {$topic['topic_id']} (slug {$topic['slug']})";
        } else {
            $actions[] = "Mapear slug {$topic['slug']} em page-topic-map.json e documentar a tela";
        }
    }
    foreach ($audit['permission_undocumented'] as $row) {
        $parent = $row['parent_topic'] ?? 'tópico pai';
        $actions[] = "Documentar permissão {$row['controller']} em docs/manual/content/**/{$parent}.html";
    }
    if ($audit['missing_map'] !== []) {
        $actions[] = 'Rodar: php scripts/generate_manual_page_topic_map.php';
    }
    if ($audit['missing_aggregate'] !== [] || $audit['new_primary_unmapped'] !== []) {
        $actions[] = 'Atualizar scripts/manual_aggregate_topic_map.php';
    }
    if ($audit['missing_html'] !== []) {
        $actions[] = 'Rodar: php scripts/audit_manual_coverage.php --changed --fix (depois revisar em português)';
    }

    $scope = $effectiveBase !== null && $effectiveBase !== ''
        ? "desde {$effectiveBase}"
        : ($stagedOnly ? 'staged (git add)' : 'working tree (staged + unstaged)');

    return [
        'scope' => $scope,
        'base_ref' => $effectiveBase,
        'base_ref_requested' => $baseResolved['requested'],
        'base_ref_warning' => $baseResolved['warning'],
        'staged_only' => $stagedOnly,
        'has_relevant_changes' => $codeChanges !== [] || $manualChanges !== [],
        'changed_files' => $changedFiles,
        'code_changes' => $codeChanges,
        'manual_changes' => $manualChanges,
        'manual_meta_changes' => $manualMetaChanges,
        'other_changes' => $otherChanges,
        'touch' => [
            'slugs' => array_keys($slugs),
            'controllers' => array_keys($touch['controllers']),
            'topic_ids' => array_keys($touch['topic_ids']),
        ],
        'affected_topics' => $affectedTopics,
        'needs_manual_update' => $needsManualUpdate,
        'audit' => $audit,
        'issue_count' => $issueCount,
        'actions' => array_values(array_unique($actions)),
    ];
}

/** @param array<string, mixed> $report */
function manual_format_change_report_markdown(array $report): string
{
    $lines = [];
    $lines[] = '# Relatório — alterações que afetam o manual (F1)';
    $lines[] = '';
    $lines[] = 'Escopo: ' . ($report['scope'] ?? '');
    if (!empty($report['base_ref_warning'])) {
        $lines[] = 'Aviso: ' . $report['base_ref_warning'];
    }
    $lines[] = '';

    if (!($report['has_relevant_changes'] ?? false)) {
        $lines[] = 'Nenhuma alteração em Controllers, Views, seeds/migrations de páginas ou tópicos HTML do manual.';
        $other = array_merge($report['other_changes'] ?? [], $report['manual_meta_changes'] ?? []);
        if ($other !== []) {
            $lines[] = '';
            $lines[] = 'Outros arquivos alterados (' . count($other) . '):';
            foreach (array_slice($other, 0, 15) as $row) {
                $lines[] = '- ' . $row['path'];
            }
            if (count($other) > 15) {
                $lines[] = '- … e mais ' . (count($other) - 15);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    $codeChanges = $report['code_changes'] ?? [];
    if ($codeChanges !== []) {
        $lines[] = '## Código alterado (exige revisão do manual)';
        foreach ($codeChanges as $row) {
            $lines[] = '- [' . $row['kind'] . '] ' . $row['path'];
        }
        $lines[] = '';
    }

    $manualChanges = $report['manual_changes'] ?? [];
    if ($manualChanges !== []) {
        $lines[] = '## Manual já alterado neste diff';
        foreach ($manualChanges as $row) {
            $lines[] = '- ' . $row['path'];
        }
        $lines[] = '';
    }

    $topics = $report['affected_topics'] ?? [];
    if ($topics !== []) {
        $lines[] = '## Tópicos do manual relacionados';
        $lines[] = '| Slug | Tela | topic_id | HTML | Status |';
        $lines[] = '|------|------|----------|------|--------|';
        foreach ($topics as $t) {
            $html = $t['html_rel'] ?? '—';
            $topicId = $t['topic_id'] ?? '—';
            $lines[] = "| `{$t['slug']}` | {$t['name']} | `{$topicId}` | `{$html}` | {$t['status']} |";
        }
        $lines[] = '';
    }

    $audit = $report['audit'] ?? [];
    $sections = [
        'missing_map' => '[MAP] Slug sem page-topic-map.json',
        'missing_aggregate' => '[AGGREGATE] Fora do mapa agregado',
        'new_primary_unmapped' => '[NOVO] Tela principal sem mapa',
        'missing_html' => '[HTML] Tópico sem arquivo',
        'skeleton' => '[SKELETON] Conteúdo ainda genérico',
        'permission_undocumented' => '[PERMISSÃO] Não citada no tópico pai',
    ];
    $hasAudit = false;
    foreach ($sections as $key => $title) {
        $rows = $audit[$key] ?? [];
        if ($rows === []) {
            continue;
        }
        $hasAudit = true;
        $lines[] = '## ' . $title;
        foreach ($rows as $row) {
            if ($key === 'permission_undocumented') {
                $parent = $row['parent_topic'] ?? '?';
                $lines[] = "- `{$row['controller']}` ({$row['name']}) → documentar em `{$parent}`";
            } elseif ($key === 'skeleton') {
                $lines[] = "- `{$row['topic_id']}` → `{$row['file']}`";
            } elseif (isset($row['slug'])) {
                $name = $row['name'] ?? '';
                $lines[] = "- `{$row['slug']}` {$name}";
            }
        }
        $lines[] = '';
    }

    $actions = $report['actions'] ?? [];
    if ($actions !== []) {
        $lines[] = '## Ações sugeridas';
        foreach ($actions as $i => $action) {
            $lines[] = ($i + 1) . '. ' . $action;
        }
        $lines[] = '';
    }

    $issueCount = (int) ($report['issue_count'] ?? 0);
    if ($issueCount === 0 && ($report['needs_manual_update'] ?? []) === []) {
        $lines[] = '**OK:** manual alinhado com as alterações detectadas.';
    } else {
        $lines[] = '**Pendências:** ' . $issueCount . ' item(ns) na auditoria; revise os tópicos acima.';
    }

    return implode("\n", $lines) . "\n";
}
