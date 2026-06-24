<?php

declare(strict_types=1);

/**
 * Gera placeholders SVG do manual até substituição por capturas PNG reais.
 * Uso: php scripts/ensure_manual_screenshot_placeholders.php
 */
$root = dirname(__DIR__);
$dir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'adms' . DIRECTORY_SEPARATOR . 'manual' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'sst';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}

$screens = [
    'dashboard' => 'Dashboard SST',
    'riscos-lista' => 'Listagem de riscos',
    'risco-relacionamentos' => 'Risco — abas de relacionamentos',
    'matriz-treinamento' => 'Matriz cargo × treinamento',
    'treinamentos-lista' => 'Catálogo de treinamentos',
    'ghe-visualizar' => 'GHE — colaboradores e treinamentos',
    'asos-lista' => 'Listagem de ASOs',
    'epis-lista' => 'Catálogo de EPIs',
];

foreach ($screens as $file => $label) {
    $path = $dir . DIRECTORY_SEPARATOR . $file . '.svg';
    if (is_file($path)) {
        continue;
    }
    $safe = htmlspecialchars($label, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="960" height="540" viewBox="0 0 960 540" role="img" aria-label="{$safe}">
  <rect width="100%" height="100%" fill="#f8f9fa" stroke="#ced4da" stroke-width="2"/>
  <rect x="24" y="24" width="912" height="48" rx="6" fill="#e9ecef"/>
  <rect x="24" y="88" width="220" height="428" rx="6" fill="#e9ecef"/>
  <rect x="260" y="88" width="676" height="428" rx="6" fill="#ffffff" stroke="#dee2e6"/>
  <text x="480" y="310" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="22" fill="#495057">{$safe}</text>
  <text x="480" y="342" text-anchor="middle" font-family="Segoe UI, Arial, sans-serif" font-size="14" fill="#868e96">Substitua por captura PNG em public/adms/manual/img/sst/{$file}.png</text>
</svg>
SVG;
    file_put_contents($path, $svg);
    echo "Criado: {$file}.svg\n";
}

echo "Concluído. Para captura real, salve PNG com o mesmo nome (ex.: dashboard.png).\n";
