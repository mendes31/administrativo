<?php
$map = json_decode(file_get_contents(__DIR__ . '/../docs/manual/page-topic-map.json'), true);
$manifest = json_decode(file_get_contents(__DIR__ . '/../docs/manual/manifest.json'), true);
$ids = [];
foreach ($manifest['modules'] as $m) {
    foreach ($m['topics'] as $t) {
        $ids[$t['id']] = true;
    }
}
$missing = [];
foreach (array_unique(array_values($map)) as $topic) {
    if (!isset($ids[$topic])) {
        $missing[] = $topic;
    }
}
echo count($missing) ? "Faltam no manifest: " . implode(', ', $missing) : "OK: todos os topicos do map estao no manifest.\n";
