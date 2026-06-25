<?php

declare(strict_types=1);

/**
 * Auditoria de cobertura do manual de ajuda (F1) vs páginas do sistema.
 *
 * Uso:
 *   php scripts/audit_manual_coverage.php           # relatório
 *   php scripts/audit_manual_coverage.php --fix     # gera esqueletos + regenera manifest/map/menu
 *   php scripts/audit_manual_coverage.php --strict  # exit 1 se houver pendências
 *   php scripts/audit_manual_coverage.php --json    # saída JSON (CI)
 *   php scripts/audit_manual_coverage.php --changed # só alterações git (pre-commit/PR)
 *   php scripts/audit_manual_coverage.php --changed --base=origin/main
 */

require_once __DIR__ . '/manual_coverage_lib.php';

$fix = in_array('--fix', $argv ?? [], true);
$strict = in_array('--strict', $argv ?? [], true);
$json = in_array('--json', $argv ?? [], true);
$changedOnly = in_array('--changed', $argv ?? [], true);
$baseRef = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseRef = substr($arg, 7);
    }
}

$changedMeta = null;
if ($changedOnly) {
    $changedMeta = manual_run_changed_coverage_audit($baseRef);
    $audit = $changedMeta['audit'];
} else {
    $audit = manual_run_coverage_audit();
}

if ($fix) {
    $created = manual_fix_missing_skeletons($audit['missing_html'], $audit['skeleton']);
    manual_write_pending_aggregate($audit['missing_aggregate']);
    manual_regenerate_manual_artifacts();
    if ($changedOnly) {
        $changedMeta = manual_run_changed_coverage_audit($baseRef);
        $audit = $changedMeta['audit'];
    } else {
        $audit = manual_run_coverage_audit();
    }
    $audit['fixed_files'] = $created;
}

$issueCount = count($audit['missing_map'])
    + count($audit['missing_aggregate'])
    + count($audit['new_primary_unmapped'])
    + count($audit['missing_html'])
    + count($audit['skeleton'])
    + count($audit['permission_undocumented']);

if ($json) {
    $payload = $audit;
    if ($changedMeta !== null) {
        $payload = [
            'changed_files' => $changedMeta['changed_files'],
            'touch' => $changedMeta['touch'],
            'audit' => $audit,
        ];
    }
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    exit($strict && $issueCount > 0 ? 1 : 0);
}

echo "=== Auditoria do Manual de Ajuda ===\n";
if ($changedOnly) {
    $n = count($changedMeta['changed_files'] ?? []);
    $scope = $baseRef !== null && $baseRef !== '' ? "desde {$baseRef}" : 'no working tree (staged + unstaged)';
    echo "Modo: --changed ({$n} arquivo(s) {$scope})\n";
}
echo 'Documentados OK: ' . $audit['ok_count'] . "\n";
echo 'Pendências: ' . $issueCount . "\n\n";

if ($audit['missing_map'] !== []) {
    echo "[MAP] Slugs sem entrada em page-topic-map.json:\n";
    foreach ($audit['missing_map'] as $row) {
        echo "  - {$row['slug']} ({$row['name']})\n";
    }
    echo "  → Rode: php scripts/generate_manual_page_topic_map.php\n\n";
}

if ($audit['missing_aggregate'] !== []) {
    echo "[AGGREGATE] Slugs rastreados fora do mapa agregado:\n";
    foreach ($audit['missing_aggregate'] as $row) {
        echo "  - {$row['slug']} ({$row['name']}) [{$row['directory']}]\n";
    }
    echo "  → Adicione em scripts/manual_aggregate_topic_map.php\n\n";
}

if (($audit['new_primary_unmapped'] ?? []) !== []) {
    echo "[NOVO] Telas principais ainda não mapeadas no manual:\n";
    foreach ($audit['new_primary_unmapped'] as $row) {
        echo "  - {$row['slug']} ({$row['name']}) [{$row['directory']}]\n";
    }
    echo "  → Inclua no mapa agregado e documente o fluxo\n\n";
}

if ($audit['missing_html'] !== []) {
    echo "[HTML] Tópicos sem arquivo HTML:\n";
    foreach ($audit['missing_html'] as $row) {
        $reason = isset($row['reason']) ? " ({$row['reason']})" : '';
        echo "  - {$row['slug']} → {$row['topic_id']}{$reason}\n";
    }
    echo "  → Rode: php scripts/audit_manual_coverage.php --fix\n\n";
}

if ($audit['skeleton'] !== []) {
    echo "[SKELETON] Tópicos só com esqueleto (revisar em português):\n";
    foreach ($audit['skeleton'] as $row) {
        echo "  - {$row['topic_id']} ({$row['file']})\n";
    }
    echo "  → Expanda o HTML manualmente\n\n";
}

if ($audit['permission_undocumented'] !== []) {
    echo "[PERMISSÃO] Permissões não citadas no tópico pai:\n";
    foreach ($audit['permission_undocumented'] as $row) {
        $parent = $row['parent_topic'] ?? 'desconhecido';
        echo "  - {$row['controller']} ({$row['name']}) → documentar em {$parent}\n";
    }
    echo "  → Inclua <em>Controller</em> no HTML do tópico da tela principal\n\n";
}

if (($audit['orphan_html'] ?? []) !== []) {
    echo '[AVISO] HTML sem página no seed: ' . count($audit['orphan_html']) . " arquivo(s)\n\n";
}

if (!empty($audit['fixed_files'])) {
    echo '[FIX] Arquivos criados: ' . count($audit['fixed_files']) . "\n\n";
}

if ($issueCount === 0) {
    if ($changedOnly) {
        echo "OK: nenhuma pendência de manual nas alterações detectadas.\n";
    } else {
        echo "OK: manual alinhado com as páginas auditadas.\n";
    }
    exit(0);
}

echo "Ação: revise docs/manual/README.md\n";
exit($strict ? 1 : 0);
