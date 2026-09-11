<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\ImageHelper;
use App\adms\Helpers\PositionDisplayHelper;

$info = $this->data['employee_info'] ?? [];
$nome = (string) ($info['name'] ?? '');
$dep = (string) ($info['dep_name'] ?? 'N/A');
$cargo = PositionDisplayHelper::formatForDisplay((string) ($info['pos_name'] ?? '')) ?: 'N/A';
$email = (string) ($info['email'] ?? '');
$status = (string) ($info['status'] ?? 'N/A');
$statusAtivo = $status === 'Ativo';
$userId = (int) ($info['id'] ?? 0);
$admissao = !empty($info['data_admissao']) ? date('d/m/Y', strtotime((string) $info['data_admissao'])) : null;
$tenure = $this->data['total_tenure'] ?? null;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Portal do Colaborador</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Portal do Colaborador</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($info !== []): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="card-body p-4"
                 style="background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 50%, #f8fafc 100%);">
                <div class="d-flex flex-wrap align-items-start gap-3 mb-4">
                    <div class="flex-shrink-0">
                        <?php
                        if (ImageHelper::userImageExists($userId, $info['image'] ?? null)) {
                            echo ImageHelper::displayImage(
                                'users/' . $userId . '/' . (string) $info['image'],
                                [
                                    'alt' => 'Foto de ' . $nome,
                                    'class' => 'rounded-circle shadow-sm border border-white border-3',
                                    'style' => 'width:72px;height:72px;object-fit:cover;',
                                ],
                                'icon_user.png',
                                'users'
                            );
                        } else {
                            echo ImageHelper::renderInitialsAvatar($nome, 72, [
                                'class' => 'shadow-sm border border-white border-3',
                            ]);
                        }
                        ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h5 class="mb-0 fw-semibold text-truncate"><?= htmlspecialchars($nome) ?></h5>
                            <span class="badge rounded-pill <?= $statusAtivo ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </div>
                        <p class="mb-2 text-muted small">
                            <?= htmlspecialchars($cargo) ?>
                            <?php if ($dep !== '' && $dep !== 'N/A'): ?>
                                <span class="mx-1">·</span><?= htmlspecialchars($dep) ?>
                            <?php endif; ?>
                        </p>
                        <?php if ($email !== ''): ?>
                            <a href="mailto:<?= htmlspecialchars($email) ?>" class="small text-decoration-none">
                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($email) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3">
                    <?php if (!empty($tenure['formatted'])): ?>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center gap-2 mb-2 text-success">
                                        <span class="rounded-2 bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                                              style="width:2rem;height:2rem;">
                                            <i class="fas fa-hourglass-half fa-sm"></i>
                                        </span>
                                        <span class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Tempo de casa</span>
                                    </div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars((string) $tenure['formatted']) ?></p>
                                    <?php if (!empty($tenure['total_periodos'])): ?>
                                        <small class="text-muted"><?= (int) $tenure['total_periodos'] ?> período(s)</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($admissao !== null): ?>
                        <div class="col-6 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body py-3">
                                    <div class="d-flex align-items-center gap-2 mb-2 text-primary">
                                        <span class="rounded-2 bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                                              style="width:2rem;height:2rem;">
                                            <i class="fas fa-calendar-check fa-sm"></i>
                                        </span>
                                        <span class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Admissão</span>
                                    </div>
                                    <p class="mb-0 fw-semibold"><?= htmlspecialchars($admissao) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="col-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2 text-info">
                                    <span class="rounded-2 bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                                          style="width:2rem;height:2rem;">
                                        <i class="fas fa-building fa-sm"></i>
                                    </span>
                                    <span class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Departamento</span>
                                </div>
                                <p class="mb-0 fw-semibold"><?= htmlspecialchars($dep) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2 text-warning">
                                    <span class="rounded-2 bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center"
                                          style="width:2rem;height:2rem;">
                                        <i class="fas fa-user-tie fa-sm"></i>
                                    </span>
                                    <span class="small text-muted text-uppercase fw-semibold" style="letter-spacing:.03em;">Cargo</span>
                                </div>
                                <p class="mb-0 fw-semibold"><?= htmlspecialchars($cargo) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $permsPortal = $this->data['buttonPermission'] ?? [];
    $canVagasInternas = in_array('VagasInternas', $permsPortal, true);
    $canMeusEpis = in_array('MyEpiDeliveries', $permsPortal, true);
    $canMeusTreinamentos = in_array('MySstTreinamentos', $permsPortal, true);
    $canChamados = in_array('ListEmployeeTickets', $permsPortal, true);
    $canSolicitacoes = in_array('ListEmployeeRequests', $permsPortal, true);
    $canDocumentos = in_array('MyPayrollDocuments', $permsPortal, true);
    $sstPendentesCount = (int) ($this->data['sst_treinamentos_pendentes_count'] ?? 0);
    $epiPendentesCount = (int) ($this->data['sst_epi_fichas_pendentes_count'] ?? 0);
    ?>
    <div class="row g-4">
        <?php if ($canSolicitacoes): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-primary shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_requests'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Solicitações</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="btn btn-primary btn-sm mt-2">
                        Ver Todas
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canChamados): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-warning shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-ticket-alt fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0"><?= $this->data['total_tickets'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Chamados</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="btn btn-warning btn-sm mt-2">
                        Ver Todos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canSolicitacoes): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-info shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x text-info mb-3"></i>
                    <h3 class="mb-0"><?= count($this->data['pending_requests'] ?? []) ?></h3>
                    <p class="text-muted mb-0">Solicitações pendentes</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests?status=pending" class="btn btn-info btn-sm mt-2">
                        Ver Pendentes
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canMeusTreinamentos): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-warning shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-graduation-cap fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0"><?= $sstPendentesCount ?></h3>
                    <p class="text-muted mb-0">Treinamentos SST</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>my-sst-treinamentos" class="btn btn-warning btn-sm mt-2">
                        Meus treinamentos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canMeusEpis): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-danger shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-hard-hat fa-3x text-danger mb-3"></i>
                    <h3 class="mb-0"><?= $epiPendentesCount ?></h3>
                    <p class="text-muted mb-0">EPIs a assinar</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>my-epi-deliveries" class="btn btn-outline-danger btn-sm mt-2">
                        Meus EPIs
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($canVagasInternas): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-success shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-briefcase fa-3x text-success mb-3"></i>
                    <h3 class="mb-0"><?= (int) ($this->data['total_vagas_internas'] ?? 0) ?></h3>
                    <p class="text-muted mb-0">Vagas internas</p>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>vagas-internas" class="btn btn-success btn-sm mt-2">
                        Ver oportunidades
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($canMeusTreinamentos && $sstPendentesCount > 0): ?>
    <div class="card mb-4 mt-4 border-light shadow">
        <div class="card-header"><span><i class="fas fa-graduation-cap me-2"></i>Treinamentos SST pendentes</span></div>
        <div class="card-body p-0">
            <?php foreach (array_slice($this->data['sst_treinamentos_pendentes'] ?? [], 0, 8) as $t): ?>
                <div class="px-3 py-2 border-bottom small">
                    <?= htmlspecialchars($t['treinamento_nome'] ?? '') ?>
                    <span class="badge bg-<?= htmlspecialchars($t['situacao_badge'] ?? 'warning') ?> ms-1"><?= htmlspecialchars($t['situacao_label'] ?? 'Pendente') ?></span>
                    <?php if (!empty($t['motivo'])): ?>
                        <span class="text-muted"> · <?= htmlspecialchars((string) $t['motivo']) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="px-3 py-2">
                <a href="<?php echo $_ENV['URL_ADM']; ?>my-sst-treinamentos" class="small">Ver todos</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4 mt-2">
        <!-- Solicitações Pendentes -->
        <div class="col-md-6">
            <div class="card mb-4 border-light shadow">
                <div class="card-header hstack gap-2">
                    <span><i class="fas fa-file-alt me-2"></i>Solicitações Pendentes</span>
                    <span class="ms-auto">
                        <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Nova
                            </a>
                        <?php } ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['pending_requests'])): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nenhuma solicitação pendente.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($this->data['pending_requests'] as $request): ?>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-request/<?= $request['id'] ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($request['title']) ?></h6>
                                        <small>
                                            <span class="badge bg-warning"><?= htmlspecialchars($request['status']) ?></span>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <small>
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($request['request_type']) ?>
                                            <?php if ($request['start_date']): ?>
                                                | <i class="fas fa-calendar me-1"></i><?= FormatHelper::formatDate($request['start_date']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chamados Abertos -->
        <div class="col-md-6">
            <div class="card mb-4 border-light shadow">
                <div class="card-header hstack gap-2">
                    <span><i class="fas fa-ticket-alt me-2"></i>Chamados Abertos</span>
                    <span class="ms-auto">
                        <?php if (in_array('CreateEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                            <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-ticket" class="btn btn-sm btn-success">
                                <i class="fas fa-plus"></i> Novo
                            </a>
                        <?php } ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (empty($this->data['open_tickets'])): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>Nenhum chamado aberto.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($this->data['open_tickets'] as $ticket): ?>
                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-employee-ticket/<?= $ticket['id'] ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($ticket['title']) ?></h6>
                                        <small>
                                            <span class="badge bg-<?= $ticket['priority'] === 'urgent' ? 'danger' : ($ticket['priority'] === 'high' ? 'warning' : 'info') ?>">
                                                <?= htmlspecialchars($ticket['priority']) ?>
                                            </span>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-muted">
                                        <small>
                                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($ticket['ticket_type']) ?>
                                            | <i class="fas fa-clock me-1"></i><?= FormatHelper::formatDate($ticket['created_at']) ?>
                                        </small>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-bolt me-2"></i>Ações Rápidas</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request?request_type=vacation" class="btn btn-outline-primary w-100">
                            <i class="fas fa-umbrella-beach me-2"></i>Solicitar Férias
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('CreateEmployeeRequest', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-request?request_type=time_off" class="btn btn-outline-info w-100">
                            <i class="fas fa-calendar-times me-2"></i>Solicitar Afastamento
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('CreateEmployeeTicket', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>create-employee-ticket" class="btn btn-outline-warning w-100">
                            <i class="fas fa-ticket-alt me-2"></i>Abrir Chamado
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('ListEmployeeRequests', $this->data['buttonPermission'] ?? [])) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-requests" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-list me-2"></i>Ver Todas Solicitações
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('MySstTreinamentos', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>my-sst-treinamentos" class="btn btn-outline-warning w-100">
                            <i class="fas fa-graduation-cap me-2"></i>Meus treinamentos SST
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('MyEpiDeliveries', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>my-epi-deliveries" class="btn btn-outline-danger w-100">
                            <i class="fas fa-hard-hat me-2"></i>Meus EPIs
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('VagasInternas', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>vagas-internas" class="btn btn-outline-success w-100">
                            <i class="fas fa-briefcase me-2"></i>Vagas internas
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('ListEmployeeTickets', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-employee-tickets" class="btn btn-outline-warning w-100">
                            <i class="fas fa-ticket-alt me-2"></i>Meus Chamados
                        </a>
                    </div>
                <?php } ?>
                <?php if (in_array('MyPayrollDocuments', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>my-payroll-documents" class="btn btn-outline-dark w-100">
                            <i class="fas fa-file-invoice-dollar me-2"></i>Meus documentos (folha)
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

