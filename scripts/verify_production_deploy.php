<?php
/**
 * Verificação rápida pós-deploy (executar via SSH na raiz do projeto).
 *
 *   php scripts/verify_production_deploy.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$errors = [];
$warnings = [];

$menuPath = $root . '/app/adms/Views/partials/menu.php';
$menuLines = 0;
if (is_file($menuPath)) {
    $menuLines = count(file($menuPath, FILE_IGNORE_NEW_LINES) ?: []);
    $menuLint = [];
    exec('php -l ' . escapeshellarg($menuPath) . ' 2>&1', $menuLint, $lintCode);
    $lintText = trim(implode("\n", $menuLint));
    if ($lintCode !== 0) {
        $errors[] = "menu.php: {$lintText}";
    }
    if ($menuLines < 1770) {
        $errors[] = "menu.php tem {$menuLines} linhas (esperado >= 1770). Restaure via deploy Git, não FileZilla parcial.";
    }
    $menuContent = (string) file_get_contents($menuPath);
    foreach (
        [
            'Tipos de equipamento',
            'Equipamentos de segurança',
            'Config. vistorias equipamentos',
            'Vistorias de equipamentos',
        ] as $needle
    ) {
        if (!str_contains($menuContent, $needle)) {
            $errors[] = "menu.php sem entrada de menu: {$needle}";
        }
    }
} else {
    $errors[] = 'menu.php não encontrado.';
}

$sstControllers = [
    'app/adms/Controllers/sst/SstListEquipamentoTipos.php',
    'app/adms/Controllers/sst/SstListEquipamentos.php',
    'app/adms/Controllers/sst/SstEquipamentoSettings.php',
    'app/adms/Controllers/sst/SstMinhasEquipamentoVistorias.php',
];
foreach ($sstControllers as $rel) {
    if (!is_file($root . '/' . $rel)) {
        $errors[] = "Ficheiro em falta: {$rel}";
    }
}

$migrations = glob($root . '/database/migrations/20260622*sst_equipamentos*.php') ?: [];
$migrations = array_merge($migrations, glob($root . '/database/migrations/20260623*sst_equipamento*.php') ?: []);
sort($migrations);
if ($migrations === []) {
    $warnings[] = 'Nenhuma migration SST equipamentos encontrada no código (verifique deploy).';
}

echo "=== Verificação de deploy — " . date('Y-m-d H:i:s') . " ===\n";
echo "Raiz: {$root}\n\n";

echo "menu.php: " . ($menuLines > 0 ? "{$menuLines} linhas" : 'N/A') . "\n";
echo "Migrations SST no código: " . count($migrations) . "\n\n";

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

echo "OK: ficheiros críticos presentes e menu.php íntegro.\n";
echo "Execute também: php vendor/bin/phinx migrate -c database/phinx.php -e production\n";
exit(0);
