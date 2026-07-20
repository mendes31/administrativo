<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/docs/manual/content';
$sections = [
    'funcao' => '/<h2>\s*Função no sistema\s*<\/h2>/iu',
    'termos' => '/<h2>\s*O que significam os termos\s*<\/h2>/iu',
    'fluxo' => '/<h2>\s*(Fluxo principal|Fluxo|Passo a passo)[^<]*<\/h2>/iu',
    'campos' => '/<h2>\s*(Campos|Campos e (telas|parâmetros|parametros)|Parâmetros|Parametros)[^<]*<\/h2>/iu',
    'problemas' => '/<h2>\s*Problemas comuns\s*<\/h2>/iu',
    'quem' => '/Quem acessa:/iu',
];

$counts = array_fill_keys(array_keys($sections), 0);
$total = 0;
$incomplete = [];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile() || strtolower($f->getExtension()) !== 'html') {
        continue;
    }
    $total++;
    $html = (string) file_get_contents($f->getPathname());
    $missing = [];
    foreach ($sections as $k => $re) {
        if (preg_match($re, $html)) {
            $counts[$k]++;
        } else {
            $missing[] = $k;
        }
    }
    $req = ['funcao', 'fluxo', 'campos', 'problemas'];
    $missReq = array_values(array_intersect($missing, $req));
    if ($missReq !== []) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
        $incomplete[] = $rel . ' [-' . implode(',', $missReq) . ']';
    }
}

echo "total={$total}\n";
foreach ($counts as $k => $v) {
    $pct = $total > 0 ? (int) round(100 * $v / $total) : 0;
    echo "{$k}={$v} ({$pct}%)\n";
}
echo 'incomplete_required=' . count($incomplete) . "\n";
echo implode("\n", array_slice($incomplete, 0, 40));
if (count($incomplete) > 40) {
    echo "\n... e mais " . (count($incomplete) - 40) . "\n";
}
