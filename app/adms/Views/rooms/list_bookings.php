<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Reservas de Salas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Reservas</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-calendar-alt me-2"></i>Listar Reservas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateBooking', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-booking" class="btn btn-success btn-sm mb-1">
                        <i class="fas fa-plus me-1"></i>Nova Reserva
                    </a>
                <?php } ?>
                <?php if (in_array('RoomCalendar', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>room-calendar" class="btn btn-primary btn-sm mb-1">
                        <i class="fas fa-calendar me-1"></i>Calendário
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="room_id" class="form-label mb-1">Sala</label>
                    <select name="room_id" id="room_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['rooms'] ?? []) as $room): ?>
                            <option value="<?= $room['id'] ?>" <?= (($this->data['filters']['room_id'] ?? '') == $room['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($room['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="pending" <?= (($this->data['filters']['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendente</option>
                        <option value="confirmed" <?= (($this->data['filters']['status'] ?? '') === 'confirmed') ? 'selected' : '' ?>>Confirmada</option>
                        <option value="in_progress" <?= (($this->data['filters']['status'] ?? '') === 'in_progress') ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="completed" <?= (($this->data['filters']['status'] ?? '') === 'completed') ? 'selected' : '' ?>>Concluída</option>
                        <option value="cancelled" <?= (($this->data['filters']['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="start_date" class="form-label mb-1">Data Início</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?= htmlspecialchars($this->data['filters']['start_date'] ?? '') ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="end_date" class="form-label mb-1">Data Fim</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?= htmlspecialchars($this->data['filters']['end_date'] ?? '') ?>">
                </div>
                
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if (empty($this->data['bookings'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma reserva encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Sala</th>
                                <th>Solicitante</th>
                                <th>Data/Hora Início</th>
                                <th>Data/Hora Fim</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['bookings'] as $booking): ?>
                                <?php
                                $statusClass = match($booking['status']) {
                                    'pending' => 'warning',
                                    'confirmed' => 'success',
                                    'in_progress' => 'info',
                                    'completed' => 'secondary',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                $statusText = match($booking['status']) {
                                    'pending' => 'Pendente',
                                    'confirmed' => 'Confirmada',
                                    'in_progress' => 'Em Andamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                    default => $booking['status']
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($booking['room_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($booking['user_name'] ?? '') ?></td>
                                    <td><?= FormatHelper::formatDate($booking['start_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td><?= FormatHelper::formatDate($booking['end_datetime'] ?? '', 'd/m/Y H:i') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <?php if (in_array('ViewBooking', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-booking/<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('UpdateBooking', $this->data['buttonPermission'] ?? []) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-booking/<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('CancelBooking', $this->data['buttonPermission'] ?? []) && $booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>cancel-booking/<?= $booking['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Tem certeza que deseja cancelar esta reserva?');" 
                                                   title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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

