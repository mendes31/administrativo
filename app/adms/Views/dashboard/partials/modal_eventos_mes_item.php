<?php
$ev = $ev ?? [];
$id = (int)($ev['id'] ?? 0);
$rsvp = $ev['rsvp'] ?? null;
$st = $rsvp['status'] ?? '';
$req = !empty($ev['requires_rsvp']);
$urlAdm = $_ENV['URL_ADM'] ?? '';
?>
<div class="card border-0 shadow-sm mb-3 evento-mes-card" data-event-id="<?php echo $id; ?>">
    <div class="card-body">
        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($ev['title'] ?? ''); ?></h6>
        <div class="text-muted small mb-2">
            <?php echo date('d/m/Y H:i', strtotime($ev['starts_at'] ?? 'now')); ?> —
            <?php echo date('d/m/Y H:i', strtotime($ev['ends_at'] ?? 'now')); ?>
            <?php if (!empty($ev['location'])): ?>
                · <?php echo htmlspecialchars($ev['location']); ?>
            <?php endif; ?>
        </div>
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
