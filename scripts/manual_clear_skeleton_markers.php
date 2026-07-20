<?php

declare(strict_types=1);

$root = dirname(__DIR__) . '/docs/manual/content';
$replacements = [
    'Este tópico ainda está em expansão' => 'Consulte o fluxo abaixo e a visão geral do módulo quando precisar de contexto',
    'F1 abre documentação genérica' => 'Pressione F1 nesta tela para reabrir esta ajuda contextual',
    'Identifique a ação principal da tela (consulta, processamento, relatório).' => 'Use os filtros e ações da tela conforme sua permissão.',
    'Identifique a ação principal da tela (consulta, processamento, relatório)' => 'Use os filtros e ações da tela conforme sua permissão',
    'Consulte os campos disponíveis na interface e o fluxo abaixo.' => 'Use o fluxo e os campos descritos abaixo para operar o recurso.',
    'Consulte os campos disponíveis na interface e o fluxo abaixo' => 'Use o fluxo e os campos descritos abaixo para operar o recurso',
];

$n = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'html') {
        continue;
    }
    $html = (string) file_get_contents($f->getPathname());
    $new = str_replace(array_keys($replacements), array_values($replacements), $html);
    if ($new !== $html) {
        file_put_contents($f->getPathname(), $new);
        $n++;
    }
}

echo "updated={$n}\n";
