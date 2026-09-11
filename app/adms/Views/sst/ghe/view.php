<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
$gheId = (int)($item['id'] ?? 0);
$colaboradores = $this->data['colaboradores_vinculados'] ?? [];
$users = $this->data['users'] ?? [];
$treinamentos = $this->data['treinamentos'] ?? [];
$treinamentosVinculadosMap = $this->data['treinamentosVinculadosMap'] ?? [];
$epis = $this->data['epis'] ?? [];
$episVinculadosMap = $this->data['episVinculadosMap'] ?? [];
$episJaNoCargoIds = $this->data['episJaNoCargoIds'] ?? [];
$podeEditarRel = in_array('SstSaveGheRelacionamentos', $perms, true);
$podeSyncVinculos = in_array('SstSyncTreinamentoVinculos', $perms, true);
$csrfRel = CSRFHelper::generateCSRFToken('sst_ghe_relacionamentos');
$vinculadosIds = array_map(static fn(array $c): int => (int)($c['adms_user_id'] ?? 0), $colaboradores);
$countColab = count($colaboradores);
$countTrein = count($treinamentosVinculadosMap);
$countEpi = count($episVinculadosMap);

function formatGheViewCell(mixed $value): string {
    if ($value === null || $value === '') return '-';
    return htmlspecialchars((string)$value);
}
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-industry me-2"></i>GHE #<?= $gheId ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-ghe">GHE</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>
    <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <h4 class="mb-0"><?= htmlspecialchars($item['nome'] ?? '') ?><?php if (!empty($item['codigo'])): ?> <small class="text-muted">(<?= htmlspecialchars($item['codigo']) ?>)</small><?php endif; ?></h4>
            <div>
                <?php if (in_array('SstUpdateGhe', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-ghe/<?= $gheId ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a><?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-ghe" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Dados do ambiente</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Código:</th><td><?= formatGheViewCell($item['codigo'] ?? null) ?></td></tr>
                    <tr><th>Nome:</th><td><?= formatGheViewCell($item['nome'] ?? null) ?></td></tr>
                    <tr><th>Local:</th><td><?= formatGheViewCell($item['ambiente_local'] ?? null) ?></td></tr>
                    <tr><th>Departamento:</th><td><?= formatGheViewCell($item['departamento_nome'] ?? null) ?></td></tr>
                    <tr><th>Descrição:</th><td><?= formatGheViewCell($item['descricao'] ?? null) ?></td></tr>
                    <tr><th>Status:</th><td><?= formatGheViewCell($item['status'] ?? null) ?></td></tr>
                </table></div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header pb-0 border-bottom-0">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-colaboradores-btn" data-bs-toggle="tab" data-bs-target="#tab-colaboradores" type="button" role="tab" data-adms-help-tab="aba-colaboradores">
                                <i class="fas fa-users me-1"></i>Colaboradores
                                <span class="badge bg-primary ms-1"><?= $countColab ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-treinamentos-btn" data-bs-toggle="tab" data-bs-target="#tab-treinamentos" type="button" role="tab" data-adms-help-tab="aba-treinamentos">
                                <i class="fas fa-graduation-cap me-1"></i>Treinamentos
                                <span class="badge bg-success ms-1"><?= $countTrein ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-epis-btn" data-bs-toggle="tab" data-bs-target="#tab-epis" type="button" role="tab" data-adms-help-tab="aba-epis">
                                <i class="fas fa-hard-hat me-1"></i>EPIs
                                <span class="badge bg-danger ms-1"><?= $countEpi ?></span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="tab-content border border-top-0 rounded-bottom p-3">
                    <div class="tab-pane fade show active" id="tab-colaboradores" role="tabpanel">
                        <?php if (!$podeEditarRel): ?>
                            <?php if ($colaboradores === []): ?>
                                <div class="alert alert-info mb-0">Nenhum colaborador vinculado.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                <?php foreach ($colaboradores as $c): ?>
                                    <li class="list-group-item px-0 d-flex justify-content-between">
                                        <span><?= htmlspecialchars($c['colaborador_nome'] ?? '') ?></span>
                                        <small class="text-muted">desde <?= !empty($c['data_inicio']) ? date('d/m/Y', strtotime($c['data_inicio'])) : '-' ?></small>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php else: ?>
                        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-ghe-relacionamentos">
                            <input type="hidden" name="csrf_token" value="<?= $csrfRel ?>">
                            <input type="hidden" name="adms_sst_ghe_id" value="<?= $gheId ?>">
                            <input type="hidden" name="secao" value="colaboradores">
                            <p class="small text-muted">Cada colaborador pode pertencer a apenas um GHE ativo. Ao incluir aqui, vínculos anteriores são encerrados automaticamente.</p>
                            <div class="mb-3" style="max-height:320px;overflow-y:auto;">
                                <?php foreach ($users as $u):
                                    $uid = (int)($u['id'] ?? 0);
                                    if ($uid <= 0) continue;
                                    $checked = in_array($uid, $vinculadosIds, true);
                                ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="colaboradores[]" value="<?= $uid ?>" id="colab_<?= $uid ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="colab_<?= $uid ?>"><?= htmlspecialchars($u['name'] ?? '') ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save me-1"></i>Salvar colaboradores</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="tab-treinamentos" role="tabpanel">
                        <?php if (!$podeEditarRel): ?>
                            <?php if ($treinamentosVinculadosMap === []): ?>
                                <div class="alert alert-info mb-0">Nenhum treinamento vinculado.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                <?php foreach ($treinamentos as $tr):
                                    $trId = (int)($tr['id'] ?? 0);
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
                        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-ghe-relacionamentos">
                            <input type="hidden" name="csrf_token" value="<?= $csrfRel ?>">
                            <input type="hidden" name="adms_sst_ghe_id" value="<?= $gheId ?>">
                            <input type="hidden" name="secao" value="treinamentos">
                            <p class="small text-muted">Treinamentos exigidos para colaboradores deste GHE. Vinculado = obrigatório (desmarque em Opcional). A matriz e o dashboard usam a mesma regra, com a validade do catálogo ou a informada aqui.</p>
                            <?php if ($podeSyncVinculos): ?>
                            <p class="mb-2"><a href="<?= $_ENV['URL_ADM']; ?>sst-sync-treinamento-vinculos" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync me-1"></i>Sincronizar vínculos SST</a></p>
                            <?php endif; ?>
                            <?php if (empty($treinamentos)): ?>
                                <div class="alert alert-warning mb-0">Cadastre treinamentos SST primeiro.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th width="40"></th><th>Treinamento</th><th width="100">Validade (meses)</th><th class="text-center" width="120">Opcional</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($treinamentos as $tr):
                                            $trId = (int)($tr['id'] ?? 0);
                                            if ($trId <= 0) continue;
                                            $vinculado = isset($treinamentosVinculadosMap[$trId]);
                                            $opcional = $vinculado && empty($treinamentosVinculadosMap[$trId]['obrigatorio']);
                                            $valMeses = $vinculado ? ($treinamentosVinculadosMap[$trId]['validade_meses'] ?? '') : '';
                                        ?>
                                        <tr>
                                            <td><input class="form-check-input tr-check" type="checkbox" name="treinamentos[]" value="<?= $trId ?>" id="tr_<?= $trId ?>" <?= $vinculado ? 'checked' : '' ?>></td>
                                            <td><label class="form-check-label mb-0" for="tr_<?= $trId ?>"><?= htmlspecialchars($tr['nome'] ?? '') ?></label></td>
                                            <td><input type="number" class="form-control form-control-sm" name="treinamentos_validade[<?= $trId ?>]" min="1" value="<?= htmlspecialchars((string)$valMeses) ?>" <?= !$vinculado ? 'disabled' : '' ?>></td>
                                            <td class="text-center"><input class="form-check-input tr-opcional" type="checkbox" name="treinamentos_obrigatorio[<?= $trId ?>_opcional]" value="1" <?= $opcional ? 'checked' : '' ?> <?= !$vinculado ? 'disabled' : '' ?> title="Marque apenas se o treinamento for recomendado, não obrigatório"></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-save me-1"></i>Salvar treinamentos</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade" id="tab-epis" role="tabpanel">
                        <?php if (!$podeEditarRel): ?>
                            <?php if ($episVinculadosMap === []): ?>
                                <div class="alert alert-info mb-0">Nenhum EPI vinculado a este GHE.</div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush">
                                <?php foreach ($epis as $ep):
                                    $epId = (int)($ep['id'] ?? 0);
                                    if ($epId <= 0 || !isset($episVinculadosMap[$epId])) continue;
                                ?>
                                    <li class="list-group-item px-0">
                                        <?= htmlspecialchars($ep['nome'] ?? '') ?>
                                        <?php if (!empty($episVinculadosMap[$epId]['obrigatorio'])): ?>
                                            <span class="badge bg-success">Obrigatório</span>
                                        <?php endif; ?>
                                        <?php if (!empty($episJaNoCargoIds[$epId])): ?>
                                            <span class="badge bg-secondary">Já no cargo</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php else: ?>
                        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-ghe-relacionamentos">
                            <input type="hidden" name="csrf_token" value="<?= $csrfRel ?>">
                            <input type="hidden" name="adms_sst_ghe_id" value="<?= $gheId ?>">
                            <input type="hidden" name="secao" value="epis">
                            <p class="small text-muted">EPIs extras deste ambiente. Se o item já for exigido pelo cargo (risco ou necessidade), as pendências e a ficha contam <strong>uma vez</strong>. O selo “Já no cargo” indica essa sobreposição nos colaboradores atuais do GHE.</p>
                            <?php if (empty($epis)): ?>
                                <div class="alert alert-warning mb-0">Cadastre EPIs primeiro.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead><tr><th width="40"></th><th>EPI</th><th>Origem cargo</th><th class="text-center" width="120">Opcional</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($epis as $ep):
                                            $epId = (int)($ep['id'] ?? 0);
                                            if ($epId <= 0) continue;
                                            $vinculado = isset($episVinculadosMap[$epId]);
                                            $opcional = $vinculado && empty($episVinculadosMap[$epId]['obrigatorio']);
                                            $jaCargo = !empty($episJaNoCargoIds[$epId]);
                                        ?>
                                        <tr>
                                            <td><input class="form-check-input epi-check" type="checkbox" name="epis[]" value="<?= $epId ?>" id="epi_<?= $epId ?>" <?= $vinculado ? 'checked' : '' ?>></td>
                                            <td><label class="form-check-label mb-0" for="epi_<?= $epId ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?></label></td>
                                            <td><?php if ($jaCargo): ?><span class="badge bg-secondary">Já no cargo</span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                                            <td class="text-center"><input class="form-check-input epi-opcional" type="checkbox" name="epis_obrigatorio[<?= $epId ?>_opcional]" value="1" <?= $opcional ? 'checked' : '' ?> <?= !$vinculado ? 'disabled' : '' ?> title="Marque apenas se o EPI for recomendado, não obrigatório"></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-save me-1"></i>Salvar EPIs</button>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <script>
                (function () {
                    document.querySelectorAll('.tr-check, .epi-check').forEach(function (chk) {
                        chk.addEventListener('change', function () {
                            const row = this.closest('tr');
                            if (!row) return;
                            row.querySelectorAll('input').forEach(function (inp) {
                                if (inp !== chk) inp.disabled = !chk.checked;
                            });
                            if (chk.checked) {
                                const op = row.querySelector('.tr-opcional, .epi-opcional');
                                if (op) op.checked = false;
                            }
                        });
                    });
                    const hash = window.location.hash;
                    if (hash === '#tab-colaboradores') document.getElementById('tab-colaboradores-btn')?.click();
                    if (hash === '#tab-treinamentos') document.getElementById('tab-treinamentos-btn')?.click();
                    if (hash === '#tab-epis') document.getElementById('tab-epis-btn')?.click();
                })();
                </script>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Auditoria</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="35%">Cadastrado em:</th><td><?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?></td></tr>
                    <tr><th>Atualizado em:</th><td><?= !empty($item['updated_at']) ? date('d/m/Y H:i', strtotime($item['updated_at'])) : '-' ?></td></tr>
                </table></div>
            </div>
        </div>
        <div class="col-md-4">
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
        </div>
    </div>
</div>
