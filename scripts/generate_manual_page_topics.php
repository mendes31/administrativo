<?php

declare(strict_types=1);

/**
 * Gera tópicos individuais por tela (slug → slug) para o manual contextual.
 * Uso: php scripts/generate_manual_page_topics.php
 */

require_once __DIR__ . '/manual_doc_lib.php';
require_once __DIR__ . '/manual_aggregate_topic_map.php';
require_once __DIR__ . '/manual_coverage_lib.php';

$contentRoot = dirname(__DIR__) . '/docs/manual/content';
$definitionsDir = dirname(__DIR__) . '/docs/manual/page-definitions';

$topicToDir = manualTopicToContentDir();
$moduleTitles = manualModuleTitlesByDir();
$aggregateLabels = manualAggregateTopicLabels();
$aggregateMap = buildManualAggregateTopicMap();

$sstPath = dirname(__DIR__) . '/docs/manual/page-topic-map.sst.json';
$sstMap = json_decode((string) file_get_contents($sstPath), true);
if (!is_array($sstMap)) {
    $sstMap = [];
}

/** @var array<string, array{title: string, html: string}> */
$detailed = [];
foreach (glob($definitionsDir . '/*.php') ?: [] as $defFile) {
    $chunk = require $defFile;
    if (is_array($chunk)) {
        $detailed = array_merge($detailed, $chunk);
    }
}

$written = 0;
$skippedSst = 0;

foreach ($aggregateMap as $slug => $aggregateTopic) {
    if (isset($sstMap[$slug])) {
        $skippedSst++;
        continue;
    }

    if ($slug === $aggregateTopic) {
        continue;
    }

    $dir = $topicToDir[$aggregateTopic] ?? null;
    if ($dir === null) {
        echo "AVISO: sem pasta para slug {$slug} (tópico {$aggregateTopic})\n";
        continue;
    }

    $moduleTitle = $moduleTitles[$dir] ?? $dir;
    $parentLabel = $aggregateLabels[$aggregateTopic] ?? manual_humanize_slug($aggregateTopic);
    $permission = manual_slug_to_permission($slug);

    $pageTitles = manual_page_titles_by_slug();

    if (isset($detailed[$slug])) {
        $title = $detailed[$slug]['title'];
        $html = $detailed[$slug]['html'];
    } else {
        $title = $pageTitles[$slug] ?? manual_humanize_slug($slug);
        $html = manual_skeleton_doc(
            $slug,
            $title,
            $permission,
            $moduleTitle,
            $aggregateTopic,
            $parentLabel
        );
    }

    $targetDir = $contentRoot . DIRECTORY_SEPARATOR . $dir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $path = $targetDir . DIRECTORY_SEPARATOR . $slug . '.html';
    file_put_contents($path, $html);
    $written++;
}

echo "Tópicos por página: {$written} arquivos gerados (SST mantido: {$skippedSst} slugs no mapa SST).\n";
echo "Execute em seguida: php scripts/generate_manual_page_topic_map.php && php scripts/generate_manual_manifest.php\n";
