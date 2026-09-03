<div class="container-fluid px-4">
    <?php include __DIR__ . '/../partials/alerts.php'; ?>
    <?php if (!empty($this->data['show_my_calendar_card'])): ?>
        <?php $dashCalCssBase = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/'); ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($dashCalCssBase, ENT_QUOTES, 'UTF-8'); ?>/public/adms/css/rooms-module.css?v=20260418">
    <?php endif; ?>
    <?php
    $renderAvatarFallback = static function (string $name): string {
        return \App\adms\Helpers\ImageHelper::renderInitialsAvatar($name, 86, [
            'class' => 'mb-2 birthday-avatar',
        ]);
    };
    ?>
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
                <?php if (!empty($this->data['show_gamification_quizzes_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>gamification-quiz-catalog" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: linear-gradient(135deg, #f5fff8 0%, #fff 100%);">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-puzzle-piece fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Quizzes</h5>
                            <div class="text-muted mb-1 text-center small">Disponíveis para responder</div>
                            <div class="text-success text-center small fw-semibold"><?php echo (int)($this->data['gamification_quizzes_catalog_count'] ?? 0); ?> publicados</div>
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
                                <?php echo (int)($this->data['company_events_year_count'] ?? 0); ?> ativos no ano
                            </div>
                            <div class="text-muted text-center small fw-semibold">
                                <?php echo (int)($this->data['company_events_month_count'] ?? 0); ?> neste mês
                            </div>
                            <div class="text-success text-center small fw-semibold" id="dashboardEventsUnreadCount">
                                <?php echo (int)($this->data['company_events_unread_count'] ?? 0); ?> não lidos no ano
                            </div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_payroll_documents_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>my-payroll-documents" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-file-invoice-dollar fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Meus documentos</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                <?php echo (int)($this->data['payroll_documents_total'] ?? 0); ?> disponíveis
                            </div>
                            <?php $latestDocs = $this->data['payroll_documents_latest'] ?? []; ?>
                            <?php if (!empty($latestDocs)): ?>
                                <div class="text-primary text-center small fw-semibold">
                                    Último ref.: <?php
                                        $d = $latestDocs[0];
                                        $ry = (int)($d['reference_year'] ?? 0);
                                        $rm = $d['reference_month'] ?? null;
                                        echo htmlspecialchars($rm !== null && $rm !== '' ? str_pad((string)$rm, 2, '0', STR_PAD_LEFT) . '/' . $ry : (string)$ry);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_employee_portal_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>employee-portal" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%);">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-user-circle fa-3x text-success"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Portal do Colaborador</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                Solicitações, chamados e vagas
                            </div>
                            <div class="text-success text-center small fw-semibold">Abrir portal</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_my_calendar_card'])): ?>
                <?php
                    $mcMonth = date('Y-m');
                    $mcFullHref = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/my-calendar?month=' . rawurlencode($mcMonth);
                ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="#"
                       class="text-decoration-none flex-fill h-100"
                       data-bs-toggle="modal"
                       data-bs-target="#modalMeuCalendarioMes"
                       onclick="event.preventDefault();">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100 position-relative" style="background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);">
                            <button type="button"
                                    class="btn btn-sm dashboard-card-more-btn"
                                    aria-label="Abrir página completa do calendário"
                                    title="Página completa"
                                    onclick="event.preventDefault(); event.stopPropagation(); window.location.href='<?php echo htmlspecialchars($mcFullHref, ENT_QUOTES, 'UTF-8'); ?>';">
                                <i class="fas fa-external-link-alt"></i>
                            </button>
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-calendar-check fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Meu calendário</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                <?php echo (int) ($this->data['my_calendar_month_count'] ?? 0); ?> neste mês
                            </div>
                            <div class="text-muted text-center small">Apenas as suas atividades</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_room_booking_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: linear-gradient(135deg, #fff8f0 0%, #fff 100%);">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-door-open fa-3x" style="color: #fd7e14;"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Reserva de Salas</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                <?php echo (int) ($this->data['meeting_rooms_active_count'] ?? 0); ?> salas ativas
                            </div>
                            <div class="text-center small fw-semibold" style="color: #fd7e14;">Agendar reunião</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($this->data['show_portaria_card'])): ?>
                <div class="col-12 col-md-3 d-flex align-items-stretch">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>portaria-painel" class="text-decoration-none flex-fill h-100">
                        <div class="card card-main dashboard-card d-flex flex-column align-items-center justify-content-center p-4 h-100" style="background: linear-gradient(135deg, #eef6ff 0%, #fff 100%);">
                            <div class="icon-main mb-2 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="fas fa-shield-alt fa-3x text-primary"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-center group-title">Portaria</h5>
                            <div class="text-muted mb-1 text-center" style="font-size: 1.05rem;">
                                Entradas, saídas e autorizações
                            </div>
                            <div class="text-primary text-center small fw-semibold">Abrir painel</div>
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
                                            <?php if (\App\adms\Helpers\ImageHelper::userImageExists((int)($aniv['id'] ?? 0), (string)($aniv['image'] ?? ''))): ?>
                                                <?php
                                                $avatarPath = 'users/' . $aniv['id'] . '/' . $aniv['image'];
                                                echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                                                    'class' => 'rounded-circle mb-2 birthday-avatar',
                                                    'style' => 'width: 86px; height: 86px; object-fit: cover;',
                                                ], 'icon_user.png', 'users');
                                                ?>
                                            <?php else: ?>
                                                <?php echo $renderAvatarFallback((string)($aniv['name'] ?? '')); ?>
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
                    <div class="row g-3" id="aniversariantesMesGrid" data-initial-month="<?php echo (int) date('n'); ?>">
                        <?php
                        $birthdayMonthItems = $this->data['aniversariantes_mes'] ?? [];
                        $urlAdm = $_ENV['URL_ADM'] ?? '';
                        include __DIR__ . '/partials/birthday_month_cards.php';
                        ?>
                    </div>
                    <div id="aniversariantesMesLoading" class="text-center text-muted py-3 d-none">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Carregando…
                    </div>
                    <div id="aniversariantesMesEmpty" class="text-center text-muted py-3 d-none">
                        Nenhum aniversariante encontrado para o mês selecionado.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/modal_eventos_mes.php'; ?>

    <?php if (!empty($this->data['show_my_calendar_card'])): ?>
    <?php
        $mesesPtDash = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];
        $yDash = (int) date('Y');
        $mDash = (int) date('n');
        $mcFullHrefModal = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/my-calendar?month=' . rawurlencode(sprintf('%04d-%02d', $yDash, $mDash));
    ?>
    <div class="modal fade" id="modalMeuCalendarioMes" tabindex="-1" aria-labelledby="modalMeuCalendarioMesLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-xl modal-dialog-centered">
            <div class="modal-content birthday-modal-content">
                <div class="modal-header birthday-modal-header">
                    <h5 class="modal-title" id="modalMeuCalendarioMesLabel">Meu calendário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body birthday-modal-body">
                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center justify-content-md-between gap-2 mb-3 birthday-filter-bar" id="myCalDashFiltersWrap">
                        <span class="text-muted small flex-shrink-0">Mês e ano</span>
                        <div class="d-flex align-items-center gap-2 flex-wrap flex-md-nowrap my-cal-dash-filters-row">
                            <select id="myCalDashMonth" class="form-select form-select-sm my-cal-dash-select-month">
                                <?php foreach ($mesesPtDash as $mn => $mname): ?>
                                    <option value="<?php echo (int) $mn; ?>" <?php echo $mn === $mDash ? 'selected' : ''; ?>><?php echo htmlspecialchars($mname); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="myCalDashYear" class="form-select form-select-sm my-cal-dash-select-year">
                                <?php for ($yy = $yDash - 1; $yy <= $yDash + 1; $yy++): ?>
                                    <option value="<?php echo (int) $yy; ?>" <?php echo $yy === $yDash ? 'selected' : ''; ?>><?php echo (int) $yy; ?></option>
                                <?php endfor; ?>
                            </select>
                            <span id="myCalDashMonthCount" class="badge rounded-pill text-bg-light border birthday-month-count">0</span>
                        </div>
                    </div>
                    <div id="myCalDashMonthWrap">
                        <div class="rooms-module-page">
                            <div class="rooms-calendar-scroll">
                                <div class="outlook-calendar p-2">
                                    <div id="dashboardMyCalGrid" class="calendar-grid-outlook"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="myCalDashDayWrap" class="d-none">
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="myCalDashDayBack" aria-label="Voltar ao calendário mensal">
                            <i class="fas fa-arrow-left me-1"></i>Voltar ao calendário
                        </button>
                        <h6 class="fw-bold mb-3" id="myCalDashDayTitle"></h6>
                        <div id="myCalDashDayList"></div>
                    </div>
                    <div class="text-center mt-3" id="myCalDashFooterWrap">
                        <a class="btn btn-outline-primary btn-sm" id="myCalDashFullPageLink" href="<?php echo htmlspecialchars($mcFullHrefModal, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-external-link-alt me-1"></i>Abrir página completa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
                    <div class="row g-3" id="aniversariantesEmpresaMesGrid" data-initial-month="<?php echo (int) date('n'); ?>">
                        <?php
                        $companyTenureMonthItems = $this->data['aniversariantes_empresa_mes'] ?? [];
                        $urlAdm = $_ENV['URL_ADM'] ?? '';
                        include __DIR__ . '/partials/company_tenure_month_cards.php';
                        ?>
                    </div>
                    <div id="aniversariantesEmpresaMesLoading" class="text-center text-muted py-3 d-none">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Carregando…
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
                                        <div class="informativo-texto adms-view-rich-content">
                                            <?php
                                            $conteudo = trim((string) ($info['conteudo'] ?? ''));
                                            if (preg_match('/<[^>]+>/', $conteudo)) {
                                                echo \App\adms\Helpers\AdmsHtmlLinkHelper::prepareRichHtml($conteudo);
                                            } else {
                                                $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
                                                echo nl2br(htmlspecialchars($conteudo, ENT_QUOTES, 'UTF-8'));
                                            }
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
    line-clamp: 2;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.25;
    min-height: 2.45em;
}
.dashboard-recent-title {
    display: -webkit-box;
    line-clamp: 2;
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

/* Modal «Meu calendário»: filtros e grelha sem overflow horizontal em telemóvel */
.my-cal-dash-select-month {
    max-width: 160px;
}
.my-cal-dash-select-year {
    max-width: 110px;
}
#modalMeuCalendarioMes .birthday-modal-body .rooms-module-page,
#modalMeuCalendarioMes .birthday-modal-body .rooms-calendar-scroll,
#modalMeuCalendarioMes .birthday-modal-body #dashboardMyCalGrid {
    min-width: 0;
}
@media (max-width: 767.98px) {
    .my-cal-dash-filters-row {
        width: 100%;
        flex-wrap: nowrap;
    }
    .my-cal-dash-select-month {
        max-width: none;
        flex: 1 1 0;
        min-width: 0;
    }
    .my-cal-dash-select-year {
        max-width: none;
        flex: 0 0 5.5rem;
        min-width: 4.75rem;
    }
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
    line-clamp: 2;
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
<?php include __DIR__ . '/../partials/document_preview_modal.php'; ?>
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

    if (typeof window.admsOpenAttachmentUrl === 'function') {
        window.admsOpenAttachmentUrl(url);
    } else {
        window.location.href = url;
    }

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
    // Modais da dashboard: apenas aplica backdrop suave; o controle de histórico
    // (botão Voltar fechando modais) é feito pelo script global em layouts/main.php.
    const dashboardManagedModalIds = [
        'imageModal',
        <?php foreach (array_slice($this->data['informativos'] ?? [], 0, 6) as $info): ?>
        'informativoModal<?php echo (int)$info['id']; ?>',
        <?php endforeach; ?>
        'modalAniversariantesDia',
        'modalAniversariantesMes',
        'modalAniversariantesEmpresa'<?php if (!empty($this->data['show_my_calendar_card'])): ?>,
        'modalMeuCalendarioMes'<?php endif; ?>
    ];

    dashboardManagedModalIds.forEach(function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('shown.bs.modal', function () {
            applyDashboardSoftBackdropToAll();
            setTimeout(applyDashboardSoftBackdropToAll, 50);
        });
    });

    // Modais de aniversário / tempo de empresa: mês atual no carregamento; outros meses via AJAX
    const dashboardBirthdaysAjaxUrl = <?php echo json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/dashboard-birthdays-ajax', JSON_THROW_ON_ERROR); ?>;
    const birthdayMonthCache = {};
    const tenureMonthCache = {};

    function setupDashboardMonthModal(config) {
        const filter = document.getElementById(config.filterId);
        const grid = document.getElementById(config.gridId);
        const empty = document.getElementById(config.emptyId);
        const loading = document.getElementById(config.loadingId);
        const countEl = document.getElementById(config.countId);
        const modal = document.getElementById(config.modalId);
        const cache = config.cache;
        if (!filter || !grid) {
            return;
        }
        const initialMonth = parseInt(grid.getAttribute('data-initial-month') || String(currentMonthNumber), 10);
        cache[initialMonth] = grid.innerHTML;

        function updateUi(count) {
            const n = typeof count === 'number' ? count : grid.querySelectorAll(config.itemSelector).length;
            if (countEl) {
                countEl.textContent = n + ' no mês';
            }
            if (empty) {
                empty.classList.toggle('d-none', n > 0);
            }
        }

        function loadMonth(month) {
            if (month < 1 || month > 12) {
                return;
            }
            if (cache[month]) {
                grid.innerHTML = cache[month];
                updateUi();
                return;
            }
            if (loading) {
                loading.classList.remove('d-none');
            }
            grid.classList.add('opacity-50');
            const url = dashboardBirthdaysAjaxUrl + '?type=' + encodeURIComponent(config.ajaxType) + '&month=' + month;
            fetch(url, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.success) {
                        cache[month] = data.html || '';
                        grid.innerHTML = cache[month];
                        updateUi(data.count);
                    } else {
                        grid.innerHTML = '';
                        updateUi(0);
                    }
                })
                .catch(function () {
                    grid.innerHTML = '';
                    updateUi(0);
                })
                .finally(function () {
                    if (loading) {
                        loading.classList.add('d-none');
                    }
                    grid.classList.remove('opacity-50');
                });
        }

        filter.addEventListener('change', function () {
            loadMonth(parseInt(filter.value || '0', 10));
        });
        if (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                filter.value = String(currentMonthNumber);
                loadMonth(currentMonthNumber);
            });
        }
        updateUi();
    }

    setupDashboardMonthModal({
        filterId: 'aniversariantesMesFilter',
        gridId: 'aniversariantesMesGrid',
        emptyId: 'aniversariantesMesEmpty',
        loadingId: 'aniversariantesMesLoading',
        countId: 'aniversariantesMesCount',
        modalId: 'modalAniversariantesMes',
        cache: birthdayMonthCache,
        ajaxType: 'birthday',
        itemSelector: '.aniversariante-mes-item',
    });
    setupDashboardMonthModal({
        filterId: 'aniversariantesEmpresaMesFilter',
        gridId: 'aniversariantesEmpresaMesGrid',
        emptyId: 'aniversariantesEmpresaMesEmpty',
        loadingId: 'aniversariantesEmpresaMesLoading',
        countId: 'aniversariantesEmpresaMesCount',
        modalId: 'modalAniversariantesEmpresa',
        cache: tenureMonthCache,
        ajaxType: 'tenure',
        itemSelector: '.aniversariante-empresa-mes-item',
    });

    <?php if (!empty($this->data['show_my_calendar_card'])): ?>
    (function () {
        var myCalEvents = <?php echo $this->data['my_calendar_modal_events_json'] ?? '[]'; ?>;
        var gridEl = document.getElementById('dashboardMyCalGrid');
        var monthSel = document.getElementById('myCalDashMonth');
        var yearSel = document.getElementById('myCalDashYear');
        var countEl = document.getElementById('myCalDashMonthCount');
        var fullLink = document.getElementById('myCalDashFullPageLink');
        var myCalModal = document.getElementById('modalMeuCalendarioMes');
        var currentMonthNumber = <?php echo (int) date('n'); ?>;
        var currentYearNumber = <?php echo (int) date('Y'); ?>;
        var myCalPageBase = <?php echo json_encode(rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/my-calendar', JSON_THROW_ON_ERROR); ?>;
        var monthWrap = document.getElementById('myCalDashMonthWrap');
        var dayWrap = document.getElementById('myCalDashDayWrap');
        var filtersWrap = document.getElementById('myCalDashFiltersWrap');
        var footerWrap = document.getElementById('myCalDashFooterWrap');
        var dayTitleEl = document.getElementById('myCalDashDayTitle');
        var dayListEl = document.getElementById('myCalDashDayList');
        var dayBackBtn = document.getElementById('myCalDashDayBack');

        function pad2(n) { return String(n).length < 2 ? '0' + n : String(n); }
        function ymdPrefix(y, m) { return y + '-' + pad2(m) + '-'; }
        function firstWeekdayMon(y, m) {
            var d = new Date(y, m - 1, 1).getDay();
            return d === 0 ? 7 : d;
        }
        function daysInMonth(y, m) { return new Date(y, m, 0).getDate(); }
        function eventsOnDay(y, m, day) {
            var p = ymdPrefix(y, m) + pad2(day);
            return (myCalEvents || []).filter(function (ev) {
                return (ev.start || '').substring(0, 10) === p;
            });
        }
        function monthEventCount(y, m) {
            var pre = ymdPrefix(y, m);
            return (myCalEvents || []).filter(function (ev) {
                return (ev.start || '').substring(0, 7) === pre.substring(0, 7);
            }).length;
        }
        function esc(s) {
            if (!s) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        }
        function sourceLabel(src) {
            if (src === 'personal') return 'Pessoal';
            if (src === 'room_booking') return 'Reserva';
            if (src === 'room_invite') return 'Convite';
            if (src === 'company_event') return 'Evento';
            return src || '—';
        }
        function formatDashDayTitle(dateStr) {
            try {
                var d = new Date(dateStr + 'T12:00:00');
                if (isNaN(d.getTime())) return dateStr;
                return d.toLocaleDateString('pt-PT', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        }
        function showMyCalMonthView() {
            if (monthWrap) monthWrap.classList.remove('d-none');
            if (dayWrap) dayWrap.classList.add('d-none');
            if (filtersWrap) filtersWrap.classList.remove('d-none');
            if (footerWrap) footerWrap.classList.remove('d-none');
        }
        function showMyCalDayView() {
            if (monthWrap) monthWrap.classList.add('d-none');
            if (dayWrap) dayWrap.classList.remove('d-none');
            if (filtersWrap) filtersWrap.classList.add('d-none');
            if (footerWrap) footerWrap.classList.add('d-none');
        }
        function eventsForDateString(dateStr) {
            return (myCalEvents || []).filter(function (ev) {
                return (ev.start || '').substring(0, 10) === dateStr;
            });
        }
        function openDashDayAgenda(dateStr) {
            var evs = eventsForDateString(dateStr).slice().sort(function (a, b) {
                return (a.start || '').localeCompare(b.start || '');
            });
            if (dayTitleEl) {
                var t = formatDashDayTitle(dateStr);
                dayTitleEl.textContent = t.charAt(0).toUpperCase() + t.slice(1);
            }
            if (!dayListEl) return;
            if (evs.length === 0) {
                dayListEl.className = '';
                dayListEl.innerHTML = '<p class="text-muted mb-0">Sem compromissos neste dia.</p>';
            } else {
                dayListEl.className = 'list-group';
                var h = '';
                evs.forEach(function (ev) {
                    var t0 = (ev.start || '').substring(11, 16);
                    var t1 = (ev.end || '').substring(11, 16);
                    var tit = ev.title || '—';
                    var src = ev.source || '';
                    var badge = '<span class="badge bg-light text-dark border ms-2">' + esc(sourceLabel(src)) + '</span>';
                    h += '<div class="list-group-item list-group-item-action py-3">';
                    h += '<div class="d-flex flex-wrap align-items-start justify-content-between gap-2">';
                    h += '<div><strong>' + esc(tit) + '</strong>' + badge + '</div>';
                    h += '</div>';
                    h += '<div class="small text-muted mt-1">' + esc(t0) + ' — ' + esc(t1) + '</div>';
                    if (ev.href && ev.href !== '#') {
                        h += '<a class="btn btn-sm btn-primary mt-2" href="' + encodeURI(ev.href) + '">' + esc(ev.label || 'Abrir') + '</a>';
                    } else {
                        h += '<span class="small text-muted mt-2 d-inline-block">Sem ligação disponível para este item.</span>';
                    }
                    h += '</div>';
                });
                dayListEl.innerHTML = h;
            }
            showMyCalDayView();
        }
        function renderMyCalDashGrid() {
            if (!gridEl || !monthSel || !yearSel) return;
            var y = parseInt(yearSel.value || '0', 10);
            var m = parseInt(monthSel.value || '0', 10);
            if (!y || !m) return;
            if (fullLink) {
                fullLink.setAttribute('href', myCalPageBase + '?month=' + y + '-' + pad2(m));
            }
            var dim = daysInMonth(y, m);
            var fw = firstWeekdayMon(y, m);
            var html = '';
            var headers = [
                ['Segunda', 'Seg'], ['Terça', 'Ter'], ['Quarta', 'Qua'], ['Quinta', 'Qui'],
                ['Sexta', 'Sex'], ['Sábado', 'Sáb'], ['Domingo', 'Dom']
            ];
            headers.forEach(function (pair) {
                html += '<div class="calendar-day-header-outlook" aria-label="' + esc(pair[0]) + '"><abbr title="' + esc(pair[0]) + '">' + esc(pair[1]) + '</abbr></div>';
            });
            for (var i = 1; i < fw; i++) {
                html += '<div class="calendar-day-outlook other-month"></div>';
            }
            var today = new Date();
            var todayStr = today.getFullYear() + '-' + pad2(today.getMonth() + 1) + '-' + pad2(today.getDate());
            for (var day = 1; day <= dim; day++) {
                var dateStr = ymdPrefix(y, m) + pad2(day);
                var evs = eventsOnDay(y, m, day);
                var isToday = dateStr === todayStr;
                var hasEvs = evs.length > 0;
                var cellClass = 'calendar-day-outlook' + (isToday ? ' today' : '') + (hasEvs ? ' dashboard-my-cal-day-clickable' : '');
                html += '<div class="' + cellClass + '" data-dash-cal-date="' + esc(dateStr) + '"' + (hasEvs ? ' role="button" tabindex="0"' : ' tabindex="-1"') + ' title="' + (hasEvs ? 'Ver agenda do dia' : 'Dia sem compromissos') + '">';
                html += '<div class="calendar-day-number-outlook">' + day + '</div>';
                if (hasEvs) {
                    html += '<div class="calendar-bookings-preview">';
                    for (var k = 0; k < Math.min(2, evs.length); k++) {
                        var ev = evs[k];
                        var t0 = (ev.start || '').substring(11, 16);
                        var t1 = (ev.end || '').substring(11, 16);
                        var tit = ev.title || '';
                        var shortT = tit.length > 18 ? tit.substring(0, 16) + '…' : tit;
                        html += '<div class="booking-preview-item" title="' + esc(tit) + '"><span class="booking-time">' + esc(t0 + '–' + t1) + '</span> <span class="booking-title">' + esc(shortT) + '</span></div>';
                    }
                    if (evs.length > 2) {
                        html += '<div class="booking-preview-more">+ ' + (evs.length - 2) + ' · toque para ver</div>';
                    }
                    html += '</div>';
                    html += '<div class="small text-success fw-semibold mt-1" style="font-size:0.68rem;">' + evs.length + ' compromisso(s)</div>';
                } else {
                    html += '<div class="calendar-day-empty text-muted small">—</div>';
                }
                html += '</div>';
            }
            gridEl.innerHTML = html;
            if (countEl) {
                countEl.textContent = String(monthEventCount(y, m)) + ' no mês';
            }
            gridEl.onclick = function (e) {
                var cell = e.target.closest('[data-dash-cal-date]');
                if (!cell || !gridEl.contains(cell)) return;
                var ds = cell.getAttribute('data-dash-cal-date');
                if (!ds) return;
                var y2 = parseInt(yearSel.value || '0', 10);
                var m2 = parseInt(monthSel.value || '0', 10);
                openDashDayAgenda(ds);
            };
            gridEl.onkeydown = function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var cell = e.target.closest('[data-dash-cal-date]');
                if (!cell || !gridEl.contains(cell)) return;
                e.preventDefault();
                var ds = cell.getAttribute('data-dash-cal-date');
                if (!ds) return;
                openDashDayAgenda(ds);
            };
        }
        function onFilterChange() {
            showMyCalMonthView();
            renderMyCalDashGrid();
        }
        if (monthSel) monthSel.addEventListener('change', onFilterChange);
        if (yearSel) yearSel.addEventListener('change', onFilterChange);
        if (dayBackBtn) {
            dayBackBtn.addEventListener('click', function () {
                showMyCalMonthView();
            });
        }
        if (myCalModal) {
            myCalModal.addEventListener('shown.bs.modal', function () {
                showMyCalMonthView();
                if (monthSel) monthSel.value = String(currentMonthNumber);
                if (yearSel) yearSel.value = String(currentYearNumber);
                renderMyCalDashGrid();
            });
        }
    })();
    <?php endif; ?>

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