<?php
/** @var array<string, mixed> $item */
/** @var array<int, array<string, mixed>> $exames */
/** @var array<int, array<string, mixed>> $epis */
/** @var list<int> $examesVinculados */
/** @var list<int> $episVinculados */
/** @var array<int, string> $buttonPermission */

use App\adms\Helpers\CSRFHelper;

$perms = $buttonPermission ?? [];
$podeSalvar = in_array('SstUpdateRisco', $perms) || in_array('SstSaveRiscoRelacionamentos', $perms);
$csrfToken = CSRFHelper::generateCSRFToken('sst_risco_relacionamentos');
$riscoId = (int) ($item['id'] ?? 0);
$examesVinculados = $examesVinculados ?? [];
$episVinculados = $episVinculados ?? [];
?>
<div class="card mb-4 shadow-sm" id="risco-relacionamentos">
    <div class="card-header">
        <h5 class="mb-0">Relacionamentos</h5>
    </div>
    <div class="card-body">
        <?php if (!$podeSalvar): ?>
            <div class="alert alert-info mb-0">Sem permissão para editar vínculos.</div>
        <?php else: ?>
        <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-save-risco-relacionamentos">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="adms_sst_risco_id" value="<?= $riscoId ?>">
            <input type="hidden" name="active_tab" id="active_tab" value="exames">

            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="tab-exames-btn" data-bs-toggle="tab" data-bs-target="#tab-exames" type="button" role="tab">
                        <i class="fas fa-stethoscope me-1"></i>Exames
                        <span class="badge bg-primary ms-1"><?= count($examesVinculados) ?></span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="tab-epis-btn" data-bs-toggle="tab" data-bs-target="#tab-epis" type="button" role="tab">
                        <i class="fas fa-hard-hat me-1"></i>EPIs
                        <span class="badge bg-warning text-dark ms-1"><?= count($episVinculados) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3">
                <div class="tab-pane fade show active" id="tab-exames" role="tabpanel">
                    <p class="small text-muted">Marque os exames complementares exigidos por este risco. Para periodicidade e categoria ASO, use <a href="<?= $_ENV['URL_ADM']; ?>sst-list-risco-exame">Exames por risco</a>.</p>
                    <?php if (empty($exames)): ?>
                        <div class="alert alert-warning mb-0">Cadastre exames complementares primeiro.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($exames as $ex):
                                $exId = (int) ($ex['id'] ?? 0);
                                if ($exId <= 0 || ($ex['status'] ?? '') === 'Inativo') continue;
                                $checked = in_array($exId, $examesVinculados, true);
                            ?>
                            <div class="col-md-4 col-lg-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="exames[]" value="<?= $exId ?>" id="ex_<?= $exId ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ex_<?= $exId ?>">
                                        <?= htmlspecialchars($ex['nome'] ?? '') ?>
                                        <?php if (!empty($ex['codigo'])): ?>
                                            <small class="text-muted">(<?= htmlspecialchars($ex['codigo']) ?>)</small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="tab-epis" role="tabpanel">
                    <p class="small text-muted">Marque os EPIs associados a este risco. Colaboradores expostos (via risco por cargo) terão pendência se não possuírem o EPI.</p>
                    <?php if (empty($epis)): ?>
                        <div class="alert alert-warning mb-0">Cadastre EPIs primeiro.</div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($epis as $ep):
                                $epId = (int) ($ep['id'] ?? 0);
                                if ($epId <= 0 || ($ep['status'] ?? '') === 'Inativo') continue;
                                $checked = in_array($epId, $episVinculados, true);
                            ?>
                            <div class="col-md-4 col-lg-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="epis[]" value="<?= $epId ?>" id="ep_<?= $epId ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ep_<?= $epId ?>"><?= htmlspecialchars($ep['nome'] ?? '') ?></label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Salvar relacionamentos</button>
            </div>
        </form>
        <script>
        (function () {
            const input = document.getElementById('active_tab');
            document.getElementById('tab-exames-btn')?.addEventListener('shown.bs.tab', () => { if (input) input.value = 'exames'; });
            document.getElementById('tab-epis-btn')?.addEventListener('shown.bs.tab', () => { if (input) input.value = 'epis'; });
            const hash = window.location.hash;
            if (hash === '#tab-epis') {
                document.getElementById('tab-epis-btn')?.click();
                if (input) input.value = 'epis';
            }
        })();
        </script>
        <?php endif; ?>
    </div>
</div>
