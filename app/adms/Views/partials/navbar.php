<?php
// Buscar informações do usuário logado
$userInfo = null;
if (!empty($_SESSION['user_id'])) {
    try {
        $userRepo = new \App\adms\Models\Repository\UsersRepository();
        $userInfo = $userRepo->getUser($_SESSION['user_id']);
    } catch (Exception $e) {
        // Em caso de erro, continuar sem as informações do usuário
        $userInfo = null;
    }
}
?>

<nav class="sb-topnav navbar navbar-expand navbar-dark bg-nav">
    <a class="navbar-brand ps-3" href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Tiaraju</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        
    </form>
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png'): ?>
                    <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/<?php echo $userInfo['id']; ?>/<?php echo $userInfo['image']; ?>" 
                         alt="Foto do usuário" 
                         class="rounded-circle me-2" 
                         style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                    <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/icon_user.png" 
                         alt="Foto padrão" 
                         class="rounded-circle me-2" 
                         style="width: 32px; height: 32px; object-fit: cover;">
                <?php endif; ?>
                
                <div class="d-none d-md-block text-start me-2">
                    <div class="text-white fw-bold" style="font-size: 0.9rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['name'] ?? 'Usuário'); ?>
                    </div>
                    <div class="text-white-50" style="font-size: 0.75rem; line-height: 1.1;">
                        <?php echo htmlspecialchars($userInfo['pos_name'] ?? 'Cargo'); ?>
                    </div>
                </div>
                
                <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.8rem;"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li class="dropdown-header">
                    <div class="d-flex align-items-center">
                        <?php if (!empty($userInfo['image']) && $userInfo['image'] !== 'icon_user.png'): ?>
                            <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/<?php echo $userInfo['id']; ?>/<?php echo $userInfo['image']; ?>" 
                                 alt="Foto do usuário" 
                                 class="rounded-circle me-2" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        <?php else: ?>
                            <img src="<?php echo $_ENV['URL_ADM']; ?>public/adms/uploads/users/icon_user.png" 
                                 alt="Foto padrão" 
                                 class="rounded-circle me-2" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                        <?php endif; ?>
                        
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