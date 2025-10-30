<?php
// View de calendário semanal para atividades
$selectedDate = $this->data['selected_date'] ?? date('Y-m-d');
$activities = $this->data['activities'] ?? [];

// Calcular início e fim da semana
$dateObj = new DateTime($selectedDate);
$dayOfWeek = (int)$dateObj->format('N'); // 1=Monday, 7=Sunday

// Ajustar para segunda-feira da semana
$startOfWeek = (clone $dateObj)->modify('-' . ($dayOfWeek - 1) . ' days');
$endOfWeek = (clone $startOfWeek)->modify('+6 days');

// Navegação de semanas
$prevWeek = (clone $startOfWeek)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $startOfWeek)->modify('+7 days')->format('Y-m-d');

// Organizar atividades por dia
$activitiesByDay = [];
foreach ($activities as $activity) {
    if ($activity['scheduled_date']) {
        $activityDate = date('Y-m-d', strtotime($activity['scheduled_date']));
        $activitiesByDay[$activityDate][] = $activity;
    }
}

// Formatar período
$mesesPT = [
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
    5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
    9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
];
$weekPeriod = $startOfWeek->format('d') . ' de ' . $mesesPT[(int)$startOfWeek->format('m')] . 
              ' - ' . $endOfWeek->format('d') . ' de ' . $mesesPT[(int)$endOfWeek->format('m')] . 
              ' de ' . $endOfWeek->format('Y');
?>

<style>
.week-calendar {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
}

.time-slot {
    background: #f8f9fa;
    padding: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-align: right;
    border-right: 2px solid #dee2e6;
}

.week-day-header {
    background: #2E9263;
    color: white;
    text-align: center;
    padding: 1rem 0.5rem;
    font-weight: 600;
}

.week-day-header.today {
    background: #1a5d3f;
}

.week-day-cell {
    background: white;
    min-height: 60px;
    padding: 0.5rem;
    position: relative;
    cursor: pointer;
    transition: background 0.2s;
}

.week-day-cell:hover {
    background: #f0f9f5;
}

.week-day-cell.today {
    background: #e7f5ef;
}

.week-activity {
    font-size: 0.75rem;
    padding: 0.3rem 0.5rem;
    margin-bottom: 0.3rem;
    border-radius: 4px;
    cursor: pointer;
    border-left: 4px solid;
}

.week-activity.type-call { background: #fce4ec; border-left-color: #e91e63; color: #e91e63; }
.week-activity.type-meeting { background: #f3e5f5; border-left-color: #9c27b0; color: #9c27b0; }
.week-activity.type-email { background: #f3e5f5; border-left-color: #ba68c8; color: #ba68c8; }
.week-activity.type-task { background: #ede7f6; border-left-color: #6a1b9a; color: #6a1b9a; }
.week-activity.type-note { background: #fff3e0; border-left-color: #ff9800; color: #ff9800; }

.week-activity:hover {
    opacity: 0.9;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.week-activity.status-Concluída {
    text-decoration: line-through;
    opacity: 0.7;
}

.btn-add-day {
    position: absolute;
    bottom: 0.25rem;
    right: 0.25rem;
    width: 28px;
    height: 28px;
    padding: 0;
    font-size: 0.8rem;
    border-radius: 50%;
}
</style>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="btn-group">
            <a href="?view=calendar-week&date=<?= $prevWeek ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chevron-left"></i>
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled style="min-width: 250px;">
                <?= $weekPeriod ?>
            </button>
            <a href="?view=calendar-week&date=<?= $nextWeek ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <a href="?view=calendar-week&date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-calendar-day me-1"></i>Semana Atual
        </a>
    </div>
    <div class="card-body p-0">
        <div class="week-calendar">
            <!-- Cabeçalho vazio para coluna de horários -->
            <div class="week-day-header" style="background: #f8f9fa;"></div>
            
            <!-- Cabeçalhos dos dias da semana -->
            <?php
            $diasSemana = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
            $currentDate = clone $startOfWeek;
            $todayStr = date('Y-m-d');
            
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayNum = $currentDate->format('d/m');
                $isToday = $dateStr === $todayStr;
                $headerClass = $isToday ? 'week-day-header today' : 'week-day-header';
                
                echo '<div class="' . $headerClass . '">';
                echo '<div>' . $diasSemana[$i] . '</div>';
                echo '<div style="font-size: 1.2rem; margin-top: 0.25rem;">' . $dayNum . '</div>';
                echo '</div>';
                
                $currentDate->modify('+1 day');
            }
            
            // Corpo do calendário (dias da semana)
            $currentDate = clone $startOfWeek;
            ?>
            
            <!-- Linha de atividades -->
            <div class="time-slot">Todo o dia</div>
            
            <?php
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDate->format('Y-m-d');
                $dayActivities = $activitiesByDay[$dateStr] ?? [];
                $isToday = $dateStr === $todayStr;
                $cellClass = $isToday ? 'week-day-cell today' : 'week-day-cell';
                
                echo '<div class="' . $cellClass . ' week-cell" data-date="' . $dateStr . '">';
                
                // Atividades do dia
                foreach ($dayActivities as $activity) {
                    $time = $activity['scheduled_date'] ? date('H:i', strtotime($activity['scheduled_date'])) : '';
                    $statusClass = $activity['status'] === 'Concluída' ? 'status-Concluída' : '';
                    
                    echo '<div class="week-activity type-' . $activity['type'] . ' ' . $statusClass . '" 
                               data-activity-id="' . $activity['id'] . '"
                               onclick="window.location=\'' . $_ENV['URL_ADM'] . 'crm-view-activity/' . $activity['id'] . '\';"
                               title="' . htmlspecialchars($activity['title']) . '">';
                    echo '<strong>' . $time . '</strong> ' . htmlspecialchars(substr($activity['title'], 0, 30));
                    echo '</div>';
                }
                
                // Botão (+) para adicionar
                echo '<button type="button" class="btn btn-success btn-sm btn-add-day" 
                             data-date="' . $dateStr . '" 
                             onclick="event.stopPropagation(); openNewActivityModal(\'' . $dateStr . '\');"
                             title="Adicionar atividade">
                        <i class="fas fa-plus"></i>
                      </button>';
                
                echo '</div>';
                
                $currentDate->modify('+1 day');
            }
            ?>
        </div>
    </div>
</div>

<script>
// Função compartilhada para abrir modal com data pré-preenchida (definida no calendar.php)
if (typeof openNewActivityModal === 'undefined') {
    function openNewActivityModal(date) {
        console.log('➕ Abrindo modal de nova atividade para:', date);
        
        const dateTime = date + 'T09:00';
        const scheduledDateInput = document.querySelector('#modalNewActivity input[name="scheduled_date"]');
        if (scheduledDateInput) {
            scheduledDateInput.value = dateTime;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('modalNewActivity'));
        modal.show();
    }
}
</script>

