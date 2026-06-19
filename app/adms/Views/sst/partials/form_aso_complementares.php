<?php

/** @var array<int, array<string, mixed>> $complementares */

/** @var array<int, array<string, mixed>> $exames */

use App\adms\Helpers\SstExameResultadoHelper;



$complementares = $complementares ?? [];

$exames = $exames ?? [];

$dataRealizacaoAso = $dataRealizacaoAso ?? '';



$examesMeta = [];

$examesLista = [];

foreach ($exames as $ex) {

    $id = (int) ($ex['id'] ?? 0);

    if ($id <= 0) {

        continue;

    }

    $examesLista[] = ['id' => $id, 'nome' => (string) ($ex['nome'] ?? '')];

    $exige = !isset($ex['exige_resultado']) || !empty($ex['exige_resultado']);

    $examesMeta[$id] = [

        'exige_resultado' => $exige,

        'tipo' => $ex['tipo'] ?? null,

        'resultados' => SstExameResultadoHelper::optionsForComplementaryLaunch($exige, $ex['tipo'] ?? null),

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

    <p class="small text-muted mb-2">

        O pacote traz exames da matriz para o tipo de ASO (nome fixo, sem troca). <strong>Obrigatórios</strong> não podem ser removidos;

        <strong>recomendados</strong> podem ser desmarcados. Use <strong>+ Adicionar exame</strong> só para incluir exame extra.

    </p>

    <div class="table-responsive">

        <table class="table table-sm table-bordered" id="tabela-aso-complementares">

            <thead class="table-light">

                <tr>

                    <th>Exame</th>

                    <th style="width:120px">Exigência</th>

                    <th style="width:150px">Data realização</th>

                    <th style="width:170px">Resultado</th>

                    <th style="width:50px"></th>

                </tr>

            </thead>

            <tbody id="aso-comp-tbody">

            <?php if ($complementares !== []): ?>

                <?php
                $examesUsadosIds = [];
                foreach ($complementares as $row) {
                    $eid = (int) ($row['adms_sst_exame_id'] ?? 0);
                    if ($eid > 0) {
                        $examesUsadosIds[$eid] = true;
                    }
                }
                $exigenciaBadges = [
                    'obrigatorio' => '<span class="badge bg-primary">Obrigatório</span>',
                    'recomendado' => '<span class="badge bg-secondary">Recomendado</span>',
                    'adicional' => '<span class="badge bg-info">Adicional</span>',
                ];
                foreach ($complementares as $i => $row):
                    $exId = (int) ($row['adms_sst_exame_id'] ?? 0);
                    $exigencia = (string) ($row['exigencia'] ?? 'adicional');
                    if (!isset($exigenciaBadges[$exigencia])) {
                        $exigencia = 'adicional';
                    }
                    $locked = in_array($exigencia, ['obrigatorio', 'recomendado'], true);
                ?>

                <tr class="aso-comp-row" data-pacote-obrigatorio="<?= $exigencia === 'obrigatorio' ? '1' : ($exigencia === 'recomendado' ? '0' : '') ?>">

                    <td>

                        <?php if ($locked && $exId > 0):
                            $nomeExame = 'Exame #' . $exId;
                            foreach ($examesLista as $exItem) {
                                if ($exItem['id'] === $exId) {
                                    $nomeExame = $exItem['nome'];
                                    break;
                                }
                            }
                        ?>
                            <span class="fw-medium"><?= htmlspecialchars($nomeExame) ?></span>
                            <input type="hidden" name="complementares[<?= $i ?>][adms_sst_exame_id]" value="<?= $exId ?>">
                        <?php else: ?>
                        <select name="complementares[<?= $i ?>][adms_sst_exame_id]" class="form-select form-select-sm aso-comp-exame" required>

                            <option value="">Selecione...</option>

                            <?php foreach ($exames as $ex):
                                $optId = (int) $ex['id'];
                                if ($optId !== $exId && isset($examesUsadosIds[$optId])) {
                                    continue;
                                }
                            ?>

                                <option value="<?= $optId ?>" <?= ($exId === $optId) ? 'selected' : '' ?>><?= htmlspecialchars($ex['nome'] ?? '') ?></option>

                            <?php endforeach; ?>

                        </select>
                        <?php endif; ?>

                    </td>

                    <td class="aso-comp-exigencia"><?= $exigenciaBadges[$exigencia] ?></td>

                    <td><input type="date" name="complementares[<?= $i ?>][data_realizacao]" class="form-control form-control-sm comp-data-realizacao" value="<?= htmlspecialchars($row['data_realizacao'] ?? $dataRealizacaoAso) ?>"></td>

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

                    <td class="text-center">

                        <?php if ($exigencia === 'obrigatorio'): ?>
                            <span class="text-muted small" title="Exame obrigatório">—</span>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-comp" title="Remover"><i class="fas fa-times"></i></button>
                        <?php endif; ?>

                    </td>

                </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-add-aso-comp"><i class="fas fa-plus me-1"></i> Adicionar exame</button>

</div>

<script>

(function () {

    const examesMeta = <?= json_encode($examesMeta, JSON_UNESCAPED_UNICODE) ?>;

    const examesLista = <?= json_encode($examesLista, JSON_UNESCAPED_UNICODE) ?>;

    const tbody = document.getElementById('aso-comp-tbody');

    const dataAso = document.getElementById('data_realizacao');

    let idx = tbody ? tbody.querySelectorAll('.aso-comp-row').length : 0;



    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function exameNomePorId(exameId) {
        const found = examesLista.find((ex) => ex.id === exameId);
        return found ? found.nome : ('Exame #' + exameId);
    }

    function buildExameCell(name, exameId, locked) {
        const td = document.createElement('td');
        if (locked && exameId > 0) {
            const span = document.createElement('span');
            span.className = 'fw-medium';
            span.textContent = exameNomePorId(exameId);
            const hid = document.createElement('input');
            hid.type = 'hidden';
            hid.name = name;
            hid.value = String(exameId);
            td.appendChild(span);
            td.appendChild(hid);
        } else {
            td.appendChild(buildExameSelect(name, exameId || 0, null));
        }
        return td;
    }

    function exigenciaHtml(tipo) {

        if (tipo === 'obrigatorio') {

            return '<span class="badge bg-primary">Obrigatório</span>';

        }

        if (tipo === 'recomendado') {

            return '<span class="badge bg-secondary">Recomendado</span>';

        }

        return '<span class="badge bg-info">Adicional</span>';

    }



    function getExameIdsEmUso(excludeSelect) {
        const ids = new Set();
        if (!tbody) return ids;
        tbody.querySelectorAll('.aso-comp-row').forEach((row) => {
            const hid = row.querySelector('input[type=hidden][name*="adms_sst_exame_id"]');
            if (hid && hid.value) {
                const id = parseInt(hid.value, 10);
                if (id > 0) ids.add(id);
                return;
            }
            const sel = row.querySelector('select.aso-comp-exame');
            if (!sel || sel === excludeSelect) return;
            const id = parseInt(sel.value, 10);
            if (id > 0) ids.add(id);
        });
        return ids;
    }

    function buildExameSelect(name, selectedId, excludeSelect) {
        const sel = document.createElement('select');
        sel.name = name;
        sel.className = 'form-select form-select-sm aso-comp-exame';
        sel.required = true;

        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = 'Selecione...';
        sel.appendChild(empty);

        const used = getExameIdsEmUso(excludeSelect);
        const selected = selectedId ? parseInt(selectedId, 10) : 0;

        examesLista.forEach((ex) => {
            if (used.has(ex.id) && ex.id !== selected) return;
            const opt = document.createElement('option');
            opt.value = String(ex.id);
            opt.textContent = ex.nome;
            if (selected === ex.id) opt.selected = true;
            sel.appendChild(opt);
        });

        return sel;
    }

    function refreshAllExameSelects() {
        if (!tbody) return;
        Array.from(tbody.querySelectorAll('select.aso-comp-exame')).forEach((sel) => {
            const selected = parseInt(sel.value, 10) || 0;
            const name = sel.name;
            const newSel = buildExameSelect(name, selected, null);
            newSel.value = selected > 0 ? String(selected) : '';
            sel.replaceWith(newSel);
        });
    }



    function buildResultadoField(name, exameId, currentValue) {

        const cell = document.createElement('td');

        cell.className = 'aso-comp-resultado-cell';

        const meta = examesMeta[exameId];

        if (meta && meta.exige_resultado && Array.isArray(meta.resultados) && meta.resultados.length > 0) {

            const sel = document.createElement('select');

            sel.name = name;

            sel.className = 'form-select form-select-sm';

            const empty = document.createElement('option');

            empty.value = '';

            empty.textContent = 'Selecione...';

            sel.appendChild(empty);

            meta.resultados.forEach((r) => {

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



    function appendRow({ exameId = 0, exigencia = 'adicional', resultado = '' } = {}) {
        if (!tbody) return;
        const i = idx++;
        const locked = exigencia === 'obrigatorio' || exigencia === 'recomendado';
        const row = document.createElement('tr');
        row.className = 'aso-comp-row';

        const tdExame = buildExameCell('complementares[' + i + '][adms_sst_exame_id]', exameId || 0, locked);

        const tdExig = document.createElement('td');
        tdExig.className = 'aso-comp-exigencia';
        tdExig.innerHTML = exigenciaHtml(exigencia);

        const tdData = document.createElement('td');
        const inpData = document.createElement('input');
        inpData.type = 'date';
        inpData.name = 'complementares[' + i + '][data_realizacao]';
        inpData.className = 'form-control form-control-sm comp-data-realizacao';
        if (dataAso && dataAso.value) inpData.value = dataAso.value;
        tdData.appendChild(inpData);

        const tdRes = buildResultadoField('complementares[' + i + '][resultado]', exameId || 0, resultado);

        const tdAct = document.createElement('td');
        tdAct.className = 'text-center';
        if (exigencia === 'obrigatorio') {
            row.dataset.pacoteObrigatorio = '1';
            tdAct.innerHTML = '<span class="text-muted small" title="Exame obrigatório">—</span>';
        } else {
            row.dataset.pacoteObrigatorio = exigencia === 'recomendado' ? '0' : '';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-danger btn-sm btn-remove-comp';
            btn.title = 'Remover';
            btn.innerHTML = '<i class="fas fa-times"></i>';
            tdAct.appendChild(btn);
        }

        row.append(tdExame, tdExig, tdData, tdRes, tdAct);
        tbody.appendChild(row);

        if (exameId > 0) {
            const sel = row.querySelector('.aso-comp-exame');
            if (sel) {
                onExameChange(sel);
            } else {
                onExameChangeForRow(row, exameId);
            }
        }
    }

    function onExameChangeForRow(row, exameId) {
        const oldCell = row.querySelector('.aso-comp-resultado-cell');
        if (!oldCell) return;
        const name = oldCell.querySelector('[name]')?.name || '';
        const current = oldCell.querySelector('select, input')?.value || '';
        const newCell = buildResultadoField(name, parseInt(String(exameId), 10) || 0, current);
        oldCell.replaceWith(newCell);
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

        document.querySelectorAll('.comp-data-realizacao').forEach((el) => {

            if (!el.value && v) el.value = v;

        });

    }



    function mergePacoteExames(data) {

        const obr = Array.isArray(data.obrigatorios) ? data.obrigatorios : (Array.isArray(data.exames) ? data.exames : []);

        const rec = Array.isArray(data.recomendados) ? data.recomendados : [];

        const map = new Map();

        obr.forEach((ex) => {

            const id = parseInt(ex.adms_sst_exame_id, 10);

            if (id > 0) map.set(id, { ...ex, obrigatorio: true });

        });

        rec.forEach((ex) => {

            const id = parseInt(ex.adms_sst_exame_id, 10);

            if (id > 0 && !map.has(id)) map.set(id, { ...ex, obrigatorio: false });

        });

        return Array.from(map.values());

    }



    document.getElementById('btn-add-aso-comp')?.addEventListener('click', function () {

        appendRow({ exigencia: 'adicional' });

        syncCompDates();

    });



    tbody?.addEventListener('click', function (e) {

        const btn = e.target.closest('.btn-remove-comp');

        if (!btn) return;

        const row = btn.closest('tr');

        if (!row || row.dataset.pacoteObrigatorio === '1') {

            return;

        }

        row.remove();
        refreshAllExameSelects();

    });



    tbody?.addEventListener('change', function (e) {

        if (e.target.classList.contains('aso-comp-exame')) {
            onExameChange(e.target);
            refreshAllExameSelects();
        }

    });



    dataAso?.addEventListener('change', syncCompDates);



    document.getElementById('btn-carregar-pacote-aso')?.addEventListener('click', function () {

        const btn = this;

        const userId = document.getElementById('adms_user_id')?.value;

        const tipo = document.getElementById('tipo')?.value;

        if (!userId || !tipo) {

            alert('Selecione colaborador e tipo de ASO primeiro.');

            return;

        }

        if (!tbody) {

            alert('Tabela de exames complementares não encontrada.');

            return;

        }



        const url = '<?= $_ENV['URL_ADM'] ?>sst-pacote-exames-aso?adms_user_id=' + encodeURIComponent(userId) + '&tipo=' + encodeURIComponent(tipo);

        btn.disabled = true;

        fetch(url, {

            headers: {

                'Accept': 'application/json',

                'X-Requested-With': 'XMLHttpRequest',

            },

        })

            .then(async (r) => {

                const text = await r.text();

                let data;

                try {

                    data = JSON.parse(text);

                } catch (e) {

                    throw new Error('Resposta inválida do servidor (verifique permissões ou recarregue a página).');

                }

                if (!r.ok || !data.ok) {

                    throw new Error(data.message || 'Não foi possível carregar o pacote.');

                }

                return data;

            })

            .then((data) => {

                const lista = mergePacoteExames(data);

                tbody.innerHTML = '';

                idx = 0;

                if (lista.length === 0) {

                    alert('Nenhum exame vinculado na matriz para este colaborador e tipo de ASO.');

                    return;

                }

                lista.forEach((ex) => {

                    const id = parseInt(ex.adms_sst_exame_id, 10);

                    appendRow({

                        exameId: id,

                        exigencia: ex.obrigatorio ? 'obrigatorio' : 'recomendado',

                    });

                });

                syncCompDates();

            })

            .catch((err) => alert(err.message || 'Erro ao carregar pacote de exames.'))

            .finally(() => { btn.disabled = false; });

    });

    refreshAllExameSelects();

})();

</script>


