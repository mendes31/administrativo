<?php
// View de Movimentos Financeiros
?>
<div class="container-fluid px-4">
    <h2 class="mt-3">Movimentos Financeiros</h2>
    <form method="GET" class="row mb-3 align-items-end">
        <div class="col-md-2">
            <label for="data_type">Tipo de Data</label>
            <select name="data_type" id="data_type" class="form-control">
                <option value="created_at" <?php echo (($this->data['form']['data_type'] ?? '') === 'created_at') ? 'selected' : ''; ?>>Data Movimento</option>
                <option value="issue_date" <?php echo (($this->data['form']['data_type'] ?? '') === 'issue_date' ? 'selected' : ''); ?>>Data Emissão</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="data_inicial">Data Inicial</label>
            <input type="date" name="data_inicial" id="data_inicial" class="form-control" value="<?php echo htmlspecialchars($this->data['form']['data_inicial'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label for="data_final">Data Final</label>
            <input type="date" name="data_final" id="data_final" class="form-control" value="<?php echo htmlspecialchars($this->data['form']['data_final'] ?? ''); ?>">
        </div>
        <div class="col-md-2">
            <label for="type">Tipo</label>
            <select name="type" id="type" class="form-control">
                <option value="">Todos</option>
                <option value="Saída" <?php echo (($this->data['form']['type'] ?? '') === 'Saída') ? 'selected' : ''; ?>>Saída</option>
                <option value="Entrada" <?php echo (($this->data['form']['type'] ?? '') === 'Entrada') ? 'selected' : ''; ?>>Entrada</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="bank_id">Banco</label>
            <select name="bank_id" id="bank_id" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($this->data['listBanks'] ?? [] as $bank): ?>
                    <option value="<?php echo $bank['id']; ?>" <?php echo (($this->data['form']['bank_id'] ?? '') == $bank['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bank['bank_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="method_id">Forma Pgto</label>
            <select name="method_id" id="method_id" class="form-control">
                <option value="">Todas</option>
                <?php foreach ($this->data['listPaymentMethods'] ?? [] as $method): ?>
                    <option value="<?php echo $method['id']; ?>" <?php echo (($this->data['form']['method_id'] ?? '') == $method['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($method['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="?" class="btn btn-secondary ms-2">Limpar Filtros</a>
        </div>
    </form>

    <?php
    $total = 0;
    foreach ($this->data['movements'] as $mov) {
        $valor = (float)$mov['movement_value'];
        if (strtolower($mov['type']) === 'saída') {
            $total -= $valor;
        } else {
            $total += $valor;
        }
    }
    ?>
    <div class="text-end mb-2">
        <span class="fw-bold">$ Total: R$ <?php echo number_format($this->data['totalGeral'], 2, ',', '.'); ?></span>
    </div>

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Data Emissão</th>
                <th>Data</th>
                <th>Movimento</th>
                <th>Descrição</th>
                <th>Local da Saída</th>
                <th>Forma de Pgto</th>
                <th>Usuário</th>
                <th>Valor</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $saldo = 0;
            foreach ($this->data['movements'] as $mov):
                $valor = (float)$mov['movement_value'];
                $isSaida = strtolower($mov['type']) === 'saída';
                $saldo += $isSaida ? -$valor : $valor;
                $isContaPagar = (stripos($mov['movement'], 'conta à pagar') !== false);
                $isContaReceber = (stripos($mov['movement'], 'conta a receber') !== false || stripos($mov['movement'], 'conta à receber') !== false);
                // Cor do saldo
                if ($saldo < 0) {
                    $corSaldo = 'style="color:#d9534f;font-weight:bold"'; // vermelho
                } elseif ($saldo > 0) {
                    $corSaldo = 'style="color:#198754;font-weight:bold"'; // verde
                } else {
                    $corSaldo = 'style="color:#6c757d;font-weight:bold"'; // cinza
                }
                // Classe para as outras células
                if ($isContaPagar) {
                    $classeLinha = 'style="color:#d9534f;"'; // vermelho
                } elseif ($isContaReceber) {
                    $classeLinha = 'style="color:#198754;"'; // verde
                } else {
                    $classeLinha = '';
                }
            ?>
            <tr>
                <td <?= $classeLinha ?>><?php echo !empty($mov['issue_date']) ? date('d/m/Y', strtotime($mov['issue_date'])) : '-'; ?></td>
                <td <?= $classeLinha ?>><?php echo date('d/m/Y', strtotime($mov['created_at'])); ?></td>
                <td <?= $classeLinha ?>><?php echo htmlspecialchars($mov['movement']); ?></td>
                <td <?= $classeLinha ?>><?php echo htmlspecialchars($mov['description']); ?></td>
                <td <?= $classeLinha ?>><?php echo htmlspecialchars($mov['bank_name'] ?? '-'); ?></td>
                <td <?= $classeLinha ?>><?php echo htmlspecialchars($mov['method_name'] ?? '-'); ?></td>
                <td <?= $classeLinha ?>><?php echo htmlspecialchars($mov['user_name']); ?></td>
                <td <?= $classeLinha ?>>R$ <?php echo number_format($valor, 2, ',', '.'); ?></td>
                <td <?= $corSaldo ?>>R$ <?php echo number_format($saldo, 2, ',', '.'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="text-end mt-2">
        <span class="fw-bold">Saldo do Período: R$ <?php echo number_format($saldo, 2, ',', '.'); ?></span>
    </div>
</div> 