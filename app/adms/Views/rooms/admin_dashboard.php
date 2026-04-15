<?php
$totalRooms = $this->data['total_rooms'] ?? 0;
$totalBookings = $this->data['total_bookings'] ?? 0;
$monthBookings = $this->data['month_bookings'] ?? 0;
$confirmedBookings = $this->data['confirmed_bookings'] ?? 0;
$pendingBookings = $this->data['pending_bookings'] ?? 0;
$cancelledBookings = $this->data['cancelled_bookings'] ?? 0;
$bookingsByRoom = $this->data['bookings_by_room'] ?? [];
$recentBookings = $this->data['recent_bookings'] ?? [];
$occupancyRate = $this->data['occupancy_rate'] ?? 0;
$totalHours = $this->data['total_hours'] ?? 0;
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Dashboard de Reservas</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a>
            </li>
            <li class="breadcrumb-item">Dashboard</li>
        </ol>
    </div>
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if (!empty($this->data['room_external_calendar_status'])): ?>
        <div class="alert alert-light border small mb-4 mb-md-3" role="status">
            <i class="fas fa-link text-muted me-2"></i>
            <strong>Calendários externos:</strong>
            <?= htmlspecialchars((string) $this->data['room_external_calendar_status']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Salas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalRooms ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-door-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Reservas Confirmadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $confirmedBookings ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Reservas Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pendingBookings ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Reservas do Mês
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $monthBookings ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Taxa de Ocupação -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Taxa de Ocupação (Últimos 30 dias)</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="h2 mb-0 font-weight-bold text-primary"><?= $occupancyRate ?>%</div>
                        <div class="text-muted small"><?= $totalHours ?> horas de reserva</div>
                    </div>
                    <div class="progress mt-3" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: <?= $occupancyRate ?>%" 
                             aria-valuenow="<?= $occupancyRate ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status das Reservas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center">
                                <div class="h4 mb-0 text-success"><?= $confirmedBookings ?></div>
                                <div class="text-muted small">Confirmadas</div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center">
                                <div class="h4 mb-0 text-warning"><?= $pendingBookings ?></div>
                                <div class="text-muted small">Pendentes</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 text-danger"><?= $cancelledBookings ?></div>
                                <div class="text-muted small">Canceladas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 mb-0 text-info"><?= $totalBookings ?></div>
                                <div class="text-muted small">Total</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reservas por Sala -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Reservas por Sala</h6>
                </div>
                <div class="card-body">
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sala</th>
                                    <th>Localização</th>
                                    <th>Capacidade</th>
                                    <th>Total de Reservas</th>
                                    <th>Reservas do Mês</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookingsByRoom)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhuma sala encontrada</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookingsByRoom as $roomData): 
                                        $room = $roomData['room'];
                                    ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-door-open me-2"></i>
                                                <strong><?= htmlspecialchars($room['name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($room['location'] ?? '-') ?></td>
                                            <td><?= $room['capacity'] ?> pessoas</td>
                                            <td>
                                                <span class="badge bg-primary"><?= $roomData['count'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $roomData['month_count'] ?></span>
                                            </td>
                                            <td>
                                                <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= $room['id'] ?>" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-calendar-plus"></i> Reservar
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array('ViewMeetingRoom', $this->data['buttonPermission'] ?? [])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-meeting-room/<?= $room['id'] ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($bookingsByRoom)): ?>
                        <div class="d-md-none">
                            <?php foreach ($bookingsByRoom as $roomData):
                                $room = $roomData['room'];
                            ?>
                                <div class="card rooms-mobile-card shadow-sm mb-3">
                                    <div class="card-body py-3">
                                        <div class="fw-semibold mb-2"><i class="fas fa-door-open me-2 text-muted"></i><?= htmlspecialchars($room['name']) ?></div>
                                        <div class="small mb-1"><span class="rooms-mobile-label">Local</span><br><?= htmlspecialchars($room['location'] ?? '—') ?></div>
                                        <div class="small mb-2"><?= (int)$room['capacity'] ?> pessoas &middot;
                                            <span class="badge bg-primary"><?= (int)$roomData['count'] ?></span> total
                                            <span class="badge bg-info"><?= (int)$roomData['month_count'] ?></span> no mês
                                        </div>
                                        <div class="rooms-mobile-actions">
                                            <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= $room['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-calendar-plus me-1"></i>Reservar</a>
                                            <?php endif; ?>
                                            <?php if (in_array('ViewMeetingRoom', $this->data['buttonPermission'] ?? [])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-meeting-room/<?= $room['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye me-1"></i>Ver sala</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reservas Recentes -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Reservas Recentes</h6>
                </div>
                <div class="card-body">
                    <div class="d-none d-md-block table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Sala</th>
                                    <th>Título</th>
                                    <th>Responsável</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentBookings)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nenhuma reserva recente</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                        <tr>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($booking['start_datetime'])) ?><br>
                                                <small class="text-muted">até <?= date('H:i', strtotime($booking['end_datetime'])) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($booking['room_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($booking['title']) ?></td>
                                            <td><?= htmlspecialchars($booking['user_name'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = match($booking['status']) {
                                                    'confirmed' => 'success',
                                                    'pending' => 'warning',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($booking['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (in_array('ViewBooking', $this->data['buttonPermission'] ?? [])): ?>
                                                    <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= $booking['id'] ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($recentBookings)): ?>
                        <div class="d-md-none">
                            <?php foreach ($recentBookings as $booking): ?>
                                <?php
                                $statusClass = match($booking['status']) {
                                    'confirmed' => 'success',
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <div class="card rooms-mobile-card shadow-sm mb-3">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div class="fw-semibold"><?= htmlspecialchars($booking['title']) ?></div>
                                            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($booking['status']) ?></span>
                                        </div>
                                        <div class="small mb-1"><?= date('d/m/Y H:i', strtotime($booking['start_datetime'])) ?> – <?= date('H:i', strtotime($booking['end_datetime'])) ?></div>
                                        <div class="small mb-1"><span class="rooms-mobile-label">Sala</span><br><?= htmlspecialchars($booking['room_name'] ?? '—') ?></div>
                                        <div class="small mb-2"><span class="rooms-mobile-label">Responsável</span><br><?= htmlspecialchars($booking['user_name'] ?? '—') ?></div>
                                        <div class="rooms-mobile-actions">
                                            <?php if (in_array('ViewBooking', $this->data['buttonPermission'] ?? [])): ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= $booking['id'] ?>" class="btn btn-sm btn-info"><i class="fas fa-eye me-1"></i>Abrir</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
</style>

