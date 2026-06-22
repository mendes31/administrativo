<?php
/**
 * Gera .ftp-deploy-sync-state.json compatível com SamKirkland/FTP-Deploy-Action v4.
 *
 * Use quando arquivos já estão corretos no servidor, mas o deploy continua
 * marcando tudo como "uploading" (estado perdido por timeout FTP ou upload manual).
 *
 * Uso (na raiz do projeto):
 *   php scripts/generate_ftp_deploy_state.php
 *   scp .ftp-deploy-sync-state.json USER@HOST:~/www/administrativo/
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$outputFile = $projectRoot . DIRECTORY_SEPARATOR . '.ftp-deploy-sync-state.json';
$stateFileName = '.ftp-deploy-sync-state.json';

/**
 * Mesmas exclusões do .github/workflows/deploy.yml (FTP-Deploy-Action).
 */
function deployPathExcluded(string $relPath): bool
{
    $relPath = str_replace('\\', '/', $relPath);

    if ($relPath === '' || $relPath === '.ftp-deploy-sync-state.json') {
        return true;
    }

    if (in_array(basename($relPath), ['.gitignore', '.DS_Store', 'LICENSE.txt', '.env'], true)) {
        return true;
    }

    if (str_ends_with($relPath, '.log')) {
        return true;
    }

    $prefixes = [
        '.git/',
        '.github/',
        'node_modules/',
        '.vscode/',
        'vendor/',
        'lib/',
        'storage/sst/epi_fichas/',
        'storage/sst/attachments/',
        'storage/lgpd/consentimentos/',
        'storage/private/payroll/',
        'storage/cache/',
        'storage/logs/',
        'logs/',
        'app/storage/cache/',
        'app/storage/logs/',
    ];

    foreach ($prefixes as $prefix) {
        if ($relPath === rtrim($prefix, '/')) {
            return true;
        }
        if (str_starts_with($relPath, $prefix)) {
            if ($relPath === 'storage/private/payroll/.gitkeep') {
                return false;
            }

            return true;
        }
    }

    return false;
}

$files = [];
$folders = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $item) {
    $absolute = $item->getPathname();
    $rel = substr($absolute, strlen($projectRoot) + 1);
    $rel = str_replace('\\', '/', $rel);

    if (deployPathExcluded($rel)) {
        continue;
    }

    if ($item->isDir()) {
        $folders[$rel] = true;
        continue;
    }

    if (!$item->isFile()) {
        continue;
    }

    $hash = hash_file('sha256', $absolute);
    if ($hash === false) {
        fwrite(STDERR, "Erro ao ler: {$rel}\n");
        exit(1);
    }

    $files[] = [
        'type' => 'file',
        'name' => $rel,
        'hash' => $hash,
        'size' => (int) $item->getSize(),
    ];

    $parts = explode('/', $rel);
    array_pop($parts);
    $acc = '';
    foreach ($parts as $part) {
        $acc = $acc === '' ? $part : $acc . '/' . $part;
        $folders[$acc] = true;
    }
}

$data = [];
foreach (array_keys($folders) as $folder) {
    $data[] = ['type' => 'folder', 'name' => $folder];
}
usort($data, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));

usort($files, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
foreach ($files as $file) {
    $data[] = $file;
}

$payload = [
    'description' => 'DO NOT DELETE THIS FILE. This file is used to keep track of which files have been synced in the most recent deployment. If you delete this file a resync will need to be done (which can take a while) - read more: https://github.com/SamKirkland/FTP-Deploy-Action',
    'version' => '1.0.0',
    'generatedTime' => (int) round(microtime(true) * 1000),
    'data' => $data,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    fwrite(STDERR, "Erro ao serializar JSON.\n");
    exit(1);
}

file_put_contents($outputFile, $json . "\n");

echo "Gerado: {$outputFile}\n";
echo 'Pastas: ' . count($folders) . ', arquivos: ' . count($files) . "\n";
echo "Envie ao servidor:\n";
echo "  scp .ftp-deploy-sync-state.json USER@HOST:~/www/administrativo/\n";
