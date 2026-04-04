<?php
use App\adms\Helpers\FormatHelper;

$s = $this->data['summary_by_status'] ?? [];
$labels = [
    'pending' => ['Pendente', 'warning'],
    'confirmed' => ['Confirmada', 'success'],
    'in_progress' => ['Em andamento', 'info'],
    'completed' => ['Concluída', 'secondary'],
    'cancelled' => ['Cancelada', 'danger'],
];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Relatórios de Reservas</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item active">Relatórios</li>
        </ol>
    </div>

    <div class="card mb-3 border-light shadow">
        <div class="card-body py-3">
            <div class="small text-muted mb-2">
                <i class="fas fa-info-circle me-1"></i>
                Contagens por status no período e sala selecionados (ignora o filtro “Status” da tabela abaixo).
            </div>
            <div class="row g-2">
                <?php foreach ($labels as $key => $pair): ?>
                    <?php [$lbl, $cls] = $pair; ?>
                    <div class="col-6 col-sm-4 col-md">
                        <div class="border rounded p-2 text-center h-100 bg-light">
                            <div class="small text-muted"><?= htmlspecialchars($lbl) ?></div>
                            <div class="fs-5 fw-semibold text-<?= $cls === 'secondary' ? 'secondary' : $cls ?>">
                                <?= (int)($s[$key] ?? 0) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
            <span><i class="fas fa-chart-bar me-2"></i>Detalhamento</span>
            <div class="rooms-card-header-actions d-flex flex-wrap gap-1">
                <?php if (in_array('ListBookings', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="btn btn-sm btn-outline-secondary w-100 w-sm-auto">
                        <i class="fas fa-list me-1"></i>Lista de reservas
                    </a>
                <?php } ?>
                <?php if (in_array('AdminBookingDashboard', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>admin-booking-dashboard" class="btn btn-sm btn-outline-primary w-100 w-sm-auto">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="room_id" class="form-label mb-1">Sala</label>
                    <select name="room_id" id="room_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['rooms'] ?? []) as $room): ?>
                            <option value="<?= (int)$room['id'] ?>" <?= (($this->data['filters']['room_id'] ?? '') == $room['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="status" class="form-label mb-1">Status (tabela)</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pending" <?= (($this->data['filters']['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendente</option>
                        <option value="confirmed" <?= (($this->data['filters']['status'] ?? '') === 'confirmed') ? 'selected' : '' ?>>Confirmada</option>
                        <option value="in_progress" <?= (($this->data['filters']['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="completed" <?= (($this->data['filters']['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Concluída</option>
                        <option value="cancelled" <?= (($this->data['filters']['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="start_date" class="form-label mb-1">Data início</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="<?= htmlspecialchars($this->data['filters']['start_date'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="end_date" class="form-label mb-1">Data fim</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= htmlspecialchars($this->data['filters']['end_date'] ?? '') ?>" required>
                </div>
                <div class="col-12 col-sm-6 col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="Aplicar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if (empty($this->data['bookings'])): ?>
                <div class="alert alert-info mb-0" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma reserva neste recorte (ajuste período ou filtros).
                </div>
            <?php else: ?>

                <div class="d-none d-md-block table-responsive">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Sala</th>
                                <th>Solicitante</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['bookings'] as $booking): ?>
                                <?php
                                $statusClass = match($booking['status'] ?? '') {
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'in_progress' => 'info',
                                    'completed' => 'secondary',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                $statusText = match($booking['status'] ?? '') {
                                    'pending' => 'Pendente',
                                    'confirmed' => 'Confirmada',
                                    'in_progress' => 'Em Andamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                    default => $booking['status'] ?? ''
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($booking['room_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($booking['user_name'] ?? '') ?></td>
                                    <td><?= FormatHelper::formatDate($booking['start_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td><?= FormatHelper::formatDate($booking['end_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td><span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                    <td>
                                        <?php if (in_array('ViewBooking', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= (int)($booking['id'] ?? 0) ?>" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none">
                    <?php foreach ($this->data['bookings'] as $booking): ?>
                        <?php
                        $statusClass = match($booking['status'] ?? '') {
                            'pending' => 'warning',
                            'confirmed' => 'success',
                            'in_progress' => 'info',
                            'completed' => 'secondary',
                            'cancelled' => 'danger',
                            default => 'secondary'
                        };
                        $statusText = match($booking['status'] ?? '') {
                            'pending' => 'Pendente',
                            'confirmed' => 'Confirmada',
                            'in_progress' => 'Em Andamento',
                            'completed' => 'Concluída',
                            'cancelled' => 'Cancelada',
                            default => $booking['status'] ?? ''
                        };
                        ?>
                        <div class="card rooms-mobile-card shadow-sm mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="fw-semibold text-break"><?= htmlspecialchars($booking['title'] ?? '') ?></div>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span>
                                </div>
                                <div class="small mb-1"><i class="fas fa-door-open text-muted me-1"></i><?= htmlspecialchars($booking['room_name'] ?? '') ?></div>
                                <div class="small mb-2"><i class="fas fa-user text-muted me-1"></i><?= htmlspecialchars($booking['user_name'] ?? '') ?></div>
                                <div class="small text-muted mb-3">
                                    <?= FormatHelper::formatDate($booking['start_datetime'] ?? '', 'd/m/Y H:i') ?>
                                    &ndash;
                                    <?= FormatHelper::formatDate($booking['end_datetime'] ?? '', 'd/m/Y H:i') ?>
                                </div>
                                <div class="rooms-mobile-actions">
                                    <?php if (in_array('ViewBooking', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= (int)($booking['id'] ?? 0) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i>Ver reserva
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($this->data['pagination'])): ?>
                    <div class="mt-3">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
