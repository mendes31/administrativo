<?php
/**
 * Imagens (até 6) e anexo — padrão create-informativo (dropzone) ou update-informativo (sidebar).
 *
 * @var array<int, array<string, mixed>> $eventImages
 * @var string|null $eventAnexo
 * @var int $maxImages
 * @var string $layoutMode create|edit
 */
$eventImages = $eventImages ?? [];
$eventAnexo = $eventAnexo ?? null;
$maxImages = (int)($maxImages ?? 6);
$layoutMode = $layoutMode ?? 'create';
$currentImageCount = count($eventImages);
$slotsLeft = max(0, $maxImages - $currentImageCount);
$urlAdm = $_ENV['URL_ADM'] ?? '';
$isEdit = ($layoutMode === 'edit');
?>

<?php if ($isEdit && $currentImageCount > 0): ?>
    <div class="mb-3">
        <label class="form-label">Imagens atuais</label>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($eventImages as $img): ?>
                <?php
                $imgId = (int)($img['id'] ?? 0);
                $imgPath = (string)($img['image_path'] ?? '');
                if ($imgPath === '') {
                    continue;
                }
                ?>
                <div class="text-center">
                    <img src="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($imgPath); ?>"
                         class="img-thumbnail d-block"
                         style="max-width: 100px; max-height: 100px; object-fit: cover;"
                         alt="Imagem do evento">
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" name="remove_image_ids[]"
                               value="<?php echo $imgId; ?>" id="remove_img_<?php echo $imgId; ?>">
                        <label class="form-check-label small" for="remove_img_<?php echo $imgId; ?>">Remover</label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($isEdit): ?>
    <?php if ($slotsLeft > 0): ?>
    <div class="mb-3">
        <label for="company_event_imagens" class="form-label">
            <?php echo $currentImageCount > 0 ? 'Adicionar imagens' : 'Imagens'; ?>
        </label>
        <input type="file" class="form-control" id="company_event_imagens" name="imagens[]"
               accept="image/*" multiple onchange="companyEventPreviewImagensEdit(this)">
        <div class="form-text">Até <?php echo $slotsLeft; ?> imagem(ns) — JPG, PNG, GIF, WebP. Máx. 20MB cada.</div>
        <div id="company_event_preview_imagens_edit" class="mt-2 d-flex flex-wrap gap-2"></div>
    </div>
    <?php elseif ($currentImageCount >= $maxImages): ?>
    <p class="small text-muted">Limite de <?php echo $maxImages; ?> imagens atingido.</p>
    <?php endif; ?>

    <div class="mb-3">
        <label for="company_event_anexo" class="form-label">Anexo</label>
        <input type="file" class="form-control" id="company_event_anexo" name="anexo"
               accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rar"
               onchange="companyEventPreviewAnexoEdit(this)">
        <div class="form-text">PDF, DOC, DOCX, TXT, XLS, XLSX, CSV, ZIP, RAR. Máx. 20MB.</div>
        <?php if (!empty($eventAnexo)): ?>
            <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                <div>
                    <small class="text-muted d-block">Anexo atual:</small>
                    <a href="<?php echo htmlspecialchars($urlAdm); ?>serve-file?path=<?php echo urlencode($eventAnexo); ?>"
                       target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-1">
                        <i class="fas fa-paperclip me-1"></i>
                        <?php echo htmlspecialchars(pathinfo($eventAnexo, PATHINFO_FILENAME)); ?>
                    </a>
                </div>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remove_anexo" value="1" id="remove_anexo">
                    <label class="form-check-label small" for="remove_anexo">Remover anexo</label>
                </div>
            </div>
        <?php endif; ?>
        <div id="company_event_preview_anexo_edit" class="mt-2"></div>
    </div>

<?php else: ?>
    <!-- Layout create: dropzones lado a lado (create-informativo) -->
    <div class="row g-3 mb-2 mt-1">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Imagens<?php echo $slotsLeft < $maxImages ? ' (até ' . $slotsLeft . ')' : ''; ?></label>
            <?php if ($slotsLeft > 0): ?>
            <div class="dropzone rounded-3 border border-2 border-dashed p-2 text-center bg-light position-relative" style="min-height: 45px; cursor: pointer;">
                <input type="file" class="d-none" id="company_event_imagens" name="imagens[]"
                       accept=".png,.jpg,.jpeg,.gif,.webp" multiple
                       onchange="companyEventPreviewImagensCreate(this)">
                <label for="company_event_imagens" id="company_event_imagens_label"
                       class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="cursor:pointer;">
                    <i class="fas fa-images fa-2x mb-2 text-secondary"></i>
                    <span class="text-muted">Clique para fazer upload ou arraste imagens aqui</span>
                    <span class="small text-muted">Até <?php echo $slotsLeft; ?> — PNG, JPG, JPEG, GIF, WebP. Máx. 20MB cada</span>
                </label>
                <div id="company_event_preview_imagens_create" class="mt-2"></div>
            </div>
            <?php else: ?>
            <p class="small text-muted mb-0">Limite de <?php echo $maxImages; ?> imagens.</p>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Anexo</label>
            <div class="dropzone rounded-3 border border-2 border-dashed p-2 text-center bg-light position-relative" style="min-height: 45px; cursor: pointer;">
                <input type="file" class="d-none" id="company_event_anexo" name="anexo"
                       accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.csv,.zip,.rar"
                       onchange="companyEventPreviewAnexoCreate(this)">
                <label for="company_event_anexo" id="company_event_anexo_label"
                       class="w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="cursor:pointer;">
                    <i class="fas fa-paperclip fa-2x mb-2 text-secondary"></i>
                    <span class="text-muted">Clique para fazer upload ou arraste um arquivo aqui</span>
                    <span class="small text-muted">PDF, DOC, DOCX, TXT, XLS, XLSX, CSV, ZIP, RAR. Máx. 20MB</span>
                </label>
                <div id="company_event_preview_anexo_create" class="mt-2"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    var maxNew = <?php echo (int)$slotsLeft; ?>;

    window.companyEventPreviewImagensCreate = function (input) {
        var preview = document.getElementById('company_event_preview_imagens_create');
        var label = document.getElementById('company_event_imagens_label');
        if (!preview) return;
        preview.innerHTML = '';
        if (!input.files || !input.files.length) {
            if (label) {
                var s = label.querySelector('span.text-muted');
                if (s) s.textContent = 'Clique para fazer upload ou arraste imagens aqui';
            }
            return;
        }
        if (input.files.length > maxNew) {
            alert('Você pode enviar no máximo ' + maxNew + ' imagem(ns).');
            input.value = '';
            return;
        }
        Array.prototype.forEach.call(input.files, function (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.insertAdjacentHTML('beforeend',
                    '<div class="dropzone-preview"><img src="' + e.target.result + '" alt=""><span class="small text-muted text-truncate" style="max-width:140px">' + file.name + '</span></div>');
            };
            reader.readAsDataURL(file);
        });
        if (label) {
            var span = label.querySelector('span.text-muted');
            if (span) span.textContent = input.files.length + ' imagem(ns) selecionada(s)';
        }
    };

    window.companyEventPreviewAnexoCreate = function (input) {
        var preview = document.getElementById('company_event_preview_anexo_create');
        var label = document.getElementById('company_event_anexo_label');
        if (!preview || !input.files || !input.files[0]) return;
        var file = input.files[0];
        var ext = (file.name.split('.').pop() || '').toLowerCase();
        var icon = 'fa-file';
        if (ext === 'pdf') icon = 'fa-file-pdf text-danger';
        else if (['doc', 'docx'].indexOf(ext) >= 0) icon = 'fa-file-word text-primary';
        else if (['xls', 'xlsx', 'csv'].indexOf(ext) >= 0) icon = 'fa-file-excel text-success';
        else if (['zip', 'rar'].indexOf(ext) >= 0) icon = 'fa-file-archive text-warning';
        preview.innerHTML = '<div class="dropzone-preview"><i class="fas ' + icon + ' fa-2x"></i><span class="small text-muted text-truncate" style="max-width:160px">' + file.name + '</span></div>';
        if (label) {
            var span = label.querySelector('span.text-muted');
            if (span) span.textContent = file.name;
        }
    };

    window.companyEventPreviewImagensEdit = function (input) {
        var preview = document.getElementById('company_event_preview_imagens_edit');
        if (!preview) return;
        preview.innerHTML = '';
        if (!input.files || !input.files.length) return;
        if (input.files.length > maxNew) {
            alert('Você pode adicionar no máximo ' + maxNew + ' imagem(ns).');
            input.value = '';
            return;
        }
        Array.prototype.forEach.call(input.files, function (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail';
                img.style.cssText = 'max-width:80px;max-height:80px;object-fit:cover';
                preview.appendChild(img);
            };
            reader.readAsDataURL(file);
        });
    };

    window.companyEventPreviewAnexoEdit = function (input) {
        var preview = document.getElementById('company_event_preview_anexo_edit');
        if (!preview || !input.files || !input.files[0]) return;
        preview.innerHTML = '<small class="text-muted">Nova seleção: ' + input.files[0].name + '</small>';
    };
})();
</script>
