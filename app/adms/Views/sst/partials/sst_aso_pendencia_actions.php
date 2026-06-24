<?php

use App\adms\Helpers\CSRFHelper;

/**
 * Botões de fluxo ASO em andamento (abrir solicitação / registrar resultados).
 *
 * @param array<string, mixed> $r Linha de pendência
 * @param list<string> $perms Permissões do usuário
 * @param string $redirect Slug de retorno após abrir ASO
 * @param int|null $defaultUserId ID do colaborador quando não vem na linha
 * @param string|null $csrfToken Token CSRF (gerado uma vez na página)
 */
function sstRenderAsoPendenciaActions(
    array $r,
    array $perms,
    string $redirect = 'sst-report-pendencias',
    ?int $defaultUserId = null,
    ?string $csrfToken = null
): void {
    $uid = (int) ($r['adms_user_id'] ?? $defaultUserId ?? 0);
    $tipoPend = (string) ($r['tipo_pendencia'] ?? '');

    if ($uid <= 0) {
        return;
    }
    if (!in_array($tipoPend, ['evento_aso', 'exame_complementar', 'exame'], true)) {
        return;
    }

    $categoria = trim((string) ($r['categoria_aso'] ?? ''));
    if ($categoria === '' && $tipoPend === 'evento_aso') {
        $categoria = 'Periódico';
    }
    if ($categoria === '') {
        return;
    }

    $asoAguardandoId = (int) ($r['aso_aguardando_id'] ?? 0);

    if ($asoAguardandoId > 0 && in_array('SstRegistrarResultadosAso', $perms, true)) {
        echo '<a href="' . $_ENV['URL_ADM'] . 'sst-registrar-resultados-aso/' . $asoAguardandoId
            . '" class="btn btn-warning btn-sm" title="Registrar resultados"><i class="fas fa-clipboard-check"></i></a>';
        return;
    }

    if (($r['situacao'] ?? '') === 'aso_aguardando_resultados') {
        return;
    }

    if (!in_array('SstAbrirAsoPendencia', $perms, true)) {
        return;
    }

    $token = $csrfToken ?? CSRFHelper::generateCSRFToken('sst_abrir_aso_pendencia');
    echo '<form action="' . $_ENV['URL_ADM'] . 'sst-abrir-aso-pendencia" method="POST" class="d-inline" '
        . 'onsubmit="return confirm(\'Abrir solicitação de ASO ' . htmlspecialchars($categoria, ENT_QUOTES) . '?\');">';
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    echo '<input type="hidden" name="adms_user_id" value="' . $uid . '">';
    echo '<input type="hidden" name="categoria" value="' . htmlspecialchars($categoria) . '">';
    echo '<input type="hidden" name="redirect" value="' . htmlspecialchars($redirect) . '">';
    echo '<button type="submit" class="btn btn-success btn-sm" title="Abrir ASO"><i class="fas fa-file-medical"></i></button>';
    echo '</form>';
}
