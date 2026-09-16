<?php
/** @var array $this->data */
$active = (string) ($this->data['sales_nav_active'] ?? 'dashboard');
$qs = (string) ($this->data['query_string'] ?? '');
$q = $qs !== '' ? '?' . $qs : '';
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$links = [];
if (!empty($this->data['can_dashboard']) || $active === 'dashboard') {
    $links[] = [
        'id' => 'dashboard',
        'label' => 'Cockpit',
        'href' => (string) ($this->data['dashboard_url'] ?? ($base . 'crm-sales-dashboard' . $q)),
        'base' => $base . 'crm-sales-dashboard',
    ];
}
if (!empty($this->data['can_carteira'])) {
    $links[] = [
        'id' => 'carteira',
        'label' => 'Carteira',
        'href' => $base . 'crm-sales-carteira' . $q,
        'base' => $base . 'crm-sales-carteira',
    ];
}
if (!empty($this->data['can_vendedores'])) {
    $links[] = [
        'id' => 'vendedores',
        'label' => 'Força de vendas',
        'href' => $base . 'crm-sales-vendedores' . $q,
        'base' => $base . 'crm-sales-vendedores',
    ];
}
if (!empty($this->data['can_produtos'])) {
    $links[] = [
        'id' => 'produtos',
        'label' => 'Produto',
        'href' => $base . 'crm-sales-produtos' . $q,
        'base' => $base . 'crm-sales-produtos',
    ];
}
?>
<?php if ($links !== []): ?>
<nav class="csd-sales-nav" aria-label="Análises de vendas SAP">
  <?php foreach ($links as $link): ?>
    <?php
    $isActive = $active === $link['id'];
    $href = htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8');
    $baseHref = htmlspecialchars($link['base'], ENT_QUOTES, 'UTF-8');
    $label = htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8');
    ?>
    <a href="<?= $href ?>"
       class="<?= $isActive ? 'is-active' : '' ?>"
       data-sales-nav="<?= $baseHref ?>"
       <?= $isActive ? 'aria-current="page"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
