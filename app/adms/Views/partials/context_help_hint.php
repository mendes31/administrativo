<?php
/**
 * Indicação discreta da ajuda contextual (F1 / link).
 */
use App\adms\Helpers\ContextHelpHelper;

if (empty($_SESSION['user_id'])) {
    return;
}

$pageMenu = (string) ($this->data['menu'] ?? '');
$helpUrl = ContextHelpHelper::buildHelpUrl($pageMenu);
?>
<span class="adms-context-help-hint text-muted" title="Abre o manual em nova aba, focado nesta tela">
    <i class="fas fa-question-circle me-1" aria-hidden="true"></i>
    <a href="<?= htmlspecialchars($helpUrl, ENT_QUOTES, 'UTF-8') ?>"
       class="text-decoration-none adms-context-help-link"
       target="_blank"
       rel="noopener noreferrer">Ajuda desta tela</a>
    <span class="d-none d-md-inline"> &middot; <kbd class="bg-white border px-1">F1</kbd></span>
</span>
