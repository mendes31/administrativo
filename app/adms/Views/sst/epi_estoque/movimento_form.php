<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$epis = $this->data['epis'] ?? [];
$saldoAtual = $this->data['saldo_atual'] ?? null;
$csrf = CSRFHelper::generateCSRFToken('sst_epi_movimento_form');
$tipo = (string)($item['tipo_movimento'] ?? 'Entrada');
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-dolly me-2"></i>Movimentação de estoque EPI</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-estoque">Estoque EPI</a></li>
            <li class="breadcrumb-item active">Nova movimentação</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-create-epi-movimento" id="formMov">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">EPI *</label>
                        <select name="adms_sst_epi_id" id="epiSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($epis as $ep): ?>
                            <option value="<?= (int)$ep['id'] ?>" <?= ((int)($item['adms_sst_epi_id'] ?? 0) === (int)$ep['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ep['nome'] ?? '') ?> (saldo: <?= (int)($ep['estoque_atual'] ?? 0) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($saldoAtual !== null): ?>
                        <div class="form-text">Saldo atual: <strong id="saldoLabel"><?= (int)$saldoAtual ?></strong></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo *</label>
                        <select name="tipo_movimento" id="tipoMov" class="form-select" required>
                            <?php foreach (['Entrada', 'Saída', 'Ajuste'] as $t): ?>
                            <option value="<?= $t ?>" <?= $tipo === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="wrapQty">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" name="quantidade" class="form-control" min="1" value="<?= (int)($item['quantidade'] ?? 1) ?>" required>
                    </div>
                    <div class="col-md-4 d-none" id="wrapSaldoNovo">
                        <label class="form-label">Saldo contado (inventário) *</label>
                        <input type="number" name="saldo_novo" class="form-control" min="0" value="<?= $saldoAtual !== null ? (int)$saldoAtual : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data *</label>
                        <input type="date" name="data_movimento" class="form-control" value="<?= htmlspecialchars($item['data_movimento'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Documento (NF, pedido…)</label>
                        <input type="text" name="documento_ref" class="form-control" placeholder="Opcional">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Registrar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-epi-movimentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const tipo = document.getElementById('tipoMov');
    const wrapQty = document.getElementById('wrapQty');
    const wrapSaldo = document.getElementById('wrapSaldoNovo');
    const qtyInput = wrapQty.querySelector('input');
    function toggle() {
        const isAjuste = tipo.value === 'Ajuste';
        wrapSaldo.classList.toggle('d-none', !isAjuste);
        wrapQty.classList.toggle('d-none', isAjuste);
        qtyInput.required = !isAjuste;
        wrapSaldo.querySelector('input').required = isAjuste;
    }
    tipo.addEventListener('change', toggle);
    toggle();
})();
</script>
