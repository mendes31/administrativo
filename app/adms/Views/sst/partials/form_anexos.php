<?php
/** Partial: campo de upload de anexos SST (usar em forms com enctype multipart). */
$anexos = $this->data['anexos'] ?? [];
$isEdit = !empty($this->data['item']['id']);
?>
<div class="col-12 mb-3">
    <label class="form-label"><i class="fas fa-paperclip me-1"></i>Anexos</label>
    <?php if ($isEdit && !empty($anexos)): ?>
        <div class="mb-2">
            <?php foreach ($anexos as $anexo): ?>
                <div class="form-check border rounded px-2 py-1 mb-1">
                    <input class="form-check-input" type="checkbox" name="delete_anexos[]" value="<?= (int)($anexo['id'] ?? 0) ?>" id="del_anexo_<?= (int)($anexo['id'] ?? 0) ?>">
                    <label class="form-check-label small" for="del_anexo_<?= (int)($anexo['id'] ?? 0) ?>">
                        <?= htmlspecialchars($anexo['file_name'] ?? '') ?>
                        <a href="<?= $_ENV['URL_ADM'] ?>../<?= htmlspecialchars($anexo['file_path'] ?? '') ?>" target="_blank" class="ms-1">abrir</a>
                    </label>
                </div>
            <?php endforeach; ?>
            <small class="text-muted">Marque os anexos que deseja excluir ao salvar.</small>
        </div>
    <?php endif; ?>
    <input type="file" name="attachments[]" class="form-control" multiple
           accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar,.ppt,.pptx">
    <small class="text-muted">Fotos, PDFs e documentos — máx. 10 MB por arquivo.</small>
</div>
