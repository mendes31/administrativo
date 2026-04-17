<?php
use App\adms\Helpers\CSRFHelper;

$selectedMonth = (string) ($this->data['selected_month'] ?? date('Y-m'));
$prevMonth = (string) ($this->data['prev_month'] ?? '');
$nextMonth = (string) ($this->data['next_month'] ?? '');
$monthNamePt = (string) ($this->data['month_name_pt'] ?? '');
/** @var \DateTimeImmutable $firstDay */
$firstDay = $this->data['first_day'] ?? new \DateTimeImmutable(date('Y-m-01'));
/** @var \DateTimeImmutable $lastDay */
$lastDay = $this->data['last_day'] ?? new \DateTimeImmutable(date('Y-m-t'));
$daysInMonth = (int) $lastDay->format('d');
$firstWeekday = (int) $firstDay->format('N');
$agendaByDate = $this->data['agenda_by_date'] ?? [];
$tomorrow = $this->data['tomorrow_items'] ?? [];
$overlaps = $this->data['overlap_warnings'] ?? [];
$editingPersonal = $this->data['editing_personal'] ?? null;
$csrf = $this->data['csrf_personal'] ?? CSRFHelper::generateCSRFToken('form_my_calendar_personal');
$bp = $this->data['buttonPermission'] ?? [];
$permViewBooking = in_array('ViewBooking', $bp, true);
$permUpdateBooking = in_array('UpdateBooking', $bp, true);
$permViewCompanyEvent = in_array('ViewCompanyEvent', $bp, true);
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';

/**
 * @param array<string, mixed> $row
 * @return array{0: string, 1: string, 2: string} href, label, css
 */
$myCalPrimaryAction = static function (array $row) use ($base, $selectedMonth, $permViewBooking, $permViewCompanyEvent): array {
    $src = (string) ($row['source'] ?? '');
    $id = (int) ($row['id'] ?? 0);
    if ($id <= 0) {
        return ['#', 'Detalhe', 'btn-outline-secondary'];
    }
    if ($src === 'personal') {
        return [$base . 'my-calendar?month=' . rawurlencode($selectedMonth) . '&edit=' . $id, 'Editar compromisso', 'btn-primary'];
    }
    if ($src === 'room_booking' && $permViewBooking) {
        return [$base . 'view-booking/' . $id, 'Ver reserva (organizador)', 'btn-outline-primary'];
    }
    if ($src === 'room_invite') {
        $tok = trim((string) ($row['rsvp_token'] ?? ''));
        if ($tok !== '') {
            return [$base . 'meeting-booking-rsvp/' . rawurlencode($tok), 'Responder ao convite (RSVP)', 'btn-outline-primary'];
        }

        return ['#', 'Convite sem link', 'btn-outline-secondary'];
    }
    if ($src === 'company_event' && $permViewCompanyEvent) {
        return [$base . 'view-company-event/' . $id, 'Ver evento / RSVP', 'btn-success'];
    }

    return ['#', 'Detalhe', 'btn-outline-secondary'];
};
?>
<?php include __DIR__ . '/../rooms/partials/module_head.php'; ?>
<div class="container-fluid rooms-module-page my-calendar-page px-2 px-sm-3 px-md-4">
    <div class="mb-2 mb-md-1 d-flex flex-column flex-md-row gap-2 align-items-start align-items-md-center">
        <h2 class="rooms-page-title mt-2 mt-md-3 mb-0">Meu calendário</h2>
        <ol class="breadcrumb mb-0 mt-1 mt-md-3 ms-md-auto small">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>profile" class="text-decoration-none">Meu perfil</a></li>
            <li class="breadcrumb-item active">Calendário</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <?php if ($overlaps !== []): ?>
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle me-1"></i> Conflitos no período visível</strong>
            <ul class="mb-0 mt-2 small">
                <?php foreach ($overlaps as $w): ?>
                    <li><?= htmlspecialchars($w, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card mb-3 border-light shadow-sm">
        <div class="card-header bg-warning bg-opacity-25"><i class="fas fa-sun me-2"></i>Lembretes — amanhã</div>
        <div class="card-body py-2">
            <?php if ($tomorrow === []): ?>
                <p class="text-muted mb-0 small">Sem compromissos agendados para amanhã.</p>
            <?php else: ?>
                <ul class="mb-0 small">
                    <?php foreach ($tomorrow as $t): ?>
                        <li>
                            <strong><?= htmlspecialchars((string) ($t['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                            — <?= htmlspecialchars((string) ($t['start_datetime'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4 order-lg-2">
            <?php if (is_array($editingPersonal) && $editingPersonal !== []): ?>
                <div class="card shadow-sm border-primary mb-3">
                    <div class="card-header bg-primary text-white"><i class="fas fa-edit me-2"></i>Editar compromisso pessoal</div>
                    <div class="card-body">
                        <form method="post" class="row g-2">
                            <input type="hidden" name="return_month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="calendar_action" value="update_personal">
                            <input type="hidden" name="entry_id" value="<?= (int) ($editingPersonal['id'] ?? 0) ?>">
                            <div class="col-12">
                                <label class="form-label">Título</label>
                                <input type="text" name="personal_title" class="form-control" required maxlength="255"
                                       value="<?= htmlspecialchars((string) ($editingPersonal['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Início</label>
                                <?php $es = strtotime((string) ($editingPersonal['start_datetime'] ?? '')); ?>
                                <input type="datetime-local" name="personal_start" class="form-control" required
                                       value="<?= $es !== false ? date('Y-m-d\TH:i', $es) : '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fim</label>
                                <?php $ee = strtotime((string) ($editingPersonal['end_datetime'] ?? '')); ?>
                                <input type="datetime-local" name="personal_end" class="form-control" required
                                       value="<?= $ee !== false ? date('Y-m-d\TH:i', $ee) : '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrição (opcional)</label>
                                <textarea name="personal_description" class="form-control" rows="2"><?= htmlspecialchars((string) ($editingPersonal['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-primary">Guardar alterações</button>
                                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($base . 'my-calendar?month=' . rawurlencode($selectedMonth), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
                            </div>
                        </form>
                        <form method="post" class="mt-2 pt-2 border-top" onsubmit="return confirm('Remover este compromisso?');">
                            <input type="hidden" name="return_month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="calendar_action" value="delete_personal">
                            <input type="hidden" name="entry_id" value="<?= (int) ($editingPersonal['id'] ?? 0) ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Apagar compromisso</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header"><i class="fas fa-plus-circle me-2"></i>Novo compromisso pessoal</div>
                <div class="card-body">
                    <form method="post" class="row g-2">
                        <input type="hidden" name="return_month" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="calendar_action" value="add_personal">
                        <div class="col-12">
                            <label class="form-label">Título</label>
                            <input type="text" name="personal_title" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Início</label>
                            <input type="datetime-local" name="personal_start" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fim</label>
                            <input type="datetime-local" name="personal_end" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição (opcional)</label>
                            <textarea name="personal_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 order-lg-1">
            <div class="card mb-4 border-light shadow">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 py-md-3" style="background-color: #2E9263; color: white;">
                    <span class="fw-semibold"><i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($monthNamePt, ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="btn-group">
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base . 'my-calendar?month=' . rawurlencode($prevMonth), ENT_QUOTES, 'UTF-8') ?>" title="Mês anterior"><i class="fas fa-chevron-left"></i></a>
                        <button type="button" class="btn btn-sm btn-light text-dark" disabled><strong>Vista mensal</strong></button>
                        <a class="btn btn-sm btn-outline-light" href="<?= htmlspecialchars($base . 'my-calendar?month=' . rawurlencode($nextMonth), ENT_QUOTES, 'UTF-8') ?>" title="Mês seguinte"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="alert alert-info mx-3 mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-1"></i>A vista mensal mostra os sete dias na mesma grelha, sem rolar para o lado. Em ecrãs estreitos os dias da semana aparecem abreviados (Seg, Ter, …); o nome completo pode surgir como dica no dispositivo.
                    </div>
                    <form method="get" class="row g-2 p-3 pb-0 m-0 align-items-end">
                        <div class="col-auto">
                            <label class="form-label small mb-0">Mês / ano</label>
                            <input type="month" name="month" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Ir</button>
                        </div>
                    </form>
                    <div class="rooms-calendar-scroll">
                        <div class="outlook-calendar p-3 pt-2">
                            <div class="calendar-grid-outlook">
                                <div class="calendar-day-header-outlook" aria-label="Segunda"><abbr title="Segunda">Seg</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Terça"><abbr title="Terça">Ter</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Quarta"><abbr title="Quarta">Qua</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Quinta"><abbr title="Quinta">Qui</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Sexta"><abbr title="Sexta">Sex</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Sábado"><abbr title="Sábado">Sáb</abbr></div>
                                <div class="calendar-day-header-outlook" aria-label="Domingo"><abbr title="Domingo">Dom</abbr></div>
                                <?php
                                for ($i = 1; $i < $firstWeekday; $i++) {
                                    echo '<div class="calendar-day-outlook other-month"></div>';
                                }
                                $today = date('Y-m-d');
                                for ($day = 1; $day <= $daysInMonth; $day++) {
                                    $date = $selectedMonth . '-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                                    $isToday = ($date === $today);
                                    $dayClass = 'calendar-day-outlook' . ($isToday ? ' today' : '');
                                    $dayItems = $agendaByDate[$date] ?? [];
                                    $cnt = count($dayItems);
                                    echo '<div class="' . htmlspecialchars($dayClass, ENT_QUOTES, 'UTF-8') . '" data-date="' . htmlspecialchars($date, ENT_QUOTES, 'UTF-8') . '" data-day-items="' . (int) $cnt . '" role="button" tabindex="0">';
                                    echo '<div class="calendar-day-number-outlook">' . (int) $day . '</div>';
                                    if ($cnt > 0) {
                                        echo '<div class="calendar-bookings-preview">';
                                        $shown = 0;
                                        foreach ($dayItems as $ev) {
                                            if ($shown >= 3) {
                                                break;
                                            }
                                            $st = strtotime((string) ($ev['start_datetime'] ?? ''));
                                            $en = strtotime((string) ($ev['end_datetime'] ?? ''));
                                            $t0 = $st !== false ? date('H:i', $st) : '';
                                            $t1 = $en !== false ? date('H:i', $en) : '';
                                            $tit = htmlspecialchars((string) ($ev['title'] ?? ''), ENT_QUOTES, 'UTF-8');
                                            $short = mb_strlen($tit) > 22 ? mb_substr($tit, 0, 20) . '…' : $tit;
                                            echo '<div class="booking-preview-item" title="' . $tit . '"><span class="booking-time">' . htmlspecialchars($t0 . '–' . $t1, ENT_QUOTES, 'UTF-8') . '</span> <span class="booking-title">' . $short . '</span></div>';
                                            $shown++;
                                        }
                                        if ($cnt > 3) {
                                            echo '<div class="booking-preview-more">+ ' . ($cnt - 3) . ' mais</div>';
                                        }
                                        echo '</div>';
                                    } else {
                                        echo '<div class="calendar-day-empty text-muted small">—</div>';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="small text-muted">Inclui reservas em que é organizador, convites de salas, eventos corporativos (com RSVP) e compromissos pessoais. Toque num dia com itens para abrir detalhes e ligações (editar reserva, RSVP ou compromisso pessoal).</p>
        </div>
    </div>
</div>

<div class="modal fade" id="myCalDayModal" tabindex="-1" aria-labelledby="myCalDayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myCalDayModalLabel">Compromissos do dia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="myCalDayModalBody"></div>
        </div>
    </div>
</div>

<?php
$dayModalPayload = [];
foreach ($agendaByDate as $d => $list) {
    $dayModalPayload[$d] = [];
    foreach ($list as $row) {
        [$href, $label, $btnClass] = $myCalPrimaryAction($row);
        $src = (string) ($row['source'] ?? '');
        $id = (int) ($row['id'] ?? 0);
        $st = strtotime((string) ($row['start_datetime'] ?? ''));
        $en = strtotime((string) ($row['end_datetime'] ?? ''));
        $extra = [
            'title' => (string) ($row['title'] ?? ''),
            'start' => $st !== false ? date('d/m/Y H:i', $st) : '',
            'end' => $en !== false ? date('d/m/Y H:i', $en) : '',
            'source' => $src,
            'primary_href' => $href,
            'primary_label' => $label,
            'primary_class' => $btnClass,
        ];
        if ($src === 'room_booking' && $permUpdateBooking && $permViewBooking) {
            $extra['secondary_href'] = $base . 'update-booking/' . $id;
            $extra['secondary_label'] = 'Editar reserva';
        } elseif ($src === 'personal') {
            $extra['secondary_href'] = $base . 'my-calendar?month=' . rawurlencode($selectedMonth) . '&edit=' . $id;
            $extra['secondary_label'] = 'Editar na página';
        }
        $dayModalPayload[$d][] = $extra;
    }
}
?>
<script>
(function () {
    var byDay = <?= json_encode($dayModalPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('myCalDayModal');
        var bodyEl = document.getElementById('myCalDayModalBody');
        var titleEl = document.getElementById('myCalDayModalLabel');
        if (!modalEl || !bodyEl) return;
        var modal = new bootstrap.Modal(modalEl);

        function openDay(dateStr) {
            var items = byDay[dateStr] || [];
            if (items.length === 0) return;
            if (titleEl) titleEl.textContent = 'Compromissos — ' + dateStr;
            var html = '';
            items.forEach(function (it) {
                html += '<div class="card mb-3"><div class="card-body">';
                html += '<h6 class="fw-bold">' + escapeHtml(it.title) + '</h6>';
                html += '<p class="small text-muted mb-2">' + escapeHtml(it.start) + ' — ' + escapeHtml(it.end) + '<br><span class="badge bg-light text-dark border">' + escapeHtml(it.source) + '</span></p>';
                html += '<div class="d-flex flex-wrap gap-2">';
                if (it.primary_href && it.primary_href !== '#') {
                    html += '<a class="btn btn-sm ' + escapeAttr(it.primary_class) + '" href="' + encodeURI(it.primary_href) + '">' + escapeHtml(it.primary_label) + '</a>';
                }
                if (it.secondary_href) {
                    html += '<a class="btn btn-sm btn-outline-secondary" href="' + encodeURI(it.secondary_href) + '">' + escapeHtml(it.secondary_label) + '</a>';
                }
                html += '</div></div></div>';
            });
            bodyEl.innerHTML = html;
            modal.show();
        }

        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function escapeAttr(s) {
            return escapeHtml(s).replace(/'/g, '&#39;');
        }

        document.querySelectorAll('.my-calendar-page .calendar-day-outlook:not(.other-month)').forEach(function (cell) {
            var n = parseInt(cell.getAttribute('data-day-items') || '0', 10);
            if (n <= 0) return;
            var dateStr = cell.getAttribute('data-date');
            if (!dateStr) return;
            cell.addEventListener('click', function () { openDay(dateStr); });
            cell.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    openDay(dateStr);
                }
            });
        });
    });
})();
</script>
