<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Ajuda de contexto (F1): resolve slug da tela → tópico do manual.
 */
final class ContextHelpHelper
{
    private const MANIFEST_PATH = 'docs/manual/manifest.json';

    public const TOPIC_UNDER_DEVELOPMENT = 'em-desenvolvimento';

    /** Fallback slug → tópico visão geral do módulo (prefixo mais longo primeiro). */
    private const MODULE_PREFIX_FALLBACK = [
        'lgpd-aipd-template-' => 'lgpd-aipd',
        'lgpd-' => 'lgpd-dashboard',
        'crm-' => 'crm-visao-geral',
        'sst-' => 'sst-visao-geral',
        'sac-' => 'sac-atendimento',
        'rh-' => 'gp-recrutamento',
        'rooms-' => 'salas-administracao',
        'booking-' => 'salas-reservas',
        'room-' => 'salas-reservas',
        'list-inventory-' => 'est-cadastros',
        'create-inventory-' => 'est-movimentacoes',
        'report-inventory-' => 'est-relatorios',
        'gamification-' => 'com-gamificacao',
        'training-' => 'rh-trein-catalogo',
        'list-training' => 'rh-trein-catalogo',
        'performance-' => 'gp-desempenho',
        'list-performance-' => 'gp-desempenho',
        'list-employee-' => 'gp-solicitacoes',
        'people-' => 'gp-analytics',
        'payroll-' => 'gp-folha',
        'strategic-' => 'pe-estrategico',
        'list-strategic-' => 'pe-estrategico',
        'list-dynamic-' => 'rel-dinamicos',
    ];

    private static ?array $pageTopicMap = null;

    public static function resolveTopicFromPageSlug(string $pageSlug): string
    {
        return self::resolveHelpTopicId($pageSlug);
    }

    /**
     * Slug da tela ou id do tópico → id do manual a exibir.
     * Telas sem tópico elaborado retornam {@see TOPIC_UNDER_DEVELOPMENT}.
     */
    public static function resolveHelpTopicId(string $raw): string
    {
        $slug = strtolower(trim($raw));
        if ($slug === '') {
            return 'index';
        }

        if (self::isTopicDocumented($slug)) {
            return $slug;
        }

        $mapped = self::pageTopicMap()[$slug] ?? null;
        if ($mapped !== null && self::isTopicDocumented($mapped)) {
            return $mapped;
        }

        $fallback = self::resolveModulePrefixFallback($slug);
        if ($fallback !== null) {
            return $fallback;
        }

        return self::TOPIC_UNDER_DEVELOPMENT;
    }

    /** @return array<string, string> */
    private static function pageTopicMap(): array
    {
        if (self::$pageTopicMap !== null) {
            return self::$pageTopicMap;
        }

        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'page-topic-map.json';
        if (!is_readable($path)) {
            self::$pageTopicMap = [];

            return self::$pageTopicMap;
        }

        $json = file_get_contents($path);
        $data = is_string($json) ? json_decode($json, true) : null;
        self::$pageTopicMap = is_array($data) ? $data : [];

        return self::$pageTopicMap;
    }

    private static function resolveModulePrefixFallback(string $slug): ?string
    {
        foreach (self::MODULE_PREFIX_FALLBACK as $prefix => $topicId) {
            if (!str_starts_with($slug, $prefix)) {
                continue;
            }
            if (self::isTopicDocumented($topicId)) {
                return $topicId;
            }
        }

        return null;
    }

    public static function isTopicDocumented(string $topicId): bool
    {
        $topicId = strtolower(trim($topicId));
        if ($topicId === '' || $topicId === self::TOPIC_UNDER_DEVELOPMENT) {
            return false;
        }

        return self::topicContentPath($topicId) !== null;
    }

    public static function formatPageSlugLabel(string $pageSlug): string
    {
        $slug = strtolower(trim($pageSlug));
        if ($slug === '') {
            return '';
        }

        return ucwords(str_replace('-', ' ', $slug));
    }

    /** @return array{modules: list<array<string, mixed>>} */
    public static function loadManifest(): array
    {
        $path = self::manifestFilePath();
        if (!is_readable($path)) {
            return ['modules' => []];
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return ['modules' => []];
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : ['modules' => []];
    }

    /** @return array<string, mixed>|null */
    public static function findTopicInManifest(string $topicId): ?array
    {
        $manifest = self::loadManifest();
        foreach ($manifest['modules'] ?? [] as $module) {
            foreach ($module['topics'] ?? [] as $topic) {
                if (($topic['id'] ?? '') === $topicId) {
                    return $topic + ['module_id' => $module['id'] ?? '', 'module_title' => $module['title'] ?? ''];
                }
            }
        }

        return null;
    }

    public static function renderTopicHtml(string $topicId): ?string
    {
        $full = self::topicContentPath($topicId);
        if ($full === null) {
            return null;
        }

        $html = file_get_contents($full);
        if ($html === false) {
            return null;
        }

        return self::processManualHtml($html);
    }

    private static function topicContentPath(string $topicId): ?string
    {
        $topic = self::findTopicInManifest($topicId);
        if ($topic === null) {
            return null;
        }

        $file = (string) ($topic['file'] ?? '');
        if ($file === '' || preg_match('/[^a-z0-9_\-\/\.]/i', $file)) {
            return null;
        }

        $base = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'content');
        $full = realpath(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
        if ($base === false || $full === false || !str_starts_with($full, $base) || !is_readable($full)) {
            return null;
        }

        return $full;
    }

    /** Substitui tokens {{IMG:nome}} e {{URL_ADM}} no HTML do manual. */
    public static function processManualHtml(string $html): string
    {
        $urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $html = str_replace('{{URL_ADM}}', $urlAdm, $html);
        $html = (string) preg_replace_callback(
            '/\{\{IMG:([a-z0-9\-]+)\}\}/',
            static function (array $m): string {
                return htmlspecialchars(self::resolveManualImageUrl($m[1]), ENT_QUOTES, 'UTF-8');
            },
            $html
        );

        return $html;
    }

    /** PNG real tem prioridade sobre placeholder SVG. */
    public static function resolveManualImageUrl(string $basename): string
    {
        $basename = trim($basename);
        if ($basename === '' || !preg_match('/^[a-z0-9\-]+$/', $basename)) {
            return '';
        }

        $urlBase = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/public/adms/manual/img/sst/';
        $fsBase = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms'
            . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'sst' . DIRECTORY_SEPARATOR;

        if (is_file($fsBase . $basename . '.png')) {
            return $urlBase . $basename . '.png';
        }
        if (is_file($fsBase . $basename . '.webp')) {
            return $urlBase . $basename . '.webp';
        }

        return $urlBase . $basename . '.svg';
    }

    /** @return array{version?: int, modules: list<array<string, mixed>>} */
    public static function loadHelpMenuNav(): array
    {
        $path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'help-menu.json';
        if (!is_readable($path)) {
            return ['modules' => []];
        }

        $json = file_get_contents($path);
        $data = is_string($json) ? json_decode($json, true) : null;

        return is_array($data) ? $data : ['modules' => []];
    }

    /** @return list<array{id: string, title: string, module_id: string, module_title: string}> */
    public static function flatTopicList(): array
    {
        $out = [];
        foreach (self::loadManifest()['modules'] ?? [] as $module) {
            $moduleId = (string) ($module['id'] ?? '');
            $moduleTitle = (string) ($module['title'] ?? '');
            foreach ($module['topics'] ?? [] as $topic) {
                $out[] = [
                    'id' => (string) ($topic['id'] ?? ''),
                    'title' => (string) ($topic['title'] ?? ''),
                    'module_id' => $moduleId,
                    'module_title' => $moduleTitle,
                ];
            }
        }

        return $out;
    }

    public static function buildHelpUrl(string $pageSlug): string
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $slug = strtolower(trim($pageSlug));
        if ($slug === '') {
            return $base . '/context-help';
        }

        return $base . '/context-help/' . rawurlencode($slug);
    }

    private static function manifestFilePath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'manifest.json';
    }
}
