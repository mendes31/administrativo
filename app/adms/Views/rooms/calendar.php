<?php
$rooms = $this->data['rooms'] ?? [];
$selectedMonth = $this->data['selected_month'] ?? date('Y-m');
$bookings = $this->data['bookings'] ?? [];
$firstDay = $this->data['first_day'] ?? new \DateTime();
$lastDay = $this->data['last_day'] ?? new \DateTime();
$prevMonth = $this->data['prev_month'] ?? '';
$nextMonth = $this->data['next_month'] ?? '';

// Calcular dias do mês
$daysInMonth = (int)$lastDay->format('d');
$firstWeekday = (int)$firstDay->format('N'); // 1=Monday, 7=Sunday

// Meses em português
$mesesPT = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$mesNumero = (int)$firstDay->format('m');
$ano = $firstDay->format('Y');
$monthName = $mesesPT[$mesNumero] . ' de ' . $ano;

// Preparar dados de reservas por data para o calendário
$bookingsCountByDate = [];
foreach ($bookings as $date => $roomsBookings) {
    $total = 0;
    foreach ($roomsBookings as $roomBookings) {
        $total += count($roomBookings);
    }
    $bookingsCountByDate[$date] = $total;
}
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Calendário de Reservas de Salas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-meeting-rooms" class="text-decoration-none">Salas</a>
            </li>
            <li class="breadcrumb-item">Calendário</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header" style="background-color: #2E9263; color: white;">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Calendário Geral de Reservas</h5>
                    <small class="text-white-50">
                        <i class="fas fa-door-open me-1"></i><?= count($rooms) ?> salas disponíveis
                    </small>
                </div>
                <div class="btn-group mt-2 mt-md-0">
                    <a href="?month=<?= $prevMonth ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-light" disabled>
                        <strong><?= $monthName ?></strong>
                    </button>
                    <a href="?month=<?= $nextMonth ?>" class="btn btn-sm btn-light">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="alert alert-info m-3 mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Como usar:</strong> Clique duas vezes em uma data para ver todas as reservas do dia. 
                Clique em uma sala para reservá-la diretamente.
            </div>

            <!-- Calendário Mensal Estilo Outlook -->
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
                        
                        $bookingsCount = $bookingsCountByDate[$date] ?? 0;
                        $dayBookings = $bookings[$date] ?? [];
                        
                        echo '<div class="' . $dayClass . '" data-date="' . $date . '" data-day="' . $day . '">';
                        echo '<div class="calendar-day-number-outlook">' . $day . '</div>';
                        
                        // Prévia das reservas do dia
                        if ($bookingsCount > 0) {
                            echo '<div class="calendar-bookings-preview">';
                            $previewCount = 0;
                            foreach ($dayBookings as $roomId => $roomBookings) {
                                if ($previewCount >= 3) break;
                                foreach ($roomBookings as $booking) {
                                    if ($previewCount >= 3) break;
                                    $startTime = date('H:i', strtotime($booking['start_datetime']));
                                    $title = htmlspecialchars($booking['title']);
                                    $userName = htmlspecialchars($booking['user_name'] ?? '');
                                    $shortTitle = strlen($title) > 15 ? substr($title, 0, 12) . '...' : $title;
                                    $shortUser = strlen($userName) > 12 ? substr($userName, 0, 9) . '...' : $userName;
                                    $roomName = htmlspecialchars($booking['room_name'] ?? '');
                                    echo '<div class="booking-preview-item" title="' . htmlspecialchars($booking['title']) . ' - ' . $roomName . ' - ' . $userName . '">';
                                    echo '<span class="booking-time">' . $startTime . '</span> ';
                                    echo '<span class="booking-title">' . $shortTitle . '</span>';
                                    if (!empty($userName)) {
                                        echo '<span class="booking-user"> - ' . $shortUser . '</span>';
                                    }
                                    echo '</div>';
                                    $previewCount++;
                                }
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
    </div>
    
    <!-- Lista de Salas -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Salas Disponíveis</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($rooms as $room): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-door-open me-2"></i><?= htmlspecialchars($room['name']) ?>
                                </h6>
                                <p class="card-text small text-muted mb-2">
                                    <?php if (!empty($room['location'])): ?>
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($room['location']) ?><br>
                                    <?php endif; ?>
                                    <i class="fas fa-users me-1"></i><?= $room['capacity'] ?> pessoas
                                </p>
                                <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= $room['id'] ?>" 
                                       class="btn btn-sm btn-success w-100">
                                        <i class="fas fa-calendar-plus me-1"></i>Reservar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Reservas do Dia -->
<div class="modal fade" id="dayBookingsModal" tabindex="-1" aria-labelledby="dayBookingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2E9263; color: white;">
                <h5 class="modal-title" id="dayBookingsModalLabel">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span id="modal-selected-date-text"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="day-bookings-content">
                    <!-- Conteúdo será preenchido via JavaScript -->
                </div>
            </div>
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
</style>

<script>
// Dados das reservas (passados do PHP para JavaScript)
const bookingsData = <?= json_encode($bookings) ?>;
const roomsData = <?= json_encode($rooms) ?>;

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
                
                // Abrir modal com reservas do dia
                openDayBookingsModal(date);
            }
        });
    });
});

function openDayBookingsModal(date) {
    const dateObj = new Date(date + 'T00:00:00');
    const dateText = dateObj.toLocaleDateString('pt-BR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
    
    document.getElementById('modal-selected-date-text').textContent = dateText.charAt(0).toUpperCase() + dateText.slice(1);
    
    // Preencher conteúdo do modal
    const content = document.getElementById('day-bookings-content');
    content.innerHTML = '';
    
    const dayBookings = bookingsData[date] || {};
    
    if (Object.keys(dayBookings).length === 0) {
        content.innerHTML = '<div class="alert alert-info text-center">Nenhuma reserva neste dia</div>';
    } else {
        let html = '<div class="row">';
        
        // Agrupar por sala
        Object.keys(dayBookings).forEach(roomId => {
            const roomBookings = dayBookings[roomId];
            const room = roomsData.find(r => r.id == roomId);
            const roomName = room ? room.name : 'Sala ' + roomId;
            
            html += '<div class="col-md-6 mb-4">';
            html += '<div class="card">';
            html += '<div class="card-header" style="background-color: #2E9263; color: white;">';
            html += '<h6 class="mb-0"><i class="fas fa-door-open me-2"></i>' + escapeHtml(roomName) + '</h6>';
            html += '</div>';
            html += '<div class="card-body">';
            
            roomBookings.forEach(booking => {
                const startTime = new Date(booking.start_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const endTime = new Date(booking.end_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                
                html += '<div class="mb-3 p-2 border rounded">';
                html += '<div class="d-flex justify-content-between align-items-start mb-2">';
                html += '<span class="badge" style="background-color: #2E9263;">' + startTime + ' - ' + endTime + '</span>';
                html += '<span class="badge bg-' + (booking.status === 'confirmed' ? 'success' : 'warning') + '">' + booking.status + '</span>';
                html += '</div>';
                html += '<h6 class="mb-1">' + escapeHtml(booking.title) + '</h6>';
                html += '<small class="text-muted">Por: ' + escapeHtml(booking.user_name) + '</small>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        content.innerHTML = html;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('dayBookingsModal'));
    modal.show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

