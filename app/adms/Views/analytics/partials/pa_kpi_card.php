<?php
/**
 * Card KPI padrão do People Analytics.
 *
 * Variáveis esperadas em $paKpi:
 * - icon (string, classes FA sem "fas", ex.: fa-users)
 * - color (string, ex.: primary) — cor do ícone
 * - value (string) — HTML já escapado ou numérico seguro
 * - label (string) — texto do rótulo (escapado aqui)
 * - subtitle (string|null) — HTML já escapado ou texto simples; se plain, passar escaped
 * - href (string|null) — se preenchido, card inteiro é link
 * - value_class (string|null) — classes extras no valor
 * - tooltip (string|null) — título do ícone info ao lado do label
 * - drill_hint (bool) — mostra “Ver listagem”
 */
$paKpi = $paKpi ?? [];
$icon = (string) ($paKpi['icon'] ?? 'fa-chart-bar');
$color = preg_replace('/[^a-z0-9\-]/', '', (string) ($paKpi['color'] ?? 'secondary')) ?: 'secondary';
$value = (string) ($paKpi['value'] ?? '—');
$label = (string) ($paKpi['label'] ?? '');
$subtitle = $paKpi['subtitle'] ?? null;
$href = $paKpi['href'] ?? null;
$valueClass = trim((string) ($paKpi['value_class'] ?? ''));
$tooltip = $paKpi['tooltip'] ?? null;
$drillHint = !empty($paKpi['drill_hint']);
$isLink = is_string($href) && $href !== '';
$tag = $isLink ? 'a' : 'div';
$extraAttrs = $isLink
    ? ' href="' . htmlspecialchars($href) . '" title="Abrir listagem correspondente"'
    : '';
?>
<<?= $tag ?> class="card h-100 pa-kpi-card pa-kpi-accent-<?= htmlspecialchars($color) ?><?= $isLink ? ' pa-kpi-card--link' : '' ?>"<?= $extraAttrs ?>>
    <div class="card-body text-center d-flex flex-column justify-content-center">
        <i class="fas <?= htmlspecialchars($icon) ?> fa-lg text-<?= htmlspecialchars($color) ?> mb-2" aria-hidden="true"></i>
        <h4 class="mb-0<?= $valueClass !== '' ? ' ' . htmlspecialchars($valueClass) : '' ?>"><?= $value ?></h4>
        <p class="text-muted mb-0 small mt-1">
            <?= htmlspecialchars($label) ?>
            <?php if (is_string($tooltip) && $tooltip !== ''): ?>
                <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top"
                      title="<?= htmlspecialchars($tooltip) ?>">
                    <i class="fas fa-info-circle text-muted" aria-hidden="true"></i>
                </span>
            <?php endif; ?>
        </p>
        <?php if (is_string($subtitle) && $subtitle !== ''): ?>
            <small class="text-muted mt-1"><?= $subtitle ?></small>
        <?php endif; ?>
        <?php if ($isLink && $drillHint): ?>
            <span class="pa-kpi-drill-hint text-primary mt-2">Ver listagem</span>
        <?php endif; ?>
    </div>
</<?= $tag ?>>
