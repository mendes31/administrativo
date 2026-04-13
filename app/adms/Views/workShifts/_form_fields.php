<?php

/** @var array $form */
$form = $this->data['form'] ?? [];
$v = static function (string $key, string $default = '') use ($form): string {
    return htmlspecialchars((string) ($form[$key] ?? $default), ENT_QUOTES, 'UTF-8');
};
?>
<div class="col-12">
    <label for="description" class="form-label">Descrição</label>
    <input type="text" name="description" id="description" class="form-control" maxlength="255"
           placeholder="Ex.: Turno 06:00h" value="<?= $v('description') ?>" required>
</div>

<div class="col-12 mt-2">
    <h6 class="text-muted mb-2">Intervalos de trabalho <small>(mesmo dia; opcionais além do 1.º)</small></h6>
</div>

<?php for ($i = 1; $i <= 3; $i++): ?>
<div class="col-md-6 col-lg-4">
    <label class="form-label">Entrada <?= $i ?></label>
    <input type="time" name="entry_<?= $i ?>" id="entry_<?= $i ?>" class="form-control work-shift-time"
           value="<?= $v('entry_' . $i) ?>">
</div>
<div class="col-md-6 col-lg-4">
    <label class="form-label">Saída <?= $i ?></label>
    <input type="time" name="exit_<?= $i ?>" id="exit_<?= $i ?>" class="form-control work-shift-time"
           value="<?= $v('exit_' . $i) ?>">
</div>
<?php endfor; ?>

<div class="col-md-6 col-lg-4">
    <label for="overtime_tolerance_minutes" class="form-label">Tolerância extras (min)</label>
    <input type="number" name="overtime_tolerance_minutes" id="overtime_tolerance_minutes" class="form-control"
           min="0" max="999" value="<?= $v('overtime_tolerance_minutes', '0') ?>">
</div>
<div class="col-md-6 col-lg-4">
    <label for="absence_tolerance_minutes" class="form-label">Tolerância faltas (min)</label>
    <input type="number" name="absence_tolerance_minutes" id="absence_tolerance_minutes" class="form-control"
           min="0" max="999" value="<?= $v('absence_tolerance_minutes', '0') ?>">
</div>
<div class="col-md-6 col-lg-4 d-flex align-items-end">
    <div class="border rounded p-2 bg-light w-100">
        <span class="text-muted small">Carga horária líquida (prévia)</span>
        <div class="fw-semibold" id="work_shift_total_preview">—</div>
    </div>
</div>

<script>
(function () {
    function parseTime(s) {
        if (!s || !/^\d{2}:\d{2}$/.test(s)) return null;
        var p = s.split(':');
        return parseInt(p[0], 10) * 60 + parseInt(p[1], 10);
    }
    function updateTotalPreview() {
        var total = 0;
        for (var i = 1; i <= 3; i++) {
            var a = document.getElementById('entry_' + i);
            var b = document.getElementById('exit_' + i);
            if (!a || !b) continue;
            var ta = parseTime(a.value);
            var tb = parseTime(b.value);
            if (ta !== null && tb !== null && tb > ta) total += (tb - ta);
        }
        var el = document.getElementById('work_shift_total_preview');
        if (!el) return;
        if (total <= 0) { el.textContent = '—'; return; }
        var h = Math.floor(total / 60), m = total % 60;
        el.textContent = (h < 10 ? '0' : '') + h + ':' + (m < 10 ? '0' : '') + m;
    }
    document.querySelectorAll('.work-shift-time').forEach(function (inp) {
        inp.addEventListener('change', updateTotalPreview);
        inp.addEventListener('input', updateTotalPreview);
    });
    updateTotalPreview();
})();
</script>
