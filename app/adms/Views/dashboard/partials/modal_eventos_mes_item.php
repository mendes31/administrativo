<?php
$ev = $ev ?? [];
$id = (int)($ev['id'] ?? 0);
$rsvp = $ev['rsvp'] ?? null;
$st = $rsvp['status'] ?? '';
$req = !empty($ev['requires_rsvp']);
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
?>
<div class="card border-0 shadow-sm mb-3 evento-mes-card" data-event-id="<?php echo $id; ?>">
    <div class="card-body p-3">
        <div class="evento-mes-row d-flex align-items-start gap-3">
            <div class="evento-mes-day-badge text-center flex-shrink-0" aria-hidden="true">
                <div class="evento-mes-day-num"><?php echo htmlspecialchars($dayBadgeTop); ?></div>
                <div class="evento-mes-day-mon"><?php echo htmlspecialchars($dayBadgeBottom); ?></div>
            </div>
            <div class="evento-mes-content flex-grow-1 min-w-0">
                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($ev['title'] ?? ''); ?></h6>
                <div class="text-muted small mb-2">
                    <?php echo htmlspecialchars($rangeText); ?>
                    <?php if ($durationDays > 1): ?>
                        <span class="badge text-bg-light border ms-1"><?php echo $durationDays; ?> dias</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($ev['location'])): ?>
            <div class="text-muted small mb-2">
                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ev['location']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($ev['description'])): ?>
            <p class="small mb-2"><?php echo nl2br(htmlspecialchars($ev['description'])); ?></p>
        <?php endif; ?>
        <?php if (!empty($ev['rsvp_deadline'])): ?>
            <div class="small text-warning mb-1">
                <i class="fas fa-clock me-1"></i>Confirmação até: <?php echo date('d/m/Y H:i', strtotime($ev['rsvp_deadline'])); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($ev['cancellation_deadline'])): ?>
            <div class="small text-secondary mb-2">
                <i class="fas fa-ban me-1"></i>Cancelamento até: <?php echo date('d/m/Y H:i', strtotime($ev['cancellation_deadline'])); ?>
            </div>
        <?php endif; ?>
        <?php if ($req): ?>
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <?php if ($st === 'confirmed'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-action="cancel" data-id="<?php echo $id; ?>">Cancelar presença</button>
                <?php elseif ($st !== 'declined'): ?>
                    <button type="button" class="btn btn-success btn-sm me-1" data-action="confirm" data-id="<?php echo $id; ?>">Confirmar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-action="decline" data-id="<?php echo $id; ?>">Recusar</button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
