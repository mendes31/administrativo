<?php
$ev = $this->data['event'] ?? [];
$rsvp = $this->data['rsvp'] ?? null;
$guests = $this->data['rsvp_guests'] ?? [];
$canEdit = !empty($this->data['can_edit']);
$eventId = (int)($this->data['event_id'] ?? 0);
$st = is_array($rsvp) ? (string)($rsvp['status'] ?? '') : '';
$requiresRsvp = !empty($ev['requires_rsvp']);
$allowsGuests = !empty($ev['allows_guests']);
$maxG = (int)($ev['max_guests_per_user'] ?? 0);
$urlAdm = $_ENV['URL_ADM'] ?? '';

$fmtDt = static function (?string $v): string {
    if (empty($v)) {
        return '—';
    }
    return date('d/m/Y H:i', strtotime($v));
};

$statusLabel = [
    'pending' => 'Pendente',
    'confirmed' => 'Confirmado',
    'declined' => 'Recusado',
    'cancelled' => 'Cancelado',
][$st] ?? ($st !== '' ? $st : '—');
?>
<style>
    /* Alinhado ao cabeçalho das listagens de informativos/políticas (identidade verde) */
    .company-event-view-page .card-header.event-view-toolbar {
        background: linear-gradient(135deg, #2E9263 0%, #2C844B 55%, #236D3D 100%);
        color: #fff;
        border-bottom: none;
    }
    .company-event-view-page .card-header.event-view-toolbar .text-muted {
        color: rgba(255, 255, 255, 0.85) !important;
    }
    .company-event-view-page .card-header.event-view-toolbar .btn-light {
        background: rgba(255, 255, 255, 0.95);
        border-color: rgba(255, 255, 255, 0.4);
        color: #1a4d2e;
    }
    .company-event-view-page .card-header.event-view-toolbar .btn-light:hover {
        background: #fff;
    }
    .company-event-view-page .card-header.event-view-toolbar .btn-warning {
        border: none;
    }

    /* Descrição longa: colapsa para não empurrar RSVP/convidados */
    .company-event-view-page .event-desc-shell {
        position: relative;
    }
    .company-event-view-page .event-desc-inner.event-desc-clamped {
        max-height: 12rem;
        overflow: hidden;
    }
    .company-event-view-page .event-desc-fade {
        display: none;
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 2.75rem;
        pointer-events: none;
        background: linear-gradient(to bottom, rgba(248, 249, 250, 0), #f8f9fa);
        border-radius: 0 0 0.375rem 0.375rem;
    }
    .company-event-view-page .event-desc-fade.is-visible {
        display: block;
    }
</style>

<div class="container-fluid px-4 company-event-view-page">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title">Visualizar evento</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events" class="text-decoration-none">Eventos corporativos</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header event-view-toolbar hstack gap-2 flex-wrap py-3">
            <span class="fw-semibold"><i class="fas fa-eye me-2"></i>Detalhes do evento</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if ($canEdit): ?>
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>update-company-event/<?php echo $eventId; ?>"
                       class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($urlAdm); ?>list-company-events"
                   class="btn btn-light btn-sm mb-1 d-none d-md-inline-block">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <button type="button"
                        class="btn btn-light btn-sm mb-1 d-inline d-md-none"
                        onclick="window.history.back();"
                        aria-label="Voltar">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </button>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <h3 class="mb-2">
                            <?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['title'] ?? ''); ?>
                            <?php if (!empty($ev['requires_rsvp'])): ?>
                                <span class="badge bg-info text-dark ms-1">
                                    <i class="fas fa-user-check me-1"></i>Confirmação
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($ev['allows_guests'])): ?>
                                <span class="badge bg-secondary ms-1">
                                    <i class="fas fa-users me-1"></i>Convidados
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($ev['ativo'])): ?>
                                <span class="badge bg-success ms-1">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary ms-1">Inativo</span>
                            <?php endif; ?>
                        </h3>

                        <div class="text-muted small mb-3">
                            <i class="fas fa-user me-1"></i>Por:
                            <?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['creator_name'] ?? '—'); ?>
                            <span class="ms-3" title="Período do evento">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo htmlspecialchars($fmtDt($ev['starts_at'] ?? null)); ?>
                                —
                                <?php echo htmlspecialchars($fmtDt($ev['ends_at'] ?? null)); ?>
                            </span>
                            <?php if (!empty($ev['location'])): ?>
                                <span class="ms-3 d-inline-block">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['location']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($ev['description'])): ?>
                            <div class="mb-4">
                                <h5>Descrição</h5>
                                <div class="border rounded bg-light event-desc-shell event-view-description">
                                    <div class="p-3 pb-2 position-relative">
                                        <div class="event-desc-inner" id="eventDescInner">
                                            <?php
                                            $desc = (string)($ev['description'] ?? '');
                                            if (preg_match('/<[^>]+>/', $desc)) {
                                                echo $desc;
                                            } else {
                                                echo nl2br(\App\adms\Helpers\TextEncodingHelper::escape($desc));
                                            }
                                            ?>
                                        </div>
                                        <div class="event-desc-fade" id="eventDescFade" aria-hidden="true"></div>
                                    </div>
                                    <div class="px-3 pb-3 pt-0">
                                        <button type="button"
                                                class="btn btn-link btn-sm text-decoration-none p-0 d-none fw-semibold"
                                                id="eventDescToggle"
                                                aria-expanded="false">
                                            Ler mais
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <script>
                            (function () {
                                var inner = document.getElementById('eventDescInner');
                                var btn = document.getElementById('eventDescToggle');
                                var fade = document.getElementById('eventDescFade');
                                if (!inner || !btn) return;

                                function applyClamp() {
                                    inner.classList.add('event-desc-clamped');
                                }
                                function removeClamp() {
                                    inner.classList.remove('event-desc-clamped');
                                }

                                applyClamp();
                                requestAnimationFrame(function () {
                                    if (inner.scrollHeight <= inner.clientHeight + 6) {
                                        removeClamp();
                                        btn.classList.add('d-none');
                                        if (fade) fade.classList.remove('is-visible');
                                        return;
                                    }
                                    btn.classList.remove('d-none');
                                    if (fade) fade.classList.add('is-visible');
                                });

                                var descOpen = false;
                                btn.addEventListener('click', function () {
                                    descOpen = !descOpen;
                                    if (descOpen) {
                                        removeClamp();
                                        btn.textContent = 'Ler menos';
                                        btn.setAttribute('aria-expanded', 'true');
                                        if (fade) fade.classList.remove('is-visible');
                                    } else {
                                        applyClamp();
                                        btn.textContent = 'Ler mais';
                                        btn.setAttribute('aria-expanded', 'false');
                                        requestAnimationFrame(function () {
                                            if (inner.scrollHeight > inner.clientHeight + 6 && fade) {
                                                fade.classList.add('is-visible');
                                            }
                                        });
                                    }
                                });
                            })();
                            </script>
                        <?php endif; ?>

                        <?php if ($requiresRsvp): ?>
                            <div class="mb-4 border rounded p-3 p-md-4 bg-white shadow-sm">
                                <h5 class="mb-3">
                                    <i class="fas fa-user-check text-success me-2"></i>Sua participação
                                </h5>
                                <p class="small text-muted mb-3">
                                    Status atual: <strong class="text-body"><?php echo htmlspecialchars($statusLabel); ?></strong>
                                </p>

                                <div class="d-flex flex-wrap gap-2 mb-3" id="companyEventRsvpActions">
                                    <?php if ($st === 'confirmed'): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm" data-rsvp-action="cancel">
                                            <i class="fas fa-times me-1"></i>Cancelar presença
                                        </button>
                                    <?php elseif ($st !== 'declined'): ?>
                                        <button type="button" class="btn btn-success btn-sm" data-rsvp-action="confirm">
                                            <i class="fas fa-check me-1"></i>Confirmar presença
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-rsvp-action="decline">
                                            Recusar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Você recusou participar deste evento.</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($allowsGuests && $maxG > 0 && $st !== 'declined'): ?>
                                    <div class="border rounded p-3 bg-light mt-3" id="guestBlock">
                                        <div class="fw-semibold small mb-2">Convidados (máx. <?php echo $maxG; ?>)</div>
                                        <p class="small text-muted mb-2">Informe ao confirmar presença. Você pode ajustar antes de enviar.</p>
                                        <div id="guestRows" class="mb-2"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="addGuestRow" <?php echo $st === 'declined' ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus me-1"></i>Adicionar convidado
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="mb-4">
                        <h5>Informações</h5>
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2 small">
                                <div class="col-12">
                                    <span class="text-muted d-block">Início</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['starts_at'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Fim</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['ends_at'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <hr class="my-2">
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Publicar a partir de</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['publish_at'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Expirar visibilidade</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['expire_at'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Prazo confirmação (RSVP)</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['rsvp_deadline'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Prazo cancelamento</span>
                                    <strong><?php echo htmlspecialchars($fmtDt($ev['cancellation_deadline'] ?? null)); ?></strong>
                                </div>
                                <div class="col-12">
                                    <hr class="my-2">
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Departamento</span>
                                    <strong><?php echo \App\adms\Helpers\TextEncodingHelper::escape($ev['department_name'] ?? 'Todos'); ?></strong>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted d-block">Máx. convidados / colaborador</span>
                                    <strong><?php echo (int)($ev['max_guests_per_user'] ?? 0); ?></strong>
                                </div>
                                <div class="col-12">
                                    <hr class="my-2">
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">ID</span>
                                    <strong><?php echo $eventId; ?></strong>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted d-block">Status</span>
                                    <?php if (!empty($ev['ativo'])): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($requiresRsvp): ?>
<script>
(function () {
    var base = <?php echo json_encode($urlAdm); ?>;
    var eventId = <?php echo (int)$eventId; ?>;
    var maxG = <?php echo (int)$maxG; ?>;
    var allowsGuests = <?php echo $allowsGuests && $maxG > 0 ? 'true' : 'false'; ?>;
    var existingGuests = <?php echo json_encode(array_map(static function ($g) {
        return [
            'full_name' => $g['full_name'] ?? '',
            'relationship' => $g['relationship'] ?? '',
        ];
    }, $guests), JSON_UNESCAPED_UNICODE); ?>;

    function guestRow(name, rel) {
        var wrap = document.createElement('div');
        wrap.className = 'row g-2 mb-2 align-items-end guest-row';
        wrap.innerHTML =
            '<div class="col-12 col-md-6">' +
            '<label class="form-label small mb-0">Nome</label>' +
            '<input type="text" class="form-control form-control-sm guest-name" maxlength="200" value="' + escapeAttr(name) + '">' +
            '</div>' +
            '<div class="col-12 col-md-4">' +
            '<label class="form-label small mb-0">Parentesco</label>' +
            '<input type="text" class="form-control form-control-sm guest-rel" maxlength="100" value="' + escapeAttr(rel) + '">' +
            '</div>' +
            '<div class="col-12 col-md-2">' +
            '<button type="button" class="btn btn-outline-danger btn-sm w-100 btn-remove-guest">Remover</button>' +
            '</div>';
        wrap.querySelector('.btn-remove-guest').onclick = function () {
            wrap.remove();
        };
        return wrap;
    }
    function escapeAttr(s) {
        if (!s) return '';
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    }
    function collectGuests() {
        var out = [];
        document.querySelectorAll('#guestRows .guest-row').forEach(function (row) {
            var n = row.querySelector('.guest-name');
            var r = row.querySelector('.guest-rel');
            var name = n ? n.value.trim() : '';
            if (name !== '') {
                out.push({ full_name: name, relationship: r ? r.value.trim() : '' });
            }
        });
        return out.slice(0, maxG);
    }
    function postRsvp(action, guestsPayload) {
        var fd = new FormData();
        fd.append('event_id', String(eventId));
        fd.append('action', action);
        if (guestsPayload && guestsPayload.length) {
            fd.append('guests', JSON.stringify(guestsPayload));
        }
        fetch(base + 'event-rsvp', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.location.reload();
                } else {
                    alert((data && data.message) ? data.message : 'Não foi possível atualizar.');
                }
            })
            .catch(function () { alert('Erro de rede.'); });
    }

    document.querySelectorAll('#companyEventRsvpActions [data-rsvp-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var act = btn.getAttribute('data-rsvp-action');
            if (act === 'cancel') {
                if (!confirm('Cancelar sua presença neste evento?')) return;
                postRsvp('cancel', null);
                return;
            }
            var guests = [];
            if (allowsGuests && act === 'confirm') {
                guests = collectGuests();
            }
            postRsvp(act === 'decline' ? 'decline' : 'confirm', guests);
        });
    });

    <?php if ($allowsGuests && $maxG > 0): ?>
    var guestRows = document.getElementById('guestRows');
    var addBtn = document.getElementById('addGuestRow');
    if (guestRows && addBtn) {
        existingGuests.forEach(function (g) {
            guestRows.appendChild(guestRow(g.full_name || '', g.relationship || ''));
        });
        addBtn.addEventListener('click', function () {
            var n = guestRows.querySelectorAll('.guest-row').length;
            if (n >= maxG) return;
            guestRows.appendChild(guestRow('', ''));
        });
    }
    <?php endif; ?>
})();
</script>
<?php endif; ?>
