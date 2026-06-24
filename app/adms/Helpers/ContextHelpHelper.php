<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Ajuda de contexto (F1): resolve slug da tela → tópico do manual.
 */
final class ContextHelpHelper
{
    private const MANIFEST_PATH = 'docs/manual/manifest.json';

    /** Slug da página (menu / controller_url) → id do tópico no manual. */
    private const PAGE_TOPIC_MAP = [
        'sst-dashboard' => 'sst-dashboard',
        'sst-employee-profile' => 'sst-perfil-colaborador',

        'sst-list-cids' => 'sst-cids',
        'sst-create-cid' => 'sst-cids',
        'sst-update-cid' => 'sst-cids',

        'sst-list-epis' => 'sst-epis',
        'sst-create-epi' => 'sst-epis',
        'sst-view-epi' => 'sst-epis',
        'sst-update-epi' => 'sst-epis',

        'sst-list-exames' => 'sst-exames',
        'sst-create-exame' => 'sst-exames',
        'sst-view-exame' => 'sst-exames',
        'sst-update-exame' => 'sst-exames',

        'sst-list-medicos' => 'sst-medicos',
        'sst-create-medico' => 'sst-medicos',
        'sst-update-medico' => 'sst-medicos',

        'sst-list-epi-necessidade' => 'sst-necessidades',
        'sst-create-epi-necessidade' => 'sst-necessidades',
        'sst-update-epi-necessidade' => 'sst-necessidades',
        'sst-list-exame-necessidade' => 'sst-necessidades',
        'sst-create-exame-necessidade' => 'sst-necessidades',
        'sst-update-exame-necessidade' => 'sst-necessidades',
        'sst-list-treinamento-necessidade' => 'sst-necessidades',
        'sst-create-treinamento-necessidade' => 'sst-necessidades',
        'sst-update-treinamento-necessidade' => 'sst-necessidades',

        'sst-matriz-treinamento-cargo' => 'sst-matriz-treinamento',
        'sst-save-matriz-treinamento-cargo' => 'sst-matriz-treinamento',

        'sst-list-riscos' => 'sst-riscos',
        'sst-create-risco' => 'sst-riscos',
        'sst-view-risco' => 'sst-riscos',
        'sst-update-risco' => 'sst-riscos',
        'sst-save-risco-relacionamentos' => 'sst-riscos',
        'sst-save-risco-treinamentos' => 'sst-riscos',
        'sst-list-risco-cargo' => 'sst-riscos',
        'sst-create-risco-cargo' => 'sst-riscos',
        'sst-update-risco-cargo' => 'sst-riscos',
        'sst-list-risco-exame' => 'sst-riscos',
        'sst-list-risco-epi' => 'sst-riscos',
        'sst-list-risco-treinamento' => 'sst-riscos',

        'sst-list-treinamentos' => 'sst-treinamentos',
        'sst-create-treinamento' => 'sst-treinamentos',
        'sst-view-treinamento' => 'sst-treinamentos',
        'sst-update-treinamento' => 'sst-treinamentos',

        'sst-list-ghe' => 'sst-ghe',
        'sst-create-ghe' => 'sst-ghe',
        'sst-view-ghe' => 'sst-ghe',
        'sst-update-ghe' => 'sst-ghe',
        'sst-save-ghe-relacionamentos' => 'sst-ghe',

        'sst-list-equipamento-tipos' => 'sst-equipamentos',
        'sst-list-equipamentos' => 'sst-equipamentos',
        'sst-equipamento-settings' => 'sst-equipamentos',
        'sst-minhas-equipamento-vistorias' => 'sst-equipamentos',
        'sst-scan-equipamento' => 'sst-equipamentos',
        'sst-execute-equipamento-vistoria' => 'sst-equipamentos',

        'sst-list-acidentes' => 'sst-acidentes-afastamentos',
        'sst-create-acidente' => 'sst-acidentes-afastamentos',
        'sst-view-acidente' => 'sst-acidentes-afastamentos',
        'sst-list-afastamentos' => 'sst-acidentes-afastamentos',
        'sst-create-afastamento' => 'sst-acidentes-afastamentos',
        'sst-view-afastamento' => 'sst-acidentes-afastamentos',

        'sst-list-asos' => 'sst-asos',
        'sst-create-aso' => 'sst-asos',
        'sst-view-aso' => 'sst-asos',
        'sst-update-aso' => 'sst-asos',
        'sst-abrir-aso-pendencia' => 'sst-asos',
        'sst-registrar-resultados-aso' => 'sst-asos',
        'sst-encaminhamento-aso' => 'sst-asos',

        'sst-list-treinamento-vinculos' => 'sst-treinamentos',
        'sst-view-treinamento-vinculo' => 'sst-treinamentos',
        'sst-apply-treinamento' => 'sst-treinamentos',
        'sst-sync-treinamento-vinculos' => 'sst-treinamentos',

        'sst-list-epi-fichas' => 'sst-epi-fichas',
        'sst-create-epi-ficha' => 'sst-epi-fichas',
        'sst-view-epi-ficha' => 'sst-epi-fichas',
        'sst-list-epi-movimentos' => 'sst-epi-fichas',
        'sst-create-epi-movimento' => 'sst-epi-fichas',
        'sst-list-epi-estoque' => 'sst-epi-fichas',

        'sst-list-inspecoes' => 'sst-cipa-inspecoes',
        'sst-create-inspecao' => 'sst-cipa-inspecoes',
        'sst-view-inspecao' => 'sst-cipa-inspecoes',
        'sst-list-cipa-mandatos' => 'sst-cipa-inspecoes',
        'sst-create-cipa-mandato' => 'sst-cipa-inspecoes',
        'sst-view-cipa-mandato' => 'sst-cipa-inspecoes',

        'sst-report-conformidade' => 'sst-conformidade',
        'sst-list-programas' => 'sst-conformidade',
        'sst-view-programa' => 'sst-conformidade',
        'sst-list-esocial-eventos' => 'sst-esocial-ppp',
        'sst-view-esocial-evento' => 'sst-esocial-ppp',
        'sst-list-ppp' => 'sst-esocial-ppp',
        'sst-view-ppp' => 'sst-esocial-ppp',

        'sst-report-pendencias' => 'sst-report-pendencias',
        'sst-report-epis' => 'sst-relatorios',
        'sst-report-exames' => 'sst-relatorios',
        'sst-report-treinamentos' => 'sst-relatorios',
        'sst-report-afastamentos' => 'sst-relatorios',
        'sst-report-cids' => 'sst-relatorios',

        'my-sst-treinamentos' => 'sst-treinamentos',
    ];

    public static function resolveTopicFromPageSlug(string $pageSlug): string
    {
        $slug = strtolower(trim($pageSlug));
        if ($slug === '') {
            return 'index';
        }

        if (isset(self::PAGE_TOPIC_MAP[$slug])) {
            return self::PAGE_TOPIC_MAP[$slug];
        }

        if (str_starts_with($slug, 'sst-')) {
            return 'sst-visao-geral';
        }

        return 'index';
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

        $html = file_get_contents($full);
        if ($html === false) {
            return null;
        }

        return self::processManualHtml($html);
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
        $topic = self::resolveTopicFromPageSlug($pageSlug);
        if ($topic === 'index') {
            return $base . '/context-help';
        }

        return $base . '/context-help/' . rawurlencode($topic);
    }

    private static function manifestFilePath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'manifest.json';
    }
}
