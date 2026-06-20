<?php
/**
 * Gera manifest MD5 dos PHP em app/adms (rodar no servidor via SSH).
 * Uso: cd /home/tiaraju/www/administrativo && php scripts/server_app_md5.php > /tmp/server_app_md5.txt
 */
declare(strict_types=1);

$base = realpath(getcwd()) ?: getcwd();
$root = $base . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'adms';

if (!is_dir($root)) {
    fwrite(STDERR, "Pasta não encontrada: {$root}\n");
    exit(1);
}

$dir = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$it = new RecursiveIteratorIterator($dir);
$rows = [];

foreach ($it as $f) {
    if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') {
        continue;
    }
    $path = $f->getRealPath() ?: $f->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($base) + 1));
    $rows[] = md5_file($path) . '  ' . $rel;
}

sort($rows);
echo implode("\n", $rows) . "\n";
