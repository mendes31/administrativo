<?php
/**
 * Verificação rápida pós-deploy (executar via SSH na raiz do projeto).
 *
 *   php scripts/verify_production_deploy.php
 */

declare(strict_types=1);

require __DIR__ . '/deploy_critical_manifest.php';

$root = dirname(__DIR__);
$errors = [];
$warnings = [];

foreach (deployCriticalManifest() as $entry) {
    $rel = $entry['path'];
    $path = $root . '/' . $rel;

    if (!is_file($path)) {
        $errors[] = "Ficheiro em falta: {$rel}";
        continue;
    }

    $lintOut = [];
    if (str_ends_with($rel, '.php')) {
        exec('php -l ' . escapeshellarg($path) . ' 2>&1', $lintOut, $lintCode);
        if ($lintCode !== 0) {
            $errors[] = "{$rel}: " . trim(implode("\n", $lintOut));
        }
    }

    $content = (string) file_get_contents($path);
    foreach ($entry['must_contain'] ?? [] as $needle) {
        if (!str_contains($content, $needle)) {
            $errors[] = "{$rel} sem marcador: {$needle}";
        }
    }
}

$menuPath = $root . '/app/adms/Views/partials/menu.php';
$menuLines = is_file($menuPath) ? count(file($menuPath, FILE_IGNORE_NEW_LINES) ?: []) : 0;
if ($menuLines > 0 && $menuLines < 1770) {
    $errors[] = "menu.php tem {$menuLines} linhas (esperado >= 1770).";
}

$statePath = $root . '/.ftp-deploy-sync-state.json';
if (!is_file($statePath)) {
    $warnings[] = '.ftp-deploy-sync-state.json ausente — próximo deploy FTP pode reenviar tudo ou pular ficheiros errados.';
} else {
    $stateSize = (int) filesize($statePath);
    if ($stateSize < 100000) {
        $warnings[] = ".ftp-deploy-sync-state.json pequeno ({$stateSize} bytes) — pode estar desatualizado.";
    }
}

$migration = $root . '/database/migrations/20260622140000_register_training_compliance_dashboard_page.php';
if (is_file($migration)) {
    $warnings[] = 'Confirme migration: php vendor/bin/phinx migrate -c database/phinx.php -e production';
}

echo "=== Verificação de deploy — " . date('Y-m-d H:i:s') . " ===\n";
echo "Raiz: {$root}\n\n";
echo "menu.php: " . ($menuLines > 0 ? "{$menuLines} linhas" : 'N/A') . "\n";
echo "Ficheiros críticos (manifest): " . count(deployCriticalManifest()) . "\n\n";

if ($warnings !== []) {
    echo "AVISOS:\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
    echo "\n";
}

if ($errors !== []) {
    echo "ERROS:\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
    echo "\nCorrija com deploy GitHub + migrations (phinx migrate).\n";
    exit(1);
}

echo "OK: ficheiros críticos presentes e íntegros.\n";
echo "Execute também: php vendor/bin/phinx migrate -c database/phinx.php -e production\n";
exit(0);
