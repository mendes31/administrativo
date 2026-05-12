<?php
/**
 * Botão "Log de Alterações" (só aparece se existir histórico em adms_log_alteracoes).
 *
 * Variáveis esperadas no escopo antes do include:
 * - $log_resumo: array{has_logs?: bool, count?: int, list_url?: string} (ex.: $this->data['log_resumo'] de LogResumoService::getResumo)
 * - $log_btn_class (opcional): classes CSS do botão (default: btn btn-outline-info)
 *
 * O botão é exibido quando existe list_url; o contador entre parênteses só aparece se count for maior que zero.
 */
$log_resumo = $log_resumo ?? [];
$log_btn_class = $log_btn_class ?? 'btn btn-outline-info';
if (empty($log_resumo['list_url'])) {
    return;
}
$count = (int) ($log_resumo['count'] ?? 0);
$labelSuffix = $count > 0 ? ' (' . $count . ')' : '';
?>
<a href="<?= htmlspecialchars($log_resumo['list_url'], ENT_QUOTES, 'UTF-8') ?>"
   class="<?= htmlspecialchars($log_btn_class, ENT_QUOTES, 'UTF-8') ?>">
    <i class="fas fa-history me-1"></i>
    Log de Alterações<?= htmlspecialchars($labelSuffix, ENT_QUOTES, 'UTF-8') ?>
</a>
