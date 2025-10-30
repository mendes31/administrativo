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
    cursor: pointer;
    transition: background 0.2s;
}

.calendar-day:hover {
    background: #f0f9f5;
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
    display: inline-block;
}

.btn-add-activity {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    width: 24px;
    height: 24px;
    padding: 0;
    font-size: 0.75rem;
    line-height: 1;
    border-radius: 50%;
    opacity: 0.6;
    transition: all 0.2s;
}

.calendar-day:hover .btn-add-activity {
    opacity: 1;
    transform: scale(1.1);
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

.calendar-activity.type-call { background: #fce4ec; border-left: 3px solid #e91e63; }
.calendar-activity.type-ligacao { background: #fce4ec; border-left: 3px solid #e91e63; }
.calendar-activity.type-meeting { background: #f3e5f5; border-left: 3px solid #9c27b0; }
.calendar-activity.type-reuniao { background: #f3e5f5; border-left: 3px solid #9c27b0; }
.calendar-activity.type-email { background: #f3e5f5; border-left: 3px solid #ba68c8; }
.calendar-activity.type-task { background: #ede7f6; border-left: 3px solid #6a1b9a; }
.calendar-activity.type-tarefa { background: #ede7f6; border-left: 3px solid #6a1b9a; }
.calendar-activity.type-note { background: #fff3e0; border-left: 3px solid #ff9800; }

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
                $fullDate = $selectedMonth . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                $activitiesCount = count($activitiesByDay[$day] ?? []);
                
                echo '<div class="' . $dayClass . ' calendar-cell" 
                           data-date="' . $fullDate . '" 
                           data-day="' . $day . '"
                           data-activities-count="' . $activitiesCount . '">';
                
                // Número do dia
                echo '<div class="calendar-day-number">' . $day . '</div>';
                
                // Botão (+) para adicionar atividade
                echo '<button type="button" class="btn btn-success btn-sm btn-add-activity" 
                             data-date="' . $fullDate . '" 
                             title="Adicionar atividade"
                             onclick="event.stopPropagation(); openNewActivityModal(\'' . $fullDate . '\');">
                        <i class="fas fa-plus"></i>
                      </button>';
                
                // Atividades do dia
                if (isset($activitiesByDay[$day])) {
                    foreach ($activitiesByDay[$day] as $activity) {
                        $typeClass = 'type-' . strtolower(str_replace(['ã', 'õ', ' '], ['a', 'o', ''], $activity['type']));
                        $statusClass = $activity['status'] === 'Concluída' ? 'status-concluida' : '';
                        $priorityClass = $activity['priority'] === 'Urgente' ? 'priority-urgente' : '';
                        
                        $time = $activity['scheduled_date'] ? date('H:i', strtotime($activity['scheduled_date'])) : '';
                        
                        echo '<div class="calendar-activity ' . $typeClass . ' ' . $statusClass . ' ' . $priorityClass . '" 
                                   data-activity-id="' . $activity['id'] . '"
                                   onclick="event.stopPropagation(); window.location=\'' . $_ENV['URL_ADM'] . 'crm-view-activity/' . $activity['id'] . '\';"
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
                    <div><span class="badge" style="background-color: #e91e63;"><i class="fas fa-phone"></i></span> Ligação</div>
                    <div><span class="badge" style="background-color: #9c27b0;"><i class="fas fa-users"></i></span> Reunião</div>
                    <div><span class="badge" style="background-color: #ba68c8;"><i class="fas fa-envelope"></i></span> E-mail</div>
                    <div><span class="badge" style="background-color: #6a1b9a;"><i class="fas fa-tasks"></i></span> Tarefa</div>
                    <div><span class="badge" style="background-color: #ff9800;"><i class="fas fa-sticky-note"></i></span> Nota</div>
                </div>
            </div>
            <div class="col-md-6">
                <strong>Dicas:</strong>
                <div class="mt-2">
                    <div><i class="fas fa-mouse-pointer text-primary me-1"></i> Clique no dia para ver atividades</div>
                    <div><i class="fas fa-plus-circle text-success me-1"></i> Botão (+) para nova atividade</div>
                    <div><i class="fas fa-check-circle text-success me-1"></i> <s>Riscado</s> = Concluída</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Atividades do Dia -->
<div class="modal fade" id="modalDayActivities" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-day me-2"></i>
                    Atividades de <span id="modal-day-title"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="day-activities-list">
                    <!-- Carregado dinamicamente -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="btn-add-activity-day">
                    <i class="fas fa-plus me-1"></i>Nova Atividade neste Dia
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ========================================
// INTERATIVIDADE DO CALENDÁRIO
// ========================================

// Dados das atividades (do PHP)
const activitiesByDay = <?= json_encode($activitiesByDay) ?>;
const currentMonth = '<?= $selectedMonth ?>';

// Abrir modal de nova atividade com data pré-preenchida
function openNewActivityModal(date) {
    console.log('➕ Abrindo modal de nova atividade para:', date);
    
    // Converter YYYY-MM-DD para YYYY-MM-DDTHH:MM (datetime-local)
    const dateTime = date + 'T09:00'; // Hora padrão 09:00
    
    // Preencher campo de data no modal de nova atividade
    const scheduledDateInput = document.querySelector('#modalNewActivity input[name="scheduled_date"]');
    if (scheduledDateInput) {
        scheduledDateInput.value = dateTime;
    }
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalNewActivity'));
    modal.show();
}

// Clicar em uma célula do calendário → Mostrar atividades do dia
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.calendar-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            const day = this.getAttribute('data-day');
            const count = this.getAttribute('data-activities-count');
            
            console.log('📅 Dia clicado:', date, '| Atividades:', count);
            
            showDayActivities(date, day);
        });
    });
});

// Função para mostrar atividades do dia em modal
function showDayActivities(date, day) {
    const dayInt = parseInt(day);
    const activities = activitiesByDay[dayInt] || [];
    
    console.log('📋 Atividades do dia', day + ':', activities);
    
    // Formatar título
    const dateObj = new Date(date + 'T00:00:00');
    const dayName = dateObj.toLocaleDateString('pt-BR', { weekday: 'long' });
    const dateFormatted = dateObj.toLocaleDateString('pt-BR');
    
    document.getElementById('modal-day-title').textContent = 
        dayName.charAt(0).toUpperCase() + dayName.slice(1) + ', ' + dateFormatted;
    
    // Construir lista de atividades
    const listContainer = document.getElementById('day-activities-list');
    
    if (activities.length === 0) {
        listContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <p class="text-muted">Nenhuma atividade agendada para este dia</p>
            </div>
        `;
    } else {
        let html = '<div class="list-group">';
        
        activities.forEach(activity => {
            const typeIcons = {
                'call': 'phone',
                'email': 'envelope',
                'meeting': 'users',
                'task': 'tasks',
                'note': 'sticky-note'
            };
            const typeTranslations = {
                'call': 'Ligação',
                'email': 'E-mail',
                'meeting': 'Reunião',
                'task': 'Tarefa',
                'note': 'Nota'
            };
            const typeColors = {
                'call': '#e91e63',      // Rosa/Pink
                'meeting': '#9c27b0',   // Roxo
                'email': '#ba68c8',     // Roxo claro/Lilás
                'task': '#6a1b9a',      // Roxo escuro
                'note': '#ff9800'       // Laranja
            };
            
            const icon = typeIcons[activity.type] || 'tasks';
            const typeText = typeTranslations[activity.type] || activity.type;
            const typeColor = typeColors[activity.type] || '#6c757d';
            const time = activity.scheduled_date ? new Date(activity.scheduled_date).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'}) : 'N/A';
            
            const statusColors = {
                'Pendente': 'warning',
                'Concluída': 'success',
                'Cancelada': 'secondary'
            };
            const statusColor = statusColors[activity.status] || 'secondary';
            
            const priorityColors = {
                'Urgente': 'danger',
                'Alta': 'warning',
                'Média': 'info',
                'Baixa': 'secondary'
            };
            const priorityColor = priorityColors[activity.priority] || 'secondary';
            
            html += `
                <a href="<?= $_ENV['URL_ADM'] ?>crm-view-activity/${activity.id}" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="fas fa-${icon} me-1" style="color: ${typeColor};"></i>
                                ${activity.title}
                            </h6>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>${time}
                                <span class="ms-2 badge" style="background-color: ${typeColor}; font-size: 0.75rem;">${typeText}</span>
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${statusColor} mb-1">${activity.status}</span><br>
                            <span class="badge bg-${priorityColor}" style="font-size: 0.7rem;">${activity.priority}</span>
                        </div>
                    </div>
                </a>
            `;
        });
        
        html += '</div>';
        listContainer.innerHTML = html;
    }
    
    // Configurar botão de adicionar atividade
    const btnAddActivity = document.getElementById('btn-add-activity-day');
    btnAddActivity.onclick = function() {
        // Fechar modal atual
        bootstrap.Modal.getInstance(document.getElementById('modalDayActivities')).hide();
        
        // Abrir modal de nova atividade
        openNewActivityModal(date);
    };
    
    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('modalDayActivities'));
    modal.show();
}
</script>

