<?php
/**
 * Navegação lista ↔ item (anterior/próximo + voltar à listagem).
 *
 * @var array<string, mixed> $itemListNav
 * @var string $navMode 'view' | 'edit'
 * @var int $itemId
 * @var array<int, string> $buttonPermission
 */
$itemListNav = $itemListNav ?? [];
$navMode = $navMode ?? 'view';
$itemId = (int)($itemId ?? 0);
$buttonPermission = $buttonPermission ?? [];
$confirmLeave = ($navMode === 'edit');
$listReturnUrl = (string)($itemListNav['list_return_url'] ?? ($_ENV['URL_ADM'] ?? '') . 'list-inventory-items');
$prevUrl = $navMode === 'edit'
    ? ($itemListNav['nav_prev_edit_url'] ?? null)
    : ($itemListNav['nav_prev_view_url'] ?? null);
$nextUrl = $navMode === 'edit'
    ? ($itemListNav['nav_next_edit_url'] ?? null)
    : ($itemListNav['nav_next_view_url'] ?? null);
$prevMeta = $itemListNav['nav_prev'] ?? null;
$nextMeta = $itemListNav['nav_next'] ?? null;
$leaveClass = $confirmLeave ? ' js-inv-item-leave' : '';
$fmtNavTitle = static function (?array $meta, string $prefix): string {
    if (!is_array($meta)) {
        return '';
    }
    $code = trim((string)($meta['code'] ?? ''));
    $desc = trim((string)($meta['description'] ?? ''));
    $label = $code !== '' ? $code : ('#' . (int)($meta['id'] ?? 0));
    if ($desc !== '') {
        $label .= ' — ' . $desc;
    }

    return $prefix . $label;
};
?>
<div class="d-inline-flex flex-wrap gap-1 align-items-center inv-item-list-nav">
    <?php if ($prevUrl): ?>
        <a href="<?= htmlspecialchars($prevUrl) ?>"
           class="btn btn-outline-secondary btn-sm<?= $leaveClass ?>"
           title="<?= htmlspecialchars($fmtNavTitle(is_array($prevMeta) ? $prevMeta : null, 'Anterior: ')) ?>">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    <?php else: ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Sem item anterior na listagem">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    <?php endif; ?>

    <?php if ($nextUrl): ?>
        <a href="<?= htmlspecialchars($nextUrl) ?>"
           class="btn btn-outline-secondary btn-sm<?= $leaveClass ?>"
           title="<?= htmlspecialchars($fmtNavTitle(is_array($nextMeta) ? $nextMeta : null, 'Próximo: ')) ?>">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Sem próximo item na listagem">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    <?php endif; ?>

    <?php if (in_array('ListInventoryItems', $buttonPermission, true)): ?>
        <a href="<?= htmlspecialchars($listReturnUrl) ?>"
           class="btn btn-info btn-sm<?= $leaveClass ?>"
           title="Voltar à listagem na mesma página e filtros">
            <i class="fa-solid fa-list"></i> Listar
        </a>
    <?php endif; ?>

    <?php if ($navMode === 'view' && in_array('UpdateInventoryItem', $buttonPermission, true) && $itemId > 0): ?>
        <a href="<?= htmlspecialchars(\App\adms\Helpers\InvInventoryItemListNavHelper::appendQueryToUrl(
            ($_ENV['URL_ADM'] ?? '') . 'update-inventory-item/' . $itemId,
            (string)($itemListNav['list_nav_query'] ?? '')
        )) ?>"
           class="btn btn-warning btn-sm">
            <i class="fa-solid fa-pen-to-square"></i> Editar
        </a>
    <?php endif; ?>

    <?php if ($navMode === 'edit' && in_array('ViewInventoryItem', $buttonPermission, true) && $itemId > 0): ?>
        <a href="<?= htmlspecialchars(\App\adms\Helpers\InvInventoryItemListNavHelper::appendQueryToUrl(
            ($_ENV['URL_ADM'] ?? '') . 'view-inventory-item/' . $itemId,
            (string)($itemListNav['list_nav_query'] ?? '')
        )) ?>"
           class="btn btn-primary btn-sm<?= $leaveClass ?>">
            <i class="fa-regular fa-eye"></i> Ver
        </a>
    <?php endif; ?>
</div>
