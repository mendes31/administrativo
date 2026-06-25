<?php

declare(strict_types=1);

/**
 * Pre-commit: bloqueia commit se alterações de feature não tiverem manual alinhado.
 *
 * Instalação (uma vez):
 *   git config core.hooksPath scripts/git-hooks
 *
 * Ou copie para .git/hooks/pre-commit e chame este script.
 */

$root = dirname(__DIR__, 2);
chdir($root);

require_once $root . '/scripts/manual_coverage_lib.php';

$relevantPatterns = [
    '#^app/adms/Controllers/#',
    '#^app/adms/Views/#',
    '#^database/migrations/.+register.+page#i',
    '#^database/seeds/AddAdmsPages\.php$#',
    '#^docs/manual/#',
];

$changed = manual_git_list_changed_files();
$touched = false;
foreach ($changed as $file) {
    foreach ($relevantPatterns as $pattern) {
        if (preg_match($pattern, $file)) {
            $touched = true;
            break 2;
        }
    }
}

if (!$touched) {
    exit(0);
}

$meta = manual_run_changed_coverage_audit();
$audit = $meta['audit'];
$issues = count($audit['missing_map'])
    + count($audit['missing_aggregate'])
    + count($audit['new_primary_unmapped'])
    + count($audit['missing_html'])
    + count($audit['skeleton'])
    + count($audit['permission_undocumented']);

if ($issues === 0) {
    exit(0);
}

fwrite(STDERR, "\n[manual] Pendências de documentação nas alterações deste commit:\n");
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/scripts/audit_manual_coverage.php') . ' --changed --strict', $code);

exit($code !== 0 ? 1 : 0);
