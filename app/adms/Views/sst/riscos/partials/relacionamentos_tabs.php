<?php
/** @var array<string, mixed> $item */
/** @var array<int, array<string, mixed>> $epis */
/** @var list<array<string, mixed>> $cargosVinculados */
/** @var list<array<string, mixed>> $examesVinculadosRows */
/** @var array<int, array<string, mixed>> $treinamentos */
/** @var array<int, array{obrigatorio: bool, validade_meses?: int|null}> $treinamentosVinculadosMap */
/** @var array<int, string> $buttonPermission */

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstRiscoNavigationHelper;

$perms = $buttonPermission ?? [];
$riscoId = (int) ($item['id'] ?? 0);
$cargosVinculados = $cargosVinculados ?? [];
$examesVinculadosRows = $examesVinculadosRows ?? [];
$episVinculadosMap = $episVinculadosMap ?? [];
$podeEditarEpis = in_array('SstUpdateRisco', $perms, true) || in_array('SstSaveRiscoRelacionamentos', $perms, true);
$podeGerirCargos = in_array('SstCreateRiscoCargo', $perms, true) || in_array('SstUpdateRiscoCargo', $perms, true);
$treinamentos = $treinamentos ?? [];
$treinamentosVinculadosMap = $treinamentosVinculadosMap ?? [];
$podeEditarTreinamentos = in_array('SstUpdateRisco', $perms, true) || in_array('SstSaveRiscoTreinamentos', $perms, true);
$csrfTreinamentos = CSRFHelper::generateCSRFToken('sst_risco_treinamentos');
$csrfDelCargo = CSRFHelper::generateCSRFToken('form_delete_sst_riscos_cargo');
$csrfDelExame = CSRFHelper::generateCSRFToken('form_delete_sst_risco_exame');
$countCargos = count($cargosVinculados);
$countExames = count($examesVinculadosRows);
$countEpis = count($episVinculadosMap);
$countTreinamentos = count($treinamentosVinculadosMap);
$podeGerirExames = in_array('SstCreateRiscoExame', $perms, true) || in_array('SstUpdateRiscoExame', $perms, true);
$csrfEpis = CSRFHelper::generateCSRFToken('sst_risco_relacionamentos');
?>
<div class="card mb-4 shadow-sm" id="risco-relacionamentos">
    <div class="card-header">
        <h5 class="mb-0">Relacionamentos</h5>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-cargos-btn" data-bs-toggle="tab" data-bs-target="#tab-cargos" type="button" role="tab" data-adms-help-tab="aba-cargos">
                    <i class="fas fa-shield-virus me-1"></i>Cargos / Setores
                    <span class="badge bg-secondary ms-1"><?= $countCargos ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-exames-btn" data-bs-toggle="tab" data-bs-target="#tab-exames" type="button" role="tab" data-adms-help-tab="aba-exames">
                    <i class="fas fa-stethoscope me-1"></i>Exames
                    <span class="badge bg-primary ms-1"><?= $countExames ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-epis-btn" data-bs-toggle="tab" data-bs-target="#tab-epis" type="button" role="tab" data-adms-help-tab="aba-epis">
                    <i class="fas fa-hard-hat me-1"></i>EPIs
                    <span class="badge bg-warning text-dark ms-1"><?= $countEpis ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-treinamentos-btn" data-bs-toggle="tab" data-bs-target="#tab-treinamentos" type="button" role="tab" data-adms-help-tab="aba-treinamentos">
                    <i class="fas fa-graduation-cap me-1"></i>Treinamentos
                    <span class="badge bg-success ms-1"><?= $countTreinamentos ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content border border-top-0 rounded-bottom p-3">
            <div class="tab-pane fade show active" id="tab-cargos" role="tabpanel">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <p class="small text-muted flex-grow-1 mb-0">Cargos e setores expostos a este risco. Só cargo: todos daquele cargo. Só departamento: todos do setor — a matriz conta os cargos dos colaboradores ativos nesse setor. Cargo e departamento: apenas essa combinação.</p>
                    <?php if ($podeGerirCargos): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-risco-cargo?adms_sst_risco_id=<?= $riscoId ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Adicionar vínculo
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($cargosVinculados === []): ?>
                    <div class="alert alert-warning mb-0">Nenhum cargo/setor vinculado a este risco.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr>
                                <th>Cargo</th><th>Departamento</th><th>Nível</th><th>Obs.</th>
                                <?php if ($podeGerirCargos): ?><th class="text-center" width="90">Ações</th><?php endif; ?>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($cargosVinculados as $c):
                                $cid = (int) ($c['id'] ?? 0);
                            ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($c['cargo_nome'] ?? 'Todos') ?>
                                        <?php if (($c['cargo_nome'] ?? '') === '' && !empty($c['cargos_do_setor'])): ?>
                                            <div class="small text-muted mt-1">
                                                Cargos com colaboradores neste setor:
                                                <?= htmlspecialchars(implode(', ', array_column($c['cargos_do_setor'], 'name'))) ?>
                                            </div>
                                        <?php elseif (($c['cargo_nome'] ?? '') === ''): ?>
                                            <div class="small text-muted mt-1">Nenhum colaborador ativo neste setor — a matriz passa a contar quando houver pessoas no cargo.</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($c['departamento_nome'] ?? 'Todos') ?></td>
                                    <td><?= htmlspecialchars($c['nivel'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($c['observacoes'] ?? '-') ?></td>
                                    <?php if ($podeGerirCargos): ?>
                                    <td class="text-center text-nowrap">
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-update-risco-cargo/<?= $cid ?>?return_risco_id=<?= $riscoId ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-risco-cargo" method="POST" class="d-inline" onsubmit="return confirm('Excluir vínculo?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfDelCargo ?>">
                                            <input type="hidden" name="id" value="<?= $cid ?>">
                                            <input type="hidden" name="return_risco_id" value="<?= $riscoId ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Excluir"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-exames" role="tabpanel">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <p class="small text-muted mb-0 flex-grow-1">Exames da matriz por categoria ASO. Obrigatórios entram no pacote ASO; recomendados podem ser incluídos no encaminhamento.</p>
                    <?php if ($podeGerirExames): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-risco-exame?adms_sst_risco_id=<?= $riscoId ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Adicionar exame
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($examesVinculadosRows === []): ?>
                    <div class="alert alert-warning mb-0">Nenhum exame vinculado a este risco.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr>
                                <th>Exame</th><th>Categoria ASO</th><th>Periodicidade</th><th>Exigência</th>
                                <?php if ($podeGerirExames): ?><th class="text-center" width="90">Ações</th><?php endif; ?>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($examesVinculadosRows as $re):
                                $reId = (int) ($re['id'] ?? 0);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($re['exame_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($re['categoria_aso'] ?? 'Todas') ?></td>
                                    <td><?= htmlspecialchars((string) ($re['periodicidade_meses'] ?? '-')) ?><?= ($re['periodicidade_meses'] ?? '') !== '' && $re['periodicidade_meses'] !== null ? ' meses' : '' ?></td>
                                    <td><?= !empty($re['obrigatorio']) ? 'Obrigatório' : 'Recomendado' ?></td>
                                    <?php if ($podeGerirExames): ?>
                                    <td class="text-center text-nowrap">
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-update-risco-exame/<?= $reId ?>?return_risco_id=<?= $riscoId ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-risco-exame" method="POST" class="d-inline" onsubmit="return confirm('Excluir vínculo?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfDelExame ?>">
                                            <input type="hidden" name="id" value="<?= $reId ?>">
                                            <input type="hidden" name="return_risco_id" value="<?= $riscoId ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Excluir"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-epis" role="tabpanel">
                <?php if (!$podeEditarEpis): ?>
                    <?php if ($episVinculadosMap === []): ?>
                        <div class="alert alert-info mb-0">Nenhum EPI vinculado.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                        <?php foreach ($epis as $ep):
                            $epId = (int) ($ep['id'] ?? 0);
                            if ($epId <= 0 || !isset($episVinculadosMap[$epId])) continue;
                        ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($ep['nome'] ?? '') ?>
                                <?php if (!empty($episVinculadosMap[$epId]['obrigatorio'])): ?>
                                    <span class="badge bg-warning text-dark">Obrigatório</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-risco-relacionamentos">
                    <input type="hidden" name="csrf_token" value="<?= $csrfEpis ?>">
                    <input type="hidden" name="adms_sst_risco_id" value="<?= $riscoId ?>">
                    <input type="hidden" name="active_tab" id="active_tab" value="cargos">
                    <p class="small text-muted">EPIs exigidos para colaboradores expostos a este risco (via cargo/setor).</p>
                    <?php if (empty($epis)): ?>
                        <div class="alert alert-warning mb-0">Cadastre EPIs primeiro.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th width="40"></th><th>EPI</th><th class="text-center" width="120">Obrigatório</th></tr></thead>
                                <tbody>
                                <?php foreach ($epis as $ep):
                                    $epId = (int) ($ep['id'] ?? 0);
                                    if ($epId <= 0 || ($ep['status'] ?? '') === 'Inativo') continue;
                                    $vinculado = isset($episVinculadosMap[$epId]);
                                    $obrigatorio = $vinculado && !empty($episVinculadosMap[$epId]['obrigatorio']);
                                ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input epi-check" type="checkbox" name="epis[]" value="<?= $epId ?>" id="ep_<?= $epId ?>" <?= $vinculado ? 'checked' : '' ?>>
                                    </td>
                                    <td><label class="form-check-label mb-0" for="ep_<?= $epId ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?></label></td>
                                    <td class="text-center">
                                        <input class="form-check-input" type="checkbox" name="epis_obrigatorio[<?= $epId ?>]" value="1" id="ep_ob_<?= $epId ?>" <?= $obrigatorio ? 'checked' : '' ?> <?= $vinculado ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Salvar EPIs</button>
                        </div>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>

            <div class="tab-pane fade" id="tab-treinamentos" role="tabpanel">
                <?php if (!$podeEditarTreinamentos): ?>
                    <?php if ($treinamentosVinculadosMap === []): ?>
                        <div class="alert alert-info mb-0">Nenhum treinamento vinculado.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                        <?php foreach ($treinamentos as $tr):
                            $trId = (int) ($tr['id'] ?? 0);
                            if ($trId <= 0 || !isset($treinamentosVinculadosMap[$trId])) continue;
                        ?>
                            <li class="list-group-item px-0">
                                <?= htmlspecialchars($tr['nome'] ?? '') ?>
                                <?php if (!empty($treinamentosVinculadosMap[$trId]['obrigatorio'])): ?>
                                    <span class="badge bg-success">Obrigatório</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-risco-treinamentos">
                    <input type="hidden" name="csrf_token" value="<?= $csrfTreinamentos ?>">
                    <input type="hidden" name="adms_sst_risco_id" value="<?= $riscoId ?>">
                    <p class="small text-muted">Treinamentos obrigatórios entram na matriz por cargo ao salvar (não depende de colaborador ativo). Exames e EPIs ficam nas respectivas telas.</p>
                    <?php if (empty($treinamentos)): ?>
                        <div class="alert alert-warning mb-0">Cadastre treinamentos SST primeiro.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead><tr><th width="40"></th><th>Treinamento</th><th width="100">Validade (meses)</th><th class="text-center" width="120">Opcional</th></tr></thead>
                                <tbody>
                                <?php foreach ($treinamentos as $tr):
                                    $trId = (int) ($tr['id'] ?? 0);
                                    if ($trId <= 0 || ($tr['status'] ?? '') === 'Inativo') continue;
                                    $vinculado = isset($treinamentosVinculadosMap[$trId]);
                                    $opcional = $vinculado && empty($treinamentosVinculadosMap[$trId]['obrigatorio']);
                                    $valMeses = $vinculado ? ($treinamentosVinculadosMap[$trId]['validade_meses'] ?? '') : '';
                                ?>
                                <tr>
                                    <td><input class="form-check-input tr-check" type="checkbox" name="treinamentos[]" value="<?= $trId ?>" id="tr_<?= $trId ?>" <?= $vinculado ? 'checked' : '' ?>></td>
                                    <td><label class="form-check-label mb-0" for="tr_<?= $trId ?>"><?= htmlspecialchars($tr['nome'] ?? '') ?></label></td>
                                    <td><input type="number" class="form-control form-control-sm" name="treinamentos_validade[<?= $trId ?>]" min="1" value="<?= htmlspecialchars((string)$valMeses) ?>" <?= $vinculado ? '' : 'disabled' ?>></td>
                                    <td class="text-center"><input class="form-check-input tr-opcional" type="checkbox" name="treinamentos_obrigatorio[<?= $trId ?>_opcional]" value="1" id="tr_op_<?= $trId ?>" <?= $opcional ? 'checked' : '' ?> <?= $vinculado ? '' : 'disabled' ?> title="Marque apenas se o treinamento for recomendado, não obrigatório"></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3"><button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Salvar treinamentos</button></div>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <script>
        (function () {
            const input = document.getElementById('active_tab');
            const map = { cargos: 'tab-cargos-btn', exames: 'tab-exames-btn', epis: 'tab-epis-btn', treinamentos: 'tab-treinamentos-btn' };
            Object.keys(map).forEach(function (tab) {
                document.getElementById(map[tab])?.addEventListener('shown.bs.tab', function () {
                    if (input) input.value = tab;
                });
            });
            document.querySelectorAll('.epi-check').forEach(function (chk) {
                chk.addEventListener('change', function () {
                    const id = this.id.replace('ep_', '');
                    const obr = document.getElementById('ep_ob_' + id);
                    if (!obr) return;
                    obr.disabled = !this.checked;
                    if (!this.checked) obr.checked = false;
                });
            });
            document.querySelectorAll('.tr-check').forEach(function (chk) {
                chk.addEventListener('change', function () {
                    const row = this.closest('tr');
                    if (!row) return;
                    row.querySelectorAll('input').forEach(function (inp) {
                        if (inp !== chk) inp.disabled = !chk.checked;
                    });
                    if (chk.checked) {
                        const op = row.querySelector('.tr-opcional');
                        if (op) op.checked = false;
                    }
                });
            });
            const hash = window.location.hash;
            if (hash.startsWith('#tab-')) {
                const tabKey = hash.replace('#tab-', '');
                if (map[tabKey]) {
                    document.getElementById(map[tabKey])?.click();
                    if (input) input.value = tabKey;
                }
            }
        })();
        </script>
    </div>
</div>
