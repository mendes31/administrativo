<?php
$dir = __DIR__ . '/../app/adms/Views/companyEvents';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $c = file_get_contents($path);
    $n = str_replace('<motion', '<div', $c);
    $n = str_replace('</motion>', '</div>', $n);
    if ($n !== $c) {
        file_put_contents($path, $n);
        echo "Fixed: $path\n";
    }
}
