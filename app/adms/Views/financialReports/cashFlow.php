<?php
// View do Fluxo de Caixa (Efetivo e Previsto)
$meses = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
$mesAtual = $this->data['mes'] ?? date('m');
$anoAtual = $this->data['ano'] ?? date('Y');
function formatMoney($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Relatório Fluxo de Caixa</h2>
    <form method="GET" class="row mb-3 align-items-end">
        <div class="col-md-2">
            <label for="mes">Selecione um mês</label>
            <select name="mes" id="mes" class="form-control">
                <?php foreach ($meses as $num => $nome): ?>
                    <option value="<?php echo $num; ?>" <?php echo ($mesAtual == $num) ? 'selected' : ''; ?>><?php echo $nome; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="ano">Ano</label>
            <input type="number" name="ano" id="ano" class="form-control" value="<?php echo $anoAtual; ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
        <div class="col-md-2">
            <a href="export-pdf-cash-flow?mes=<?php echo $mesAtual; ?>&ano=<?php echo $anoAtual; ?>" target="_blank" class="btn btn-danger">Exportar PDF</a>
        </div>
    </form>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4 border-dark shadow">
                <div class="card-header bg-primary text-white">Valores Efetivos</div>
                <div class="card-body p-2">
                    <?php include_once __DIR__ . '/partials/fluxo_caixa_tabela.php';
                    exibeFluxoCaixaTabela(
                        $this->data['efetivo'],
                        'efetivo',
                        $mesAtual,
                        $anoAtual,
                        $this->data['acumuladoInicialEfetivo'] ?? 0
                    ); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4 border-dark shadow">
                <div class="card-header bg-secondary text-white">Valores Previstos</div>
                <div class="card-body p-2">
                    <?php include_once __DIR__ . '/partials/fluxo_caixa_tabela.php';
                    exibeFluxoCaixaTabela(
                        $this->data['previsto'],
                        'previsto',
                        $mesAtual,
                        $anoAtual,
                        $this->data['acumuladoInicialPrevisto'] ?? 0
                    ); ?>
                </div>
            </div>
        </div>
    </div>
</div> 