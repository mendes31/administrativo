<?php

/**
 * Ações contextuais por tipo de pendência (relatório, perfil SST).
 *
 * @param array<string, mixed> $r
 * @param list<string> $perms
 */
function sstRenderPendenciaRowActions(
    array $r,
    array $perms,
    string $redirect = 'sst-report-pendencias',
    ?int $defaultUserId = null,
    ?string $csrfAso = null
): void {
    if (!is_array($perms)) {
        $perms = [];
    }
    $uid = (int) ($r['adms_user_id'] ?? $defaultUserId ?? 0);
    if ($uid <= 0) {
        return;
    }

    $tipo = (string) ($r['tipo_pendencia'] ?? '');
    if ($tipo === '') {
        if (isset($r['epi_nome'])) {
            $tipo = 'epi';
        } elseif (isset($r['exame_nome'])) {
            $tipo = 'exame_complementar';
        } elseif (isset($r['treinamento_nome'])) {
            $tipo = 'treinamento';
        }
    }

    $urlAdm = (string) ($_ENV['URL_ADM'] ?? '');

    if (in_array('SstEmployeeProfile', $perms, true)) {
        echo '<a href="' . htmlspecialchars($urlAdm . 'sst-employee-profile/' . $uid, ENT_QUOTES, 'UTF-8')
            . '" class="btn btn-info btn-sm" title="Perfil SST"><i class="fa-regular fa-eye"></i></a> ';
    }

    if ($tipo === 'epi' && in_array('SstCreateEpiFicha', $perms, true)) {
        $href = $urlAdm . 'sst-create-epi-ficha?adms_user_id=' . $uid;
        $epiId = (int) ($r['adms_sst_epi_id'] ?? 0);
        if ($epiId > 0) {
            $href .= '&adms_sst_epi_id=' . $epiId;
        }
        echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '" class="btn btn-success btn-sm" title="Registrar entrega de EPI"><i class="fas fa-hard-hat"></i></a> ';
    }

    if ($tipo === 'treinamento') {
        $treinId = (int) ($r['adms_sst_treinamento_id'] ?? 0);
        if ($treinId > 0 && in_array('SstListTreinamentoVinculos', $perms, true)) {
            $href = $urlAdm . 'sst-list-treinamento-vinculos?adms_user_id=' . $uid
                . '&adms_sst_treinamento_id=' . $treinId;
            echo '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
                . '" class="btn btn-warning btn-sm" title="Vínculos de treinamento"><i class="fas fa-graduation-cap"></i></a> ';
        }
    }

    if (in_array($tipo, ['evento_aso', 'exame_complementar', 'exame'], true)) {
        sstRenderAsoPendenciaActions($r, $perms, $redirect, $defaultUserId, $csrfAso);
    }
}
