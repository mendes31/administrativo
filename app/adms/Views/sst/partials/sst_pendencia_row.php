<?php

/**
 * Linha do relatório de pendências SST (desktop e mobile).
 *
 * @param array<string, mixed> $r
 */
function sstPendenciaRow(array $r, bool $mobile = false): void
{
    global $perms, $csrfAbrirAso;
    if (!is_array($perms)) {
        $perms = [];
    }

    $nome = htmlspecialchars($r['colaborador_nome'] ?? $r['name'] ?? '-');
    $item = htmlspecialchars($r['epi_nome'] ?? $r['exame_nome'] ?? $r['treinamento_nome'] ?? '-');
    $sit = $r['situacao_label'] ?? ($r['situacao'] ?? '-');
    $badge = $r['situacao_badge'] ?? 'secondary';
    $uid = (int) ($r['adms_user_id'] ?? 0);

    if ($mobile) {
        echo '<div class="card mb-2 shadow-sm"><div class="card-body py-2">';
        echo '<div class="fw-semibold">' . $nome . '</div>';
        echo '<div class="small text-muted">' . $item . '</div>';
        echo '<span class="badge bg-' . htmlspecialchars((string) $badge) . ' mt-1">' . htmlspecialchars((string) $sit) . '</span>';
        if ($uid > 0) {
            echo '<div class="mt-2 d-flex flex-wrap gap-1">';
            sstRenderPendenciaRowActions($r, $perms, 'sst-report-pendencias', null, $csrfAbrirAso);
            echo '</div>';
        }
        echo '</div></div>';
        return;
    }

    echo '<tr>';
    echo '<td>' . $nome . '</td>';
    echo '<td>' . htmlspecialchars($r['departamento_nome'] ?? $r['name_dep'] ?? '-') . '</td>';
    echo '<td>' . $item . '</td>';
    echo '<td><span class="badge bg-' . htmlspecialchars((string) $badge) . '">' . htmlspecialchars((string) $sit) . '</span></td>';
    echo '<td class="text-center text-nowrap">';
    if ($uid > 0) {
        sstRenderPendenciaRowActions($r, $perms, 'sst-report-pendencias', null, $csrfAbrirAso);
    }
    echo '</td></tr>';
}
