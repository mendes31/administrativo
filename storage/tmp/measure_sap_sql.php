<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\adms\Models\Services\SapProjects\SapProjectsSql;

$sql = new SapProjectsSql('');
$queries = [
    'projetos' => $sql->projetos([]),
    'documentos' => $sql->documentos([]),
    'itens18' => $sql->itens([], 18),
    'headers22' => $sql->documentHeadersByEntries(22, range(1, 80)),
];

foreach ($queries as $name => $q) {
    $compact = preg_replace('/\s{2,}/', ' ', str_replace(["\r\n", "\r", "\n", "\t"], ' ', trim($q))) ?? $q;
    $url = 'http://example/query?sql=' . rawurlencode($compact);
    echo $name . ' sql=' . strlen($compact) . ' url=' . strlen($url) . PHP_EOL;
}
