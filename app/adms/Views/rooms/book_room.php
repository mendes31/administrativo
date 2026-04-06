<?php
use App\adms\Helpers\FormatHelper;
$room = $this->data['room'] ?? [];
$view = $this->data['view'] ?? 'month';
$bookings = $this->data['bookings'] ?? [];

// Meses em português
$mesesPT = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Inicializar variáveis
$selectedMonth = '';
$monthName = '';
$prevMonth = '';
$nextMonth = '';
$startOfWeek = null;
$endOfWeek = null;
$prevWeek = '';
$nextWeek = '';
$weekDays = [];
$daysInMonth = 0;
$firstWeekday = 1;

if ($view === 'week') {
    // Visualização semanal
    $startOfWeek = $this->data['start_of_week'] ?? new \DateTime();
    $endOfWeek = $this->data['end_of_week'] ?? new \DateTime();
    $prevWeek = $this->data['prev_week'] ?? '';
    $nextWeek = $this->data['next_week'] ?? '';
    
    // Preparar dados da semana
    $currentDate = clone $startOfWeek;
    for ($i = 0; $i < 7; $i++) {
        $weekDays[] = clone $currentDate;
        $currentDate->modify('+1 day');
    }
} else {
    // Visualização mensal
    $selectedMonth = $this->data['selected_month'] ?? date('Y-m');
    $firstDay = $this->data['first_day'] ?? new \DateTime();
    $lastDay = $this->data['last_day'] ?? new \DateTime();
    $prevMonth = $this->data['prev_month'] ?? '';
    $nextMonth = $this->data['next_month'] ?? '';
    
    // Calcular dias do mês
    $daysInMonth = (int)$lastDay->format('d');
    $firstWeekday = (int)$firstDay->format('N'); // 1=Monday, 7=Sunday
    
    $mesNumero = (int)$firstDay->format('m');
    $ano = $firstDay->format('Y');
    $monthName = $mesesPT[$mesNumero] . ' de ' . $ano;
}

// Horários do dia (8h às 18h, intervalos de 30 minutos)
$timeSlots = [];
for ($hour = 8; $hour < 18; $hour++) {
    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
}

// Preparar dados de reservas por data para o calendário
$bookingsByDate = [];
$bookingsCountByDate = [];
foreach ($bookings as $date => $dateBookings) {
    $bookingsByDate[$date] = $dateBookings;
    $bookingsCountByDate[$date] = count($dateBookings);
}

// Preparar matriz de horários ocupados por data/hora
$bookedSlots = [];
foreach ($bookings as $date => $dateBookings) {
    foreach ($dateBookings as $booking) {
        $start = new \DateTime($booking['start_datetime']);
        $end = new \DateTime($booking['end_datetime']);
        $current = clone $start;
        
        while ($current < $end) {
            $timeKey = $current->format('H:i');
            $dateKey = $current->format('Y-m-d');
            $bookedSlots[$dateKey][$timeKey] = $booking;
            $current->modify('+30 minutes');
        }
    }
}
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Reservar Sala: <?= htmlspecialchars($room['name'] ?? '') ?></h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a>
            </li>
            <li class="breadcrumb-item">Reservar</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header" style="background-color: #2E9263; color: white;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-0"><i class="fas fa-door-open me-2"></i><?= htmlspecialchars($room['name']) ?></h5>
                    <small class="text-white-50">
                        <?php if (!empty($room['location'])): ?>
                            <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($room['location']) ?>
                        <?php endif; ?>
                        <span class="ms-2"><i class="fas fa-users me-1"></i><?= $room['capacity'] ?> pessoas</span>
                    </small>
                </div>
                <div class="d-flex gap-2 mt-2 mt-md-0">
                    <div class="btn-group">
                        <a href="?room_id=<?= $room['id'] ?>&view=month<?= isset($selectedMonth) ? '&month=' . $selectedMonth : '' ?>" 
                           class="btn btn-sm <?= $view === 'month' ? 'btn-light active' : 'btn-outline-light' ?>">
                            <i class="fas fa-calendar-alt me-1"></i> Mês
                        </a>
                        <a href="?room_id=<?= $room['id'] ?>&view=week<?= isset($selectedDate) ? '&date=' . $selectedDate : '' ?>" 
                           class="btn btn-sm <?= $view === 'week' ? 'btn-light active' : 'btn-outline-light' ?>">
                            <i class="fas fa-calendar-week me-1"></i> Semana
                        </a>
                    </div>
                    <div class="btn-group">
                        <?php if ($view === 'week'): 
                            $weekPeriod = $startOfWeek->format('d/m') . ' - ' . $endOfWeek->format('d/m/Y');
                        ?>
                            <a href="?room_id=<?= $room['id'] ?>&view=week&date=<?= $prevWeek ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light" disabled>
                                <strong><?= $weekPeriod ?></strong>
                            </button>
                            <a href="?room_id=<?= $room['id'] ?>&view=week&date=<?= $nextWeek ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="?room_id=<?= $room['id'] ?>&view=month&month=<?= $prevMonth ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light" disabled>
                                <strong><?= $monthName ?></strong>
                            </button>
                            <a href="?room_id=<?= $room['id'] ?>&view=month&month=<?= $nextMonth ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="alert alert-info m-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <?php if ($view === 'week'): ?>
                    <strong>Como reservar:</strong> Clique em um horário disponível (verde) para criar uma reserva. Horários ocupados (vermelho) permitem apenas entrar na lista de espera.
                <?php else: ?>
                    <strong>Como reservar:</strong> Clique duas vezes em uma data no calendário para ver os horários disponíveis e criar uma reserva.
                <?php endif; ?>
            </div>

            <?php if ($view === 'week'): ?>
                <!-- Calendário Semanal -->
                <div class="rooms-calendar-scroll">
                <?php include './app/adms/Views/rooms/book_room_week.php'; ?>
                </div>
            <?php else: ?>
                <!-- Calendário Mensal Estilo Outlook -->
            <div class="rooms-calendar-scroll">
            <div class="outlook-calendar p-3">
                <div class="calendar-grid-outlook">
                    <!-- Cabeçalho dos dias da semana -->
                    <div class="calendar-day-header-outlook">Segunda</div>
                    <div class="calendar-day-header-outlook">Terça</div>
                    <div class="calendar-day-header-outlook">Quarta</div>
                    <div class="calendar-day-header-outlook">Quinta</div>
                    <div class="calendar-day-header-outlook">Sexta</div>
                    <div class="calendar-day-header-outlook">Sábado</div>
                    <div class="calendar-day-header-outlook">Domingo</div>

                    <?php
                    // Dias vazios antes do primeiro dia do mês
                    for ($i = 1; $i < $firstWeekday; $i++) {
                        echo '<div class="calendar-day-outlook other-month"></div>';
                    }

                    // Dias do mês
                    $today = date('Y-m-d');
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $isToday = ($date === $today);
                        $dayClass = 'calendar-day-outlook';
                        
                        if ($isToday) {
                            $dayClass .= ' today';
                        }
                        
                        $dayBookings = $bookingsByDate[$date] ?? [];
                        $bookingsCount = $bookingsCountByDate[$date] ?? 0;
                        
                        echo '<div class="' . $dayClass . '" data-date="' . $date . '" data-day="' . $day . '">';
                        echo '<div class="calendar-day-number-outlook">' . $day . '</div>';
                        
                        // Prévia das reservas do dia
                        if ($bookingsCount > 0) {
                            echo '<div class="calendar-bookings-preview">';
                            $previewCount = 0;
                            foreach ($dayBookings as $booking) {
                                if ($previewCount >= 3) break; // Mostrar apenas 3 reservas
                                $startTime = date('H:i', strtotime($booking['start_datetime']));
                                $endTime = date('H:i', strtotime($booking['end_datetime']));
                                $title = htmlspecialchars($booking['title']);
                                $userName = htmlspecialchars($booking['user_name'] ?? '');
                                $shortTitle = strlen($title) > 20 ? substr($title, 0, 17) . '...' : $title;
                                $shortUser = strlen($userName) > 15 ? substr($userName, 0, 12) . '...' : $userName;
                                echo '<div class="booking-preview-item" title="' . $startTime . ' - ' . $endTime . ' ' . htmlspecialchars($booking['title']) . ' - ' . $userName . '">';
                                echo '<span class="booking-time">' . $startTime . ' - ' . $endTime . '</span> ';
                                echo '<span class="booking-title">' . $shortTitle . '</span>';
                                if (!empty($userName)) {
                                    echo '<span class="booking-user"> - ' . $shortUser . '</span>';
                                }
                                echo '</div>';
                                $previewCount++;
                            }
                            if ($bookingsCount > 3) {
                                echo '<div class="booking-preview-more">+ ' . ($bookingsCount - 3) . ' mais</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="calendar-day-empty">Disponível</div>';
                        }
                        
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Selecionar Horário do Dia -->
<div class="modal fade" id="dayTimeSlotsModal" tabindex="-1" aria-labelledby="dayTimeSlotsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2E9263; color: white;">
                <h5 class="modal-title" id="dayTimeSlotsModalLabel">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span id="modal-selected-date-text"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="mb-3">Horários Disponíveis e Ocupados</h6>
                        <div class="time-slots-container" id="time-slots-container">
                            <!-- Slots serão preenchidos via JavaScript -->
                        </div>
                    </div>
                    <div class="col-md-4">
                        <h6 class="mb-3">Reservas do Dia</h6>
                        <div id="day-bookings-list" class="day-bookings-list">
                            <!-- Lista de reservas será preenchida via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Criar Reserva Rápida -->
<div class="modal fade" id="createBookingModal" tabindex="-1" aria-labelledby="createBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?php echo $_ENV['URL_ADM']; ?>create-booking" method="POST" id="quickBookingForm">
                <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_quick_booking') ?>">
                <input type="hidden" name="from_quick_booking" value="1">
                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                <input type="hidden" name="start_datetime" id="modal_start_datetime">
                <input type="hidden" name="end_datetime" id="modal_end_datetime">
                
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="createBookingModalLabel">
                        <i class="fas fa-calendar-plus me-2"></i>Criar Reserva Rápida
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Sala</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($room['name']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora de Início <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="start_datetime_display" id="modal_start_datetime_display" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data e Hora de Fim <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="end_datetime_display" id="modal_end_datetime_display" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Título da Reunião <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="Ex: Reunião de Planejamento">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Descreva o objetivo da reunião..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Criar Reserva</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Lista de Espera -->
<div class="modal fade" id="waitlistModal" tabindex="-1" aria-labelledby="waitlistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?php echo $_ENV['URL_ADM']; ?>join-waitlist" method="POST">
                <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_join_waitlist') ?>">
                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                <input type="hidden" name="desired_start_datetime" id="waitlist_start_datetime">
                <input type="hidden" name="desired_end_datetime" id="waitlist_end_datetime">
                
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="waitlistModalLabel">
                        <i class="fas fa-clock me-2"></i>Entrar na Lista de Espera
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Este horário já está reservado. Você será notificado caso a reserva seja cancelada.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Horário Desejado</label>
                        <input type="text" class="form-control" id="waitlist_time_display" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duração Desejada (horas)</label>
                        <input type="number" name="duration_hours" class="form-control" min="0.5" max="8" step="0.5" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Entrar na Lista de Espera</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Calendário Estilo Outlook */
.outlook-calendar {
    background: #f8f9fa;
}

.calendar-grid-outlook {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
}

.calendar-day-header-outlook {
    background: #2E9263;
    color: white;
    text-align: center;
    padding: 0.75rem;
    font-weight: 600;
    font-size: 0.9rem;
}

.calendar-day-outlook {
    background: white;
    min-height: 120px;
    padding: 0.5rem;
    position: relative;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e9ecef;
}

.calendar-day-outlook:hover {
    background: #e7f5ef;
    border-color: #2E9263;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(46,146,99,0.2);
}

.calendar-day-outlook.other-month {
    background: #f8f9fa;
    opacity: 0.5;
}

.calendar-day-outlook.today {
    background: #e7f5ef;
    border: 2px solid #2E9263;
    font-weight: 600;
}

.calendar-day-number-outlook {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.calendar-day-outlook.today .calendar-day-number-outlook {
    color: #2E9263;
    font-size: 1.1rem;
}

.calendar-bookings-preview {
    font-size: 0.75rem;
    line-height: 1.4;
}

.booking-preview-item {
    background: #2E9263;
    color: white;
    padding: 0.2rem 0.4rem;
    margin-bottom: 0.2rem;
    border-radius: 3px;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: background 0.2s;
}

.booking-preview-item:hover {
    background: #258556;
}

.booking-time {
    font-weight: 600;
    margin-right: 0.3rem;
}

.booking-title {
    font-size: 0.7rem;
}

.booking-user {
    font-size: 0.65rem;
    opacity: 0.9;
    font-style: italic;
}

.booking-preview-more {
    color: #2E9263;
    font-weight: 600;
    font-size: 0.7rem;
    margin-top: 0.2rem;
    text-align: center;
}

.calendar-day-empty {
    color: #6c757d;
    font-size: 0.75rem;
    font-style: italic;
    text-align: center;
    margin-top: 1rem;
}

/* Container de Slots de Horário */
.time-slots-container {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
}

.time-slot-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.time-slot-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.time-slot-item.available {
    background: #d4edda;
    border-color: #28a745;
}

.time-slot-item.available:hover {
    background: #c3e6cb;
    border-color: #218838;
}

.time-slot-item.booked {
    background: #f8d7da;
    border-color: #dc3545;
    cursor: not-allowed;
}

.time-slot-item.booked:hover {
    background: #f5c6cb;
}

.time-slot-item.past {
    background: #e9ecef;
    opacity: 0.5;
    cursor: not-allowed;
}

.time-slot-time {
    font-weight: 600;
    min-width: 80px;
    font-size: 0.9rem;
}

.time-slot-info {
    flex: 1;
    margin-left: 1rem;
}

.time-slot-title {
    font-weight: 500;
    margin-bottom: 0.2rem;
}

.time-slot-user {
    font-size: 0.85rem;
    color: #6c757d;
}

/* Lista de Reservas do Dia */
.day-bookings-list {
    max-height: 500px;
    overflow-y: auto;
}

.day-booking-item {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
}

.day-booking-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.day-booking-time {
    font-weight: 600;
    color: #0078d4;
}

.day-booking-title {
    font-weight: 500;
    margin-bottom: 0.3rem;
}

.day-booking-user {
    font-size: 0.85rem;
    color: #6c757d;
}

.day-booking-actions .btn-action {
    font-size: 0.75rem;
}
</style>

<script>
// Dados das reservas (passados do PHP para JavaScript)
const bookingsData = <?= json_encode($bookingsByDate) ?>;
const bookedSlotsData = <?= json_encode($bookedSlots) ?>;
const timeSlots = <?= json_encode($timeSlots) ?>;
const roomId = <?= $room['id'] ?>;
const urlAdm = <?= json_encode(rtrim((string)($_ENV['URL_ADM'] ?? ''), '/') . '/') ?>;
const bookRoomCurrentUserId = <?= (int)($this->data['book_room_current_user_id'] ?? 0) ?>;
const bookRoomIsSuperAdmin = <?= !empty($this->data['book_room_is_super_admin']) ? 'true' : 'false' ?>;
<?php
$bpBookRoom = $this->data['buttonPermission'] ?? [];
$permUpdateBookRoom = in_array('UpdateBooking', $bpBookRoom, true);
$permCancelBookRoom = in_array('CancelBooking', $bpBookRoom, true);
?>
const permUpdateBooking = <?= $permUpdateBookRoom ? 'true' : 'false' ?>;
const permCancelBooking = <?= $permCancelBookRoom ? 'true' : 'false' ?>;

function canManageOwnBooking(booking) {
    const st = booking.status || '';
    if (st === 'cancelled' || st === 'completed') {
        return false;
    }
    const uid = booking.user_id ? parseInt(booking.user_id, 10) : 0;
    return bookRoomIsSuperAdmin || (uid > 0 && uid === bookRoomCurrentUserId);
}

document.addEventListener('DOMContentLoaded', function() {
    let clickTimer = null;
    
    // Clique duplo em uma data do calendário
    document.querySelectorAll('.calendar-day-outlook:not(.other-month)').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            if (!date) return;
            
            if (clickTimer === null) {
                clickTimer = setTimeout(() => {
                    clickTimer = null;
                }, 300);
            } else {
                clearTimeout(clickTimer);
                clickTimer = null;
                
                // Abrir modal de horários do dia
                openDayTimeSlotsModal(date);
            }
        });
    });
    
    // Sincronizar campos de data/hora no formulário de reserva
    document.getElementById('modal_start_datetime_display')?.addEventListener('change', function() {
        const dateTimeObj = new Date(this.value);
        const year = dateTimeObj.getFullYear();
        const month = String(dateTimeObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateTimeObj.getDate()).padStart(2, '0');
        const hours = String(dateTimeObj.getHours()).padStart(2, '0');
        const minutes = String(dateTimeObj.getMinutes()).padStart(2, '0');
        const formatted = `${year}-${month}-${day} ${hours}:${minutes}:00`;
        document.getElementById('modal_start_datetime').value = formatted;
    });
    
    document.getElementById('modal_end_datetime_display')?.addEventListener('change', function() {
        const dateTimeObj = new Date(this.value);
        const year = dateTimeObj.getFullYear();
        const month = String(dateTimeObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateTimeObj.getDate()).padStart(2, '0');
        const hours = String(dateTimeObj.getHours()).padStart(2, '0');
        const minutes = String(dateTimeObj.getMinutes()).padStart(2, '0');
        const formatted = `${year}-${month}-${day} ${hours}:${minutes}:00`;
        document.getElementById('modal_end_datetime').value = formatted;
    });
});

function openDayTimeSlotsModal(date) {
    const dateObj = new Date(date + 'T00:00:00');
    const dateText = dateObj.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    document.getElementById('modal-selected-date-text').textContent = dateText.charAt(0).toUpperCase() + dateText.slice(1);
    
    // Preencher slots de horário
    const container = document.getElementById('time-slots-container');
    container.innerHTML = '';
    
    const now = new Date();
    const selectedDate = new Date(date + 'T00:00:00');
    const isToday = selectedDate.toDateString() === now.toDateString();
    const isPast = selectedDate < new Date(now.toDateString());
    
    timeSlots.forEach(time => {
        const datetime = date + ' ' + time + ':00';
        const slotDateTime = new Date(datetime.replace(' ', 'T'));
        const isPastTime = isPast || (isToday && slotDateTime < now);
        const booking = bookedSlotsData[date]?.[time] || null;
        
        const slotDiv = document.createElement('div');
        slotDiv.className = 'time-slot-item';
        
        if (isPastTime) {
            slotDiv.classList.add('past');
        } else if (booking) {
            slotDiv.classList.add('booked');
        } else {
            slotDiv.classList.add('available');
        }
        
        slotDiv.dataset.date = date;
        slotDiv.dataset.time = time;
        slotDiv.dataset.datetime = datetime;
        
        if (booking) {
            slotDiv.dataset.bookingId = booking.id;
            slotDiv.dataset.bookingTitle = booking.title;
            slotDiv.innerHTML = `
                <div class="time-slot-time">${time}</div>
                <div class="time-slot-info">
                    <div class="time-slot-title">${escapeHtml(booking.title)}</div>
                    <div class="time-slot-user">Reservado por: ${escapeHtml(booking.user_name)}</div>
                </div>
            `;
            slotDiv.addEventListener('click', () => openWaitlistModal(datetime));
        } else {
            slotDiv.innerHTML = `
                <div class="time-slot-time">${time}</div>
                <div class="time-slot-info">
                    <div class="time-slot-title">Disponível</div>
                    <div class="time-slot-user">Clique para reservar</div>
                </div>
            `;
            if (!isPastTime) {
                slotDiv.addEventListener('click', () => openCreateBookingModal(datetime));
            }
        }
        
        container.appendChild(slotDiv);
    });
    
    // Preencher lista de reservas do dia
    const bookingsList = document.getElementById('day-bookings-list');
    bookingsList.innerHTML = '';
    
    const dayBookings = bookingsData[date] || [];
    if (dayBookings.length === 0) {
        bookingsList.innerHTML = '<div class="text-muted text-center p-3">Nenhuma reserva neste dia</div>';
    } else {
        dayBookings.forEach(booking => {
            const startTime = new Date(booking.start_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const endTime = new Date(booking.end_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const manage = canManageOwnBooking(booking);
            let actionsHtml = '';
            if (manage) {
                actionsHtml += `<a href="${urlAdm}view-booking/${booking.id}" class="btn btn-sm btn-outline-primary btn-action"><i class="fas fa-eye me-1"></i>Ver</a>`;
                if (permUpdateBooking) {
                    actionsHtml += `<a href="${urlAdm}update-booking/${booking.id}" class="btn btn-sm btn-outline-warning btn-action"><i class="fas fa-edit me-1"></i>Editar</a>`;
                }
                if (permCancelBooking) {
                    actionsHtml += `<a href="${urlAdm}cancel-booking/${booking.id}" class="btn btn-sm btn-outline-danger btn-action" onclick="return confirm('Cancelar esta reserva?');"><i class="fas fa-times me-1"></i>Cancelar</a>`;
                }
            }

            const bookingDiv = document.createElement('div');
            bookingDiv.className = 'day-booking-item';
            bookingDiv.innerHTML = `
                <div class="day-booking-item-header">
                    <span class="day-booking-time">${startTime} - ${endTime}</span>
                </div>
                <div class="day-booking-title">${escapeHtml(booking.title)}</div>
                <div class="day-booking-user">Por: ${escapeHtml(booking.user_name)}</div>
                ${actionsHtml ? `<div class="day-booking-actions mt-2 d-flex flex-wrap gap-1">${actionsHtml}</div>` : ''}
            `;
            bookingsList.appendChild(bookingDiv);
        });
    }
    
    const modal = new bootstrap.Modal(document.getElementById('dayTimeSlotsModal'));
    modal.show();
}

function openCreateBookingModal(datetime) {
    // Parse datetime como hora local (YYYY-MM-DD HH:MM:SS)
    const [datePart, timePart] = datetime.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hours, minutes] = timePart.split(':');
    
    // Criar objeto Date usando hora local (não UTC)
    const dateTimeObj = new Date(parseInt(year), parseInt(month) - 1, parseInt(day), parseInt(hours), parseInt(minutes));
    
    // Formatar para datetime-local (YYYY-MM-DDTHH:MM)
    const localYear = dateTimeObj.getFullYear();
    const localMonth = String(dateTimeObj.getMonth() + 1).padStart(2, '0');
    const localDay = String(dateTimeObj.getDate()).padStart(2, '0');
    const localHours = String(dateTimeObj.getHours()).padStart(2, '0');
    const localMinutes = String(dateTimeObj.getMinutes()).padStart(2, '0');
    const localDateTime = `${localYear}-${localMonth}-${localDay}T${localHours}:${localMinutes}`;
    
    document.getElementById('modal_start_datetime').value = datetime;
    document.getElementById('modal_start_datetime_display').value = localDateTime;
    
    // Calcular fim (padrão 1 hora) - usando hora local
    const endDateTime = new Date(dateTimeObj);
    endDateTime.setHours(endDateTime.getHours() + 1);
    
    const endYear = endDateTime.getFullYear();
    const endMonth = String(endDateTime.getMonth() + 1).padStart(2, '0');
    const endDay = String(endDateTime.getDate()).padStart(2, '0');
    const endHours = String(endDateTime.getHours()).padStart(2, '0');
    const endMinutes = String(endDateTime.getMinutes()).padStart(2, '0');
    const endDateTimeFormatted = `${endYear}-${endMonth}-${endDay} ${endHours}:${endMinutes}:00`;
    const endDateTimeStr = `${endYear}-${endMonth}-${endDay}T${endHours}:${endMinutes}`;
    
    document.getElementById('modal_end_datetime').value = endDateTimeFormatted;
    document.getElementById('modal_end_datetime_display').value = endDateTimeStr;
    
    const modal = new bootstrap.Modal(document.getElementById('createBookingModal'));
    modal.show();
}

function openWaitlistModal(datetime) {
    document.getElementById('waitlist_start_datetime').value = datetime;
    
    // Calcular fim (padrão 1 hora) - usando hora local
    const [datePart, timePart] = datetime.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hours, minutes] = timePart.split(':');
    const dateTimeObj = new Date(parseInt(year), parseInt(month) - 1, parseInt(day), parseInt(hours), parseInt(minutes));
    
    const endDateTime = new Date(dateTimeObj);
    endDateTime.setHours(endDateTime.getHours() + 1);
    
    const endYear = endDateTime.getFullYear();
    const endMonth = String(endDateTime.getMonth() + 1).padStart(2, '0');
    const endDay = String(endDateTime.getDate()).padStart(2, '0');
    const endHours = String(endDateTime.getHours()).padStart(2, '0');
    const endMinutes = String(endDateTime.getMinutes()).padStart(2, '0');
    const endDateTimeStr = `${endYear}-${endMonth}-${endDay} ${endHours}:${endMinutes}:00`;
    document.getElementById('waitlist_end_datetime').value = endDateTimeStr;
    
    // Exibir horário
    const timeDisplay = dateTimeObj.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    document.getElementById('waitlist_time_display').value = timeDisplay;
    
    const modal = new bootstrap.Modal(document.getElementById('waitlistModal'));
    modal.show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
