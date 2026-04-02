<?php
$ev = $ev ?? [];
$id = (int)($ev['id'] ?? 0);
$rsvp = $ev['rsvp'] ?? null;
$st = $rsvp['status'] ?? '';
$req = !empty($ev['requires_rsvp']);
$allowsGuests = !empty($ev['allows_guests']);
$maxG = (int)($ev['max_guests_per_user'] ?? 0);
$urlAdm = $_ENV['URL_ADM'] ?? '';

$startAt = !empty($ev['starts_at']) ? new DateTime((string)$ev['starts_at']) : new DateTime();
$endAt = !empty($ev['ends_at']) ? new DateTime((string)$ev['ends_at']) : new DateTime();
$sameDay = $startAt->format('Y-m-d') === $endAt->format('Y-m-d');
$sameMonthYear = $startAt->format('Y-m') === $endAt->format('Y-m');
$dayDiff = (int)$startAt->setTime(0, 0)->diff($endAt->setTime(0, 0))->format('%a');
$durationDays = max(1, $dayDiff + 1);

$monthShort = [1 => 'JAN', 2 => 'FEV', 3 => 'MAR', 4 => 'ABR', 5 => 'MAI', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO', 9 => 'SET', 10 => 'OUT', 11 => 'NOV', 12 => 'DEZ'];
$startMon = $monthShort[(int)$startAt->format('n')] ?? strtoupper($startAt->format('M'));
$endMon = $monthShort[(int)$endAt->format('n')] ?? strtoupper($endAt->format('M'));

$dayBadgeTop = $startAt->format('d');
$dayBadgeBottom = $startMon;

$rangeText = $sameDay
    ? ($startAt->format('d/m/Y') . ' · ' . $startAt->format('H:i') . ' às ' . $endAt->format('H:i'))
    : ($sameMonthYear
        ? ($startAt->format('d') . '–' . $endAt->format('d') . ' ' . $startMon . ' · ' . $startAt->format('H:i') . ' às ' . $endAt->format('H:i'))
        : ($startAt->format('d') . ' ' . $startMon . ' → ' . $endAt->format('d') . ' ' . $endMon . ' · ' . $startAt->format('H:i') . ' às ' . $endAt->format('H:i')));

$endsTs = !empty($ev['ends_at']) ? strtotime((string)$ev['ends_at']) : false;
$eventEnded = ($endsTs !== false && $endsTs < time());

$rsvpDlTs = !empty($ev['rsvp_deadline']) ? strtotime((string)$ev['rsvp_deadline']) : false;
$cancelDlTs = !empty($ev['cancellation_deadline']) ? strtotime((string)$ev['cancellation_deadline']) : false;
$rsvpPrazoEncerrado = ($rsvpDlTs !== false && $rsvpDlTs < time());
$cancelPrazoEncerrado = ($cancelDlTs !== false && $cancelDlTs < time());
?>
<div class="card border-0 shadow-sm mb-2 evento-mes-card<?php echo $eventEnded ? ' evento-mes-past' : ''; ?>" data-event-id="<?php echo $id; ?>">
    <div class="card-body p-2">
        <div class="evento-mes-row d-flex align-items-start gap-3">
            <div class="evento-mes-day-badge text-center flex-shrink-0" aria-hidden="true">
                <div class="evento-mes-day-num"><?php echo htmlspecialchars($dayBadgeTop); ?></div>
                <div class="evento-mes-day-mon"><?php echo htmlspecialchars($dayBadgeBottom); ?></div>
            </div>
            <div class="evento-mes-content flex-grow-1 min-w-0">
                <h6 class="fw-bold mb-1 d-flex flex-wrap align-items-center gap-1">
                    <span class="min-w-0"><?php echo htmlspecialchars($ev['title'] ?? ''); ?></span>
                    <?php if ($req): ?>
                        <?php if ($st === 'confirmed'): ?>
                            <span class="badge bg-success flex-shrink-0">Confirmado</span>
                        <?php elseif ($st === 'declined'): ?>
                            <span class="badge bg-secondary flex-shrink-0">Recusado</span>
                        <?php elseif ($st === 'cancelled'): ?>
                            <span class="badge bg-warning text-dark flex-shrink-0">Presença cancelada</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border flex-shrink-0">Pendente</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </h6>
                <div class="text-muted small mb-1">
                    <?php echo htmlspecialchars($rangeText); ?>
                    <?php if ($durationDays > 1): ?>
                        <span class="badge text-bg-light border ms-1"><?php echo $durationDays; ?> dias</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($ev['location'])): ?>
            <div class="text-muted small mb-1">
                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ev['location']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($ev['description'])): ?>
            <div class="evento-mes-desc-shell position-relative mt-1">
                <div class="evento-mes-desc-inner evento-mes-desc-clamped small text-body mb-0"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></div>
                <div class="evento-mes-desc-fade" aria-hidden="true"></div>
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 mt-1 evento-mes-desc-toggle d-none" aria-expanded="false">Ler mais</button>
            </div>
        <?php endif; ?>
        <?php if (!empty($ev['rsvp_deadline'])): ?>
            <div class="small text-warning mb-1">
                <i class="fas fa-clock me-1"></i>Confirmação até: <?php echo date('d/m/Y H:i', strtotime($ev['rsvp_deadline'])); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($ev['cancellation_deadline'])): ?>
            <div class="small text-secondary mb-1">
                <i class="fas fa-ban me-1"></i>Cancelamento até: <?php echo date('d/m/Y H:i', strtotime($ev['cancellation_deadline'])); ?>
            </div>
        <?php endif; ?>
        <?php if ($req && !$eventEnded): ?>
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <?php if ($st === 'confirmed'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-action="cancel" data-id="<?php echo $id; ?>"
                        <?php echo $cancelPrazoEncerrado ? ' disabled title="' . htmlspecialchars('Prazo de cancelamento encerrado.', ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>Cancelar presença</button>
                <?php elseif ($st === 'declined' && !$rsvpPrazoEncerrado): ?>
                    <button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="<?php echo $id; ?>">
                        <i class="fas fa-check me-1"></i>Confirmar presença
                    </button>
                    <?php if ($allowsGuests && $maxG > 0): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-action="toggle-guests" data-id="<?php echo $id; ?>"
                            title="<?php echo htmlspecialchars('Informar acompanhantes (máx. ' . (int)$maxG . ')', ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-user-plus me-1"></i>Convidados <span class="fw-normal">(máx. <?php echo (int)$maxG; ?>)</span>
                        </button>
                    <?php endif; ?>
                <?php elseif ($st !== 'declined'): ?>
                    <button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="<?php echo $id; ?>"
                        <?php echo $rsvpPrazoEncerrado ? ' disabled title="' . htmlspecialchars('Prazo de confirmação encerrado.', ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>Confirmar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-action="decline" data-id="<?php echo $id; ?>"
                        <?php echo $rsvpPrazoEncerrado ? ' disabled title="' . htmlspecialchars('Prazo de confirmação encerrado.', ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>Recusar</button>
                    <?php if ($allowsGuests && $maxG > 0): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-action="toggle-guests" data-id="<?php echo $id; ?>"
                            title="<?php echo htmlspecialchars($rsvpPrazoEncerrado ? 'Prazo de confirmação encerrado.' : 'Informar acompanhantes (máx. ' . (int)$maxG . ')', ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo $rsvpPrazoEncerrado ? ' disabled' : ''; ?>>
                            <i class="fas fa-user-plus me-1"></i>Convidados <span class="fw-normal">(máx. <?php echo (int)$maxG; ?>)</span>
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="small text-muted">Prazo de confirmação encerrado — resposta mantida como recusada.</span>
                <?php endif; ?>
            </div>
            <?php if ($st === 'declined' && !$rsvpPrazoEncerrado): ?>
                <div class="small text-muted mt-1"><i class="fas fa-info-circle me-1"></i>Você recusou antes; ainda pode confirmar dentro do prazo.</div>
            <?php endif; ?>
            <?php if ($allowsGuests && $maxG > 0 && $st !== 'confirmed' && !$rsvpPrazoEncerrado): ?>
                <div class="evento-mes-guests-box d-none" data-guests-box data-max-guests="<?php echo (int)$maxG; ?>">
                    <div class="guest-rows"></div>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 mt-1" data-action="add-guest" data-id="<?php echo $id; ?>">
                        <i class="fas fa-plus small me-1"></i>Outro convidado
                    </button>
                </div>
            <?php endif; ?>
        <?php elseif ($req && $eventEnded): ?>
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <span class="badge text-bg-secondary">Realizado — apenas consulta</span>
            </div>
        <?php endif; ?>
    </div>
</div>
