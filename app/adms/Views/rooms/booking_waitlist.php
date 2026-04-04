<?php
use App\adms\Helpers\FormatHelper;

$waitlistStatusMap = [
    'waiting' => ['warning', 'Aguardando vaga'],
    'notified' => ['info', 'Notificado'],
    'accepted' => ['success', 'Confirmado'],
    'expired' => ['secondary', 'Expirado'],
    'cancelled' => ['danger', 'Cancelado'],
];
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Lista de Espera</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reserva de Salas</li>
            <li class="breadcrumb-item active">Lista de Espera</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header rooms-card-header d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
            <span><i class="fas fa-clock me-2"></i>Fila por horário desejado</span>
            <div class="rooms-card-header-actions d-flex flex-wrap gap-1">
                <?php if (in_array('ListBookings', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-bookings" class="btn btn-sm btn-outline-secondary w-100 w-sm-auto">
                        <i class="fas fa-calendar-check me-1"></i>Reservas
                    </a>
                <?php } ?>
                <?php if (in_array('RoomCalendar', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>room-calendar" class="btn btn-sm btn-primary w-100 w-sm-auto">
                        <i class="fas fa-calendar me-1"></i>Calendário
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (empty($this->data['is_full_access'])): ?>
                <p class="text-muted small mb-3 mb-md-2">
                    <i class="fas fa-user me-1"></i>Mostrando apenas as suas inscrições na fila.
                </p>
            <?php endif; ?>

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
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $statusOpts = [
                            'waiting' => 'Aguardando',
                            'notified' => 'Notificado',
                            'accepted' => 'Confirmado',
                            'expired' => 'Expirado',
                            'cancelled' => 'Cancelado',
                        ];
                        foreach ($statusOpts as $val => $label):
                        ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= (($this->data['filters']['status'] ?? '') === $val) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="start_date" class="form-label mb-1">Data (de)</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="<?= htmlspecialchars($this->data['filters']['start_date'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="end_date" class="form-label mb-1">Data (até)</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= htmlspecialchars($this->data['filters']['end_date'] ?? '') ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-1">
                    <button type="submit" class="btn btn-primary w-100" title="Filtrar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if (empty($this->data['waitlist'])): ?>
                <div class="alert alert-info mb-0" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma entrada na lista de espera com os filtros atuais.
                </div>
            <?php else: ?>

                <div class="d-none d-md-block table-responsive">
                    <table class="table table-striped table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Prioridade</th>
                                <th>Sala</th>
                                <?php if (!empty($this->data['is_full_access'])): ?>
                                    <th>Solicitante</th>
                                <?php endif; ?>
                                <th>Início desejado</th>
                                <th>Fim desejado</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['waitlist'] as $row): ?>
                                <?php
                                $st = (string)($row['status'] ?? '');
                                [$stClass, $stLabel] = $waitlistStatusMap[$st] ?? ['secondary', $st];
                                ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= (int)($row['priority'] ?? 0) ?></span></td>
                                    <td><?= htmlspecialchars($row['room_name'] ?? '') ?></td>
                                    <?php if (!empty($this->data['is_full_access'])): ?>
                                        <td><?= htmlspecialchars($row['user_name'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <td><?= FormatHelper::formatDate($row['desired_start_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td><?= FormatHelper::formatDate($row['desired_end_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td><span class="badge bg-<?= $stClass ?>"><?= htmlspecialchars($stLabel) ?></span></td>
                                    <td>
                                        <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= (int)($row['room_id'] ?? 0) ?>"
                                               class="btn btn-sm btn-outline-success" title="Calendário da sala">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none">
                    <?php foreach ($this->data['waitlist'] as $row): ?>
                        <?php
                        $st = (string)($row['status'] ?? '');
                        [$stClass, $stLabel] = $waitlistStatusMap[$st] ?? ['secondary', $st];
                        ?>
                        <div class="card rooms-mobile-card shadow-sm mb-3">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="fw-semibold"><?= htmlspecialchars($row['room_name'] ?? '') ?></div>
                                    <span class="badge bg-<?= $stClass ?>"><?= htmlspecialchars($stLabel) ?></span>
                                </div>
                                <?php if (!empty($this->data['is_full_access'])): ?>
                                    <div class="small mb-2"><i class="fas fa-user text-muted me-1"></i><?= htmlspecialchars($row['user_name'] ?? '') ?></div>
                                <?php endif; ?>
                                <div class="small mb-1">
                                    <span class="rooms-mobile-label">Horário desejado</span><br>
                                    <?= FormatHelper::formatDate($row['desired_start_datetime'] ?? '', 'd/m/Y H:i') ?>
                                    &ndash;
                                    <?= FormatHelper::formatDate($row['desired_end_datetime'] ?? '', 'H:i') ?>
                                </div>
                                <div class="small text-muted mb-2">Prioridade na fila: <?= (int)($row['priority'] ?? 0) ?></div>
                                <div class="rooms-mobile-actions">
                                    <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])) { ?>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= (int)($row['room_id'] ?? 0) ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-door-open me-1"></i>Abrir sala
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
