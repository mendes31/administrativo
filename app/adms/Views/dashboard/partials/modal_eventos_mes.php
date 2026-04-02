<?php
$mesesPt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];
$ySel = (int)date('Y');
$mSel = 0;
$events = $this->data['company_events_dashboard'] ?? [];
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
                            <option value="0" <?php echo $mSel === 0 ? 'selected' : ''; ?>>Todos os meses</option>
                            <?php foreach ($mesesPt as $n => $nome): ?>
                                <option value="<?php echo $n; ?>"><?php echo $nome; ?></option>
                            <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3 d-grid">
                            <button type="button" class="btn btn-primary btn-sm eventos-mes-filtrar-btn" id="eventosMesFiltrar" title="Atualiza a lista conforme ano e mês">Aplicar</button>
                        </div>
                    </div>
                </div>
                <div id="eventosMesLista" class="eventos-mes-lista">
                    <?php if (empty($events)): ?>
                        <p class="text-muted text-center py-3 mb-0">Nenhum evento ativo neste período.</p>
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
        scroll-padding-bottom: 1.25rem;
        scroll-padding-top: 0.5rem;
    }

    #modalEventosMes .evento-mes-card {
        border: 1px solid #d9e5f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05) !important;
    }

    #modalEventosMes .evento-mes-card.evento-mes-past {
        opacity: 0.96;
        background: #fafbfc !important;
    }

    #modalEventosMes .evento-mes-day-badge {
        width: 46px;
        border: 1px solid #d3deea;
        border-radius: 8px;
        background: #f8fbff;
        padding: 3px 0;
        margin-top: 0;
    }

    #modalEventosMes .evento-mes-day-num {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f3a56;
        line-height: 1.05;
    }

    #modalEventosMes .evento-mes-day-mon {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.35px;
        color: #3a78b3;
        line-height: 1.05;
    }

    #modalEventosMes .evento-mes-content h6 {
        font-size: 0.95rem;
        line-height: 1.25;
        margin-bottom: 0.2rem !important;
    }

    #modalEventosMes .evento-mes-desc-inner.evento-mes-desc-clamped {
        max-height: 4.2em;
        overflow: hidden;
        line-height: 1.4;
    }

    #modalEventosMes .evento-mes-desc-fade {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 1.75rem;
        background: linear-gradient(to bottom, rgba(255, 255, 255, 0), #fff);
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.15s ease;
    }

    #modalEventosMes .evento-mes-card.evento-mes-past .evento-mes-desc-fade {
        background: linear-gradient(to bottom, rgba(250, 251, 252, 0), #fafbfc);
    }

    #modalEventosMes .evento-mes-desc-fade.is-visible {
        opacity: 1;
    }

    #modalEventosMes .evento-mes-guests-box {
        border-top: 1px solid #e8eef4;
        margin-top: 0.35rem;
        padding-top: 0.45rem;
        scroll-margin-top: 0.75rem;
        scroll-margin-bottom: 1rem;
    }

    #modalEventosMes .btn:disabled,
    #modalEventosMes .btn.disabled {
        cursor: not-allowed;
        opacity: 0.55;
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

    function escAttr(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function toDate(v) {
        const d = new Date(v);
        return Number.isNaN(d.getTime()) ? null : d;
    }

    function isDeadlinePassed(raw) {
        if (raw === null || raw === undefined || String(raw).trim() === '') {
            return false;
        }
        const d = toDate(raw);
        if (!d) return false;
        return d.getTime() < Date.now();
    }

    /** Badge de RSVP ao lado do título (eventos com confirmação obrigatória). */
    function rsvpStatusBadgeHtml(st, req) {
        if (!req) return '';
        const s = String(st || '').trim();
        if (s === 'confirmed') {
            return '<span class="badge bg-success align-middle ms-1 flex-shrink-0">Confirmado</span>';
        }
        if (s === 'declined') {
            return '<span class="badge bg-secondary align-middle ms-1 flex-shrink-0">Recusado</span>';
        }
        if (s === 'cancelled') {
            return '<span class="badge bg-warning text-dark align-middle ms-1 flex-shrink-0">Presença cancelada</span>';
        }
        return '<span class="badge bg-light text-dark border align-middle ms-1 flex-shrink-0">Pendente</span>';
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

    function isEventPast(ev) {
        const end = toDate(ev.ends_at);
        return !!(end && end.getTime() < Date.now());
    }

    /**
     * Garante que o painel de convidados fique visível dentro do modal (lista com scroll).
     */
    function scrollGuestPanelIntoView(box) {
        if (!box) return;
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                box.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                var firstInput = box.querySelector('.guest-name');
                if (firstInput) {
                    setTimeout(function () {
                        try {
                            firstInput.focus({ preventScroll: true });
                        } catch (err) {
                            firstInput.focus();
                        }
                    }, 400);
                }
            });
        });
    }

    function guestRowHtml(name, rel) {
        return '' +
            '<div class="row g-1 mb-1 align-items-end guest-row">' +
            '<div class="col-6 col-sm-5">' +
            '<label class="form-label small mb-0 text-muted">Nome</label>' +
            '<input type="text" class="form-control form-control-sm guest-name" maxlength="200" placeholder="Nome completo" value="' + esc(name || '') + '">' +
            '</div>' +
            '<div class="col-4 col-sm-5">' +
            '<label class="form-label small mb-0 text-muted">Relação</label>' +
            '<input type="text" class="form-control form-control-sm guest-rel" maxlength="100" placeholder="Opcional" value="' + esc(rel || '') + '">' +
            '</div>' +
            '<div class="col-2 d-grid">' +
            '<span class="form-label small mb-0 d-none d-sm-block">&nbsp;</span>' +
            '<button type="button" class="btn btn-outline-danger btn-sm btn-remove-guest" title="Remover"><i class="fas fa-trash"></i></button>' +
            '</div>' +
            '</div>';
    }

    function renderEventCard(ev) {
        const rsvp = ev.rsvp || null;
        const st = rsvp ? (rsvp.status || '') : '';
        const rsvpDeadline = ev.rsvp_deadline;
        const cancelDeadline = ev.cancellation_deadline;
        const rsvpClosed = isDeadlinePassed(rsvpDeadline);
        const cancelClosed = isDeadlinePassed(cancelDeadline);
        const disRsvp = rsvpClosed ? ' disabled aria-disabled="true" title="' + escAttr('Prazo de confirmação encerrado.') + '"' : '';
        const disCancel = cancelClosed ? ' disabled aria-disabled="true" title="' + escAttr('Prazo de cancelamento encerrado.') + '"' : '';
        const req = ev.requires_rsvp == 1 || ev.requires_rsvp === true;
        const guests = ev.allows_guests == 1 || ev.allows_guests === true;
        const maxG = parseInt(ev.max_guests_per_user || 0, 10) || 0;
        const when = formatRange(ev.starts_at, ev.ends_at);
        const past = isEventPast(ev);

        let actions = '';
        let statusHint = '';
        if (req && !past) {
            if (st === 'confirmed') {
                actions += '<button type="button" class="btn btn-outline-danger btn-sm" data-action="cancel" data-id="' + ev.id + '"' + disCancel + '>Cancelar presença</button> ';
            } else if (st === 'declined') {
                if (!rsvpClosed) {
                    actions += '<button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="' + ev.id + '">' +
                        '<i class="fas fa-check me-1"></i>Confirmar presença</button>';
                    if (guests && maxG > 0) {
                        actions += '<button type="button" class="btn btn-outline-primary btn-sm" data-action="toggle-guests" data-id="' + ev.id + '" title="' +
                            escAttr('Informar acompanhantes (máx. ' + maxG + ')') + '">' +
                            '<i class="fas fa-user-plus me-1"></i>Convidados <span class="fw-normal">(máx. ' + maxG + ')</span>' +
                            '</button>';
                    }
                    statusHint = '<div class="small text-muted mt-1"><i class="fas fa-info-circle me-1"></i>Você recusou antes; ainda pode confirmar dentro do prazo.</div>';
                } else {
                    statusHint = '<div class="small text-muted mt-1">Prazo de confirmação encerrado — resposta mantida como recusada.</div>';
                }
            } else {
                actions += '<button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="' + ev.id + '"' + disRsvp + '>Confirmar</button>';
                actions += '<button type="button" class="btn btn-outline-secondary btn-sm" data-action="decline" data-id="' + ev.id + '"' + disRsvp + '>Recusar</button>';
                if (guests && maxG > 0) {
                    const gTitle = rsvpClosed
                        ? escAttr('Prazo de confirmação encerrado.')
                        : escAttr('Informar acompanhantes (máx. ' + maxG + ')');
                    const disG = rsvpClosed ? ' disabled aria-disabled="true"' : '';
                    actions += '<button type="button" class="btn btn-outline-primary btn-sm" data-action="toggle-guests" data-id="' + ev.id + '" title="' + gTitle + '"' + disG + '>' +
                        '<i class="fas fa-user-plus me-1"></i>Convidados <span class="fw-normal">(máx. ' + maxG + ')</span>' +
                        '</button>';
                }
            }
        }
        if (past) {
            actions += '<span class="badge text-bg-secondary">Realizado — apenas consulta</span>';
        }

        let guestsHtml = '';
        // Inclui quem recusou mas ainda pode confirmar (dentro do prazo): precisa informar convidados ao confirmar de novo.
        if (!past && guests && maxG > 0 && req && !rsvpClosed && st !== 'confirmed') {
            guestsHtml =
                '<div class="evento-mes-guests-box d-none" data-guests-box data-max-guests="' + maxG + '">' +
                '<div class="guest-rows"></div>' +
                '<button type="button" class="btn btn-link btn-sm text-decoration-none p-0 mt-1" data-action="add-guest" data-id="' + ev.id + '">' +
                '<i class="fas fa-plus small me-1"></i>Outro convidado' +
                '</button>' +
                '</div>';
        }

        let descHtml = '';
        if (ev.description) {
            const raw = String(ev.description);
            descHtml =
                '<div class="evento-mes-desc-shell position-relative mt-1">' +
                '<div class="evento-mes-desc-inner evento-mes-desc-clamped small text-body mb-0">' +
                esc(raw).replace(/\r\n|\r|\n/g, '<br>') +
                '</div>' +
                '<div class="evento-mes-desc-fade" aria-hidden="true"></div>' +
                '<button type="button" class="btn btn-link btn-sm text-decoration-none p-0 mt-1 evento-mes-desc-toggle d-none" aria-expanded="false">Ler mais</button>' +
                '</div>';
        }

        const pastClass = past ? ' evento-mes-past' : '';
        return (
            '<div class="card border-0 shadow-sm mb-2 evento-mes-card' + pastClass + '" data-event-id="' + ev.id + '">' +
            '<div class="card-body p-2">' +
            '<div class="evento-mes-row d-flex align-items-start gap-3">' +
            '<div class="evento-mes-day-badge text-center flex-shrink-0" aria-hidden="true">' +
            '<div class="evento-mes-day-num">' + esc(when.dayNum) + '</div>' +
            '<div class="evento-mes-day-mon">' + esc(when.dayMon) + '</div>' +
            '</div>' +
            '<div class="evento-mes-content flex-grow-1 min-w-0">' +
            '<h6 class="fw-bold mb-1 d-flex flex-wrap align-items-center gap-1">' +
            '<span class="min-w-0">' + esc(ev.title || '') + '</span>' +
            rsvpStatusBadgeHtml(st, req) +
            '</h6>' +
            '<div class="text-muted small mb-1">' +
            esc(when.rangeLabel) +
            (when.durationDays > 1 ? ' <span class="badge text-bg-light border ms-1">' + esc(String(when.durationDays)) + ' dias</span>' : '') +
            '</div>' +
            '</div>' +
            '</div>' +
            (ev.location ? '<div class="text-muted small mb-1"><i class="fas fa-map-marker-alt me-1"></i>' + esc(ev.location) + '</div>' : '') +
            descHtml +
            (rsvpDeadline ? '<div class="small text-warning mb-1"><i class="fas fa-clock me-1"></i>Confirmação até: ' + esc(rsvpDeadline) + '</div>' : '') +
            (cancelDeadline ? '<div class="small text-secondary mb-1"><i class="fas fa-ban me-1"></i>Cancelamento até: ' + esc(cancelDeadline) + '</div>' : '') +
            '<div class="d-flex flex-wrap gap-1 align-items-center">' + actions + '</div>' +
            statusHint +
            guestsHtml +
            '</div></div>'
        );
    }

    function renderList(events) {
        const el = document.getElementById('eventosMesLista');
        if (!el) return;
        if (!events || !events.length) {
            el.innerHTML = '<p class="text-muted text-center py-3 mb-0">Nenhum evento ativo neste período.</p>';
            return;
        }
        el.innerHTML = events.map(renderEventCard).join('');
        initEventoMesDescToggles(el);
    }

    function initEventoMesDescToggles(container) {
        if (!container) return;
        container.querySelectorAll('.evento-mes-desc-shell').forEach(function (shell) {
            if (shell.getAttribute('data-readmore-bound') === '1') return;
            shell.setAttribute('data-readmore-bound', '1');
            var inner = shell.querySelector('.evento-mes-desc-inner');
            var btn = shell.querySelector('.evento-mes-desc-toggle');
            var fade = shell.querySelector('.evento-mes-desc-fade');
            if (!inner || !btn) return;

            function applyClamp() {
                inner.classList.add('evento-mes-desc-clamped');
            }
            function removeClamp() {
                inner.classList.remove('evento-mes-desc-clamped');
            }

            applyClamp();
            requestAnimationFrame(function () {
                if (inner.scrollHeight <= inner.clientHeight + 4) {
                    removeClamp();
                    btn.classList.add('d-none');
                    if (fade) fade.classList.remove('is-visible');
                    return;
                }
                btn.classList.remove('d-none');
                if (fade) fade.classList.add('is-visible');
            });

            btn.addEventListener('click', function () {
                var expanded = btn.getAttribute('aria-expanded') === 'true';
                if (!expanded) {
                    removeClamp();
                    btn.textContent = 'Ler menos';
                    btn.setAttribute('aria-expanded', 'true');
                    if (fade) fade.classList.remove('is-visible');
                } else {
                    applyClamp();
                    btn.textContent = 'Ler mais';
                    btn.setAttribute('aria-expanded', 'false');
                    requestAnimationFrame(function () {
                        if (inner.scrollHeight > inner.clientHeight + 4 && fade) {
                            fade.classList.add('is-visible');
                        }
                    });
                }
            });
        });
    }

    function bindRsvpDelegation() {
        const lista = document.getElementById('eventosMesLista');
        if (!lista || lista.getAttribute('data-rsvp-delegation') === '1') return;
        lista.setAttribute('data-rsvp-delegation', '1');
        lista.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-action][data-id]');
            if (!btn || !lista.contains(btn)) return;
            if (btn.disabled || btn.getAttribute('aria-disabled') === 'true') {
                e.preventDefault();
                return;
            }
            const id = parseInt(btn.getAttribute('data-id'), 10);
            const action = btn.getAttribute('data-action');
            if (!id || !action) return;
            const card = btn.closest('.evento-mes-card');
            if (!card) return;

            if (action === 'toggle-guests') {
                e.preventDefault();
                const box = card.querySelector('[data-guests-box]');
                if (!box) return;
                box.classList.toggle('d-none');
                if (box.classList.contains('d-none')) {
                    return;
                }
                const rowsWrap = box.querySelector('.guest-rows');
                if (rowsWrap && rowsWrap.querySelectorAll('.guest-row').length === 0) {
                    rowsWrap.insertAdjacentHTML('beforeend', guestRowHtml('', ''));
                    bindGuestRowRemove(rowsWrap);
                }
                scrollGuestPanelIntoView(box);
                return;
            }

            if (action === 'add-guest') {
                e.preventDefault();
                const box = card.querySelector('[data-guests-box]');
                if (!box) return;
                const rowsWrap = box.querySelector('.guest-rows');
                const maxGuests = parseInt(box.getAttribute('data-max-guests') || '0', 10) || 0;
                const current = rowsWrap ? rowsWrap.querySelectorAll('.guest-row').length : 0;
                if (!rowsWrap || current >= maxGuests) {
                    alert('Limite de convidados atingido.');
                    return;
                }
                rowsWrap.insertAdjacentHTML('beforeend', guestRowHtml('', ''));
                bindGuestRowRemove(rowsWrap);
                var lastRow = rowsWrap.lastElementChild;
                if (lastRow) {
                    requestAnimationFrame(function () {
                        requestAnimationFrame(function () {
                            lastRow.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                            var inp = lastRow.querySelector('.guest-name');
                            if (inp) {
                                setTimeout(function () {
                                    try {
                                        inp.focus({ preventScroll: true });
                                    } catch (err2) {
                                        inp.focus();
                                    }
                                }, 350);
                            }
                        });
                    });
                }
                return;
            }

            const fd = new FormData();
            fd.append('event_id', String(id));
            fd.append('action', action === 'cancel' ? 'cancel' : (action === 'decline' ? 'decline' : 'confirm'));
            if (action === 'confirm') {
                const guestsPayload = collectGuestsFromCard(card);
                if (guestsPayload.length) {
                    fd.append('guests', JSON.stringify(guestsPayload));
                }
            }
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
        });
    }

    function bindGuestRowRemove(scope) {
        if (!scope) return;
        scope.querySelectorAll('.btn-remove-guest').forEach(function (btn) {
            btn.onclick = function () {
                const row = btn.closest('.guest-row');
                if (row) row.remove();
            };
        });
    }

    function collectGuestsFromCard(card) {
        const result = [];
        card.querySelectorAll('[data-guests-box] .guest-row').forEach(function (row) {
            const nEl = row.querySelector('.guest-name');
            const rEl = row.querySelector('.guest-rel');
            const fullName = nEl ? String(nEl.value || '').trim() : '';
            const relationship = rEl ? String(rEl.value || '').trim() : '';
            if (fullName.length > 0) {
                result.push({ full_name: fullName, relationship: relationship });
            }
        });
        return result;
    }

    function loadMonth(year, month) {
        const m = (month === '' || month === undefined) ? '0' : String(month);
        fetch(base + 'company-events-month?year=' + encodeURIComponent(year) + '&month=' + encodeURIComponent(m), {
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
    bindRsvpDelegation();
    initEventoMesDescToggles(document.getElementById('eventosMesLista'));
})();
</script>
