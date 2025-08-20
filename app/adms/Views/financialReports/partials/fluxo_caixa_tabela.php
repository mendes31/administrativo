<?php
function exibeFluxoCaixaTabela($dados, $tipo, $mes = null, $ano = null, $acumuladoInicial = 0) {
    $mes = $mes ?: date('m');
    $ano = $ano ?: date('Y');
    $diasMes = cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$ano);
    $acumulado = $acumuladoInicial;
    $totalReceita = 0;
    $totalDespesa = 0;
    $totalSaldo = 0;
    $dias = [];
    foreach ($dados as $d) {
        $dias[(int)$d['dia']] = $d;
    }
    echo '<table class="table table-bordered table-sm text-center align-middle" style="font-size:0.95rem">';
    echo '<thead class="table-primary"><tr><th>Dia</th><th>Receita</th><th>Despesa</th><th>Saldo</th><th>Acumulado</th></tr></thead><tbody>';
    for ($i = 1; $i <= $diasMes; $i++) {
        $receita = isset($dias[$i]) ? (float)$dias[$i]['receita'] : 0;
        $despesa = isset($dias[$i]) ? (float)$dias[$i]['despesa'] : 0;
        $saldo = $receita - $despesa;
        $acumulado += $saldo;
        $totalReceita += $receita;
        $totalDespesa += $despesa;
        $totalSaldo += $saldo;
        $corSaldo = $saldo < 0 ? 'style="background:#d9534f;color:#fff"' : ($saldo > 0 ? 'style="background:#198754;color:#fff"' : 'style="background:#6c757d;color:#fff"');
        $corAcumulado = $acumulado < 0 ? 'style="background:#d9534f;color:#fff"' : ($acumulado > 0 ? 'style="background:#198754;color:#fff"' : 'style="background:#6c757d;color:#fff"');
        echo "<tr><td>".str_pad($i,2,'0',STR_PAD_LEFT)."</td><td>".formatMoney($receita)."</td><td>".formatMoney($despesa)."</td><td $corSaldo>".formatMoney($saldo)."</td><td $corAcumulado>".formatMoney($acumulado)."</td></tr>";
    }
    echo '</tbody><tfoot class="fw-bold"><tr><td>Total</td><td>'.formatMoney($totalReceita).'</td><td>'.formatMoney($totalDespesa).'</td><td style="background:#d9534f;color:#fff">'.formatMoney($totalSaldo).'</td><td></td></tr></tfoot></table>';
} 