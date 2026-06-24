<?php

use App\adms\Helpers\CSRFHelper;

$user = $this->data['user'];

$pend = $this->data['pendencias'] ?? ['epis' => [], 'exames' => [], 'treinamentos' => [], 'resumo' => ['total' => 0]];

$pendTotal = (int) ($pend['resumo']['total'] ?? 0);

$resumo = $this->data['resumo'] ?? [];

$riscos = $this->data['riscos'] ?? [];

$obrig = $this->data['obrigatoriedades'] ?? ['epis' => [], 'exames' => []];

$timeline = $this->data['timeline'] ?? [];

$pppHistorico = $this->data['ppp_historico'] ?? [];

$csrfPpp = CSRFHelper::generateCSRFToken('sst_generate_ppp');
$csrfAbrirAso = CSRFHelper::generateCSRFToken('sst_abrir_aso_pendencia');
require __DIR__ . '/partials/sst_aso_pendencia_actions.php';

$perms = $this->data['buttonPermission'] ?? [];

$uid = (int) ($user['id'] ?? 0);

$ultimoAso = $resumo['ultimo_aso'] ?? null;

$afastAtivo = $resumo['afastamento_ativo'] ?? null;

$proximosExames = $resumo['proximos_exames'] ?? [];

$proximoExame = $proximosExames[0] ?? null;

$hoje = date('Y-m-d');



$asoValidadeClass = 'secondary';

if (!empty($ultimoAso['data_validade'])) {

    $asoValidadeClass = $ultimoAso['data_validade'] < $hoje ? 'danger' : ($ultimoAso['data_validade'] <= date('Y-m-d', strtotime('+30 days')) ? 'warning' : 'success');

}

?>

<div class="container-fluid px-4">

    <div class="mb-1 hstack gap-2 flex-wrap">

        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-user-shield me-2"></i>SST — <?= htmlspecialchars($user['name'] ?? '') ?></h2>

        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">

            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>

            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>

            <li class="breadcrumb-item">Perfil colaborador</li>

        </ol>

    </div>



    <div class="card mb-3 border-light shadow-sm">

        <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">

            <div>

                <span class="text-muted small">Cargo:</span> <?= htmlspecialchars($user['name_pos'] ?? '-') ?>

                <span class="text-muted small ms-2">Depto:</span> <?= htmlspecialchars($user['name_dep'] ?? '-') ?>

                <a href="<?= $_ENV['URL_ADM']; ?>view-user/<?= $uid ?>" class="btn btn-link btn-sm py-0 ms-2"><i class="fas fa-user"></i> Ficha do colaborador</a>

            </div>

            <div class="d-flex flex-wrap gap-1">

                <?php if (in_array('SstCreateAso', $perms, true)): ?>

                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-aso?adms_user_id=<?= $uid ?>" class="btn btn-success btn-sm">+ ASO</a>

                <?php endif; ?>

                <?php if (in_array('SstEncaminhamentoAso', $perms, true)): ?>

                    <a href="<?= $_ENV['URL_ADM']; ?>sst-encaminhamento-aso?adms_user_id=<?= $uid ?>" class="btn btn-primary btn-sm"><i class="fas fa-file-export"></i> Encaminhamento ASO</a>

                <?php endif; ?>

                <?php if (in_array('SstCreateAfastamento', $perms, true)): ?>

                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-afastamento?adms_user_id=<?= $uid ?>" class="btn btn-success btn-sm">+ Afastamento</a>

                <?php endif; ?>

                <?php if (in_array('SstCreateEpiFicha', $perms, true)): ?>

                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-epi-ficha?adms_user_id=<?= $uid ?>" class="btn btn-success btn-sm">+ Ficha EPI</a>

                <?php endif; ?>

                <?php if (in_array('SstCreateAcidente', $perms, true)): ?>

                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-acidente?adms_user_id=<?= $uid ?>" class="btn btn-outline-danger btn-sm">+ Acidente</a>

                <?php endif; ?>

                <?php if (in_array('SstGeneratePpp', $perms, true)): ?>

                    <form action="<?= $_ENV['URL_ADM']; ?>sst-generate-ppp" method="POST" class="d-inline" onsubmit="return confirm('Gerar nova versão do PPP com os dados atuais?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrfPpp ?>">
                        <input type="hidden" name="adms_user_id" value="<?= $uid ?>">
                        <input type="hidden" name="redirect" value="<?= $_ENV['URL_ADM']; ?>sst-employee-profile/<?= $uid ?>">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-file-alt"></i> Gerar PPP</button>
                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>



    <div class="row g-2 mb-3">

        <div class="col-6 col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-2">

                    <div class="text-muted small">Último ASO</div>

                    <?php if ($ultimoAso): ?>

                        <div class="fw-semibold"><?= !empty($ultimoAso['data_realizacao']) ? date('d/m/Y', strtotime($ultimoAso['data_realizacao'])) : '-' ?></div>

                        <div class="small text-muted"><?= htmlspecialchars($ultimoAso['exame_nome'] ?? $ultimoAso['tipo'] ?? '') ?></div>

                    <?php else: ?>

                        <div class="text-muted">Sem registro</div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <div class="col-6 col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-2">

                    <div class="text-muted small">Validade ASO</div>

                    <?php if (!empty($ultimoAso['data_validade'])): ?>

                        <div class="fw-semibold text-<?= $asoValidadeClass ?>"><?= date('d/m/Y', strtotime($ultimoAso['data_validade'])) ?></div>

                        <div class="small"><?= htmlspecialchars($ultimoAso['resultado'] ?? '') ?></div>

                    <?php else: ?>

                        <div class="text-muted">—</div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <div class="col-6 col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-2">

                    <div class="text-muted small">Próximo exame</div>

                    <?php if ($proximoExame): ?>

                        <?php

                        $proxData = $proximoExame['proxima_data'] ?? null;

                        $proxClass = 'secondary';

                        if ($proxData) {

                            $proxClass = $proxData < $hoje ? 'danger' : ($proxData <= date('Y-m-d', strtotime('+30 days')) ? 'warning' : 'success');

                        }

                        ?>

                        <div class="fw-semibold text-<?= $proxClass ?>"><?= $proxData ? date('d/m/Y', strtotime($proxData)) : 'Sem previsão' ?></div>

                        <div class="small text-muted"><?= htmlspecialchars($proximoExame['exame_nome'] ?? '') ?></div>

                    <?php else: ?>

                        <div class="text-muted">Nenhum vínculo</div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

        <div class="col-6 col-md-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body py-2">

                    <div class="text-muted small">Afastamento</div>

                    <?php if ($afastAtivo): ?>

                        <div class="fw-semibold text-info"><?= htmlspecialchars($afastAtivo['tipo'] ?? 'Ativo') ?></div>

                        <div class="small">desde <?= !empty($afastAtivo['data_inicio']) ? date('d/m/Y', strtotime($afastAtivo['data_inicio'])) : '-' ?></div>

                    <?php else: ?>

                        <div class="text-success fw-semibold">Nenhum ativo</div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>



    <?php if (($resumo['pendencias_total'] ?? 0) > 0): ?>

        <div class="alert alert-<?= ($resumo['pendencias_criticas'] ?? 0) > 0 ? 'danger' : 'warning' ?> py-2 mb-3">

            <i class="fas fa-exclamation-triangle me-1"></i>

            <?= (int) ($resumo['pendencias_total'] ?? 0) ?> pendência(s) por vínculo

            <?php if (($resumo['pendencias_criticas'] ?? 0) > 0): ?>

                — <strong><?= (int) $resumo['pendencias_criticas'] ?> crítica(s)</strong>

            <?php endif; ?>

        </div>

    <?php endif; ?>



    <?php if (!empty($this->data['bloqueio_epi_treinamento_ativo']) && ($this->data['impedimentos_epi_treinamento'] ?? []) !== []): ?>
        <div class="alert alert-danger py-2 mb-3">
            <i class="fas fa-ban me-1"></i>
            <strong>Entrega de EPI bloqueada</strong> por treinamento SST:
            <?php foreach ($this->data['impedimentos_epi_treinamento'] as $imp): ?>
                <span class="badge bg-danger ms-1"><?= htmlspecialchars($imp['treinamento_nome'] ?? '') ?> — <?= htmlspecialchars($imp['situacao_label'] ?? '') ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php $gheAtivo = $this->data['ghe_ativo'] ?? null; ?>
    <?php if (!empty($gheAtivo)): ?>
        <div class="alert alert-info py-2 mb-3 d-flex flex-wrap align-items-center gap-2">
            <span><i class="fas fa-industry me-1"></i><strong>GHE:</strong> <?= htmlspecialchars($gheAtivo['nome'] ?? '') ?>
            <?php if (!empty($gheAtivo['ambiente_local'])): ?> — <?= htmlspecialchars($gheAtivo['ambiente_local']) ?><?php endif; ?>
            <?php if (!empty($gheAtivo['data_inicio'])): ?><small class="text-muted ms-1">(desde <?= date('d/m/Y', strtotime($gheAtivo['data_inicio'])) ?>)</small><?php endif; ?>
            </span>
            <?php if (in_array('SstViewGhe', $this->data['buttonPermission'] ?? [], true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-view-ghe/<?= (int)($gheAtivo['id'] ?? 0) ?>" class="btn btn-outline-primary btn-sm ms-auto">Ver GHE</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php include './app/adms/Views/partials/alerts.php'; ?>



    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">

        <li class="nav-item">

            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pend" data-adms-help-tab="aba-pendencias">

                Pendências <?php if ($pendTotal > 0): ?><span class="badge bg-danger"><?= $pendTotal ?></span><?php endif; ?>

            </button>

        </li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-riscos" data-adms-help-tab="aba-riscos">Riscos <?php if (count($riscos) > 0): ?><span class="badge bg-secondary"><?= count($riscos) ?></span><?php endif; ?></button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-obrig" data-adms-help-tab="aba-obrig">Obrigatoriedades</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-timeline" data-adms-help-tab="aba-timeline">Timeline</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-asos" data-adms-help-tab="aba-asos">ASOs</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-afast" data-adms-help-tab="aba-afastamentos">Afastamentos</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-epi" data-adms-help-tab="aba-epis">EPIs</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-acid" data-adms-help-tab="aba-acidentes">Acidentes</button></li>

        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ppp" data-adms-help-tab="aba-ppp">PPP <?php if (count($pppHistorico) > 0): ?><span class="badge bg-secondary"><?= count($pppHistorico) ?></span><?php endif; ?></button></li>

    </ul>



    <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-pend">

            <p class="text-muted small mb-3">Itens obrigatórios pelo cargo/departamento (vínculos) sem registro válido.</p>

            <?php if ($pendTotal === 0): ?>

                <div class="alert alert-success mb-0">Nenhuma pendência por vínculo para este colaborador.</div>

            <?php else: ?>

                <?php if (!empty($pend['epis'])): ?>

                    <h6 class="mt-2"><i class="fas fa-hard-hat me-1"></i> EPIs</h6>

                    <div class="d-none d-md-block table-responsive mb-3">

                        <table class="table table-sm table-bordered"><thead><tr><th>EPI</th><th>Situação</th><th>Última entrega</th><th>Prev. troca</th></tr></thead><tbody>

                            <?php foreach ($pend['epis'] as $r): ?><tr>

                                <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>

                                <td><span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span></td>

                                <td><?= !empty($r['ultima_entrega']) ? date('d/m/Y', strtotime($r['ultima_entrega'])) : '-' ?></td>

                                <td><?= !empty($r['data_prevista_troca']) ? date('d/m/Y', strtotime($r['data_prevista_troca'])) : '-' ?></td>

                            </tr><?php endforeach; ?>

                        </tbody></table>

                    </div>

                    <div class="d-block d-md-none mb-3">

                        <?php foreach ($pend['epis'] as $r): ?>

                            <div class="card mb-2 shadow-sm"><div class="card-body py-2">

                                <div class="fw-semibold"><?= htmlspecialchars($r['epi_nome'] ?? '') ?></div>

                                <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>

                            </div></div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <?php if (!empty($pend['exames'])): ?>

                    <h6 class="mt-2"><i class="fas fa-stethoscope me-1"></i> Exames / ASO</h6>

                    <div class="d-none d-md-block table-responsive">

                        <table class="table table-sm table-bordered"><thead><tr><th>Exame</th><th>Situação</th><th>Último ASO</th><th>Validade</th><th class="text-center">Ações</th></tr></thead><tbody>

                            <?php foreach ($pend['exames'] as $r): ?><tr>

                                <td><?= htmlspecialchars($r['exame_nome'] ?? '') ?></td>

                                <td><span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span></td>

                                <td><?= !empty($r['ultimo_aso']) ? date('d/m/Y', strtotime($r['ultimo_aso'])) : '-' ?></td>

                                <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>

                                <td class="text-center"><?php sstRenderAsoPendenciaActions($r, $perms, 'sst-employee-profile/' . $uid, $uid, $csrfAbrirAso); ?></td>

                            </tr><?php endforeach; ?>

                        </tbody></table>

                    </div>

                    <div class="d-block d-md-none">

                        <?php foreach ($pend['exames'] as $r): ?>

                            <div class="card mb-2 shadow-sm"><div class="card-body py-2">

                                <div class="fw-semibold"><?= htmlspecialchars($r['exame_nome'] ?? '') ?></div>

                                <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>

                                <div class="mt-1"><?php sstRenderAsoPendenciaActions($r, $perms, 'sst-employee-profile/' . $uid, $uid, $csrfAbrirAso); ?></div>

                            </div></div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

                <?php if (!empty($pend['treinamentos'])): ?>

                    <h6 class="mt-2"><i class="fas fa-graduation-cap me-1"></i> Treinamentos obrigatórios</h6>

                    <div class="d-none d-md-block table-responsive mb-3">

                        <table class="table table-sm table-bordered"><thead><tr><th>Treinamento</th><th>Situação</th><th>Status vínculo</th></tr></thead><tbody>

                            <?php foreach ($pend['treinamentos'] as $r): ?><tr>

                                <td><?= htmlspecialchars($r['treinamento_nome'] ?? '') ?></td>

                                <td><span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span></td>

                                <td><?= htmlspecialchars($r['treinamento_status'] ?? '-') ?></td>

                            </tr><?php endforeach; ?>

                        </tbody></table>

                    </div>

                    <div class="d-block d-md-none mb-3">

                        <?php foreach ($pend['treinamentos'] as $r): ?>

                            <div class="card mb-2 shadow-sm"><div class="card-body py-2">

                                <div class="fw-semibold"><?= htmlspecialchars($r['treinamento_nome'] ?? '') ?></div>

                                <span class="badge bg-<?= htmlspecialchars($r['situacao_badge'] ?? 'secondary') ?>"><?= htmlspecialchars($r['situacao_label'] ?? '') ?></span>

                            </div></div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>



        <div class="tab-pane fade" id="tab-riscos">

            <p class="text-muted small mb-3">Riscos ocupacionais vinculados ao cargo e departamento do colaborador.</p>

            <?php if (empty($riscos)): ?>

                <div class="alert alert-light border mb-0">Nenhum risco cadastrado para este cargo/departamento.</div>

            <?php else: ?>

                <div class="d-none d-md-block table-responsive">

                    <table class="table table-sm table-bordered table-hover">

                        <thead><tr><th>Risco</th><th>Tipo</th><th>Nível</th><th>Regra</th></tr></thead>

                        <tbody>

                            <?php foreach ($riscos as $r): ?>

                                <tr>

                                    <td><?= htmlspecialchars($r['risco_nome'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($r['risco_tipo'] ?? '-') ?></td>

                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['nivel'] ?? '-') ?></span></td>

                                    <td class="small text-muted">

                                        <?= htmlspecialchars($r['cargo_regra'] ?? 'Qualquer cargo') ?>

                                        / <?= htmlspecialchars($r['departamento_regra'] ?? 'Qualquer depto') ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <div class="d-block d-md-none">

                    <?php foreach ($riscos as $r): ?>

                        <div class="card mb-2 shadow-sm"><div class="card-body py-2">

                            <div class="fw-semibold"><?= htmlspecialchars($r['risco_nome'] ?? '') ?></div>

                            <span class="badge bg-secondary"><?= htmlspecialchars($r['nivel'] ?? '-') ?></span>

                            <span class="small text-muted ms-1"><?= htmlspecialchars($r['risco_tipo'] ?? '') ?></span>

                        </div></div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>



        <div class="tab-pane fade" id="tab-obrig">

            <p class="text-muted small mb-3">EPIs e exames obrigatórios definidos por vínculo de cargo/departamento.</p>

            <?php if (empty($obrig['epis']) && empty($obrig['exames'])): ?>

                <div class="alert alert-light border mb-0">Nenhuma obrigatoriedade cadastrada para este cargo/departamento.</div>

            <?php else: ?>

                <?php if (!empty($obrig['epis'])): ?>

                    <h6><i class="fas fa-hard-hat me-1"></i> EPIs obrigatórios</h6>

                    <div class="table-responsive mb-3">

                        <table class="table table-sm table-bordered">

                            <thead><tr><th>EPI</th><th>CA</th><th>Regra cargo/depto</th><th>Obs.</th></tr></thead>

                            <tbody>

                                <?php foreach ($obrig['epis'] as $r): ?>

                                    <tr>

                                        <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>

                                        <td><?= htmlspecialchars($r['ca_numero'] ?? '-') ?></td>

                                        <td class="small"><?= htmlspecialchars($r['cargo_regra'] ?? 'Qualquer') ?> / <?= htmlspecialchars($r['departamento_regra'] ?? 'Qualquer') ?></td>

                                        <td class="small"><?= htmlspecialchars($r['observacoes'] ?? '') ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

                <?php if (!empty($obrig['exames'])): ?>

                    <h6><i class="fas fa-stethoscope me-1"></i> Exames obrigatórios</h6>

                    <div class="table-responsive">

                        <table class="table table-sm table-bordered">

                            <thead><tr><th>Exame</th><th>Periodicidade (meses)</th><th>Regra cargo/depto</th><th>Obs.</th></tr></thead>

                            <tbody>

                                <?php foreach ($obrig['exames'] as $r): ?>

                                    <tr>

                                        <td><?= htmlspecialchars($r['exame_nome'] ?? '') ?></td>

                                        <td><?= (int) ($r['periodicidade_meses'] ?? $r['exame_periodicidade_padrao'] ?? 0) ?: '-' ?></td>

                                        <td class="small"><?= htmlspecialchars($r['cargo_regra'] ?? 'Qualquer') ?> / <?= htmlspecialchars($r['departamento_regra'] ?? 'Qualquer') ?></td>

                                        <td class="small"><?= htmlspecialchars($r['observacoes'] ?? '') ?></td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </div>



        <div class="tab-pane fade" id="tab-timeline">

            <p class="text-muted small mb-3">Histórico unificado de eventos SST do colaborador.</p>

            <?php if (empty($timeline)): ?>

                <div class="alert alert-light border mb-0">Nenhum evento registrado.</div>

            <?php else: ?>

                <div class="list-group list-group-flush">

                    <?php foreach ($timeline as $ev): ?>

                        <?php

                        $viewPerm = match ($ev['tipo'] ?? '') {

                            'aso' => 'SstViewAso',

                            'epi' => 'SstViewEpiFicha',

                            'afastamento' => 'SstViewAfastamento',

                            'acidente' => 'SstViewAcidente',

                            default => '',

                        };

                        $canView = $viewPerm && in_array($viewPerm, $perms, true) && !empty($ev['id']);

                        ?>

                        <div class="list-group-item px-0 border-start border-3 border-<?= htmlspecialchars($ev['cor'] ?? 'secondary') ?>">

                            <div class="d-flex gap-2 align-items-start">

                                <i class="fas <?= htmlspecialchars($ev['icone'] ?? 'fa-circle') ?> text-<?= htmlspecialchars($ev['cor'] ?? 'secondary') ?> mt-1"></i>

                                <div class="flex-grow-1">

                                    <div class="d-flex flex-wrap justify-content-between gap-1">

                                        <strong><?= htmlspecialchars($ev['titulo'] ?? '') ?></strong>

                                        <span class="text-muted small"><?= !empty($ev['data']) ? date('d/m/Y', strtotime($ev['data'])) : '-' ?></span>

                                    </div>

                                    <?php if (!empty($ev['detalhe'])): ?>

                                        <div class="small text-muted"><?= htmlspecialchars($ev['detalhe']) ?></div>

                                    <?php endif; ?>

                                    <?php if ($canView): ?>

                                        <a href="<?= $_ENV['URL_ADM'] . ($ev['url'] ?? '') . $ev['id'] ?>" class="btn btn-link btn-sm p-0">Ver detalhes</a>

                                    <?php endif; ?>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>



        <div class="tab-pane fade" id="tab-asos">

            <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered table-hover"><thead><tr><th>Exame</th><th>Tipo</th><th>Realização</th><th>Validade</th><th>Resultado</th><th></th></tr></thead><tbody>

                <?php foreach ($this->data['asos'] ?? [] as $r): ?><tr>

                    <td><?= htmlspecialchars($r['exame_nome'] ?? '-') ?></td>

                    <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>

                    <td><?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?></td>

                    <td><?= !empty($r['data_validade']) ? date('d/m/Y', strtotime($r['data_validade'])) : '-' ?></td>

                    <td><?= htmlspecialchars($r['resultado'] ?? '') ?></td>

                    <td><?php if (in_array('SstViewAso', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-aso/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a><?php endif; ?></td>

                </tr><?php endforeach; ?>

            </tbody></table></div>

            <div class="d-block d-md-none">

                <?php foreach ($this->data['asos'] ?? [] as $r): ?>

                    <div class="card mb-2 shadow-sm"><div class="card-body py-2 small">

                        <strong><?= htmlspecialchars($r['tipo'] ?? '') ?></strong> — <?= htmlspecialchars($r['resultado'] ?? '') ?><br>

                        <?= !empty($r['data_realizacao']) ? date('d/m/Y', strtotime($r['data_realizacao'])) : '-' ?>

                        <?php if (!empty($r['data_validade'])): ?> · val. <?= date('d/m/Y', strtotime($r['data_validade'])) ?><?php endif; ?>

                    </div></div>

                <?php endforeach; ?>

            </div>

        </div>



        <div class="tab-pane fade" id="tab-afast">

            <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tipo</th><th>Início</th><th>Fim</th><th>Status</th><th></th></tr></thead><tbody>

                <?php foreach ($this->data['afastamentos'] ?? [] as $r): ?><tr>

                    <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>

                    <td><?= !empty($r['data_inicio']) ? date('d/m/Y', strtotime($r['data_inicio'])) : '-' ?></td>

                    <td><?= !empty($r['data_fim']) ? date('d/m/Y', strtotime($r['data_fim'])) : '-' ?></td>

                    <td><?= htmlspecialchars($r['status'] ?? '') ?></td>

                    <td><?php if (in_array('SstViewAfastamento', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-afastamento/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a><?php endif; ?></td>

                </tr><?php endforeach; ?>

            </tbody></table></div>

            <div class="d-block d-md-none">

                <?php foreach ($this->data['afastamentos'] ?? [] as $r): ?>

                    <div class="card mb-2 shadow-sm"><div class="card-body py-2 small">

                        <?= htmlspecialchars($r['tipo'] ?? '') ?> — <?= htmlspecialchars($r['status'] ?? '') ?>

                    </div></div>

                <?php endforeach; ?>

            </div>

        </div>



        <div class="tab-pane fade" id="tab-epi">

            <?php if (in_array('SstListEpiFichas', $perms, true)): ?>
            <h6 class="mt-2"><i class="fas fa-file-signature me-1"></i> Fichas de entrega</h6>
            <div class="table-responsive mb-3"><table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Data</th><th>Itens</th><th>Assinatura</th><th></th></tr></thead><tbody>
                <?php foreach ($this->data['epi_fichas'] ?? [] as $f): ?><tr>
                    <td><?= (int)$f['id'] ?></td>
                    <td><?= !empty($f['data_entrega']) ? date('d/m/Y', strtotime($f['data_entrega'])) : '-' ?></td>
                    <td><?= (int)($f['total_itens'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($f['status_assinatura'] ?? '') ?></td>
                    <td><?php if (in_array('SstViewEpiFicha', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-epi-ficha/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a><?php endif; ?></td>
                </tr><?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>

            <h6><i class="fas fa-hard-hat me-1"></i> Todos os EPIs entregues</h6>
            <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Data</th><th>EPI</th><th>CA</th><th>Qtde</th><th>Prev. troca</th></tr></thead><tbody>

                <?php foreach ($this->data['epis_entregues_consolidado'] ?? [] as $r): ?><tr>

                    <td><?= !empty($r['data_entrega']) ? date('d/m/Y', strtotime($r['data_entrega'])) : '-' ?></td>

                    <td><?= htmlspecialchars($r['epi_nome'] ?? '') ?></td>

                    <td><?= htmlspecialchars($r['ca'] ?? '-') ?></td>

                    <td><?= (int)($r['quantidade'] ?? 0) ?></td>

                    <td><?= !empty($r['data_prevista_troca']) ? date('d/m/Y', strtotime($r['data_prevista_troca'])) : '-' ?></td>

                </tr><?php endforeach; ?>

            </tbody></table></div>

            <div class="d-block d-md-none">

                <?php foreach ($this->data['epis_entregues_consolidado'] ?? [] as $r): ?>

                    <div class="card mb-2 shadow-sm"><div class="card-body py-2 small">

                        <?= htmlspecialchars($r['epi_nome'] ?? '') ?> — <?= !empty($r['data_entrega']) ? date('d/m/Y', strtotime($r['data_entrega'])) : '-' ?>

                    </div></div>

                <?php endforeach; ?>

            </div>

        </div>



        <div class="tab-pane fade" id="tab-acid">

            <div class="d-none d-md-block table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Tipo</th><th>Data</th><th>Status</th><th></th></tr></thead><tbody>

                <?php foreach ($this->data['acidentes'] ?? [] as $r): ?><tr>

                    <td><?= htmlspecialchars($r['tipo'] ?? '') ?></td>

                    <td><?= !empty($r['data_ocorrencia']) ? date('d/m/Y H:i', strtotime($r['data_ocorrencia'])) : '-' ?></td>

                    <td><?= htmlspecialchars($r['status'] ?? '') ?></td>

                    <td><?php if (in_array('SstViewAcidente', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-acidente/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a><?php endif; ?></td>

                </tr><?php endforeach; ?>

            </tbody></table></div>

        </div>

        <div class="tab-pane fade" id="tab-ppp">

            <p class="text-muted small">Histórico de PPP gerados automaticamente a partir dos registros SST.</p>

            <?php if (in_array('SstGeneratePpp', $perms, true)): ?>

                <form action="<?= $_ENV['URL_ADM']; ?>sst-generate-ppp" method="POST" class="mb-3" onsubmit="return confirm('Gerar nova versão do PPP?');">
                    <input type="hidden" name="csrf_token" value="<?= $csrfPpp ?>">
                    <input type="hidden" name="adms_user_id" value="<?= $uid ?>">
                    <input type="hidden" name="redirect" value="<?= $_ENV['URL_ADM']; ?>sst-employee-profile/<?= $uid ?>#tab-ppp">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-8"><label class="form-label small">Observações (opcional)</label><input type="text" name="observacoes" class="form-control form-control-sm" placeholder="Complementos para esta versão"></div>
                        <div class="col-md-4"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-file-alt"></i> Gerar nova versão</button></div>
                    </div>
                </form>

            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Versão</th><th>Gerado em</th><th>Por</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($pppHistorico as $p): ?>
                        <tr>
                            <td>v<?= (int)($p['versao'] ?? 1) ?></td>
                            <td><?= !empty($p['created_at']) ? date('d/m/Y H:i', strtotime($p['created_at'])) : '-' ?></td>
                            <td><?= htmlspecialchars($p['gerado_por_nome'] ?? '-') ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewPpp', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-ppp/<?= (int)$p['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstExportPppPdf', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-export-ppp-pdf/<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i></a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($pppHistorico)): ?><tr><td colspan="4" class="text-muted">Nenhum PPP gerado ainda.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (in_array('SstListPpp', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-list-ppp" class="btn btn-outline-secondary btn-sm">Ver todos os PPPs</a><?php endif; ?>

        </div>

    </div>



    <a href="<?= $_ENV['URL_ADM']; ?>view-user/<?= $uid ?>" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left"></i> Voltar ao colaborador</a>

</div>

