<?php
/**
 * Partial da vista semanal — incluído por book_room.php.
 *
 * @var array<string, array<int, array<string, mixed>>> $bookings Reservas por data (Y-m-d)
 * @var list<\DateTime> $weekDays Dias da semana em exibição
 * @var int $bookRoomCurrentUserId ID do utilizador logado (destaque laranja)
 */
$bookings = $bookings ?? [];
$weekDays = $weekDays ?? [];
$bookRoomCurrentUserId = (int)($bookRoomCurrentUserId ?? 0);

// Horários do dia (8h às 18h, intervalos de 30 minutos)
$timeSlots = [];
for ($hour = 8; $hour < 18; $hour++) {
    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
    $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':30';
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

$today = date('Y-m-d');
if ($bookRoomCurrentUserId <= 0) {
    $bookRoomCurrentUserId = (int)($_SESSION['user_id'] ?? 0);
}
?>
<div class="week-calendar-view p-3">
    <div class="week-calendar-grid">
        <!-- Coluna de horários -->
        <div class="time-column-week">
            <div class="time-slot-header-week"></div>
            <?php foreach ($timeSlots as $time): ?>
                <div class="time-slot-week"><?= $time ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Colunas dos dias da semana -->
        <?php
        $weekDayNames = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        foreach ($weekDays as $index => $dayDate):
            $dateStr = $dayDate->format('Y-m-d');
            $dayName = $weekDayNames[$index];
            $dayNumber = $dayDate->format('d');
            $isToday = ($dateStr === $today);
            $isPast = ($dateStr < $today);
        ?>
            <div class="week-day-column-week">
                <div class="week-day-header-week<?= $isToday ? ' today' : '' ?>">
                    <div class="week-day-name"><?= $dayName ?></div>
                    <div class="week-day-number"><?= $dayNumber ?></div>
                </div>
                
                <?php foreach ($timeSlots as $time): 
                    $datetime = $dateStr . ' ' . $time . ':00';
                    $booking = $bookedSlots[$dateStr][$time] ?? null;
                    $slotClass = 'week-time-slot';
                    
                    if ($isPast) {
                        $slotClass .= ' past';
                    } elseif ($booking) {
                        $isMine = ((int)($booking['user_id'] ?? 0) === $bookRoomCurrentUserId);
                        $slotClass .= $isMine ? ' booked booked-mine' : ' booked';
                    } else {
                        $slotClass .= ' available';
                    }
                ?>
                    <div class="<?= $slotClass ?>" 
                         data-date="<?= $dateStr ?>" 
                         data-time="<?= $time ?>" 
                         data-datetime="<?= $datetime ?>"
                         <?php if ($booking): ?>
                             data-booking-id="<?= $booking['id'] ?>"
                             data-booking-title="<?= htmlspecialchars($booking['title']) ?>"
                             title="<?= htmlspecialchars($booking['title']) ?> - <?= htmlspecialchars($booking['user_name'] ?? '') ?>"
                         <?php else: ?>
                             title="Disponível - Clique para reservar"
                         <?php endif; ?>>
                        <?php if ($booking): ?>
                            <div class="week-booking-block<?= $isMine ? ' week-booking-block--mine' : '' ?>">
                                <div class="week-booking-time"><?= $time ?></div>
                                <div class="week-booking-title"><?= htmlspecialchars($booking['title']) ?></div>
                                <div class="week-booking-user"><?= htmlspecialchars($booking['user_name'] ?? '') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.week-calendar-view {
    background: #f8f9fa;
}

.week-calendar-grid {
    display: grid;
    grid-template-columns: 100px repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
    max-height: 70vh;
    overflow-y: auto;
}

.time-column-week {
    position: sticky;
    left: 0;
    z-index: 10;
    background: white;
}

.time-slot-header-week {
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

.time-slot-week {
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
.time-slot-week:nth-child(even) {
    background: #f1f3f5;
    border-bottom: 1px solid #d1d5db;
}

.time-slot-week:nth-child(odd) {
    background: #ffffff;
    border-bottom: 1px solid #d1d5db;
}

/* Linha mais destacada a cada hora (a cada 2 slots) */
.time-slot-week:nth-child(2n) {
    border-bottom: 2px solid #94a3b8;
}

.week-day-column-week {
    display: flex;
    flex-direction: column;
}

.week-day-header-week {
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

.week-day-header-week.today {
    background: #258556;
}

.week-day-name {
    font-size: 0.9rem;
    margin-bottom: 0.2rem;
}

.week-day-number {
    font-size: 1.2rem;
    font-weight: 700;
}

.week-time-slot {
    min-height: 50px;
    height: 50px;
    border-bottom: 1px solid #d1d5db;
    border-right: 1px solid #e9ecef;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
    padding: 0.25rem;
}

/* Tons alternados para melhor identificação dos horários */
/* O primeiro filho é o cabeçalho, então os slots começam do segundo filho */
.week-day-column-week .week-time-slot:nth-child(2n+2) {
    /* Slots ímpares (08:00, 09:00, 10:00...) - primeiro slot de cada hora */
    background: #ffffff;
    border-bottom: 1px solid #d1d5db;
}

.week-day-column-week .week-time-slot:nth-child(2n+3) {
    /* Slots pares (08:30, 09:30, 10:30...) - segundo slot de cada hora */
    background: #f8f9fa;
    border-bottom: 1px solid #cbd5e1;
}

/* Linha mais destacada a cada hora (a cada 2 slots) */
/* Os slots pares (2n+3) marcam o fim de cada hora */
.week-day-column-week .week-time-slot:nth-child(2n+3) {
    border-bottom: 2px solid #94a3b8;
}

.week-time-slot:hover {
    background: #f0f7ff !important;
    z-index: 5;
    box-shadow: inset 0 0 0 1px #3b82f6;
}

.week-time-slot.available {
    background: #d4edda !important;
    border-left: 3px solid #28a745;
}

.week-time-slot.available:nth-child(2n+3) {
    /* Slots pares disponíveis */
    background: #c3e6cb !important;
}

.week-time-slot.available:hover {
    background: #b8e0c4 !important;
    border-left-color: #218838;
}

.week-time-slot.booked {
    background: #f8d7da !important;
    border-left: 3px solid #dc3545;
    cursor: pointer;
}

.week-time-slot.booked:nth-child(2n+3) {
    /* Slots pares ocupados */
    background: #f5c6cb !important;
}

.week-time-slot.booked:hover {
    background: #f1b0b7 !important;
}

.week-time-slot.booked-mine {
    background: #ffe8cc !important;
    border-left: 3px solid #fd7e14;
}

.week-time-slot.booked-mine:nth-child(2n+3) {
    background: #ffd4a3 !important;
}

.week-time-slot.booked-mine:hover {
    background: #ffc285 !important;
}

.week-time-slot.past {
    background: #e9ecef !important;
    opacity: 0.5;
    cursor: not-allowed;
}

.week-time-slot.past:nth-child(2n+3) {
    /* Slots pares no passado */
    background: #dee2e6 !important;
}

.week-booking-block {
    padding: 0.3rem;
    border-radius: 3px;
    font-size: 0.7rem;
    color: #721c24;
    background-color: #f8d7da;
    border-left: 3px solid #dc3545;
}

.week-booking-block--mine {
    color: #7c2d12;
    background-color: #ffe8cc;
    border-left: 3px solid #fd7e14;
}

.week-booking-time {
    font-weight: 600;
    margin-bottom: 0.1rem;
}

.week-booking-title {
    font-weight: 500;
    margin-bottom: 0.1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.week-booking-user {
    font-size: 0.65rem;
    opacity: 0.8;
    font-style: italic;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<script>
// Adicionar event listeners para os slots semanais
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.week-time-slot').forEach(slot => {
        slot.addEventListener('click', function() {
            const isBooked = this.classList.contains('booked');
            const isPast = this.classList.contains('past');
            const date = this.dataset.date;
            const time = this.dataset.time;
            const datetime = this.dataset.datetime;
            
            if (isPast) {
                alert('Não é possível reservar horários no passado.');
                return;
            }
            
            if (isBooked) {
                // Abrir modal de lista de espera
                openWaitlistModal(datetime);
            } else {
                void openCreateBookingModal(datetime);
            }
        });
    });
});
</script>

