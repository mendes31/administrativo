<?php if (!isset($this)) { exit; } ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Saída de Estoque</span>
        <div>
            <?php if (!empty($this->data['buttonPermission']) && in_array('ListInventoryItems', $this->data['buttonPermission'])): ?>
                <a href="<?= $_ENV['URL_ADM'] ?>list-inventory-items" class="btn btn-sm btn-outline-secondary">Itens</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= \App\adms\Helpers\CSRFHelper::generateCSRFToken('form_create_inventory_exit') ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Nº Documento</label>
                    <input class="form-control" name="doc_number" value="<?= htmlspecialchars($this->data['nextDocId'] ?? '') ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data</label>
                    <input type="date" class="form-control" name="movement_date" value="<?= date('Y-m-d') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Observações</label>
                    <input class="form-control" name="notes" />
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Itens da Saída</strong>
                <button id="btn-add-item" type="button" class="btn btn-sm btn-primary">Adicionar item</button>
            </div>

            <template id="mov-item-row">
                <div class="row mov-item g-2 align-items-end mb-2">
                    <div class="col-md-3">
                        <label class="form-label">Item</label>
                        <select class="form-select" name="items[][inv_item_id]" required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Estoque origem</label>
                        <select class="form-select" name="items[][from_stock_id]" data-row-stock required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Posição origem</label>
                        <select class="form-select" name="items[][from_position_id]"><option value="">Selecione...</option></select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Qtd</label>
                        <input type="number" min="0" step="0.0001" class="form-control" name="items[][qty]" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Lote</label>
                        <input class="form-control" data-field="batch_code" name="items[][batch_code]" />
                    </div>
                    <div class="col-md-2" data-serial-container style="display:none;">
                        <label class="form-label">Nº de série(s)</label>
                        <textarea class="form-control" name="items[][serial_list]" rows="1"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Equipamento (opcional)</label>
                        <input class="form-control" name="items[][equipment_tag]" placeholder="Tag/ID do equipamento" />
                    </div>
                    <div class="col-md-12 text-end">
                        <button class="btn btn-sm btn-outline-danger" data-action="remove" type="button">Remover</button>
                    </div>
                </div>
            </template>

            <div id="mov-items"></div>
            <script>window.INV_ITEMS = <?= json_encode($this->data['listItems'] ?? []) ?>; window.URL_ADM = '<?= $_ENV['URL_ADM'] ?>';</script>
            <script src="<?= $_ENV['URL_ADM'] ?>public/adms/js/inventory-movements.js?v=1"></script>
            <button class="btn btn-success">Salvar</button>
        </form>
    </div>
</div>


