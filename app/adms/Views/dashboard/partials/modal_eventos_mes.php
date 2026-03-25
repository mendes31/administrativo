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
                <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-end gap-2 mb-3">
                    <div>
                        <label class="form-label small mb-0" for="eventosMesAno">Ano</label>
                        <select id="eventosMesAno" class="form-select form-select-sm" style="min-width: 110px;">
                            <?php for ($yy = $ySel - 1; $yy <= $ySel + 2; $yy++): ?>
                                <option value="<?php echo $yy; ?>" <?php echo $yy === $ySel ? 'selected' : ''; ?>><?php echo $yy; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small mb-0" for="eventosMesNum">Mês</label>
                        <select id="eventosMesNum" class="form-select form-select-sm" style="min-width: 160px;">
                            <?php foreach ($mesesPt as $n => $nome): ?>
                                <option value="<?php echo $n; ?>" <?php echo $n === $mSel ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm mt-0 mt-md-4" id="eventosMesFiltrar">Filtrar</button>
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
<script>
(function () {
    const base = <?php echo json_encode($urlAdm); ?>;
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderEventCard(ev) {
        const rsvp = ev.rsvp || null;
        const st = rsvp ? (rsvp.status || '') : '';
        const rsvpDeadline = ev.rsvp_deadline;
        const cancelDeadline = ev.cancellation_deadline;
        const req = ev.requires_rsvp == 1 || ev.requires_rsvp === true;
        const guests = ev.allows_guests == 1 || ev.allows_guests === true;
        const maxG = parseInt(ev.max_guests_per_user || 0, 10) || 0;

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
            '<div class="card-body">' +
            '<h6 class="fw-bold mb-1">' + esc(ev.title || '') + '</h6>' +
            '<div class="text-muted small mb-2">' +
            esc(ev.starts_at || '') + ' — ' + esc(ev.ends_at || '') +
            (ev.location ? ' · ' + esc(ev.location) : '') +
            '</div>' +
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
    bindRsvp();
})();
</script>
