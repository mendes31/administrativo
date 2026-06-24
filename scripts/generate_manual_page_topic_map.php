<?php

declare(strict_types=1);

/**
 * Gera docs/manual/page-topic-map.json.
 * Telas com HTML individual (slug.html) mapeiam para o próprio slug; SST mantém mapa dedicado.
 * Execute: php scripts/generate_manual_page_topic_map.php
 */

require_once __DIR__ . '/manual_aggregate_topic_map.php';

$contentRoot = dirname(__DIR__) . '/docs/manual/content';
$topicToDir = manualTopicToContentDir();
$aggregateMap = buildManualAggregateTopicMap();

$sstPath = __DIR__ . '/../docs/manual/page-topic-map.sst.json';
$sst = json_decode((string) file_get_contents($sstPath), true);
if (!is_array($sst)) {
    $sst = [];
}

$map = $sst;

foreach ($aggregateMap as $slug => $aggregateTopic) {
    if (isset($sst[$slug])) {
        continue;
    }

    $dir = $topicToDir[$aggregateTopic] ?? null;
    $pageFile = $dir !== null
        ? $contentRoot . '/' . $dir . '/' . $slug . '.html'
        : null;

    if ($pageFile !== null && is_file($pageFile)) {
        $map[$slug] = $slug;
    } else {
        $map[$slug] = $aggregateTopic;
    }
}

ksort($map);

$out = __DIR__ . '/../docs/manual/page-topic-map.json';
file_put_contents($out, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

$individual = 0;
foreach ($map as $slug => $topic) {
    if ($slug === $topic) {
        $individual++;
    }
}

echo 'Gerado: ' . $out . ' (' . count($map) . " entradas, {$individual} mapeamentos 1:1 por tela).\n";
