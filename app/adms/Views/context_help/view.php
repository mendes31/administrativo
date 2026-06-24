<?php
$meta = $this->data['topic_meta'] ?? null;
$topicId = (string) ($this->data['topic_id'] ?? '');
?>
<?php if (is_array($meta) && !empty($meta['module_title'])): ?>
<nav class="help-breadcrumb" aria-label="breadcrumb">
    <?= htmlspecialchars((string) $meta['module_title']) ?>
    <?php if (!empty($meta['title'])): ?>
        &rsaquo; <?= htmlspecialchars((string) $meta['title']) ?>
    <?php endif; ?>
</nav>
<?php endif; ?>

<?= $this->data['topic_html'] ?? '' ?>

<?php if ($topicId !== 'index'): ?>
<hr class="my-4">
<p class="text-muted small mb-0">
    <i class="fas fa-external-link-alt me-1" aria-hidden="true"></i>
    A ajuda abre em <strong>nova aba</strong> para você consultar enquanto usa o sistema na aba anterior.
    Pressione <kbd>F1</kbd> na tela desejada para atualizar o contexto.
</p>
<?php endif; ?>
