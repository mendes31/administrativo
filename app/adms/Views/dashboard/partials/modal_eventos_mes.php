<?php
$mesesPt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];
$ySel = (int)date('Y');
$mSel = (int)date('n');
$events = $this->data['company_events_month'] ?? [];
$urlAdm = $_ENV['URL_ADM'] ?? '';
?>
<div class="modal fade" id="modalEventosMes" tabindex="-1" aria-labelledby="modalEventosMesLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down modal-lg modal-dialog-centered">
        <div class="modal-content birthday-modal-content">
            <div class="modal-header birthday-modal-header">
                <h5 class="modal-title" id="modalEventosMesLabel">Eventos corporativos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body birthday-modal-body">
                <div class="eventos-mes-toolbar mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <label class="form-label small fw-semibold mb-1" for="eventosMesAno">Ano</label>
                            <select id="eventosMesAno" class="form-select form-select-sm">
                            <?php for ($yy = $ySel - 1; $yy <= $ySel + 2; $yy++): ?>
                                <option value="<?php echo $yy; ?>" <?php echo $yy === $ySel ? 'selected' : ''; ?>><?php echo $yy; ?></option>
                            <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-semibold mb-1" for="eventosMesNum">Mês</label>
                            <select id="eventosMesNum" class="form-select form-select-sm">
                            <?php foreach ($mesesPt as $n => $nome): ?>
                                <option value="<?php echo $n; ?>" <?php echo $n === $mSel ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3 d-grid">
                            <button type="button" class="btn btn-primary btn-sm eventos-mes-filtrar-btn" id="eventosMesFiltrar">Filtrar</button>
                        </div>
                    </div>
                </div>
                <div id="eventosMesLista" class="eventos-mes-lista">
                    <?php if (empty($events)): ?>
                        <p class="text-muted text-center py-3 mb-0">Nenhum evento neste período.</p>
                    <?php else: ?>
                        <?php foreach ($events as $ev): ?>
                            <?php include __DIR__ . '/modal_eventos_mes_item.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #modalEventosMes .eventos-mes-toolbar {
        border: 1px solid #dbe6f1;
        border-radius: 12px;
        padding: 0.65rem;
        background: #fff;
    }

    #modalEventosMes .eventos-mes-filtrar-btn {
        font-weight: 600;
        min-height: 31px;
    }

    #modalEventosMes .eventos-mes-lista {
        max-height: 58vh;
        overflow-y: auto;
        padding-right: 2px;
    }

    #modalEventosMes .evento-mes-card {
        border: 1px solid #d9e5f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    }

    #modalEventosMes .evento-mes-day-badge {
        width: 54px;
        border: 1px solid #d3deea;
        border-radius: 10px;
        background: #f8fbff;
        padding: 4px 0;
        margin-top: 1px;
    }

    #modalEventosMes .evento-mes-day-num {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1f3a56;
        line-height: 1.05;
    }

    #modalEventosMes .evento-mes-day-mon {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        color: #3a78b3;
        line-height: 1.1;
    }

    #modalEventosMes .evento-mes-content h6 {
        line-height: 1.2;
    }
</style>
<script>
(function () {
    const base = <?php echo json_encode($urlAdm); ?>;
    const monthsShort = ['JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function toDate(v) {
        const d = new Date(v);
        return Number.isNaN(d.getTime()) ? null : d;
    }

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function formatRange(startRaw, endRaw) {
        const start = toDate(startRaw);
        const end = toDate(endRaw);
        if (!start || !end) {
            return { dayNum: '--', dayMon: '---', rangeLabel: '', durationDays: 1 };
        }

        const sameDay = start.getFullYear() === end.getFullYear()
            && start.getMonth() === end.getMonth()
            && start.getDate() === end.getDate();
        const sameMonthYear = start.getFullYear() === end.getFullYear()
            && start.getMonth() === end.getMonth();

        const dayNum = pad2(start.getDate());
        const dayMon = monthsShort[start.getMonth()] || '---';

        const startTime = pad2(start.getHours()) + ':' + pad2(start.getMinutes());
        const endTime = pad2(end.getHours()) + ':' + pad2(end.getMinutes());

        let rangeLabel = '';
        if (sameDay) {
            rangeLabel = dayNum + '/' + pad2(start.getMonth() + 1) + '/' + start.getFullYear() + ' · ' + startTime + ' às ' + endTime;
        } else if (sameMonthYear) {
            rangeLabel = dayNum + '–' + pad2(end.getDate()) + ' ' + dayMon + ' · ' + startTime + ' às ' + endTime;
        } else {
            const endMon = monthsShort[end.getMonth()] || '---';
            rangeLabel = dayNum + ' ' + dayMon + ' → ' + pad2(end.getDate()) + ' ' + endMon + ' · ' + startTime + ' às ' + endTime;
        }

        const startDateOnly = new Date(start.getFullYear(), start.getMonth(), start.getDate());
        const endDateOnly = new Date(end.getFullYear(), end.getMonth(), end.getDate());
        const durationDays = Math.max(1, Math.floor((endDateOnly - startDateOnly) / 86400000) + 1);

        return { dayNum, dayMon, rangeLabel, durationDays };
    }

    function renderEventCard(ev) {
        const rsvp = ev.rsvp || null;
        const st = rsvp ? (rsvp.status || '') : '';
        const rsvpDeadline = ev.rsvp_deadline;
        const cancelDeadline = ev.cancellation_deadline;
        const req = ev.requires_rsvp == 1 || ev.requires_rsvp === true;
        const guests = ev.allows_guests == 1 || ev.allows_guests === true;
        const maxG = parseInt(ev.max_guests_per_user || 0, 10) || 0;
        const when = formatRange(ev.starts_at, ev.ends_at);

        let actions = '';
        if (req) {
            if (st === 'confirmed') {
                actions += '<button type="button" class="btn btn-outline-danger btn-sm" data-action="cancel" data-id="' + ev.id + '">Cancelar presença</button> ';
            } else if (st !== 'declined') {
                actions += '<button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="' + ev.id + '">Confirmar</button>';
                actions += '<button type="button" class="btn btn-outline-secondary btn-sm" data-action="decline" data-id="' + ev.id + '">Recusar</button>';
            }
        }

        let guestsHtml = '';
        if (guests && maxG > 0 && (st === 'pending' || st === '' || !st)) {
            guestsHtml = '<div class="small text-muted mt-2">Convidados (máx. ' + maxG + '): preencha após confirmar.</div>';
        }

        return (
            '<div class="card border-0 shadow-sm mb-3 evento-mes-card" data-event-id="' + ev.id + '">' +
            '<div class="card-body p-3">' +
            '<div class="evento-mes-row d-flex align-items-start gap-3">' +
            '<div class="evento-mes-day-badge text-center flex-shrink-0" aria-hidden="true">' +
            '<div class="evento-mes-day-num">' + esc(when.dayNum) + '</div>' +
            '<div class="evento-mes-day-mon">' + esc(when.dayMon) + '</div>' +
            '</div>' +
            '<div class="evento-mes-content flex-grow-1 min-w-0">' +
            '<h6 class="fw-bold mb-1">' + esc(ev.title || '') + '</h6>' +
            '<div class="text-muted small mb-2">' +
            esc(when.rangeLabel) +
            (when.durationDays > 1 ? ' <span class="badge text-bg-light border ms-1">' + esc(String(when.durationDays)) + ' dias</span>' : '') +
            '</div>' +
            '</div>' +
            '</div>' +
            (ev.location ? '<div class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>' + esc(ev.location) + '</div>' : '') +
            (ev.description ? '<p class="small mb-2">' + esc(ev.description) + '</p>' : '') +
            (rsvpDeadline ? '<div class="small text-warning mb-1"><i class="fas fa-clock me-1"></i>Confirmação até: ' + esc(rsvpDeadline) + '</div>' : '') +
            (cancelDeadline ? '<div class="small text-secondary mb-2"><i class="fas fa-ban me-1"></i>Cancelamento até: ' + esc(cancelDeadline) + '</div>' : '') +
            '<div class="d-flex flex-wrap gap-1 align-items-center">' + actions + '</div>' +
            guestsHtml +
            '</div></div>'
        );
    }

    function renderList(events) {
        const el = document.getElementById('eventosMesLista');
        if (!el) return;
        if (!events || !events.length) {
            el.innerHTML = '<p class="text-muted text-center py-3 mb-0">Nenhum evento neste período.</p>';
            return;
        }
        el.innerHTML = events.map(renderEventCard).join('');
        bindRsvp();
    }

    function bindRsvp() {
        document.querySelectorAll('#eventosMesLista [data-action][data-id]').forEach(function (btn) {
            btn.onclick = function () {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const action = btn.getAttribute('data-action');
                if (!id || !action) return;
                const fd = new FormData();
                fd.append('event_id', String(id));
                fd.append('action', action === 'cancel' ? 'cancel' : (action === 'decline' ? 'decline' : 'confirm'));
                fetch(base + 'event-rsvp', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            if (typeof data.unread_year_count !== 'undefined') {
                                const unreadEl = document.getElementById('dashboardEventsUnreadCount');
                                if (unreadEl) {
                                    unreadEl.textContent = String(parseInt(data.unread_year_count, 10) || 0) + ' não lidos no ano';
                                }
                            }
                            const ano = document.getElementById('eventosMesAno');
                            const mes = document.getElementById('eventosMesNum');
                            if (ano && mes) loadMonth(ano.value, mes.value);
                        } else {
                            alert((data && data.message) ? data.message : 'Não foi possível atualizar.');
                        }
                    })
                    .catch(function () { alert('Erro de rede.'); });
            };
        });
    }

    function loadMonth(year, month) {
        fetch(base + 'company-events-month?year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(month), {
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    renderList(data.events || []);
                    if (typeof data.unread_year_count !== 'undefined') {
                        const unreadEl = document.getElementById('dashboardEventsUnreadCount');
                        if (unreadEl) {
                            unreadEl.textContent = String(parseInt(data.unread_year_count, 10) || 0) + ' não lidos no ano';
                        }
                    }
                }
            })
            .catch(function () {});
    }

    const btnFiltrar = document.getElementById('eventosMesFiltrar');
    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', function () {
            const ano = document.getElementById('eventosMesAno');
            const mes = document.getElementById('eventosMesNum');
            if (ano && mes) loadMonth(ano.value, mes.value);
        });
    }
    const modalEl = document.getElementById('modalEventosMes');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            const ano = document.getElementById('eventosMesAno');
            const mes = document.getElementById('eventosMesNum');
            if (ano && mes) loadMonth(ano.value, mes.value);
        });
    }
    bindRsvp();
})();
</script>
