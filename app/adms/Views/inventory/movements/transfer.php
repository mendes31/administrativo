<?php if (!isset($this)) { exit; } ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Transferência de Estoque</span>
        <div>
            <?php if (!empty($this->data['buttonPermission']) && in_array('ListInventoryItems', $this->data['buttonPermission'])): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="btn btn-sm btn-outline-secondary">Itens</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_inventory_transfer') ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Estoque origem</label>
                    <select class="form-select" name="from_stock_id" required data-stock-select data-target-position="from_position_id">
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['listStocks'] ?? []) as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Posição origem</label>
                    <select class="form-select" id="from_position_id" name="from_position_id">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estoque destino</label>
                    <select class="form-select" name="to_stock_id" required data-stock-select data-target-position="to_position_id">
                        <option value="">Selecione...</option>
                        <?php foreach (($this->data['listStocks'] ?? []) as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Posição destino</label>
                    <select class="form-select" id="to_position_id" name="to_position_id">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Observações</label>
                    <input class="form-control" name="notes" />
                </div>
            </div>
            <template id="mov-item-row">
                <div class="row mov-item g-2 align-items-end mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="items[][inv_item_id]" required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qtd</label>
                        <input type="number" min="0" step="0.0001" class="form-control" name="items[][qty]" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Lote</label>
                        <input class="form-control" data-field="batch_code" name="items[][batch_code]" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Validade</label>
                        <input type="date" class="form-control" data-field="expiration_date" name="items[][expiration_date]" />
                    </div>
                    <div class="col-md-12 text-end">
                        <button class="btn btn-sm btn-outline-danger" data-action="remove" type="button">Remover</button>
                    </div>
                </div>
            </template>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Itens da Transferência</strong>
                <button id="btn-add-item" type="button" class="btn btn-sm btn-primary">Adicionar item</button>
            </div>
            <div id="mov-items"></div>
            <script>window.INV_ITEMS = <?= json_encode($this->data['listItems'] ?? []) ?>; window.URL_ADM = '<?= $_ENV['URL_ADM'] ?>';</script>
            <script src="<?= $_ENV['URL_ADM'] ?>public/adms/js/inventory-movements.js?v=1"></script>
            <button class="btn btn-success">Salvar</button>
        </form>
    </div>
</div>


