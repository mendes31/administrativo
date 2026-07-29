<?php
/** Formulário de nova requisição de pessoal. */
use App\adms\Helpers\CSRFHelper;
$csrf = CSRFHelper::generateCSRFToken('form_create_rh_personnel_request');
$form = $this->data['form'] ?? [];
$positionDepartmentMap = $this->data['position_department_map'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Nova Requisição de Pessoal</h2>
    </div>
    <div class="card border-light shadow">
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="pr-area-id">Área *</label>
                        <select name="form[area_id]" id="pr-area-id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($this->data['departments'] ?? []) as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>" <?= ((string) ($form['area_id'] ?? '') === (string) $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pr-cargo-id">Cargo *</label>
                        <select name="form[cargo_id]" id="pr-cargo-id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach (($this->data['positions'] ?? []) as $pos):
                                $posId = (int) ($pos['id'] ?? 0);
                                $deptIds = $positionDepartmentMap[$posId] ?? [];
                                $deptAttr = $deptIds !== [] ? implode(',', array_map('intval', $deptIds)) : '';
                                ?>
                                <option
                                    value="<?= $posId ?>"
                                    data-dept-ids="<?= htmlspecialchars($deptAttr) ?>"
                                    <?= ((string) ($form['cargo_id'] ?? '') === (string) $posId) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars((string) ($pos['name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="pr-show-all-cargos">
                            <label class="form-check-label" for="pr-show-all-cargos">
                                Mostrar todos os cargos
                            </label>
                        </div>
                        <div class="form-text" id="pr-cargo-hint">
                            Com a área selecionada, a lista prioriza cargos já usados por colaboradores ativos dessa área. Use o nome completo (inclui JR/PL/SR).
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" min="1" name="form[quantidade]" class="form-control" value="<?= htmlspecialchars((string) ($form['quantidade'] ?? '1')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Contrato</label>
                        <select name="form[tipo_contrato]" class="form-select">
                            <?php foreach (['CLT', 'PJ', 'Estágio', 'Temporário'] as $tc): ?>
                                <option value="<?= $tc ?>" <?= ($form['tipo_contrato'] ?? 'CLT') === $tc ? 'selected' : '' ?>><?= $tc ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Motivo</label>
                        <select name="form[motivo_tipo]" class="form-select">
                            <option value="aumento" <?= ($form['motivo_tipo'] ?? '') === 'aumento' ? 'selected' : '' ?>>Aumento de quadro</option>
                            <option value="reposicao" <?= ($form['motivo_tipo'] ?? '') === 'reposicao' ? 'selected' : '' ?>>Reposição</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data desejada</label>
                        <input type="date" name="form[data_desejada]" class="form-control" value="<?= htmlspecialchars((string) ($form['data_desejada'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salário mín.</label>
                        <input type="text" name="form[salario_min]" class="form-control" value="<?= htmlspecialchars((string) ($form['salario_min'] ?? '')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salário máx.</label>
                        <input type="text" name="form[salario_max]" class="form-control" value="<?= htmlspecialchars((string) ($form['salario_max'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Justificativa *</label>
                        <textarea name="form[justificativa]" class="form-control" rows="4" required><?= htmlspecialchars((string) ($form['justificativa'] ?? '')) ?></textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Enviar para aprovação</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    const areaSelect = document.getElementById('pr-area-id');
    const cargoSelect = document.getElementById('pr-cargo-id');
    const showAll = document.getElementById('pr-show-all-cargos');
    const hint = document.getElementById('pr-cargo-hint');
    if (!areaSelect || !cargoSelect || !showAll) {
        return;
    }

    const options = Array.from(cargoSelect.querySelectorAll('option[data-dept-ids]'));

    function parseDeptIds(raw) {
        if (!raw) {
            return [];
        }
        return raw.split(',').map(function (v) { return parseInt(v, 10); }).filter(function (n) { return n > 0; });
    }

    function applyFilter() {
        const areaId = parseInt(areaSelect.value || '0', 10);
        const listAll = showAll.checked;
        const selectedValue = cargoSelect.value;
        let visibleCount = 0;

        options.forEach(function (opt) {
            const deptIds = parseDeptIds(opt.getAttribute('data-dept-ids') || '');
            let visible = true;
            if (!listAll && areaId > 0) {
                visible = deptIds.indexOf(areaId) !== -1;
            }
            opt.hidden = !visible;
            opt.disabled = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        const selectedOpt = cargoSelect.querySelector('option[value="' + selectedValue + '"]');
        if (selectedOpt && selectedOpt.disabled) {
            cargoSelect.value = '';
        }

        if (hint) {
            if (!areaId) {
                hint.textContent = 'Selecione a área para filtrar os cargos já usados nessa área. Marque “Mostrar todos” se precisar de um cargo novo na área.';
            } else if (listAll) {
                hint.textContent = 'Listando todos os cargos cadastrados (nome completo, com JR/PL/SR).';
            } else if (visibleCount === 0) {
                hint.textContent = 'Nenhum cargo encontrado entre colaboradores ativos desta área. Marque “Mostrar todos os cargos”.';
            } else {
                hint.textContent = visibleCount + ' cargo(s) já usados nesta área. Nome completo inclui JR/PL/SR.';
            }
        }
    }

    areaSelect.addEventListener('change', applyFilter);
    showAll.addEventListener('change', applyFilter);
    applyFilter();
})();
</script>
