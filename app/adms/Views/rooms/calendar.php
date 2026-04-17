<?php
$view = $this->data['view'] ?? 'month';
$rooms = $this->data['rooms'] ?? [];
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
    $selectedDate = $this->data['selected_date'] ?? date('Y-m-d');
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
    
    // Formatar período da semana
    $mesNumeroInicio = (int)$startOfWeek->format('m');
    $mesNumeroFim = (int)$endOfWeek->format('m');
    $ano = $startOfWeek->format('Y');
    $weekPeriod = $startOfWeek->format('d') . ' de ' . $mesesPT[$mesNumeroInicio];
    if ($mesNumeroInicio !== $mesNumeroFim) {
        $weekPeriod .= ' - ' . $endOfWeek->format('d') . ' de ' . $mesesPT[$mesNumeroFim] . ' de ' . $ano;
    } else {
        $weekPeriod .= ' - ' . $endOfWeek->format('d') . ' de ' . $mesesPT[$mesNumeroFim] . ' de ' . $ano;
    }
} else {
    // Visualização mensal
    $selectedDate = date('Y-m-d'); // Para uso no toggle
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

// Preparar dados de reservas por data para o calendário
$bookingsCountByDate = [];
foreach ($bookings as $date => $roomsBookings) {
    $total = 0;
    foreach ($roomsBookings as $roomBookings) {
        $total += count($roomBookings);
    }
    $bookingsCountByDate[$date] = $total;
}

// Função para gerar cor única para cada sala baseada no ID
function getRoomColor($roomId) {
    // Paleta de cores distintas para salas
    $colors = [
        '#2E9263', // Verde padrão
        '#2563EB', // Azul
        '#DC2626', // Vermelho
        '#F59E0B', // Laranja
        '#8B5CF6', // Roxo
        '#EC4899', // Rosa
        '#10B981', // Verde claro
        '#3B82F6', // Azul claro
        '#EF4444', // Vermelho claro
        '#F97316', // Laranja claro
        '#6366F1', // Índigo
        '#14B8A6', // Ciano
        '#F43F5E', // Rosa escuro
        '#06B6D4', // Ciano claro
        '#84CC16', // Verde lima
    ];
    
    // Usar o ID da sala para selecionar uma cor da paleta
    $index = ($roomId - 1) % count($colors);
    return $colors[$index];
}

// Criar mapa de cores por sala
$roomColors = [];
foreach ($rooms as $room) {
    $roomColors[$room['id']] = getRoomColor($room['id']);
}
?>
<?php include __DIR__ . '/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Calendário de Reservas de Salas</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
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
                <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                    <!-- Toggle de visualização -->
                    <div class="btn-group" role="group">
                        <a href="?view=month<?= $view === 'month' && !empty($selectedMonth) ? '&month=' . $selectedMonth : '' ?>" 
                           class="btn btn-sm <?= $view === 'month' ? 'btn-light' : 'btn-outline-light' ?>">
                            <i class="fas fa-calendar-alt me-1"></i>Mensal
                        </a>
                        <a href="?view=week<?= $view === 'week' && isset($selectedDate) ? '&date=' . $selectedDate : '' ?>" 
                           class="btn btn-sm <?= $view === 'week' ? 'btn-light' : 'btn-outline-light' ?>">
                            <i class="fas fa-calendar-week me-1"></i>Semanal
                        </a>
                    </div>
                    <!-- Navegação -->
                    <div class="btn-group">
                        <?php if ($view === 'week'): ?>
                            <a href="?view=week&date=<?= $prevWeek ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light" disabled>
                                <strong><?= $weekPeriod ?? '' ?></strong>
                            </button>
                            <a href="?view=week&date=<?= $nextWeek ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="?view=month&month=<?= $prevMonth ?>" class="btn btn-sm btn-light">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light" disabled>
                                <strong><?= $monthName ?></strong>
                            </button>
                            <a href="?view=month&month=<?= $nextMonth ?>" class="btn btn-sm btn-light">
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
                <strong>Como usar:</strong> 
                <?php if ($view === 'week'): ?>
                    Clique em um horário para ver os detalhes da reserva. Cada sala possui uma cor diferente para fácil identificação.
                <?php else: ?>
                    Clique duas vezes em uma data para ver todas as reservas do dia. 
                    Clique em uma sala para reservá-la diretamente.
                <?php endif; ?>
            </div>

            <?php if ($view === 'week'): ?>
                <!-- Calendário Semanal -->
                <?php
                // Horários do dia (8h às 18h, intervalos de 30 minutos)
                $timeSlots = [];
                for ($hour = 8; $hour < 18; $hour++) {
                    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
                }
                
                // Preparar matriz de reservas por data e hora (agrupando todas as salas)
                // Calcular posição e altura de cada reserva
                $bookingsByDateTime = [];
                foreach ($bookings as $date => $roomsBookings) {
                    foreach ($roomsBookings as $roomId => $roomBookings) {
                        foreach ($roomBookings as $booking) {
                            $start = new \DateTime($booking['start_datetime']);
                            $end = new \DateTime($booking['end_datetime']);
                            $startTimeKey = $start->format('H:i');
                            $bookingDate = $start->format('Y-m-d');
                            
                            // Calcular duração em minutos
                            $duration = ($end->getTimestamp() - $start->getTimestamp()) / 60;
                            
                            // Calcular quantos slots de 30 minutos a reserva ocupa
                            $slotsCount = ceil($duration / 30);
                            
                            // Encontrar o índice do slot de início
                            $startSlotIndex = array_search($startTimeKey, $timeSlots);
                            
                            if ($startSlotIndex !== false) {
                                if (!isset($bookingsByDateTime[$bookingDate][$startTimeKey])) {
                                    $bookingsByDateTime[$bookingDate][$startTimeKey] = [];
                                }
                                
                                $bookingsByDateTime[$bookingDate][$startTimeKey][] = [
                                    'booking' => $booking,
                                    'room_id' => $roomId,
                                    'room_color' => $roomColors[$roomId] ?? '#2E9263',
                                    'room_name' => htmlspecialchars($booking['room_name'] ?? ''),
                                    'start' => $start,
                                    'end' => $end,
                                    'slots_count' => $slotsCount,
                                    'slot_index' => $startSlotIndex
                                ];
                            }
                        }
                    }
                }
                
                $today = date('Y-m-d');
                ?>
                <div class="rooms-calendar-scroll">
                <div class="week-calendar-simple p-3">
                    <div class="week-calendar-grid-simple">
                        <!-- Coluna de horários -->
                        <div class="time-column-week-simple">
                            <div class="time-slot-header-week-simple"></div>
                            <?php foreach ($timeSlots as $time): ?>
                                <div class="time-slot-week-simple"><?= $time ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Colunas dos dias da semana -->
                        <?php foreach ($weekDays as $dayIndex => $dayDate): 
                            $dateStr = $dayDate->format('Y-m-d');
                            $dayName = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'][$dayIndex];
                            $dayNumber = $dayDate->format('d');
                            $isToday = ($dateStr === $today);
                        ?>
                            <div class="week-day-column-simple">
                                <!-- Cabeçalho do dia -->
                                <div class="week-day-header-simple<?= $isToday ? ' today' : '' ?>">
                                    <div class="week-day-name-simple"><?= $dayName ?></div>
                                    <div class="week-day-number-simple"><?= $dayNumber ?></div>
                                </div>
                                
                                <!-- Container de slots de horário (para posicionamento absoluto dos cards) -->
                                <div class="week-day-slots-container">
                                    <?php foreach ($timeSlots as $slotIndex => $time): 
                                        $datetime = $dateStr . ' ' . $time . ':00';
                                        $dayBookings = $bookingsByDateTime[$dateStr][$time] ?? [];
                                        $slotClass = 'week-time-slot-simple';
                                        
                                        if (count($dayBookings) > 0) {
                                            $slotClass .= ' has-booking';
                                        } else {
                                            $slotClass .= ' available';
                                        }
                                    ?>
                                        <div class="<?= $slotClass ?>" 
                                             data-date="<?= $dateStr ?>" 
                                             data-time="<?= $time ?>"
                                             data-slot-index="<?= $slotIndex ?>"
                                             data-datetime="<?= $datetime ?>"
                                             title="<?= count($dayBookings) > 0 ? 'Reservas neste horário' : 'Disponível - Clique para reservar' ?>">
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <!-- Cards de reservas posicionados absolutamente -->
                                    <?php 
                                    // Agrupar todas as reservas do dia para renderizar
                                    $allDayBookings = [];
                                    foreach ($timeSlots as $time) {
                                        if (isset($bookingsByDateTime[$dateStr][$time])) {
                                            foreach ($bookingsByDateTime[$dateStr][$time] as $index => $bookingData) {
                                                $bookingData['original_index'] = count($allDayBookings);
                                                $allDayBookings[] = $bookingData;
                                            }
                                        }
                                    }
                                    
                                    // Agrupar reservas por horário de início para empilhar
                                    $bookingsByStartTime = [];
                                    foreach ($allDayBookings as $bookingData) {
                                        $startTimeKey = $bookingData['start']->format('H:i');
                                        if (!isset($bookingsByStartTime[$startTimeKey])) {
                                            $bookingsByStartTime[$startTimeKey] = [];
                                        }
                                        $bookingsByStartTime[$startTimeKey][] = $bookingData;
                                    }
                                    
                                    foreach ($allDayBookings as $bookingIndex => $bookingData): 
                                        $booking = $bookingData['booking'];
                                        $roomColor = $bookingData['room_color'];
                                        $roomName = $bookingData['room_name'];
                                        $startTime = $bookingData['start']->format('H:i');
                                        $endTime = $bookingData['end']->format('H:i');
                                        $bookingTitle = htmlspecialchars($booking['title']);
                                        $bookingUser = htmlspecialchars($booking['user_name'] ?? '');
                                        $slotsCount = $bookingData['slots_count'];
                                        $slotIndex = $bookingData['slot_index'];
                                        
                                        // Calcular altura baseada no número de slots (50px por slot)
                                        $cardHeight = ($slotsCount * 50) - 2; // -2 para gap entre slots
                                        $topPosition = ($slotIndex * 50) + 1; // +1 para alinhar com o slot
                                        
                                        // Calcular offset horizontal se houver múltiplas reservas no mesmo horário
                                        $startTimeKey = $startTime;
                                        $sameTimeBookings = $bookingsByStartTime[$startTimeKey] ?? [];
                                        $totalSameTime = count($sameTimeBookings);
                                        
                                        // Encontrar a posição desta reserva no grupo
                                        $bookingPosition = 0;
                                        foreach ($sameTimeBookings as $idx => $bt) {
                                            if ($bt['original_index'] === $bookingData['original_index']) {
                                                $bookingPosition = $idx;
                                                break;
                                            }
                                        }
                                        
                                        // Se houver múltiplas reservas, dividir a largura
                                        if ($totalSameTime > 1) {
                                            $cardWidth = (100 / $totalSameTime) - 1;
                                            $leftOffset = ($bookingPosition * (100 / $totalSameTime)) + 0.5;
                                            $widthStyle = "width: calc($cardWidth% - 1px); left: $leftOffset%;";
                                        } else {
                                            $widthStyle = "left: 2px; right: 2px;";
                                        }
                                        
                                        // Ajustar tamanhos para exibição
                                        $shortTitle = strlen($bookingTitle) > 20 ? substr($bookingTitle, 0, 17) . '...' : $bookingTitle;
                                        $shortUser = strlen($bookingUser) > 15 ? substr($bookingUser, 0, 12) . '...' : $bookingUser;
                                        $shortRoom = strlen($roomName) > 15 ? substr($roomName, 0, 12) . '...' : $roomName;
                                    ?>
                                        <div class="week-booking-card" 
                                             style="background-color: <?= $roomColor ?>; 
                                                    color: white; 
                                                    top: <?= $topPosition ?>px; 
                                                    height: <?= $cardHeight ?>px;
                                                    <?= $widthStyle ?>"
                                             title="<?= $startTime . ' - ' . $endTime . ' ' . $bookingTitle . ' - ' . $bookingUser . ' - ' . $roomName ?>">
                                            <div class="week-booking-content">
                                                <div class="week-booking-header-line">
                                                    <span class="week-booking-time"><?= $startTime ?> - <?= $endTime ?></span>
                                                    <span class="week-booking-room">[<?= $shortRoom ?>]</span>
                                                </div>
                                                <div class="week-booking-title"><?= $shortTitle ?></div>
                                                <?php if (!empty($bookingUser)): ?>
                                                    <div class="week-booking-user"><?= $shortUser ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
            <?php else: ?>
                <!-- Calendário Mensal Estilo Outlook -->
                <div class="rooms-calendar-scroll">
                <div class="outlook-calendar p-3">
                <div class="calendar-grid-outlook">
                    <!-- Cabeçalho dos dias da semana -->
                    <div class="calendar-day-header-outlook" aria-label="Segunda"><abbr title="Segunda">Seg</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Terça"><abbr title="Terça">Ter</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Quarta"><abbr title="Quarta">Qua</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Quinta"><abbr title="Quinta">Qui</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Sexta"><abbr title="Sexta">Sex</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Sábado"><abbr title="Sábado">Sáb</abbr></div>
                    <div class="calendar-day-header-outlook" aria-label="Domingo"><abbr title="Domingo">Dom</abbr></div>

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
                                    $endTime = date('H:i', strtotime($booking['end_datetime']));
                                    $title = htmlspecialchars($booking['title']);
                                    $userName = htmlspecialchars($booking['user_name'] ?? '');
                                    $roomName = htmlspecialchars($booking['room_name'] ?? '');
                                    $roomColor = $roomColors[$roomId] ?? '#2E9263';
                                    
                                    // Ajustar tamanhos para incluir o nome da sala
                                    $shortTitle = strlen($title) > 12 ? substr($title, 0, 9) . '...' : $title;
                                    $shortUser = strlen($userName) > 10 ? substr($userName, 0, 7) . '...' : $userName;
                                    $shortRoom = strlen($roomName) > 10 ? substr($roomName, 0, 7) . '...' : $roomName;
                                    
                                    echo '<div class="booking-preview-item" style="background-color: ' . $roomColor . ';" title="' . $startTime . ' - ' . $endTime . ' ' . htmlspecialchars($booking['title']) . ' - ' . $roomName . ' - ' . $userName . '">';
                                    echo '<span class="booking-time">' . $startTime . ' - ' . $endTime . '</span> ';
                                    echo '<span class="booking-room">[' . $shortRoom . ']</span> ';
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
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Lista de Salas -->
    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i>Salas Disponíveis</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($rooms as $room): 
                    $roomColor = $roomColors[$room['id']] ?? '#2E9263';
                ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header" style="background-color: <?= $roomColor ?>; color: white;">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-door-open me-2"></i><?= htmlspecialchars($room['name']) ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text small text-muted mb-2">
                                    <?php if (!empty($room['location'])): ?>
                                        <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($room['location']) ?><br>
                                    <?php endif; ?>
                                    <i class="fas fa-users me-1"></i><?= $room['capacity'] ?> pessoas
                                </p>
                                <?php if (in_array('BookRoom', $this->data['buttonPermission'] ?? [])): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>book-room?room_id=<?= $room['id'] ?>" 
                                       class="btn btn-sm w-100" style="background-color: <?= $roomColor ?>; color: white; border-color: <?= $roomColor ?>;">
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

.calendar-day-header-outlook abbr {
    text-decoration: none;
    border-bottom: 0;
    cursor: help;
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
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.booking-preview-item:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.booking-time {
    font-weight: 600;
    margin-right: 0.3rem;
}

.booking-room {
    font-weight: 600;
    font-size: 0.7rem;
    margin-right: 0.3rem;
    opacity: 0.95;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 0.1rem 0.3rem;
    border-radius: 2px;
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

/* Calendário Semanal - Modelo Simples */
.week-calendar-simple {
    background: #f8f9fa;
    overflow-x: auto;
}

.week-calendar-grid-simple {
    display: grid;
    grid-template-columns: 100px repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
    max-height: 70vh;
    overflow-y: auto;
}

.time-column-week-simple {
    position: sticky;
    left: 0;
    z-index: 10;
    background: white;
}

.time-slot-header-week-simple {
    background: #2E9263;
    color: white;
    text-align: center;
    padding: 0.75rem;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 20;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.time-slot-week-simple {
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-align: right;
    border-right: 2px solid #dee2e6;
    border-bottom: 1px solid #d1d5db;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    position: sticky;
    left: 0;
    z-index: 5;
}

/* Tons alternados na coluna de horários */
.time-slot-week-simple:nth-child(even) {
    background: #f1f3f5;
    border-bottom: 1px solid #d1d5db;
}

.time-slot-week-simple:nth-child(odd) {
    background: #ffffff;
    border-bottom: 1px solid #d1d5db;
}

/* Linha mais destacada a cada hora (a cada 2 slots) */
.time-slot-week-simple:nth-child(2n) {
    border-bottom: 2px solid #94a3b8;
}

.week-day-column-simple {
    display: flex;
    flex-direction: column;
}

.week-day-header-simple {
    background: #2E9263;
    color: white;
    text-align: center;
    padding: 0.75rem 0.5rem;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 15;
    min-height: 60px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.week-day-header-simple.today {
    background: #258556;
}

.week-day-name-simple {
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.week-day-number-simple {
    font-size: 1.2rem;
    font-weight: 700;
}

.week-day-slots-container {
    position: relative;
    flex: 1;
}

.week-time-slot-simple {
    background: white;
    height: 50px;
    border-bottom: 1px solid #d1d5db;
    border-right: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

/* Tons alternados para melhor identificação dos horários */
.week-day-slots-container .week-time-slot-simple:nth-child(odd) {
    background: #ffffff;
    border-bottom: 1px solid #d1d5db;
}

.week-day-slots-container .week-time-slot-simple:nth-child(even) {
    background: #f8f9fa;
    border-bottom: 1px solid #cbd5e1;
}

/* Linha mais destacada a cada hora (a cada 2 slots) */
.week-day-slots-container .week-time-slot-simple:nth-child(2n) {
    border-bottom: 2px solid #94a3b8;
}

.week-time-slot-simple:hover {
    background: #f0f7ff !important;
    z-index: 5;
    box-shadow: inset 0 0 0 1px #3b82f6;
}

.week-time-slot-simple.available {
    background: #d4edda !important;
    border-left: 3px solid #28a745;
}

.week-time-slot-simple.available:nth-child(even) {
    background: #c3e6cb !important;
}

.week-time-slot-simple.available:hover {
    background: #b8e0c4 !important;
    border-left-color: #218838;
}

.week-time-slot-simple.has-booking {
    background: #fff !important;
    cursor: pointer;
}

.week-time-slot-simple.has-booking:nth-child(even) {
    background: #f8f9fa !important;
}

.week-booking-card {
    position: absolute;
    padding: 0.4rem 0.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    z-index: 10;
    overflow: hidden;
    display: flex;
    align-items: center;
    min-height: 40px;
    margin: 0 1px;
}

.week-booking-card:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    z-index: 20;
}

.week-booking-content {
    width: 100%;
    font-size: 0.75rem;
    line-height: 1.4;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    overflow: hidden;
}

.week-booking-header-line {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    flex-wrap: wrap;
}

.week-booking-time {
    font-weight: 600;
    font-size: 0.8rem;
    white-space: nowrap;
}

.week-booking-room {
    font-weight: 600;
    font-size: 0.7rem;
    opacity: 0.95;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 0.1rem 0.25rem;
    border-radius: 2px;
    white-space: nowrap;
}

.week-booking-title {
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 500;
    margin-top: 0.1rem;
}

.week-booking-user {
    font-size: 0.7rem;
    opacity: 0.9;
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
// Dados das reservas (passados do PHP para JavaScript)
const bookingsData = <?= json_encode($bookings) ?>;
const roomsData = <?= json_encode($rooms) ?>;
const roomColors = <?= json_encode($roomColors) ?>;

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
            
            const roomColor = roomColors[roomId] || '#2E9263';
            
            html += '<div class="col-md-6 mb-4">';
            html += '<div class="card">';
            html += '<div class="card-header" style="background-color: ' + roomColor + '; color: white;">';
            html += '<h6 class="mb-0"><i class="fas fa-door-open me-2"></i>' + escapeHtml(roomName) + '</h6>';
            html += '</div>';
            html += '<div class="card-body">';
            
            roomBookings.forEach(booking => {
                const startTime = new Date(booking.start_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const endTime = new Date(booking.end_datetime).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                const bookingRoomColor = roomColors[roomId] || '#2E9263';
                const bookingRoomName = escapeHtml(booking.room_name || roomName);
                
                html += '<div class="mb-3 p-2 border rounded" style="border-left: 4px solid ' + bookingRoomColor + ' !important;">';
                html += '<div class="d-flex justify-content-between align-items-start mb-2">';
                html += '<span class="badge" style="background-color: ' + bookingRoomColor + ';">' + startTime + ' - ' + endTime + ' [' + bookingRoomName + ']</span>';
                html += '<span class="badge bg-' + (booking.status === 'confirmed' ? 'success' : 'warning') + '">' + booking.status + '</span>';
                html += '</div>';
                html += '<h6 class="mb-1">' + escapeHtml(booking.title) + '</h6>';
                html += '<small class="text-muted">Por: ' + escapeHtml(booking.user_name) + ' | Sala: ' + bookingRoomName + '</small>';
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

