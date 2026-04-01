<div class="container-fluid px-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">
            <div class="bg-success bg-gradient rounded-4 p-4 mb-4" style="margin-top: 2rem;">
                <h2 class="fw-bold text-white mb-1" style="font-size: 1.7rem; letter-spacing: -1px;">Bem-vindo(a), <?php echo htmlspecialchars($this->data['user_name'] ?? 'Usuário'); ?>!</h2>
                <div class="text-white" style="font-size: 1.05rem;">Portal Interno do Grupo Tiaraju - Sua central de informações corporativas</div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="row g-3 justify-content-center align-items-stretch dashboard-quick-row">
                <?php if (!empty($this->data['show_informativos_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-informativos" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-newspaper fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Informativos</h5>
                            <div class="text-muted mb-2 text-center" style="font-size: 1.1rem;"><?php echo $this->data['informativos_ativos'] ?? 0; ?> ativos</div>
                            <div class="text-primary text-center small fw-semibold"><?php echo (int)($this->data['informativos_nao_lidos'] ?? 0); ?> não lidos</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_policies_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-policies" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: #fff7f7;">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-file-contract fa-3x text-danger"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-danger text-center group-title">Políticas Internas</h5>
                            <div class="text-danger mb-2 text-center" style="font-size: 1.1rem;">
                                <?php echo $this->data['policies_ativas'] ?? 0; ?> ativas
                            </div>
                            <div class="text-danger text-center small fw-semibold">
                                <?php echo (int)($this->data['policies_nao_lidas'] ?? 0); ?> não lidas
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php $hasAniversarianteHoje = !empty($this->data['qtd_aniversariantes_dia']); ?>
                <?php if (!empty($this->data['show_aniversariantes_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="#"
                       class="text-decoration-none flex-fill h-100 <?php echo $hasAniversarianteHoje ? '' : 'birthday-card-disabled'; ?>"
                       <?php if ($hasAniversarianteHoje): ?>
                           data-bs-toggle="modal"
                           data-bs-target="#modalAniversariantesDia"
                           onclick="event.preventDefault();"
                       <?php else: ?>
                           aria-disabled="true"
                           onclick="event.preventDefault(); return false;"
                       <?php endif; ?>>
                        <div class="card card-main dashboard-card dashboard-birthday-card d-flex flex-column align-items-center justify-content-center p-4 h-100 position-relative">
                            <button type="button"
                                    class="btn btn-sm dashboard-card-more-btn"
                                    aria-label="Abrir calendário de aniversários"
                                    title="Ver calendário do mês"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAniversariantesMes"
                                    onclick="event.preventDefault(); event.stopPropagation();">
                                <i class="fas fa-calendar-alt"></i>
                            </button>
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-birthday-cake fa-3x text-warning"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Aniversariantes de hoje</h5>
                            <div class="text-muted mb-2 text-center" style="font-size: 1.1rem;">
                                <?php echo $this->data['qtd_aniversariantes_dia'] ?? 0; ?> hoje
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_tempo_empresa_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="#"
                       class="text-decoration-none flex-fill h-100"
                       data-bs-toggle="modal"
                       data-bs-target="#modalAniversariantesEmpresa"
                       onclick="event.preventDefault();">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-briefcase fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Tempo de Empresa</h5>
                            <div class="text-muted mb-2 text-center" style="font-size: 1.1rem;">
                                <?php echo $this->data['qtd_aniversariantes_empresa_mes'] ?? 0; ?> este mês
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="row g-3 justify-content-center align-items-stretch mt-1">
                <?php if (!empty($this->data['show_timeline_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>timeline" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: linear-gradient(135deg, #f0f9ff 0%, #fff 100%);">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-stream fa-3x text-info"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Timeline</h5>
                            <div class="text-muted mb-1 text-center small">Comunicação entre colaboradores</div>
                            <div class="text-info text-center small fw-semibold"><?php echo (int)($this->data['timeline_notificacoes_nao_lidas'] ?? 0); ?> não lidas</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_eventos_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="#"
                       class="text-decoration-none flex-fill h-100"
                       data-bs-toggle="modal"
                       data-bs-target="#modalEventosMes"
                       onclick="event.preventDefault();">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-calendar-alt fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Eventos</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                <?php echo (int)($this->data['company_events_month_count'] ?? 0); ?> ativos neste mês
                            </div>
                            <div class="text-muted text-center small fw-semibold">
                                <?php echo (int)($this->data['company_events_year_count'] ?? 0); ?> no ano
                            </div>
                            <div class="text-success text-center small fw-semibold" id="dashboardEventsUnreadCount">
                                <?php echo (int)($this->data['company_events_unread_count'] ?? 0); ?> não lidos no ano
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Modal de aniversariantes do dia -->
    <div class="modal fade" id="modalAniversariantesDia" tabindex="-1" aria-labelledby="modalAniversariantesDiaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg modal-dialog-centered">
            <div class="modal-content birthday-modal-content">
                <div class="modal-header birthday-modal-header">
                    <h5 class="modal-title" id="modalAniversariantesDiaLabel">Aniversariantes de Hoje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body birthday-modal-body">
                    <?php if (!empty($this->data['aniversariantes_dia'])): ?>
                        <div class="row g-3">
                            <?php foreach ($this->data['aniversariantes_dia'] as $aniv): ?>
                                <div class="col-12 col-md-6 col-lg-4 d-flex">
                                    <div class="card birthday-person-card text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center">
                                        <div class="mb-2">
                                            <?php if (!empty($aniv['image'])): ?>
                                                <?php
                                                $avatarPath = 'users/' . $aniv['id'] . '/' . $aniv['image'];
                                                echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                                                    'class' => 'rounded-circle mb-2 birthday-avatar',
                                                    'style' => 'width: 86px; height: 86px; object-fit: cover;',
                                                ], 'icon_user.png', 'users');
                                                ?>
                                            <?php else: ?>
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aniv['name']); ?>&background=ececec&color=6c757d&size=100" class="rounded-circle mb-2 birthday-avatar" style="width: 86px; height: 86px;">
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($aniv['name']); ?></h6>
                                        <div class="text-muted small mb-1"><?php echo htmlspecialchars($aniv['departamento'] ?? ''); ?></div>
                                        <div class="text-muted small mt-1 birthday-date-pill"><i class="fas fa-birthday-cake text-warning me-1"></i><span class="fw-bold" style="color:#ff9800;"> <?php echo $aniv['aniversario']; ?></span></div>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($_ENV['URL_ADM']); ?>timeline-profile/<?php echo (int)$aniv['id']; ?>?from=birthday"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-comment-dots me-1"></i>Parabenizar na Timeline
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-day fa-2x mb-2"></i>
                            <div>Nenhum aniversariante hoje.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de aniversariantes do mês -->
    <div class="modal fade" id="modalAniversariantesMes" tabindex="-1" aria-labelledby="modalAniversariantesMesLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg modal-dialog-centered">
            <div class="modal-content birthday-modal-content">
                <div class="modal-header birthday-modal-header">
                    <h5 class="modal-title" id="modalAniversariantesMesLabel">Aniversariantes do Mês</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body birthday-modal-body">
                    <?php
                    $mesesPt = [
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                    ];
                    $mesAtualCalendar = (int)date('n');
                    ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 birthday-filter-bar">
                        <span class="text-muted small">Filtrar por mês</span>
                        <div class="d-flex align-items-center gap-2">
                            <select id="aniversariantesMesFilter" class="form-select form-select-sm" style="max-width: 210px;">
                                <?php foreach ($mesesPt as $mesNum => $mesNome): ?>
                                    <option value="<?php echo $mesNum; ?>" <?php echo $mesNum === $mesAtualCalendar ? 'selected' : ''; ?>>
                                        <?php echo $mesNome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span id="aniversariantesMesCount" class="badge rounded-pill text-bg-light border birthday-month-count">0</span>
                        </div>
                    </div>

                    <div class="row g-3" id="aniversariantesMesGrid">
                        <?php foreach ($this->data['aniversariantes_todos'] ?? [] as $aniv): ?>
                            <div class="col-12 col-md-6 col-lg-4 d-flex aniversariante-mes-item" data-mes="<?php echo (int)($aniv['aniversario_mes'] ?? 0); ?>">
                                <div class="card birthday-person-card text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center">
                                    <div class="mb-2">
                                        <?php if (!empty($aniv['image'])): ?>
                                            <?php
                                            $avatarPath = 'users/' . $aniv['id'] . '/' . $aniv['image'];
                                            echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                                                'class' => 'rounded-circle mb-2 birthday-avatar',
                                                'style' => 'width: 86px; height: 86px; object-fit: cover;',
                                            ], 'icon_user.png', 'users');
                                            ?>
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aniv['name']); ?>&background=ececec&color=6c757d&size=100" class="rounded-circle mb-2 birthday-avatar" style="width: 86px; height: 86px;">
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($aniv['name']); ?></h6>
                                    <div class="text-muted small mb-1"><?php echo htmlspecialchars($aniv['departamento'] ?? ''); ?></div>
                                    <div class="text-muted small mt-1 birthday-date-pill"><i class="fas fa-birthday-cake text-warning me-1"></i><span class="fw-bold" style="color:#ff9800;"> <?php echo $aniv['aniversario']; ?></span></div>
                                    <div class="mt-2">
                                        <a href="<?php echo htmlspecialchars($_ENV['URL_ADM']); ?>timeline-profile/<?php echo (int)$aniv['id']; ?>?from=birthday"
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-comment-dots me-1"></i>Parabenizar na Timeline
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="aniversariantesMesEmpty" class="text-center text-muted py-3 d-none">
                        Nenhum aniversariante encontrado para o mês selecionado.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/modal_eventos_mes.php'; ?>

    <!-- Modal de aniversariantes de empresa -->
    <div class="modal fade" id="modalAniversariantesEmpresa" tabindex="-1" aria-labelledby="modalAniversariantesEmpresaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg modal-dialog-centered">
            <div class="modal-content birthday-modal-content">
                <div class="modal-header birthday-modal-header">
                    <h5 class="modal-title" id="modalAniversariantesEmpresaLabel">Tempo de Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body birthday-modal-body">
                    <?php
                    $mesesPt = [
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
                    ];
                    $mesAtualCalendar = (int)date('n');
                    ?>
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 birthday-filter-bar">
                        <span class="text-muted small">Filtrar por mês</span>
                        <div class="d-flex align-items-center gap-2">
                            <select id="aniversariantesEmpresaMesFilter" class="form-select form-select-sm" style="max-width: 210px;">
                                <?php foreach ($mesesPt as $mesNum => $mesNome): ?>
                                    <option value="<?php echo $mesNum; ?>" <?php echo $mesNum === $mesAtualCalendar ? 'selected' : ''; ?>>
                                        <?php echo $mesNome; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span id="aniversariantesEmpresaMesCount" class="badge rounded-pill text-bg-light border birthday-month-count">0</span>
                        </div>
                    </div>
                    <div class="row g-3" id="aniversariantesEmpresaMesGrid">
                        <?php foreach ($this->data['aniversariantes_empresa_todos'] ?? [] as $aniv): ?>
                            <div class="col-12 col-md-6 col-lg-4 d-flex aniversariante-empresa-mes-item" data-mes="<?php echo (int)($aniv['aniversario_empresa_mes'] ?? 0); ?>">
                                <div class="card birthday-person-card text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center">
                                    <div class="mb-2">
                                        <?php if (!empty($aniv['image'])): ?>
                                            <?php
                                            $avatarPath = 'users/' . $aniv['id'] . '/' . $aniv['image'];
                                            echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                                                'class' => 'rounded-circle mb-2 birthday-avatar',
                                                'style' => 'width: 86px; height: 86px; object-fit: cover;',
                                            ], 'icon_user.png', 'users');
                                            ?>
                                        <?php else: ?>
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($aniv['name']); ?>&background=ececec&color=6c757d&size=100" class="rounded-circle mb-2 birthday-avatar" style="width: 86px; height: 86px;">
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($aniv['name']); ?></h6>
                                    <div class="text-muted small mb-1"><?php echo htmlspecialchars($aniv['departamento'] ?? ''); ?></div>
                                    <?php
                                        $anosEmpresa = isset($aniv['anos_empresa']) ? (int)$aniv['anos_empresa'] : null;
                                    ?>
                                    <div class="text-muted small mt-1">
                                        <i class="fas fa-briefcase text-primary me-1"></i>
                                        <span class="fw-bold" style="color:#1976d2;">
                                            <?php echo $aniv['aniversario_empresa'] ?? ''; ?>
                                        </span>
                                        <?php if ($anosEmpresa === 0): ?>
                                            <br>
                                            <span class="small fw-semibold"
                                                  style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:999px;background:#e8f5e9;color:#2e7d32;">
                                                Novo Colaborador
                                            </span>
                                        <?php elseif ($anosEmpresa !== null && $anosEmpresa > 0): ?>
                                            <br>
                                            <span class="small text-muted">
                                                <?php echo $anosEmpresa; ?> ano(s) de casa
                                            </span>
                                        <?php endif; ?>
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($_ENV['URL_ADM']); ?>timeline-profile/<?php echo (int)$aniv['id']; ?>?from=tenure&years=<?php echo $anosEmpresa === null ? '' : (int)$anosEmpresa; ?>"
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-comment-dots me-1"></i>Reconhecer na Timeline
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="aniversariantesEmpresaMesEmpty" class="text-center text-muted py-3 d-none">
                        Nenhum colaborador encontrado para o mês selecionado.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php // Seção temporariamente oculta para reduzir poluição visual no Dashboard. ?>
    <?php if (false): ?>
    <!-- Informativos Recentes -->
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <h5 class="fw-bold mb-3 mt-2" style="color: #219150; text-align: left;">Informativos Recentes</h5>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-10">
            <div class="row g-4 dashboard-recent-row">
                <?php
                $recentInformativos = array_slice($this->data['informativos'] ?? [], 0, 6);
                $userIdDashboard = (int)($_SESSION['user_id'] ?? 0);
                $unreadInformativoSetDashboard = [];

                if ($userIdDashboard > 0 && !empty($recentInformativos)) {
                    $recentIds = array_map(static fn($x) => (int)($x['id'] ?? 0), $recentInformativos);
                    $infoRepo = new \App\adms\Models\Repository\InformativosRepository();
                    $unreadIds = $infoRepo->getNaoLidosIdsByInformativoIds($userIdDashboard, $recentIds);
                    $unreadInformativoSetDashboard = array_fill_keys(array_map('intval', $unreadIds), true);
                }
                ?>

                <?php foreach ($recentInformativos as $infoIdx => $info): ?>
                    <?php
                    $infoId = (int)($info['id'] ?? 0);
                    $requiresAck = !empty($info['requires_ack']);
                    $isUnread = $infoId > 0 && isset($unreadInformativoSetDashboard[$infoId]);
                    $recentBadges = [];
                    // Prioridade visual: 1) Ciência/Pendência/Novo 2) Urgente 3) Categoria/Departamento
                    if ($requiresAck) {
                        if ($isUnread) {
                            $recentBadges[] = ['class' => 'bg-warning text-dark border border-danger-subtle', 'label' => '<i class="fas fa-triangle-exclamation me-1"></i>Ciência pendente', 'isHtml' => true];
                        } else {
                            $recentBadges[] = ['class' => 'bg-success', 'label' => '<i class="fas fa-check-circle me-1"></i>Ciente', 'isHtml' => true];
                        }
                    } elseif ($isUnread) {
                        $recentBadges[] = ['class' => 'bg-primary', 'label' => 'Novo'];
                    }
                    if (!empty($info['urgente'])) {
                        $recentBadges[] = ['class' => 'bg-danger', 'label' => 'Urgente'];
                    }
                    $recentBadges[] = ['class' => 'bg-info text-white', 'label' => htmlspecialchars($info['categoria_nome'] ?? $info['categoria'])];
                    if (!empty($info['department_name'])) {
                        $recentBadges[] = ['class' => 'bg-secondary', 'label' => htmlspecialchars($info['department_name'])];
                    }
                    $desktopBadgeLimit = 3;
                    $mobileBadgeLimit = 2;
                    $desktopExtraBadges = max(0, count($recentBadges) - $desktopBadgeLimit);
                    $mobileExtraBadges = max(0, count($recentBadges) - $mobileBadgeLimit);
                    ?>
                    <div class="col-12 col-md-6 col-lg-3 d-flex">
                        <div class="card border-0 shadow-sm p-4 flex-fill d-flex flex-column card-info dashboard-recent-card" style="border-radius: 14px;">
                            <div class="mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-calendar-alt text-muted" title="Publicado em"></i>
                                    <span class="text-muted small"><?php echo date('d/m/Y', strtotime($info['created_at'])); ?></span>
                                    <?php if (!empty($info['expire_at'])): ?>
                                        <i class="fas fa-hourglass-end text-muted ms-3" title="Expira em"></i>
                                        <span class="text-muted small"><?php echo date('d/m/Y', strtotime($info['expire_at'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-none d-md-flex align-items-center gap-1 flex-nowrap dashboard-recent-badges mt-1">
                                    <?php foreach (array_slice($recentBadges, 0, $desktopBadgeLimit) as $badge): ?>
                                        <span class="badge <?php echo $badge['class']; ?>" style="font-size:0.82rem;">
                                            <?php if (!empty($badge['isHtml'])): ?>
                                                <?php echo $badge['label']; ?>
                                            <?php else: ?>
                                                <?php echo $badge['label']; ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($desktopExtraBadges > 0): ?>
                                        <span class="badge bg-light text-secondary border">+<?php echo $desktopExtraBadges; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex d-md-none align-items-center gap-1 flex-nowrap dashboard-recent-badges mt-1">
                                    <?php foreach (array_slice($recentBadges, 0, $mobileBadgeLimit) as $badge): ?>
                                        <span class="badge <?php echo $badge['class']; ?>" style="font-size:0.76rem;">
                                            <?php if (!empty($badge['isHtml'])): ?>
                                                <?php echo $badge['label']; ?>
                                            <?php else: ?>
                                                <?php echo $badge['label']; ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if ($mobileExtraBadges > 0): ?>
                                        <span class="badge bg-light text-secondary border">+<?php echo $mobileExtraBadges; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <h6 class="fw-bold mb-1 text-start dashboard-recent-title" title="<?php echo htmlspecialchars($info['titulo']); ?>"><?php echo htmlspecialchars($info['titulo']); ?></h6>
                            <div class="dashboard-informativo-resumo text-muted mb-2 flex-grow-1 text-start" style="font-size: 1rem;">
                                <?php echo htmlspecialchars($info['resumo'] ?? substr(strip_tags($info['conteudo']), 0, 100) . '...'); ?>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <?php if (!empty($info['imagem'])): ?>
                                    <a href="#"
                                       class="dashboard-informativo-thumb-wrap d-inline-block"
                                       onclick="return openDashboardInformativoImage(event, <?php echo (int)$infoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, '<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>');">
                                        <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>"
                                             class="img-fluid rounded shadow dashboard-informativo-thumb"
                                             alt=""
                                             width="56"
                                             height="56"
                                             loading="eager"
                                             decoding="async"
                                             <?php if ($infoIdx < 3): ?>fetchpriority="high"<?php endif; ?>
                                             style="width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef; cursor: pointer;">
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($info['anexo'])): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['anexo']); ?>"
                                       target="_blank"
                                       title="Baixar anexo"
                                       onclick="return openDashboardInformativoAttachment(event, <?php echo (int)$infoId; ?>, <?php echo $requiresAck ? 'true' : 'false'; ?>, this.href);">
                                        <?php echo \App\adms\Helpers\FormatHelper::renderFileIcon($info['anexo'], 'fa-2x'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="dashboard-recent-footer text-end">
                                <button type="button" class="btn btn-outline-primary fw-semibold px-4 dashboard-recent-vermais-btn" style="border-radius: 8px; border-width:2px; min-width: 120px;" data-bs-toggle="modal" data-bs-target="#informativoModal<?php echo $info['id']; ?>">
                                    <i class="fas fa-eye me-1"></i>Ver Mais
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Modal para cada informativo - Design Moderno Reformulado -->
                    <div class="modal fade dashboard-informativo-modal" id="informativoModal<?php echo $info['id']; ?>" tabindex="-1" aria-labelledby="informativoModalLabel<?php echo $info['id']; ?>" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
                        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg modal-dialog-centered">
                            <div class="modal-content informativo-modal-modern">
                                
                                <!-- HEADER SIMPLIFICADO - Apenas Título -->
                                <div class="modal-header border-0 pb-0 informativo-header-novo">
                                    <h3 class="modal-title w-100 informativo-titulo-novo" id="informativoModalLabel<?php echo $info['id']; ?>">
                                        <?php echo htmlspecialchars($info['titulo']); ?>
                                    </h3>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                
                                <!-- BADGES - Logo após o título, dentro do body -->
                                <div class="informativo-badges-bar">
                                    <?php if ($info['urgente']): ?>
                                        <span class="badge badge-urgente">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Urgente
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge badge-categoria">
                                        <?php echo htmlspecialchars($info['categoria_nome'] ?? $info['categoria']); ?>
                                    </span>
                                    <?php if (!empty($info['department_name'])): ?>
                                        <span class="badge badge-departamento">
                                            <?php echo htmlspecialchars($info['department_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- BODY - Conteúdo Principal -->
                                <div class="modal-body informativo-body">
                                    
                                    <!-- Seção: Conteúdo do Informativo -->
                                    <section class="informativo-secao-conteudo">
                                        <div class="informativo-texto">
                                            <?php 
                                            // Preservar formatação original
                                            $conteudo = trim($info['conteudo']);
                                            $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
                                            echo nl2br(htmlspecialchars($conteudo)); 
                                            ?>
                                        </div>
                                    </section>
                                    
                                    <!-- Seção: Anexos (Imagem e PDF) -->
                                    <?php if (!empty($info['imagem']) || !empty($info['anexo'])): ?>
                                        <section class="informativo-secao-anexos">
                                            <h6 class="informativo-secao-titulo">
                                                <i class="fas fa-paperclip me-2"></i>Anexos
                                            </h6>
                                            <div class="informativo-anexos-grid">
                                                <?php if (!empty($info['imagem'])): ?>
                                                    <div class="informativo-anexo-item">
                                                        <a href="#" 
                                                           onclick="showImageModal('<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>'); return false;"
                                                           class="informativo-imagem-link">
                                                            <img src="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['imagem']); ?>"
                                                                 class="informativo-imagem"
                                                                 alt="Imagem do informativo"
                                                                 loading="lazy">
                                                            <div class="informativo-imagem-overlay">
                                                                <i class="fas fa-search-plus fa-2x"></i>
                                                            </div>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($info['anexo'])): ?>
                                                    <div class="informativo-anexo-item">
                                                        <a href="<?php echo $_ENV['URL_ADM']; ?>serve-file?path=<?php echo urlencode($info['anexo']); ?>" 
                                                           target="_blank" 
                                                           class="informativo-pdf-link">
                                                            <i class="fas fa-file-pdf fa-3x mb-2"></i>
                                                            <span class="d-block fw-bold">Baixar PDF</span>
                                                            <small class="text-muted">Clique para abrir</small>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </section>
                                    <?php endif; ?>
                                    
                                    <!-- Seção: Informações de Publicação -->
                                    <section class="informativo-secao-metadados">
                                        <div class="row g-3">
                                            <div class="col-md-4 col-12">
                                                <div class="informativo-meta-item">
                                                    <i class="fas fa-user informativo-meta-icon"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Publicado por</small>
                                                        <strong><?php echo htmlspecialchars($info['usuario_nome'] ?? 'N/A'); ?></strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 col-12">
                                                <div class="informativo-meta-item">
                                                    <i class="fas fa-calendar-alt informativo-meta-icon"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Data de publicação</small>
                                                        <strong><?php echo date('d/m/Y', strtotime($info['created_at'])); ?></strong>
                                                        <small class="text-muted d-block"><?php echo date('H:i', strtotime($info['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($info['expire_at'])): ?>
                                                <div class="col-md-4 col-12">
                                                    <div class="informativo-meta-item">
                                                        <i class="fas fa-clock informativo-meta-icon"></i>
                                                        <div>
                                                            <small class="text-muted d-block">Expira em</small>
                                                            <strong><?php echo date('d/m/Y', strtotime($info['expire_at'])); ?></strong>
                                                            <small class="text-muted d-block"><?php echo date('H:i', strtotime($info['expire_at'])); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </section>
                                    
                                </div>
                                
                                <!-- FOOTER - Ações -->
                                <div class="modal-footer informativo-footer">
                                    <?php if ($info['requires_ack']): ?>
                                        <button type="button" 
                                                class="btn btn-success me-auto" 
                                                id="btn-ack-<?php echo $info['id']; ?>" 
                                                onclick="confirmarCiencia(<?php echo $info['id']; ?>)">
                                            <i class="fas fa-check-circle me-2"></i>Estou ciente
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-2"></i>Fechar
                                    </button>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.card {
    border-radius: 18px !important;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border: none;
}
.card-main {
    transition: box-shadow 0.2s, border 0.2s;
    min-height: 110px;
}
.card-main:hover, .card-main:focus-within {
    box-shadow: 0 8px 25px rgba(0,123,255,0.10);
    border: 2px solid #0d6efd22;
}
.icon-main {
    background: #f8f9fa;
    border-radius: 50%;
    min-width: 60px;
    min-height: 60px;
}
.card-info {
    border: none !important;
    box-shadow: 0 2px 12px rgba(0,123,255,0.07) !important;
    transition: border 0.2s, box-shadow 0.2s;
}
.card-info:hover, .card-info:focus-within {
    border: 2px solid #0d6efd !important;
    box-shadow: 0 8px 25px rgba(0,123,255,0.10) !important;
}
.btn-outline-primary {
    color: #0d6efd;
    border-color: #0d6efd;
    background: #fff;
}
.btn-outline-primary:hover, .btn-outline-primary:focus {
    background: #eaf4ff;
    color: #0a58ca;
    border-color: #0a58ca;
}
.badge.bg-info {
    background: #0dcaf0 !important;
    color: #fff !important;
}
.group-title {
    font-size: 1.25rem;
    min-height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.card-main {
    min-height: 160px !important;
}
.card-info, .card, .card.border-0, .card.shadow-sm {
    border: 1px solid #e3e8ee !important;
    border-radius: 16px !important;
    box-shadow: none !important;
}
.dashboard-card, .dashboard-card-clickable {
    min-height: 170px !important;
    border-radius: 16px !important;
    border: 1px solid #cfd9e6 !important;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.04) !important;
    transition: box-shadow 0.2s, border-color 0.2s;
    background: #fff;
}
.dashboard-recent-card {
    min-height: 188px !important;
    height: 188px !important;
    padding: 1rem !important;
    padding-bottom: 3.1rem !important;
    position: relative;
    overflow: hidden;
    border: 1px solid #cfd9e6 !important;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.04) !important;
}
.dashboard-recent-badges {
    min-width: 0;
    overflow: hidden;
}
.dashboard-recent-badges .badge {
    white-space: nowrap;
}
.dashboard-recent-footer {
    position: absolute;
    right: 1rem;
    bottom: 0.75rem;
    margin-top: 0 !important;
}
.dashboard-recent-vermais-btn {
    min-width: 104px !important;
    height: 30px;
    padding: 0.2rem 0.75rem !important;
    border-radius: 999px !important;
    border-width: 1px !important;
    border-color: #7eb5ea !important;
    color: #2b75c7 !important;
    background: #f8fcff !important;
    font-size: 0.9rem !important;
    font-weight: 600 !important;
    line-height: 1.1;
    box-shadow: 0 1px 2px rgba(43, 117, 199, 0.12);
}
.dashboard-recent-vermais-btn:hover,
.dashboard-recent-vermais-btn:focus {
    color: #1f62ad !important;
    border-color: #5d9fe0 !important;
    background: #eef6ff !important;
    box-shadow: 0 2px 5px rgba(43, 117, 199, 0.18);
}
.dashboard-recent-card .dashboard-informativo-resumo {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.25;
    min-height: 2.45em;
}
.dashboard-recent-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.2;
    min-height: 2.4em;
    font-size: 1rem;
}
.dashboard-card:hover, .dashboard-card-clickable:hover {
    box-shadow: 0 4px 18px rgba(33, 145, 80, 0.10) !important;
    border-color: #219150 !important;
    cursor: pointer;
}
.dashboard-birthday-card {
    position: relative !important;
    overflow: visible;
}
.birthday-card-disabled {
    cursor: default;
}
.birthday-card-disabled .dashboard-birthday-card {
    cursor: default !important;
}
.dashboard-card-more-btn {
    position: absolute !important;
    inset: auto 12px 12px auto;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    margin: 0 !important;
    padding: 0;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    border: 1px solid #d8e5dc !important;
    background: linear-gradient(180deg, #ffffff 0%, #f2fbf6 100%) !important;
    color: #1f7f4e !important;
    box-shadow: 0 4px 12px rgba(33, 145, 80, 0.16);
    transition: all 0.2s ease;
}
.dashboard-card-more-btn i {
    font-size: 0.95rem;
}
.dashboard-card-more-btn:hover,
.dashboard-card-more-btn:focus {
    border-color: #219150 !important;
    background: linear-gradient(180deg, #ffffff 0%, #e9f8ef 100%) !important;
    color: #15673d !important;
    box-shadow: 0 6px 14px rgba(33, 145, 80, 0.24);
    transform: translateY(-1px);
}
.dashboard-card-more-btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(33, 145, 80, 0.22), 0 6px 14px rgba(33, 145, 80, 0.24);
}
.birthday-modal-content {
    border: 1px solid #d7e3ef;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 14px 35px rgba(15, 23, 42, 0.10);
    background: #f7fafc;
}
.birthday-modal-header {
    border-bottom: 1px solid #d7e3ef;
    background: linear-gradient(180deg, #ffffff 0%, #f3f8fc 100%);
    padding: 0.95rem 1rem;
}
.birthday-modal-header .modal-title {
    font-weight: 700;
    letter-spacing: 0.2px;
}
.birthday-modal-body {
    padding: 1rem;
}
.birthday-filter-bar {
    border: 1px solid #dbe6f1;
    border-radius: 12px;
    padding: 0.55rem 0.7rem;
    background: #ffffff;
}
.birthday-filter-bar .form-select {
    border-color: #ced9e6;
    border-radius: 10px;
    font-weight: 600;
}
.birthday-month-count {
    min-width: 72px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #35556f !important;
    background: #f6fbff !important;
    border-color: #d6e4f1 !important;
}
.birthday-person-card {
    border: 1px solid #d9e5f0 !important;
    border-radius: 16px !important;
    min-height: 192px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%) !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.birthday-person-card:hover {
    transform: translateY(-1px);
    border-color: #b8d0e8 !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.09) !important;
}
.birthday-avatar {
    border: 2px solid #e7eef5;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.10);
}
.birthday-date-pill {
    border: 1px solid #f8d8a0;
    border-radius: 999px;
    background: #fff8ea;
    padding: 0.18rem 0.62rem;
}
.card-info:hover, .card:hover, .card.border-0:hover, .card.shadow-sm:hover {
    box-shadow: 0 6px 16px rgba(33, 145, 80, 0.12), 0 2px 6px rgba(15, 23, 42, 0.06) !important;
    border-color: #219150 !important;
    cursor: pointer;
}

/* Limitar altura APENAS nos cards da lista (não no modal) */
.card .informativo-conteudo {
    max-height: 80px;
    overflow: hidden;
    position: relative;
}

/* Limitar resumo nos cards do dashboard (não crescer no mobile) */
.dashboard-informativo-resumo {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Miniatura: área visível enquanto a imagem carrega (evita “quadrado branco” vazio) */
.dashboard-informativo-thumb {
    background-color: #e9ecef;
    min-width: 56px;
    min-height: 56px;
    vertical-align: middle;
}

/* Mobile: ajustes de densidade nos cards */
@media (max-width: 767.98px) {
    .dashboard-quick-row {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }

    .dashboard-recent-row {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }

    .dashboard-recent-card {
        min-height: 188px !important;
        height: auto !important;
        padding: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .dashboard-recent-ver-mais-btn,
    .dashboard-recent-ver-mais-btn * {
        font-size: 0.9rem !important;
    }

    .dashboard-recent-vermais-btn {
        min-width: 100px !important;
        padding-left: 0.9rem !important;
        padding-right: 0.9rem !important;
    }

    /* Ajuste no espaçamento entre conteúdo e botão */
    .dashboard-recent-card .mt-2 {
        margin-top: 0.6rem !important;
    }

    .dashboard-recent-footer {
        position: static;
        margin-top: auto !important;
    }

    .dashboard-card-more-btn {
        inset: auto 12px 12px auto;
        width: 34px;
        height: 34px;
        border-radius: 10px;
    }

    .dashboard-card-more-btn i {
        font-size: 0.95rem;
    }

    .birthday-modal-body {
        padding: 0.85rem;
    }

    .birthday-person-card {
        min-height: 180px;
        padding: 1rem !important;
    }
}

/* No modal, mostrar conteúdo completo sem limitação */
.modal-body .informativo-conteudo {
    max-height: none !important;
    overflow: visible !important;
}

/* Mobile: evita que o scroll “vaze” para a página de trás e reduz gestos que disparam voltar */
.dashboard-informativo-modal.modal .modal-dialog-scrollable .modal-body,
#imageModal.modal .modal-body {
    overscroll-behavior: contain;
    touch-action: pan-y;
}

/* Modal de imagem: barra de ações sempre visível; só o miolo rola (evita “Fechar” só no fim da página) */
#imageModal .dashboard-image-modal-content {
    min-height: 100dvh;
    min-height: 100svh;
}
@media (min-width: 768px) {
    #imageModal .dashboard-image-modal-content {
        min-height: auto;
        max-height: 92vh;
    }
}
#imageModal .dashboard-image-modal-body {
    min-height: 0;
    flex: 1 1 auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
#imageModal #modalImage {
    max-height: calc(100vh - 7rem) !important;
}
@supports (height: 100dvh) {
    #imageModal #modalImage {
        max-height: min(78dvh, calc(100dvh - 7rem)) !important;
    }
}

/* Backdrop mais suave em todos os modais da dashboard (informativos, imagem, aniversários) */
.modal-backdrop.dashboard-soft-backdrop.show {
    opacity: 0.12 !important;
    background-color: #f8fafc !important;
}
</style>
<!-- Modal para ampliar imagem - Tela cheia em mobile; Fechar sempre na barra superior -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down modal-xl">
    <div class="modal-content dashboard-image-modal-content d-flex flex-column bg-white border-0 rounded-0">
      <div class="modal-header flex-shrink-0 border-0 py-2 px-3 shadow-sm bg-white align-items-center justify-content-end">
        <span class="visually-hidden" id="imageModalLabel">Visualização de imagem</span>
        <div class="d-flex gap-2 align-items-center">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i>Fechar
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
      </div>
      <div class="modal-body dashboard-image-modal-body text-center d-flex align-items-center justify-content-center p-2 p-md-3 bg-white">
        <img id="modalImage" src="" alt="Imagem ampliada" class="img-fluid" style="width: auto; height: auto; object-fit: contain;">
      </div>
    </div>
  </div>
</div>
<script>
function applyDashboardSoftBackdropToAll() {
    document.querySelectorAll('.modal-backdrop.show').forEach(function (b) {
        b.classList.add('dashboard-soft-backdrop');
    });
}

function showImageModal(src) {
    const img = document.getElementById('modalImage');
    const modalEl = document.getElementById('imageModal');
    if (!img || !modalEl) return;

    let opened = false;
    function openModalOnce() {
        if (opened) return;
        opened = true;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
            backdrop: 'static',
            keyboard: true,
            focus: true
        });
        modal.show();
        setTimeout(function () {
            applyDashboardSoftBackdropToAll();
            img.style.filter = '';
            img.style.opacity = '1';
        }, 50);
    }

    img.onerror = function () {
        opened = false;
        alert('Erro ao carregar a imagem. Por favor, tente novamente.');
    };

    img.onload = function () {
        openModalOnce();
    };

    img.src = src;
    // Imagem já em cache (comum no localhost): onload pode não disparar
    if (img.complete && img.naturalWidth > 0) {
        setTimeout(openModalOnce, 0);
    }
}

// Dashboard: ao abrir imagem/anexo, manter coerência com o sino.
// - requires_ack=1: não remove notificação automaticamente (apenas abre).
// - requires_ack=0: marca como lido via read-informativo (sem reload — evita fechar o modal de imagem).
function openDashboardInformativoImage(event, informativoId, requiresAck, imageUrl) {
    event.preventDefault();
    event.stopPropagation();

    if (requiresAck) {
        showImageModal(imageUrl);
        return false;
    }

    fetch(`${window.location.origin}/administrativo/read-informativo/${informativoId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    }).catch(function () {});

    showImageModal(imageUrl);

    return false;
}

function openDashboardInformativoAttachment(event, informativoId, requiresAck, url) {
    event.preventDefault();
    event.stopPropagation();

    if (!requiresAck) {
        fetch(`${window.location.origin}/administrativo/read-informativo/${informativoId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).catch(function () {});
    }

    // Abrir anexo na mesma aba: botão Voltar retorna para o dashboard
    window.location.href = url;

    return false;
}

function confirmarCiencia(informativoId) {
    // Desabilitar o botão para evitar cliques múltiplos
    const btn = document.getElementById(`btn-ack-${informativoId}`);
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Confirmando...';
    
    // Fazer a requisição AJAX
    fetch(`${window.location.origin}/administrativo/acknowledge-informativo/${informativoId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sucesso - alterar o botão
            btn.innerHTML = '<i class="fas fa-check me-1"></i>Ciente confirmado';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-success');
            btn.disabled = true;
            
            // Mostrar mensagem de sucesso
            Swal.fire({
                icon: 'success',
                title: 'Ciência confirmada!',
                text: 'Sua confirmação foi registrada com sucesso.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            // Erro - restaurar o botão
            btn.disabled = false;
            btn.innerHTML = originalText;
            
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: data.message || 'Erro ao confirmar ciência. Tente novamente.',
            });
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        // Erro - restaurar o botão
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Erro de conexão. Tente novamente.',
        });
    });
}
// Registrar leitura ao abrir o modal
document.addEventListener('DOMContentLoaded', function() {
    const currentMonthNumber = <?php echo (int)date('n'); ?>;
    /**
     * Modais da dashboard: backdrop suave + histórico (Fechar / Voltar do navegador ou navbar
     * fecham o overlay e mantêm o usuário na própria página da dashboard).
     * Um push no histórico por abertura (dataset.historyPushed) evita duplicar entradas se shown.bs.modal disparar mais de uma vez.
     */
    let closingFromPopstate = false;
    /** Evita fechar o modal “de baixo” quando history.back() veio do próprio script (fechar com X). */
    let ignoreNextPopstate = false;

    const dashboardManagedModalIds = [
        'imageModal',
        <?php foreach (array_slice($this->data['informativos'] ?? [], 0, 6) as $info): ?>
        'informativoModal<?php echo (int)$info['id']; ?>',
        <?php endforeach; ?>
        'modalAniversariantesDia',
        'modalAniversariantesMes',
        'modalAniversariantesEmpresa'
    ];

    function getTopmostDashboardModalEl() {
        const order = [
            'imageModal',
            <?php foreach (array_slice($this->data['informativos'] ?? [], 0, 6) as $info): ?>
            'informativoModal<?php echo (int)$info['id']; ?>',
            <?php endforeach; ?>
            'modalAniversariantesDia',
            'modalAniversariantesMes',
            'modalAniversariantesEmpresa'
        ];
        for (let i = 0; i < order.length; i++) {
            const el = document.getElementById(order[i]);
            if (el && el.classList.contains('show')) {
                return el;
            }
        }
        return null;
    }

    function wireDashboardModal(el) {
        if (!el) return;

        el.addEventListener('shown.bs.modal', function () {
            applyDashboardSoftBackdropToAll();
            setTimeout(applyDashboardSoftBackdropToAll, 50);
            if (!el.dataset.historyPushed) {
                history.pushState({ dashboardModal: true }, '', window.location.href);
                el.dataset.historyPushed = '1';
            }
        });

        el.addEventListener('hidden.bs.modal', function () {
            if (closingFromPopstate) {
                closingFromPopstate = false;
                if (el.dataset.historyPushed) {
                    delete el.dataset.historyPushed;
                }
                return;
            }
            if (el.dataset.historyPushed) {
                delete el.dataset.historyPushed;
                ignoreNextPopstate = true;
                history.back();
                setTimeout(function () {
                    if (ignoreNextPopstate) {
                        ignoreNextPopstate = false;
                    }
                }, 400);
            }
        });
    }

    dashboardManagedModalIds.forEach(function (id) {
        wireDashboardModal(document.getElementById(id));
    });

    window.addEventListener('popstate', function () {
        if (ignoreNextPopstate) {
            ignoreNextPopstate = false;
            return;
        }
        const openModalEl = getTopmostDashboardModalEl();
        if (!openModalEl) {
            return;
        }
        closingFromPopstate = true;
        const inst = bootstrap.Modal.getInstance(openModalEl)
            || bootstrap.Modal.getOrCreateInstance(openModalEl);
        inst.hide();
    });

    // Calendário de aniversariantes por mês (modal do mês)
    const aniversariantesMesFilter = document.getElementById('aniversariantesMesFilter');
    const aniversariantesMesItems = Array.from(document.querySelectorAll('.aniversariante-mes-item'));
    const aniversariantesMesEmpty = document.getElementById('aniversariantesMesEmpty');
    const aniversariantesMesModal = document.getElementById('modalAniversariantesMes');
    const aniversariantesMesCount = document.getElementById('aniversariantesMesCount');
    const aniversariantesEmpresaMesFilter = document.getElementById('aniversariantesEmpresaMesFilter');
    const aniversariantesEmpresaMesItems = Array.from(document.querySelectorAll('.aniversariante-empresa-mes-item'));
    const aniversariantesEmpresaMesEmpty = document.getElementById('aniversariantesEmpresaMesEmpty');
    const aniversariantesEmpresaMesModal = document.getElementById('modalAniversariantesEmpresa');
    const aniversariantesEmpresaMesCount = document.getElementById('aniversariantesEmpresaMesCount');

    function applyAniversariantesMesFilter() {
        if (!aniversariantesMesFilter || aniversariantesMesItems.length === 0) return;
        const selectedMonth = parseInt(aniversariantesMesFilter.value || '0', 10);
        let visibleCount = 0;

        aniversariantesMesItems.forEach(function (item) {
            const itemMonth = parseInt(item.getAttribute('data-mes') || '0', 10);
            const show = selectedMonth === 0 || itemMonth === selectedMonth;
            item.classList.toggle('d-none', !show);
            if (show) visibleCount++;
        });

        if (aniversariantesMesEmpty) {
            aniversariantesMesEmpty.classList.toggle('d-none', visibleCount > 0);
        }
        if (aniversariantesMesCount) {
            aniversariantesMesCount.textContent = `${visibleCount} no mês`;
        }
    }

    if (aniversariantesMesFilter) {
        aniversariantesMesFilter.addEventListener('change', applyAniversariantesMesFilter);
    }
    if (aniversariantesMesModal) {
        aniversariantesMesModal.addEventListener('shown.bs.modal', function () {
            if (aniversariantesMesFilter) {
                aniversariantesMesFilter.value = String(currentMonthNumber);
            }
            applyAniversariantesMesFilter();
        });
    }
    applyAniversariantesMesFilter();

    function applyAniversariantesEmpresaMesFilter() {
        if (!aniversariantesEmpresaMesFilter || aniversariantesEmpresaMesItems.length === 0) return;
        const selectedMonth = parseInt(aniversariantesEmpresaMesFilter.value || '0', 10);
        let visibleCount = 0;

        aniversariantesEmpresaMesItems.forEach(function (item) {
            const itemMonth = parseInt(item.getAttribute('data-mes') || '0', 10);
            const show = selectedMonth === 0 || itemMonth === selectedMonth;
            item.classList.toggle('d-none', !show);
            if (show) visibleCount++;
        });

        if (aniversariantesEmpresaMesEmpty) {
            aniversariantesEmpresaMesEmpty.classList.toggle('d-none', visibleCount > 0);
        }
        if (aniversariantesEmpresaMesCount) {
            aniversariantesEmpresaMesCount.textContent = `${visibleCount} no mês`;
        }
    }

    if (aniversariantesEmpresaMesFilter) {
        aniversariantesEmpresaMesFilter.addEventListener('change', applyAniversariantesEmpresaMesFilter);
    }
    if (aniversariantesEmpresaMesModal) {
        aniversariantesEmpresaMesModal.addEventListener('shown.bs.modal', function () {
            if (aniversariantesEmpresaMesFilter) {
                aniversariantesEmpresaMesFilter.value = String(currentMonthNumber);
            }
            applyAniversariantesEmpresaMesFilter();
        });
    }
    applyAniversariantesEmpresaMesFilter();

    <?php foreach (array_slice($this->data['informativos'] ?? [], 0, 6) as $info): ?>
    const modalEl<?php echo $info['id']; ?> = document.getElementById('informativoModal<?php echo $info['id']; ?>');
    if (modalEl<?php echo $info['id']; ?>) {
        modalEl<?php echo $info['id']; ?>.addEventListener('shown.bs.modal', function () {
            fetch(`${window.location.origin}/administrativo/read-informativo/<?php echo $info['id']; ?>`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            }).then(r => r.json()).then(data => {
                if (data && data.acknowledged) {
                    const btn = document.getElementById('btn-ack-<?php echo $info['id']; ?>');
                    if (btn) {
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-success');
                        btn.innerHTML = '<i class="fas fa-check me-1"></i>Ciente confirmado';
                        btn.disabled = true;
                        btn.onclick = null;
                    }
                }
            }).catch(() => {});
        });
    }
    <?php endforeach; ?>
});
</script>