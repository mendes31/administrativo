<?php
/** @var array<int, array<string, mixed>> $complementares */
/** @var array<int, array<string, mixed>> $exames */
use App\adms\Helpers\SstExameResultadoHelper;

$complementares = $complementares ?? [];
$exames = $exames ?? [];
$dataRealizacaoAso = $dataRealizacaoAso ?? '';

$examesMeta = [];
foreach ($exames as $ex) {
    $id = (int) ($ex['id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $lista = $ex['resultados_permitidos_list'] ?? SstExameResultadoHelper::decode($ex['resultados_permitidos'] ?? null);
    $examesMeta[$id] = [
        'exige_resultado' => !isset($ex['exige_resultado']) || !empty($ex['exige_resultado']),
        'resultados' => $lista,
    ];
}
?>
<div class="col-12 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Exames complementares do ASO</label>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btn-carregar-pacote-aso">
            <i class="fas fa-sync-alt me-1"></i> Carregar pacote sugerido
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered" id="tabela-aso-complementares">
            <thead class="table-light">
                <tr>
                    <th>Exame</th>
                    <th style="width:160px">Data realização</th>
                    <th style="width:180px">Resultado</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($complementares !== []): ?>
                <?php foreach ($complementares as $i => $row): ?>
                <tr class="aso-comp-row">
                    <td>
                        <select name="complementares[<?= $i ?>][adms_sst_exame_id]" class="form-select form-select-sm aso-comp-exame" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($exames as $ex): ?>
                                <option value="<?= (int)$ex['id'] ?>" <?= ((int)($row['adms_sst_exame_id'] ?? 0) === (int)$ex['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ex['nome'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="date" name="complementares[<?= $i ?>][data_realizacao]" class="form-control form-control-sm" value="<?= htmlspecialchars($row['data_realizacao'] ?? $dataRealizacaoAso) ?>"></td>
                    <td class="aso-comp-resultado-cell"><?php
                        $exId = (int) ($row['adms_sst_exame_id'] ?? 0);
                        $resultadoAtual = $row['resultado'] ?? '';
                        $opts = $examesMeta[$exId]['resultados'] ?? [];
                        if ($opts !== []): ?>
                            <select name="complementares[<?= $i ?>][resultado]" class="form-select form-select-sm">
                                <option value="">Selecione...</option>
                                <?php foreach ($opts as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt) ?>" <?= $resultadoAtual === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="complementares[<?= $i ?>][resultado]" class="form-control form-control-sm" value="<?= htmlspecialchars($resultadoAtual) ?>" placeholder="Normal, alterado…">
                        <?php endif; ?>
                    </td>
                    <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-comp" title="Remover"><i class="fas fa-times"></i></button></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-add-aso-comp"><i class="fas fa-plus me-1"></i> Adicionar exame</button>
</div>
<template id="tpl-aso-comp-row">
    <tr class="aso-comp-row">
        <td>
            <select name="complementares[__IDX__][adms_sst_exame_id]" class="form-select form-select-sm aso-comp-exame">
                <option value="">Selecione...</option>
                <?php foreach ($exames as $ex): ?>
                    <option value="<?= (int)$ex['id'] ?>"><?= htmlspecialchars($ex['nome'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="date" name="complementares[__IDX__][data_realizacao]" class="form-control form-control-sm comp-data-realizacao"></td>
        <td class="aso-comp-resultado-cell"><input type="text" name="complementares[__IDX__][resultado]" class="form-control form-control-sm" placeholder="Normal, alterado…"></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-comp" title="Remover"><i class="fas fa-times"></i></button></td>
    </tr>
</template>
<script>
(function () {
    const examesMeta = <?= json_encode($examesMeta, JSON_UNESCAPED_UNICODE) ?>;
    const tbody = document.querySelector('#tabela-aso-complementares tbody');
    const tpl = document.getElementById('tpl-aso-comp-row');
    const dataAso = document.getElementById('data_realizacao');
    let idx = tbody ? tbody.querySelectorAll('.aso-comp-row').length : 0;

    function buildResultadoField(name, exameId, currentValue) {
        const meta = examesMeta[exameId];
        const cell = document.createElement('td');
        cell.className = 'aso-comp-resultado-cell';
        if (meta && Array.isArray(meta.resultados) && meta.resultados.length > 0) {
            const sel = document.createElement('select');
            sel.name = name;
            sel.className = 'form-select form-select-sm';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'Selecione...';
            sel.appendChild(empty);
            meta.resultados.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r;
                opt.textContent = r;
                if (currentValue === r) opt.selected = true;
                sel.appendChild(opt);
            });
            cell.appendChild(sel);
        } else {
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.name = name;
            inp.className = 'form-control form-control-sm';
            inp.placeholder = 'Normal, alterado…';
            if (currentValue) inp.value = currentValue;
            cell.appendChild(inp);
        }
        return cell;
    }

    function onExameChange(select) {
        const row = select.closest('tr');
        if (!row) return;
        const oldCell = row.querySelector('.aso-comp-resultado-cell');
        if (!oldCell) return;
        const name = oldCell.querySelector('[name]')?.name || '';
        const current = oldCell.querySelector('select, input')?.value || '';
        const newCell = buildResultadoField(name, parseInt(select.value, 10) || 0, current);
        oldCell.replaceWith(newCell);
    }

    function syncCompDates() {
        const v = dataAso ? dataAso.value : '';
        document.querySelectorAll('.comp-data-realizacao').forEach(el => { if (!el.value && v) el.value = v; });
    }

    document.getElementById('btn-add-aso-comp')?.addEventListener('click', function () {
        if (!tpl || !tbody) return;
        const html = tpl.innerHTML.replace(/__IDX__/g, String(idx++));
        tbody.insertAdjacentHTML('beforeend', html);
        syncCompDates();
    });

    tbody?.addEventListener('click', function (e) {
        if (e.target.closest('.btn-remove-comp')) {
            e.target.closest('tr')?.remove();
        }
    });

    tbody?.addEventListener('change', function (e) {
        if (e.target.classList.contains('aso-comp-exame')) {
            onExameChange(e.target);
        }
    });

    dataAso?.addEventListener('change', syncCompDates);

    document.getElementById('btn-carregar-pacote-aso')?.addEventListener('click', function () {
        const userId = document.getElementById('adms_user_id')?.value;
        const tipo = document.getElementById('tipo')?.value;
        if (!userId || !tipo) {
            alert('Selecione colaborador e tipo de ASO primeiro.');
            return;
        }
        const url = '<?= $_ENV['URL_ADM'] ?>sst-pacote-exames-aso?adms_user_id=' + encodeURIComponent(userId) + '&tipo=' + encodeURIComponent(tipo);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !Array.isArray(data.exames)) {
                    alert(data.message || 'Não foi possível carregar o pacote.');
                    return;
                }
                tbody.innerHTML = '';
                idx = 0;
                data.exames.forEach(ex => {
                    const html = tpl.innerHTML.replace(/__IDX__/g, String(idx++));
                    tbody.insertAdjacentHTML('beforeend', html);
                    const row = tbody.lastElementChild;
                    const sel = row.querySelector('.aso-comp-exame');
                    if (sel) {
                        sel.value = String(ex.adms_sst_exame_id);
                        onExameChange(sel);
                    }
                });
                syncCompDates();
            })
            .catch(() => alert('Erro ao carregar pacote de exames.'));
    });
})();
</script>
