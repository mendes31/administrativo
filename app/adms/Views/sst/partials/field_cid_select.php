<?php
/**
 * Campo CID com busca Select2 (autocomplete).
 *
 * @var string $name       name do input
 * @var string $id         id do select
 * @var string $label      rótulo
 * @var int    $selectedId id selecionado
 * @var string $selectedText texto exibido (codigo — descricao)
 * @var bool   $required
 * @var bool   $frequentesOnly prioriza CIDs marcados como frequentes na 1ª busca
 */
$name = $name ?? 'adms_sst_cid_id';
$id = $id ?? $name;
$label = $label ?? 'CID';
$selectedId = (int) ($selectedId ?? 0);
$selectedText = $selectedText ?? '';
$required = !empty($required);
$frequentesOnly = !empty($frequentesOnly);
$searchUrl = $_ENV['URL_ADM'] . 'sst-search-cids';
?>
<div class="col-md-6 mb-3">
    <label class="form-label" for="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($label) ?><?= $required ? ' *' : '' ?></label>
    <select name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($id) ?>" class="form-select sst-cid-select" <?= $required ? 'required' : '' ?>
        data-search-url="<?= htmlspecialchars($searchUrl) ?>"
        data-frequentes="<?= $frequentesOnly ? '1' : '0' ?>">
        <?php if ($selectedId > 0): ?>
            <option value="<?= $selectedId ?>" selected><?= htmlspecialchars($selectedText) ?></option>
        <?php else: ?>
            <option value="">Selecione ou digite para buscar...</option>
        <?php endif; ?>
    </select>
    <div class="form-text">Digite código ou descrição. CIDs frequentes aparecem primeiro.</div>
</div>
