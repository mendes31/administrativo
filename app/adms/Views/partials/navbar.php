<?php

// Buscar informações do usuário logado, comunicados não lidos e notificações internas
$userInfo = null;
$navbarNotifCount = 0;
$navbarNotifList = [];
$navbarInternalCount = 0;
$navbarInternalList = [];
if (!empty($_SESSION['user_id'])) {
    try {
        $userId = (int)$_SESSION['user_id'];
        $userRepo = new \App\adms\Models\Repository\UsersRepository();
        $userInfo = $userRepo->getUser($userId);
        $infoRepo = new \App\adms\Models\Repository\InformativosRepository();
        $notifRepo = new \App\adms\Models\Repository\NotificationsRepository();
        $navbarNotifCount = $infoRepo->countNaoLidos($userId);
        $navbarNotifList = $navbarNotifCount > 0 ? $infoRepo->getListNaoLidos($userId, 10) : [];
        $navbarInternalCount = $notifRepo->countUnread($userId);
        $navbarInternalList = $navbarInternalCount > 0 ? $notifRepo->listUnreadForUser($userId, 10) : [];
        $navbarTotalCount = $navbarNotifCount + $navbarInternalCount;
    } catch (\Exception $e) {
        $userInfo = null;
        $navbarTotalCount = 0;
        $navbarNotifList = [];
        $navbarInternalList = [];
    }
} else {
    $navbarTotalCount = 0;
}
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-nav">
    <a class="navbar-brand ps-3" href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Tiaraju</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" type="button" aria-label="Alternar menu lateral" title="Alternar menu lateral">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        
    </form>
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4 align-items-center">
        <li class="nav-item dropdown">
            <a class="nav-link position-relative" href="#" id="navbarNotifications" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificações" title="Notificações">
                <i class="fas fa-bell"></i>
                <?php if (!empty($navbarTotalCount) && $navbarTotalCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="navbarNotifBadge" style="font-size: 0.65rem;"><?php echo $navbarTotalCount > 99 ? '99+' : (int)$navbarTotalCount; ?></span>
                <?php else: ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light" id="navbarNotifBadge" style="display: none; font-size: 0.65rem;">0</span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow navbar-notifications-dropdown" aria-labelledby="navbarNotifications" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Notificações</span>
                    <?php if (!empty($navbarTotalCount) && $navbarTotalCount > 0): ?><span class="badge bg-primary"><?php echo (int)$navbarTotalCount; ?> não lido<?php echo $navbarTotalCount !== 1 ? 's' : ''; ?></span><?php endif; ?>
                </li>
                <li><hr class="dropdown-divider my-0"></li>
                <?php if (!empty($navbarNotifList)): ?>
                    <li class="dropdown-header small text-muted">Comunicados</li>
                    <?php foreach ($navbarNotifList as $notif): ?>
                    <li>
                        <a class="dropdown-item py-2 d-block" href="<?php echo $_ENV['URL_ADM']; ?>view-informativo/<?php echo (int)$notif['id']; ?>">
                            <span class="d-block fw-semibold small"><?php if (!empty($notif['urgente'])): ?><i class="fas fa-exclamation-circle text-danger me-1"></i><?php endif; ?><?php echo htmlspecialchars($notif['titulo'] ?? ''); ?></span>
                            <span class="d-block text-muted" style="font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($notif['created_at'] ?? 'now')); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <?php if (!empty($navbarInternalList)): ?>
                    <li class="dropdown-header small text-muted">Atribuições e avisos</li>
                    <?php foreach ($navbarInternalList as $intNotif): ?>
                    <li>
                        <?php
                        $intLink = !empty($intNotif['link_url']) ? $intNotif['link_url'] : ($_ENV['URL_ADM'] . 'list-notifications');
                        if (!empty($intNotif['link_url']) && strpos($intNotif['link_url'], 'list-notifications') === false) {
                            $intLink .= (strpos($intLink, '?') !== false ? '&' : '?') . 'mark_notification=' . (int)($intNotif['id'] ?? 0);
                        }
                        ?>
                        <a class="dropdown-item py-2 d-block" href="<?php echo htmlspecialchars($intLink); ?>">
                            <span class="d-block fw-semibold small"><?php if (($intNotif['type'] ?? '') === 'projeto_etapa'): ?><i class="fas fa-tasks text-primary me-1"></i><?php endif; ?><?php echo htmlspecialchars($intNotif['title'] ?? ''); ?></span>
                            <span class="d-block text-muted" style="font-size: 0.8rem;"><?php echo date('d/m/Y H:i', strtotime($intNotif['created_at'] ?? 'now')); ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <?php if (empty($navbarNotifList) && empty($navbarInternalList)): ?>
                <li><div class="dropdown-item text-muted small py-3 text-center">Nenhuma notificação nova.</div></li>
                <li><hr class="dropdown-divider my-0"></li>
                <?php endif; ?>
                <li><a class="dropdown-item small text-center" href="<?php echo $_ENV['URL_ADM']; ?>list-informativos"><i class="fas fa-bullhorn me-1"></i>Comunicados</a></li>
                <li><a class="dropdown-item small text-center" href="<?php echo $_ENV['URL_ADM']; ?>list-notifications"><i class="fas fa-bell me-1"></i>Ver todas as notificações</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu do usuário">
                <?php
                $navbarAvatarPath = null;
                if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png') {
                    $navbarAvatarPath = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                }
                echo \App\adms\Helpers\ImageHelper::displayImage($navbarAvatarPath, [
                    'alt' => 'Foto do usuário',
                    'class' => 'rounded-circle me-2',
                    'style' => 'width: 32px; height: 32px; object-fit: cover;',
                ], 'icon_user.png', 'users');
                ?>
                
                <div class="d-none d-md-block text-start me-2">
                    <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?>
                    </div>
                    <div class="text-white-50" style="font-size: 0.75rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['pos_name'] ?? 'Cargo'); ?>
                    </div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li class="dropdown-header">
                    <div class="d-flex align-items-center">
                        <?php
                        $navbarAvatarPathLg = null;
                        if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png') {
                            $navbarAvatarPathLg = 'users/' . $userInfo['id'] . '/' . $userInfo['image'];
                        }
                        echo \App\adms\Helpers\ImageHelper::displayImage($navbarAvatarPathLg, [
                            'alt' => 'Foto do usuário',
                            'class' => 'rounded-circle me-2',
                            'style' => 'width: 40px; height: 40px; object-fit: cover;',
                        ], 'icon_user.png', 'users');
                        ?>
                        
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($userInfo['pos_name'] ?? 'Cargo'); ?></div>
                        </div>
                    </div>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>profile">
                        <i class="fa-solid fa-user-pen me-2"></i> Meu Perfil
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>update-password">
                        <i class="fa-solid fa-key me-2"></i> Alterar Senha
                    </a>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item" href="<?php echo $_ENV['URL_ADM']; ?>logout">
                        <i class="fas fa-arrow-right-from-bracket me-2"></i> Sair
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<style>
/* Mobile: balão de notificações centralizado, usando a área central (não corta nas laterais) */
@media (max-width: 767.98px) {
    .navbar-notifications-dropdown {
        position: fixed !important;
        left: 50% !important;
        right: auto !important;
        transform: translateX(-50%) !important;
        top: 56px !important;
        width: calc(100vw - 2rem) !important;
        min-width: 280px !important;
        max-width: 400px !important;
    }
    .navbar-notifications-dropdown .dropdown-header .fw-bold {
        font-size: 0.9rem;
    }
    .navbar-notifications-dropdown .dropdown-header .badge {
        font-size: 0.75rem;
    }
    .navbar-notifications-dropdown .dropdown-item {
        font-size: 0.9rem;
        white-space: normal;
    }
}
@media (max-width: 576px) {
    .navbar-notifications-dropdown {
        width: calc(100vw - 1.5rem) !important;
        min-width: 260px !important;
    }
}
</style>