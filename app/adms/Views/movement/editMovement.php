<?php
// Exibe mensagens de erro/sucesso
if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
$movement = $this->data['movement'] ?? [];
$listBanks = $this->data['listBanks'] ?? [];
$listPaymentMethods = $this->data['listPaymentMethods'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2>Editar Movimento</h2>
            <a href="<?= $_ENV['URL_ADM'] . 'view-pay/' . ($movement['movement_id'] ?? '') ?>" class="btn btn-outline-primary">Voltar</a>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="mb-3 col-md-4">
                        <label class="form-label">ID Movimento</label>
                        <input type="text" class="form-control" value="<?= $movement['id'] ?? '' ?>" readonly>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label">Data PGTO</label>
                        <input type="text" class="form-control" value="<?= isset($movement['created_at']) ? date('d-m-Y H:i:s', strtotime($movement['created_at'])) : '' ?>" readonly>
                    </div>
                    <div class="mb-3 col-md-4">
                        <label class="form-label">Valor Pago</label>
                        <input type="text" class="form-control" value="<?= number_format($movement['movement_value'] ?? 0, 2, ',', '.') ?>" readonly>
                    </div>
                </div>
                <div class="row">
                    <div class="mb-3 col-md-6">
                        <label for="method_id" class="form-label">Forma PGTO</label>
                        <select class="form-select" id="method_id" name="method_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($listPaymentMethods as $item): ?>
                                <option value="<?= $item['id'] ?>" <?= ($movement['id_method'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($movement['name_method'])): ?>
                            <small class="text-muted">Atual: <?= htmlspecialchars($movement['name_method']) ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 col-md-6">
                        <label for="bank_id" class="form-label">Local de Saída</label>
                        <select class="form-select" id="bank_id" name="bank_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($listBanks as $item): ?>
                                <option value="<?= $item['id'] ?>" <?= ($movement['id_bank_pgto'] ?? '') == $item['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['name'] ?? $item['bank_name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($movement['name_bank'])): ?>
                            <small class="text-muted">Atual: <?= htmlspecialchars($movement['name_bank']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="<?= $_ENV['URL_ADM'] . 'view-pay/' . ($movement['movement_id'] ?? '') ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div> 