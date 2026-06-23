<?php
/** @var string $activeTab 'connected' | 'last-access' */
$activeTab = $activeTab ?? 'connected';
$base = $_ENV['URL_ADM'] ?? '';
?>
<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'connected' ? 'active' : ''; ?>"
           href="<?= htmlspecialchars($base . 'list-connected-users', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-user-check me-1"></i> Usuários conectados
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'last-access' ? 'active' : ''; ?>"
           href="<?= htmlspecialchars($base . 'list-users-last-access', ENT_QUOTES, 'UTF-8'); ?>">
            <i class="fas fa-clock-rotate-left me-1"></i> Último acesso
        </a>
    </li>
</ul>
