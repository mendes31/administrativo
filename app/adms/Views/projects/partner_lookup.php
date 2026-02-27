<?php ?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Lista de Parceiros de Negócio</h2>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span>Selecionar Parceiro</span>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4 mb-2">
                    <label for="q" class="form-label mb-1">Código / Nome / Documento</label>
                    <input type="text" name="q" id="q" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($this->data['search'] ?? ''); ?>"
                           placeholder="Ex.: * para todos, parte do nome + * para filtrar">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($this->data['per_page'] ?? 10) == $opt ? 'selected' : ''; ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Procurar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>project-partner-lookup" class="btn btn-secondary btn-sm btn-filtros-mobile ms-1">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 8%;">Tipo PN</th>
                            <th style="width: 10%;">Código</th>
                            <th style="width: 20%;">Nome</th>
                            <th style="width: 8%;">Tipo</th>
                            <th style="width: 12%;">Documento</th>
                            <th style="width: 12%;">Telefone</th>
                            <th style="width: 15%;">E-mail</th>
                            <th style="width: 15%;">Endereço</th>
                            <th style="width: 15%;">Descrição</th>
                            <th style="width: 10%;">Data Nasc.</th>
                            <th style="width: 6%;">Ativo</th>
                            <th style="width: 8%;" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['partners'])): ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Nenhum parceiro encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['partners'] as $partner): ?>
                                <tr>
                                    <td><?= htmlspecialchars($partner['origin'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['code'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['name'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['person_type'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['document'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['phone'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['email'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['address'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['description'] ?? ''); ?></td>
                                    <td><?= htmlspecialchars($partner['date_birth'] ?? ''); ?></td>
                                    <td><?= !empty($partner['active']) ? 'SIM' : 'NÃO'; ?></td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-primary btn-sm"
                                                onclick="selectPartner('<?= htmlspecialchars($partner['code'] ?? '', ENT_QUOTES); ?>','<?= htmlspecialchars($partner['name'] ?? '', ENT_QUOTES); ?>')">
                                            Selecionar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            $paginationHtml = $this->data['pagination']['html'] ?? '';
            if ($paginationHtml) {
                echo $paginationHtml;
            }
            ?>
        </div>
    </div>
</div>

<script>
function selectPartner(code, name) {
    if (window.opener && !window.opener.closed) {
        if (typeof window.opener.setProjectPartner === 'function') {
            window.opener.setProjectPartner(code, name);
        } else {
            if (window.opener.document.getElementById('pn_code')) {
                window.opener.document.getElementById('pn_code').value = code;
            }
            if (window.opener.document.getElementById('pn_name')) {
                window.opener.document.getElementById('pn_name').value = name;
            }
        }
    }
    window.close();
}
</script>

