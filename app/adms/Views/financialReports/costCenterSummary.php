<?php
$selectedYear = $_GET['year'] ?? ($this->data['year'] ?? date('Y'));
$years = range(date('Y')-5, date('Y')+1);
$months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<div class="container-fluid px-4">
    <h2 class="mt-3 mb-4">Resumo por Centro de Custo</h2>
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <form method="get" class="row g-2 align-items-end mb-0">
                <div class="col-auto">
                    <label for="year" class="form-label mb-0 me-2">Ano:</label>
                    <select name="year" id="year" class="form-select d-inline w-auto" onchange="this.form.submit()">
                        <?php foreach ($years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" name="export" value="excel" class="btn btn-success btn-sm">Exportar Excel</button>
                    <button type="submit" name="export" value="pdf" class="btn btn-danger btn-sm">Exportar PDF</button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" style="font-size:0.97rem;">
                    <thead>
                        <tr>
                            <th class="text-start" style="background:#254e7b;color:#fff; min-width: 200px;">Centro de Custo</th>
                            <?php foreach ($months as $m): ?>
                                <th class="text-end" style="background:#254e7b;color:#fff; min-width: 90px;"> <?= $m ?> </th>
                            <?php endforeach; ?>
                            <th class="text-end" style="background:#254e7b;color:#fff; min-width: 100px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $zebra1 = '#4a90e2'; // azul forte
                        $zebra2 = '#b3d1f7'; // azul claro
                        foreach ($this->data['costCenters'] as $idx => $cc):
                            $bg = ($idx % 2 == 0) ? $zebra1 : $zebra2;
                            $color = ($idx % 2 == 0) ? '#fff' : '#222';
                        ?>
                            <tr>
                                <td class="text-start fw-bold" style="background:<?= $bg ?>;color:<?= $color ?>; min-width: 200px;"> <?= htmlspecialchars($cc['name']) ?> </td>
                                <?php for ($i=1; $i<=12; $i++): ?>
                                    <td class="text-end" style="background:<?= $bg ?>;color:<?= $color ?>; min-width: 90px;"> <?= 'R$ ' . number_format($cc['months'][$i], 2, ',', '.') ?> </td>
                                <?php endfor; ?>
                                <td class="text-end" style="background:<?= $bg ?>;color:<?= $color ?>; min-width: 100px;"> <?= 'R$ ' . number_format($cc['total'], 2, ',', '.') ?> </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Sparkline com Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.querySelectorAll('.sparkline').forEach(function(canvas) {
    const values = JSON.parse(canvas.dataset.values);
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: [...Array(12).keys()].map(i => i+1),
            datasets: [{
                data: values,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0,123,255,0.1)',
                pointRadius: 0,
                borderWidth: 1,
                tension: 0.4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { x: { display: false }, y: { display: false } },
            elements: { line: { borderJoinStyle: 'round' } }
        }
    });
});
</script> 