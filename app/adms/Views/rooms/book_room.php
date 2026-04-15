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
                        <p id="day-slots-range-hint" class="small text-muted mb-2">
                            <span class="d-block"><strong><i class="fas fa-mobile-alt me-1"></i>Telemóvel / tablet:</strong> toque no horário de <strong>início</strong>; em seguida toque no <strong>último</strong> bloco de 30 min do intervalo (mesmo dia). Não precisa de tecla Ctrl. Para reservar <strong>só 1 hora</strong>: toque duas vezes no mesmo horário livre.</span>
                            <span class="d-block mt-2"><strong><i class="fas fa-desktop me-1"></i>Computador:</strong> clique no <strong>início</strong>; depois <strong>Ctrl</strong>+clique (ou <strong>Cmd</strong> no Mac) no <strong>fim</strong> — ficam todos os blocos de 30 min entre os dois (ex.: início 10:00 e fim 13:00 → até 13:30). <strong>Só 1 hora:</strong> duplo clique no mesmo horário livre ou Ctrl+clique no mesmo bloco do início.</span>
                        </p>
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
                <input type="hidden" name="slot_hold_token" id="slot_hold_token" value="">
                
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
                    <div class="mb-3 border-top pt-3">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="recurrence_enabled" value="1" id="quick_recurrence_enabled" class="form-check-input">
                            <label class="form-check-label" for="quick_recurrence_enabled">Repetir <strong>semanalmente</strong> até…</label>
                        </div>
                        <label class="form-label" for="quick_recurrence_until">Data final da série</label>
                        <input type="date" name="recurrence_until" id="quick_recurrence_until" class="form-control" style="max-width: 280px">
                        <small class="text-muted d-block mt-1">Todas as datas são validadas na gravação; a série só é criada se não houver conflitos.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Participantes (utilizadores internos)</label>
                        <?php
                        $bookingUsers = $this->data['booking_users_for_select'] ?? [];
                        $sessUid = (int)($_SESSION['user_id'] ?? 0);
                        $internalInviteCount = 0;
                        foreach ($bookingUsers as $_bu) {
                            if ((int)($_bu['id'] ?? 0) !== $sessUid) {
                                $internalInviteCount++;
                            }
                        }
                        ?>
                        <select name="participants[]" id="quick_participants" class="form-select" multiple size="5"<?= $internalInviteCount === 0 ? ' disabled' : '' ?>>
                            <?php foreach ($bookingUsers as $u):
                                if ((int)($u['id'] ?? 0) === $sessUid) {
                                    continue;
                                }
                            ?>
                                <option value="<?= (int)($u['id'] ?? 0) ?>"><?= htmlspecialchars((string)($u['name'] ?? '')) ?><?php if (!empty($u['email'])): ?> (<?= htmlspecialchars((string)$u['email']) ?>)<?php endif; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($internalInviteCount === 0): ?>
                            <small class="text-muted d-block mt-1">Não há outros utilizadores ativos para convidar (ou só existe a sua conta).</small>
                        <?php else: ?>
                            <small class="text-muted">Mantenha Ctrl (Cmd no Mac) para selecionar vários.</small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Participantes externos (e-mail)</label>
                        <textarea name="participant_guest_emails" id="quick_participant_guest_emails" class="form-control" rows="2" placeholder="Um e-mail por linha, ou separados por vírgula."></textarea>
                        <small class="text-muted">Recebem convite por e-mail com link para aceitar ou recusar.</small>
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

.time-slot-item.hold-other {
    background: #fff3cd;
    border-color: #ffc107;
    cursor: not-allowed;
}

.time-slot-item.hold-other:hover {
    transform: none;
    box-shadow: none;
}

.time-slot-item.hold-own {
    background: #e7f1ff;
    border-color: #0d6efd;
}

.time-slot-item.slot-range-anchor {
    outline: 3px solid #0d6efd;
    outline-offset: -2px;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
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
const slotHoldCsrf = <?= json_encode((string)($this->data['csrf_slot_hold'] ?? '')) ?>;
const slotHoldUrl = urlAdm + 'room-booking-slot-hold';

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const cb = document.getElementById('quick_recurrence_enabled');
        const dt = document.getElementById('quick_recurrence_until');
        if (!cb || !dt) return;
        const sync = function () {
            dt.required = !!cb.checked;
            if (!cb.checked) dt.value = '';
        };
        cb.addEventListener('change', sync);
        sync();
    });
})();

let activeSlotHoldToken = '';
let slotHoldRenewTimer = null;

/** @type {{ date: string, time: string, datetime: string } | null} */
let daySlotRangeAnchor = null;

function parseSqlDateTime(s) {
    if (!s) return null;
    const d = new Date(String(s).replace(' ', 'T'));
    return isNaN(d.getTime()) ? null : d;
}

function intervalsOverlap(aStart, aEnd, bStart, bEnd) {
    return aStart < bEnd && bStart < aEnd;
}

function slotHalfHourRange(dateStr, timeHHMM) {
    const start = new Date((dateStr + ' ' + timeHHMM + ':00').replace(' ', 'T'));
    const end = new Date(start.getTime() + 30 * 60 * 1000);
    return { start, end };
}

function holdBlocksSlot(holds, dateStr, timeHHMM) {
    const r = slotHalfHourRange(dateStr, timeHHMM);
    for (const h of holds) {
        const hs = parseSqlDateTime(h.start_datetime);
        const he = parseSqlDateTime(h.end_datetime);
        if (!hs || !he) continue;
        if (intervalsOverlap(r.start, r.end, hs, he)) {
            return h;
        }
    }
    return null;
}

async function postSlotHold(bodyObj) {
    const fd = new FormData();
    fd.append('csrf_token', slotHoldCsrf);
    Object.keys(bodyObj).forEach((k) => fd.append(k, bodyObj[k]));
    const res = await fetch(slotHoldUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd
    });
    return res.json();
}

function clearSlotHoldRenew() {
    if (slotHoldRenewTimer) {
        clearInterval(slotHoldRenewTimer);
        slotHoldRenewTimer = null;
    }
}

async function releaseActiveSlotHold() {
    clearSlotHoldRenew();
    const tok = activeSlotHoldToken;
    activeSlotHoldToken = '';
    const hid = document.getElementById('slot_hold_token');
    if (hid) hid.value = '';
    if (!tok) return;
    try {
        await postSlotHold({ action: 'release', hold_token: tok });
    } catch (e) { /* ignorar */ }
}

function startSlotHoldRenew() {
    clearSlotHoldRenew();
    slotHoldRenewTimer = setInterval(async () => {
        const tok = activeSlotHoldToken;
        if (!tok) return;
        try {
            const j = await postSlotHold({ action: 'renew', hold_token: tok });
            if (!j.success) {
                await releaseActiveSlotHold();
            }
        } catch (e) { /* ignorar */ }
    }, 45000);
}

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
                void openDayTimeSlotsModal(date);
            }
        });
    });

    const qm = document.getElementById('createBookingModal');
    if (qm) {
        qm.addEventListener('hidden.bs.modal', function () {
            void releaseActiveSlotHold();
        });
    }

    const dayModal = document.getElementById('dayTimeSlotsModal');
    if (dayModal) {
        dayModal.addEventListener('hidden.bs.modal', function () {
            daySlotRangeAnchor = null;
            document.querySelectorAll('.time-slot-item.slot-range-anchor').forEach((el) => el.classList.remove('slot-range-anchor'));
        });
    }
    
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

function timeToMinutes(timeHHMM) {
    const p = String(timeHHMM || '').split(':');
    return parseInt(p[0], 10) * 60 + parseInt(p[1] || '0', 10);
}

/** Lista ordenada de chaves "HH:MM" em timeSlots entre tMin e tMax (inclusive). */
function enumerateSlotTimesBetween(tMin, tMax) {
    const lo = Math.min(timeToMinutes(tMin), timeToMinutes(tMax));
    const hi = Math.max(timeToMinutes(tMin), timeToMinutes(tMax));
    const out = [];
    timeSlots.forEach((t) => {
        const m = timeToMinutes(t);
        if (m >= lo && m <= hi) {
            out.push(t);
        }
    });
    return out;
}

/** Fim do bloco de 30 min que começa em timeHHMM → string "YYYY-MM-DD HH:MM:00". */
function endOfHalfHourBlock(dateStr, timeHHMM) {
    const dt = new Date((dateStr + ' ' + timeHHMM + ':00').replace(' ', 'T'));
    dt.setMinutes(dt.getMinutes() + 30);
    const y = dt.getFullYear();
    const mo = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    const h = String(dt.getHours()).padStart(2, '0');
    const mi = String(dt.getMinutes()).padStart(2, '0');
    return `${y}-${mo}-${d} ${h}:${mi}:00`;
}

function isSlotPast(dateStr, timeHHMM, isToday, isPastDay) {
    if (isPastDay) return true;
    const dt = new Date((dateStr + ' ' + timeHHMM + ':00').replace(' ', 'T'));
    return isToday && dt < new Date();
}

/**
 * @param {string[]} times
 * @returns {string|null} mensagem de erro ou null se ok
 */
function validateContiguousRange(dateStr, times, holds, isToday, isPastDay) {
    if (times.length === 0) {
        return 'Selecione pelo menos um horário.';
    }
    const sorted = [...times].sort((a, b) => timeToMinutes(a) - timeToMinutes(b));
    for (let i = 1; i < sorted.length; i++) {
        if (timeToMinutes(sorted[i]) - timeToMinutes(sorted[i - 1]) !== 30) {
            return 'Os horários selecionados têm de ser consecutivos (sem buracos).';
        }
    }
    for (const t of sorted) {
        if (isSlotPast(dateStr, t, isToday, isPastDay)) {
            return 'O intervalo inclui horários no passado.';
        }
        if (bookedSlotsData[dateStr]?.[t]) {
            return 'O intervalo inclui horários já reservados.';
        }
        const hh = holdBlocksSlot(holds, dateStr, t);
        if (hh && parseInt(hh.user_id, 10) !== bookRoomCurrentUserId) {
            return 'O intervalo inclui um horário em reserva por outro utilizador.';
        }
    }
    return null;
}

function slotRangeHelpShort() {
    try {
        if (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches) {
            return '1.º toque: início · 2.º toque: fim do intervalo';
        }
    } catch (e) { /* empty */ }
    return 'Clique = início · Ctrl+clique = fim do intervalo';
}

function slotRangeHoldOwnHint() {
    try {
        if (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches) {
            return 'Intervalo: 1.º toque início · 2.º toque fim';
        }
    } catch (e) { /* empty */ }
    return 'Início de intervalo ou Ctrl+fim';
}

/**
 * @param {string} date
 * @param {string} anchorTime
 * @param {string} endSlotTime
 * @param {object[]} holds
 */
function finishDaySlotRangeSelection(date, anchorTime, endSlotTime, holds, isToday, isPastDay) {
    const times = enumerateSlotTimesBetween(anchorTime, endSlotTime);
    const err = validateContiguousRange(date, times, holds, isToday, isPastDay);
    if (err) {
        alert(err);
        return;
    }
    const tStart = times[0];
    const tEnd = times[times.length - 1];
    const startSql = `${date} ${tStart}:00`;
    let endSql;
    if (times.length === 1 && anchorTime === endSlotTime) {
        const d0 = new Date((date + ' ' + tStart + ':00').replace(' ', 'T'));
        d0.setHours(d0.getHours() + 1);
        const y = d0.getFullYear();
        const mo = String(d0.getMonth() + 1).padStart(2, '0');
        const d = String(d0.getDate()).padStart(2, '0');
        const h = String(d0.getHours()).padStart(2, '0');
        const mi = String(d0.getMinutes()).padStart(2, '0');
        endSql = `${y}-${mo}-${d} ${h}:${mi}:00`;
    } else {
        endSql = endOfHalfHourBlock(date, tEnd);
    }

    daySlotRangeAnchor = null;
    clearDaySlotAnchorClass();
    void openCreateBookingModal(startSql, endSql);
}

function clearDaySlotAnchorClass() {
    document.querySelectorAll('.time-slot-item.slot-range-anchor').forEach((el) => el.classList.remove('slot-range-anchor'));
}

/**
 * @param {MouseEvent} ev
 * @param {{ date: string, time: string, datetime: string, holds: object[], isToday: boolean, isPastDay: boolean }} ctx
 */
function handleDaySlotRangeClick(ev, ctx) {
    const { date, time, datetime, holds, isToday, isPastDay } = ctx;
    const multi = ev.ctrlKey === true || ev.metaKey === true;

    if (multi) {
        if (!daySlotRangeAnchor || daySlotRangeAnchor.date !== date) {
            alert('Primeiro defina o horário de início. No computador use Ctrl+clique no fim; no telemóvel toque em seguida no último horário do intervalo (mesmo dia).');
            return;
        }
        finishDaySlotRangeSelection(date, daySlotRangeAnchor.time, time, holds, isToday, isPastDay);
        return;
    }

    if (daySlotRangeAnchor && daySlotRangeAnchor.date === date && daySlotRangeAnchor.time !== time) {
        finishDaySlotRangeSelection(date, daySlotRangeAnchor.time, time, holds, isToday, isPastDay);
        return;
    }

    if (daySlotRangeAnchor && daySlotRangeAnchor.date === date && daySlotRangeAnchor.time === time) {
        daySlotRangeAnchor = null;
        clearDaySlotAnchorClass();
        void openCreateBookingModal(datetime, null);
        return;
    }

    daySlotRangeAnchor = { date, time, datetime };
    clearDaySlotAnchorClass();
    if (ev.currentTarget && ev.currentTarget.classList) {
        ev.currentTarget.classList.add('slot-range-anchor');
    }
}

async function openDayTimeSlotsModal(date) {
    daySlotRangeAnchor = null;
    clearDaySlotAnchorClass();

    const dateObj = new Date(date + 'T00:00:00');
    const dateText = dateObj.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    document.getElementById('modal-selected-date-text').textContent = dateText.charAt(0).toUpperCase() + dateText.slice(1);
    
    let holds = [];
    try {
        const hj = await postSlotHold({ action: 'list', room_id: String(roomId), date });
        if (hj.success && Array.isArray(hj.holds)) {
            holds = hj.holds;
        }
    } catch (e) { /* ignorar */ }

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
            const holdHit = !isPastTime ? holdBlocksSlot(holds, date, time) : null;
            const holdUid = holdHit ? parseInt(holdHit.user_id, 10) : 0;
            const holdName = holdHit ? String(holdHit.user_display_name || 'Outro utilizador') : '';

            if (!isPastTime && holdHit && holdUid !== bookRoomCurrentUserId) {
                slotDiv.classList.remove('available');
                slotDiv.classList.add('hold-other');
                slotDiv.innerHTML = `
                    <div class="time-slot-time">${time}</div>
                    <div class="time-slot-info">
                        <div class="time-slot-title">Em reserva</div>
                        <div class="time-slot-user">Por: ${escapeHtml(holdName)}</div>
                    </div>
                `;
                slotDiv.addEventListener('click', () => {
                    alert('Este horário está a ser reservado por: ' + holdName);
                });
            } else if (!isPastTime && holdHit && holdUid === bookRoomCurrentUserId) {
                slotDiv.classList.remove('available');
                slotDiv.classList.add('hold-own');
                slotDiv.innerHTML = `
                    <div class="time-slot-time">${time}</div>
                    <div class="time-slot-info">
                        <div class="time-slot-title">A sua reserva em curso</div>
                        <div class="time-slot-user">${slotRangeHoldOwnHint()}</div>
                    </div>
                `;
                slotDiv.addEventListener('click', (ev) => {
                    handleDaySlotRangeClick(ev, { date, time, datetime, holds, isToday, isPastDay: isPast });
                });
            } else {
                slotDiv.innerHTML = `
                    <div class="time-slot-time">${time}</div>
                    <div class="time-slot-info">
                        <div class="time-slot-title">Disponível</div>
                        <div class="time-slot-user">${slotRangeHelpShort()}</div>
                    </div>
                `;
                slotDiv.addEventListener('click', (ev) => {
                    handleDaySlotRangeClick(ev, { date, time, datetime, holds, isToday, isPastDay: isPast });
                });
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

function sqlDatetimeToLocalInput(sqlDt) {
    const m = String(sqlDt || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):(\d{2})/);
    if (!m) return '';
    return `${m[1]}-${m[2]}-${m[3]}T${m[4]}:${m[5]}`;
}

/**
 * @param {string} datetime início "YYYY-MM-DD HH:MM:00"
 * @param {string|null} endOverride fim "YYYY-MM-DD HH:MM:00" ou null para +1 h
 */
async function openCreateBookingModal(datetime, endOverride = null) {
    await releaseActiveSlotHold();

    // Parse datetime como hora local (YYYY-MM-DD HH:MM:SS)
    const [datePart, timePart] = datetime.split(' ');
    const [year, month, day] = datePart.split('-');
    const [hours, minutes] = timePart.split(':');
    
    // Criar objeto Date usando hora local (não UTC)
    const dateTimeObj = new Date(parseInt(year, 10), parseInt(month, 10) - 1, parseInt(day, 10), parseInt(hours, 10), parseInt(minutes, 10));
    
    // Formatar para datetime-local (YYYY-MM-DDTHH:MM)
    const localYear = dateTimeObj.getFullYear();
    const localMonth = String(dateTimeObj.getMonth() + 1).padStart(2, '0');
    const localDay = String(dateTimeObj.getDate()).padStart(2, '0');
    const localHours = String(dateTimeObj.getHours()).padStart(2, '0');
    const localMinutes = String(dateTimeObj.getMinutes()).padStart(2, '0');
    const localDateTime = `${localYear}-${localMonth}-${localDay}T${localHours}:${localMinutes}`;
    
    document.getElementById('modal_start_datetime').value = datetime;
    document.getElementById('modal_start_datetime_display').value = localDateTime;

    let endDateTimeFormatted;
    let endDateTimeStr;
    const endTrim = endOverride && String(endOverride).trim() !== '' ? String(endOverride).trim() : null;
    if (endTrim) {
        endDateTimeFormatted = endTrim;
        endDateTimeStr = sqlDatetimeToLocalInput(endTrim);
    } else {
        const endDateTime = new Date(dateTimeObj);
        endDateTime.setHours(endDateTime.getHours() + 1);
        const endYear = endDateTime.getFullYear();
        const endMonth = String(endDateTime.getMonth() + 1).padStart(2, '0');
        const endDay = String(endDateTime.getDate()).padStart(2, '0');
        const endHours = String(endDateTime.getHours()).padStart(2, '0');
        const endMinutes = String(endDateTime.getMinutes()).padStart(2, '0');
        endDateTimeFormatted = `${endYear}-${endMonth}-${endDay} ${endHours}:${endMinutes}:00`;
        endDateTimeStr = `${endYear}-${endMonth}-${endDay}T${endHours}:${endMinutes}`;
    }
    
    document.getElementById('modal_end_datetime').value = endDateTimeFormatted;
    document.getElementById('modal_end_datetime_display').value = endDateTimeStr;

    const startSql = document.getElementById('modal_start_datetime').value;
    const endSql = document.getElementById('modal_end_datetime').value;
    const startProbe = new Date(String(startSql).replace(' ', 'T'));
    const endProbe = new Date(String(endSql).replace(' ', 'T'));
    if (!(endProbe > startProbe)) {
        alert('A data/hora de fim tem de ser posterior ao início.');
        return;
    }

    try {
        const j = await postSlotHold({
            action: 'acquire',
            room_id: String(roomId),
            start_datetime: startSql,
            end_datetime: endSql
        });
        if (!j.success) {
            const who = j.blocked_by ? String(j.blocked_by) : '';
            alert(who ? ('Este horário está a ser reservado por: ' + who) : (j.error || 'Não foi possível reservar este horário.'));
            return;
        }
        activeSlotHoldToken = j.token || '';
        document.getElementById('slot_hold_token').value = activeSlotHoldToken;
    } catch (e) {
        alert('Erro de comunicação ao bloquear o horário. Tente novamente.');
        return;
    }

    const sel = document.getElementById('quick_participants');
    if (sel) {
        Array.from(sel.options).forEach((o) => { o.selected = false; });
    }
    const gta = document.getElementById('quick_participant_guest_emails');
    if (gta) gta.value = '';
    const qrec = document.getElementById('quick_recurrence_enabled');
    const quntil = document.getElementById('quick_recurrence_until');
    if (qrec) qrec.checked = false;
    if (quntil) {
        quntil.value = '';
        quntil.required = false;
    }

    const modal = new bootstrap.Modal(document.getElementById('createBookingModal'));
    modal.show();
    startSlotHoldRenew();
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
