<?php
$year = $this->data['year'];
$months = $this->data['months'];
$cashFlow = $this->data['cashFlow'];
$competence = $this->data['competence'];
function money($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
?>
<div class="container-fluid px-4">
    <h2 class="mt-3 mb-4">Resumo Financeiro - Regime de Caixa e de Competência</h2>
    <form method="get" class="mb-3 d-flex align-items-end gap-2">
        <label for="year" class="form-label mb-0 me-2">Ano:</label>
        <select name="year" id="year" class="form-select d-inline w-auto" onchange="this.form.submit()">
            <?php for ($y = date('Y')-5; $y <= date('Y')+1; $y++): ?>
                <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <a href="?year=<?= $year ?>&export=excel" class="btn btn-success btn-sm ms-2">Exportar Excel</a>
        <a href="?year=<?= $year ?>&export=pdf" class="btn btn-danger btn-sm ms-2">Exportar PDF</a>
    </form>

    <!-- Fluxo de Caixa -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-sm align-middle flow-cash-table" style="font-size:0.97rem;">
            <thead>
                <tr>
                    <th style="background:#254e7b;color:#fff;">Fluxo de Caixa</th>
                    <?php foreach ($months as $m): ?>
                        <th style="background:#254e7b;color:#fff;"> <?= $m ?> </th>
                    <?php endforeach; ?>
                    <th style="background:#254e7b;color:#fff;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $linhas = [
                    'Saldo Inicial', 'Saldo Limites', 'Saldo Aplicacoes', 'Receita', 'Despesa', 'Saldo Financeiro', 'Acumulado'
                ];
                $acumulado = 0;
                $totalReceita = 0;
                $totalDespesa = 0;
                foreach ($linhas as $linha):
                ?>
                <tr>
                    <td style="background:#4a90e2;color:#fff;"> <?= $linha ?> </td>
                    <?php
                    $totalLinha = 0;
                    $valorDezembro = 0;
                    for ($i=1; $i<=12; $i++):
                        $valor = 0;
                        switch ($linha) {
                            case 'Saldo Inicial': $valor = $cashFlow[$i]['saldo_inicial']; if($i==12) $valorDezembro = $valor; break;
                            case 'Saldo Limites': $valor = $cashFlow[$i]['saldo_limites']; break;
                            case 'Saldo Aplicacoes': $valor = $cashFlow[$i]['saldo_aplicacoes']; break;
                            case 'Receita': $valor = $cashFlow[$i]['receita']; $totalReceita += $valor; break;
                            case 'Despesa': $valor = $cashFlow[$i]['despesa']; $totalDespesa += $valor; break;
                            case 'Saldo Financeiro': $valor = $cashFlow[$i]['saldo_financeiro']; break;
                            case 'Acumulado':
                                $acumulado = ($i == 1 ? $cashFlow[$i]['saldo_inicial'] : $acumulado) + $cashFlow[$i]['saldo_financeiro'];
                                $valor = $acumulado;
                                if($i==12) $valorDezembro = $valor;
                                break;
                        }
                        $totalLinha += $valor;
                        $cor = '';
                        if ($linha == 'Saldo Financeiro' || $linha == 'Acumulado') {
                            $cor = $valor < 0 ? 'background:#d9534f;color:#fff;' : 'background:#198754;color:#fff;';
                        }
                        if ($linha == 'Acumulado' && $valor == 0) $cor = 'background:#ffe600;color:#222;';
                    ?>
                        <td style="background:#eaf3fa;<?= $cor ?>"> <?= money($valor) ?> </td>
                    <?php endfor; ?>
                    <td style="background:#eaf3fa;<?= $cor ?? '' ?>">
                        <?php
                        switch ($linha) {
                            case 'Saldo Inicial': echo money($valorDezembro); break;
                            case 'Saldo Aplicacoes': echo money($totalLinha); break;
                            case 'Receita': echo money($totalReceita); break;
                            case 'Despesa': echo money($totalDespesa); break;
                            case 'Saldo Financeiro': echo money($totalReceita - $totalDespesa); break;
                            case 'Acumulado': echo money($valorDezembro); break;
                            default: echo money($totalLinha); break;
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Demonstrativo de Contas -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle competence-table" style="font-size:0.97rem;">
            <thead>
                <tr>
                    <th style="background:#254e7b;color:#fff;">Demonstrativo de Contas</th>
                    <?php foreach ($months as $m): ?>
                        <th style="background:#254e7b;color:#fff;"> <?= $m ?> </th>
                    <?php endforeach; ?>
                    <th style="background:#254e7b;color:#fff;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $linhas = [
                    'Contas a Receber', 'Contas a Pagar', 'Necessidade de Caixa', 'Necessidade Acumulada'
                ];
                $necessidadeAcumulada = 0;
                foreach ($linhas as $linha):
                ?>
                <tr>
                    <td style="background:#4a90e2;color:#fff;"> <?= $linha ?> </td>
                    <?php
                    $totalLinha = 0;
                    for ($i=1; $i<=12; $i++):
                        $valor = 0;
                        switch ($linha) {
                            case 'Contas a Receber': $valor = $competence[$i]['receber']; break;
                            case 'Contas a Pagar': $valor = $competence[$i]['pagar']; break;
                            case 'Necessidade de Caixa': $valor = $competence[$i]['necessidade_caixa']; break;
                            case 'Necessidade Acumulada':
                                $necessidadeAcumulada = ($i == 1 ? $competence[$i]['necessidade_caixa'] : $necessidadeAcumulada) + $competence[$i]['necessidade_caixa'];
                                $valor = $necessidadeAcumulada;
                                break;
                        }
                        $totalLinha += $valor;
                        $cor = '';
                        if ($linha == 'Necessidade de Caixa' || $linha == 'Necessidade Acumulada') {
                            $cor = $valor < 0 ? 'background:#d9534f;color:#fff;' : 'background:#198754;color:#fff;';
                        }
                    ?>
                        <td style="background:#eaf3fa;<?= $cor ?>"> <?= money($valor) ?> </td>
                    <?php endfor; ?>
                    <td style="background:#eaf3fa;<?= $cor ?? '' ?>"> <?= money($totalLinha) ?> </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Gráfico Acumulado -->
    <div class="mt-4">
        <canvas id="graficoAcumulado" height="80"></canvas>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const acumulado = [
        <?php
        $acumulado = 0;
        for ($i=1; $i<=12; $i++) {
            $acumulado = ($i == 1 ? $cashFlow[$i]['saldo_inicial'] : $acumulado) + $cashFlow[$i]['saldo_financeiro'];
            echo $acumulado . ',';
        }
        ?>
    ];
    new Chart(document.getElementById('graficoAcumulado'), {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Acumulado',
                data: acumulado,
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            plugins: { legend: { display: true } },
            scales: { y: { beginAtZero: false } }
        }
    });
    </script>
</div>
<style>
    .flow-cash-table th,
    .flow-cash-table td,
    .competence-table th,
    .competence-table td {
        min-width: 110px;
        max-width: 110px;
        width: 110px;
        text-align: right;
        vertical-align: middle;
    }
    .flow-cash-table th:first-child,
    .flow-cash-table td:first-child,
    .competence-table th:first-child,
    .competence-table td:first-child {
        min-width: 180px;
        max-width: 180px;
        width: 180px;
        text-align: left;
    }
</style> 