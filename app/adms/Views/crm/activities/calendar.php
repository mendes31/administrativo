<?php
// View de calendário mensal para atividades
$selectedMonth = $this->data['selected_month'] ?? date('Y-m');
$activities = $this->data['activities'] ?? [];

// Calcular dados do mês
$firstDay = new DateTime($selectedMonth . '-01');
$lastDay = (clone $firstDay)->modify('last day of this month');
$daysInMonth = (int)$lastDay->format('d');
$firstWeekday = (int)$firstDay->format('N'); // 1=Monday, 7=Sunday

// Organizar atividades por dia
$activitiesByDay = [];
foreach ($activities as $activity) {
    if ($activity['scheduled_date']) {
        $day = (int)date('d', strtotime($activity['scheduled_date']));
        $activitiesByDay[$day][] = $activity;
    }
}

// Navegação de meses
$prevMonth = (clone $firstDay)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $firstDay)->modify('+1 month')->format('Y-m');

// Formatar nome do mês em português
$mesesPT = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
$mesNumero = (int)$firstDay->format('m');
$ano = $firstDay->format('Y');
$monthName = $mesesPT[$mesNumero] . ' de ' . $ano;
?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
}

.calendar-day-header {
    background: #2E9263;
    color: white;
    text-align: center;
    padding: 0.5rem;
    font-weight: 600;
    font-size: 0.85rem;
}

.calendar-day {
    background: white;
    min-height: 100px;
    padding: 0.5rem;
    position: relative;
}

.calendar-day.other-month {
    background: #f8f9fa;
    opacity: 0.5;
}

.calendar-day.today {
    background: #e7f5ef;
    border: 2px solid #2E9263;
}

.calendar-day-number {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.calendar-activity {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    margin-bottom: 0.2rem;
    border-radius: 3px;
    cursor: pointer;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.calendar-activity.type-ligacao { background: #cfe2ff; border-left: 3px solid #0d6efd; }
.calendar-activity.type-reuniao { background: #d1e7dd; border-left: 3px solid #198754; }
.calendar-activity.type-email { background: #fff3cd; border-left: 3px solid #ffc107; }
.calendar-activity.type-tarefa { background: #e2e3e5; border-left: 3px solid #6c757d; }

.calendar-activity.status-concluida { text-decoration: line-through; opacity: 0.7; }
.calendar-activity.priority-urgente { border-left-color: #dc3545 !important; font-weight: 600; }
</style>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="btn-group">
            <a href="?view=calendar&month=<?= $prevMonth ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chevron-left"></i>
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                <?= $monthName ?>
            </button>
            <a href="?view=calendar&month=<?= $nextMonth ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <a href="?view=calendar&month=<?= date('Y-m') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-calendar-day me-1"></i>Hoje
        </a>
    </div>
    <div class="card-body p-0">
        <div class="calendar-grid">
            <!-- Cabeçalhos dos dias da semana -->
            <div class="calendar-day-header">Seg</div>
            <div class="calendar-day-header">Ter</div>
            <div class="calendar-day-header">Qua</div>
            <div class="calendar-day-header">Qui</div>
            <div class="calendar-day-header">Sex</div>
            <div class="calendar-day-header">Sáb</div>
            <div class="calendar-day-header">Dom</div>

            <?php
            // Dias em branco antes do primeiro dia do mês
            for ($i = 1; $i < $firstWeekday; $i++) {
                echo '<div class="calendar-day other-month"></div>';
            }

            // Dias do mês
            $today = (int)date('d');
            $currentMonth = date('Y-m');
            
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($day === $today && $selectedMonth === $currentMonth);
                $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';
                
                echo '<div class="' . $dayClass . '">';
                echo '<div class="calendar-day-number">' . $day . '</div>';
                
                // Atividades do dia
                if (isset($activitiesByDay[$day])) {
                    foreach ($activitiesByDay[$day] as $activity) {
                        $typeClass = 'type-' . strtolower(str_replace(['ã', 'õ', ' '], ['a', 'o', ''], $activity['type']));
                        $statusClass = $activity['status'] === 'Concluída' ? 'status-concluida' : '';
                        $priorityClass = $activity['priority'] === 'Urgente' ? 'priority-urgente' : '';
                        
                        $time = $activity['scheduled_date'] ? date('H:i', strtotime($activity['scheduled_date'])) : '';
                        
                        echo '<div class="calendar-activity ' . $typeClass . ' ' . $statusClass . ' ' . $priorityClass . '" 
                                   title="' . htmlspecialchars($activity['title']) . '">';
                        echo $time . ' ' . htmlspecialchars(substr($activity['title'], 0, 20));
                        echo '</div>';
                    }
                }
                
                echo '</div>';
            }

            // Dias em branco após o último dia do mês
            $lastWeekday = (int)$lastDay->format('N');
            for ($i = $lastWeekday; $i < 7; $i++) {
                echo '<div class="calendar-day other-month"></div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Legenda -->
<div class="card shadow-sm mt-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <strong>Tipos de Atividade:</strong>
                <div class="d-flex gap-3 mt-2 flex-wrap">
                    <div><span class="calendar-activity type-ligacao" style="display:inline-block; width:15px; height:15px;"></span> Ligação</div>
                    <div><span class="calendar-activity type-reuniao" style="display:inline-block; width:15px; height:15px;"></span> Reunião</div>
                    <div><span class="calendar-activity type-email" style="display:inline-block; width:15px; height:15px;"></span> E-mail</div>
                    <div><span class="calendar-activity type-tarefa" style="display:inline-block; width:15px; height:15px;"></span> Tarefa</div>
                </div>
            </div>
            <div class="col-md-6">
                <strong>Status:</strong>
                <div class="d-flex gap-3 mt-2">
                    <div><i class="fas fa-circle text-warning"></i> Pendente</div>
                    <div><i class="fas fa-check-circle text-success"></i> Concluída</div>
                    <div><i class="fas fa-exclamation-circle text-danger"></i> Urgente</div>
                </div>
            </div>
        </div>
    </div>
</div>

