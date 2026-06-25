<?php

declare(strict_types=1);

/**
 * Pre-commit: bloqueia commit se alterações de feature não tiverem manual alinhado.
 *
 * Audita apenas arquivos no índice (git add), não o working tree inteiro.
 *
 * Instalação (uma vez):
 *   git config core.hooksPath scripts/git-hooks
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

$staged = manual_git_list_changed_files(null, true);
$relevantStaged = [];
foreach ($staged as $file) {
    foreach ($relevantPatterns as $pattern) {
        if (preg_match($pattern, $file)) {
            $relevantStaged[] = $file;
            break;
        }
    }
}

if ($relevantStaged === []) {
    exit(0);
}

$meta = manual_run_changed_coverage_audit(null, true);
$audit = $meta['audit'];

$issues = count($audit['missing_map'])
    + count($audit['missing_aggregate'])
    + count($audit['new_primary_unmapped'])
    + count($audit['missing_html'])
    + count($audit['permission_undocumented']);

// Commits só de HTML do manual: esqueleto é esperado até revisão editorial.
$onlyManualDocs = array_reduce(
    $relevantStaged,
    static fn (bool $ok, string $path): bool => $ok && str_starts_with($path, 'docs/manual/'),
    true
);
if (!$onlyManualDocs) {
    $issues += count($audit['skeleton']);
}

if ($issues === 0) {
    exit(0);
}

fwrite(STDERR, "\n[manual] Pendências de documentação no commit (arquivos preparados):\n");
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/scripts/audit_manual_coverage.php') . ' --changed --staged --strict', $code);

exit($code !== 0 ? 1 : 0);
