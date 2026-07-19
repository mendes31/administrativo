<?php
use App\adms\Helpers\FormatHelper;
$e = $this->data['entrevista'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Detalhes da Entrevista</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="text-decoration-none">Entrevistas</a></li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-calendar-alt me-2"></i>Entrevista #<?= (int)($e['id'] ?? 0) ?></span>
            <div class="btn-group">
                <?php if (!empty($this->data['buttonPermission']['RhEntrevistasEdit'])): ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-edit/<?= (int)$e['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-edit me-1"></i>Editar
                </a>
                <?php endif; ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Voltar
                </a>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../../partials/button_log_alteracoes.php';
                ?>
            </div>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <dl class="row mb-0">
                <dt class="col-sm-3">Candidato</dt>
                <dd class="col-sm-9">
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)($e['rh_candidato_id'] ?? 0) ?>">
                        <?= htmlspecialchars($e['candidato_nome'] ?? '-') ?>
                    </a>
                    <?php if (!empty($e['candidato_email'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($e['candidato_email']) ?></small>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-3">Vaga</dt>
                <dd class="col-sm-9">
                    <?php if (!empty($e['rh_vaga_id'])): ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= (int)$e['rh_vaga_id'] ?>">
                            <?= htmlspecialchars($e['vaga_titulo'] ?? '-') ?>
                        </a>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9"><?= htmlspecialchars(ucfirst($e['tipo'] ?? '-')) ?></dd>

                <dt class="col-sm-3">Data e Hora</dt>
                <dd class="col-sm-9"><?= FormatHelper::formatDateTime($e['data_hora'] ?? '') ?></dd>

                <dt class="col-sm-3">Entrevistador</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($e['entrevistador_nome'] ?? '-') ?></dd>

                <dt class="col-sm-3">Local</dt>
                <dd class="col-sm-9"><?= htmlspecialchars($e['local'] ?? '-') ?></dd>

                <dt class="col-sm-3">Resultado</dt>
                <dd class="col-sm-9">
                    <?php
                    $res = $e['resultado'] ?? '';
                    $resClass = match($res) {
                        'aprovado' => 'badge bg-success',
                        'reprovado' => 'badge bg-danger',
                        'agendado' => 'badge bg-info',
                        'pendente' => 'badge bg-warning text-dark',
                        default => 'badge bg-secondary',
                    };
                    ?>
                    <span class="<?= $resClass ?>"><?= $res ? htmlspecialchars(ucfirst($res)) : '—' ?></span>
                </dd>

                <?php if (!empty($e['observacoes'])): ?>
                <dt class="col-sm-3">Observações</dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars($e['observacoes'])) ?></dd>
                <?php endif; ?>

                <?php if (!empty($e['feedback'])): ?>
                <dt class="col-sm-3">Feedback</dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars($e['feedback'])) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <?php $painel = $this->data['painel_avaliadores'] ?? []; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-users me-2"></i>Painel de avaliadores</div>
        <div class="card-body">
            <?php if (empty($painel)): ?>
                <p class="text-muted mb-0">Nenhum avaliador no painel. Defina o entrevistador principal e adicionais na edição.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Avaliador</th>
                                <th>Papel</th>
                                <th>Status</th>
                                <th>Scorecard</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($painel as $av): ?>
                                <?php
                                $scStatus = $av['scorecard_status'] ?? null;
                                if ($scStatus === null || $scStatus === '') {
                                    $scLabel = 'ausente';
                                    $scClass = 'bg-light text-dark';
                                } elseif ($scStatus === 'finalizado') {
                                    $scLabel = 'finalizado';
                                    $scClass = 'bg-success';
                                } else {
                                    $scLabel = 'rascunho';
                                    $scClass = 'bg-secondary';
                                }
                                ?>
                                <tr class="<?= ($av['status'] ?? '') === 'removido' ? 'text-muted' : '' ?>">
                                    <td><?= htmlspecialchars($av['avaliador_nome'] ?? ('#' . (int) ($av['avaliador_id'] ?? 0))) ?></td>
                                    <td><?= htmlspecialchars($av['papel'] ?? 'avaliador') ?></td>
                                    <td><?= htmlspecialchars($av['status'] ?? 'ativo') ?></td>
                                    <td>
                                        <span class="badge <?= $scClass ?>"><?= htmlspecialchars($scLabel) ?></span>
                                        <?php if (isset($av['scorecard_nota']) && $av['scorecard_nota'] !== null && $av['scorecard_nota'] !== ''): ?>
                                            <span class="small text-muted ms-1"><?= number_format((float) $av['scorecard_nota'], 2, ',', '.') ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">A designação no painel não concede acesso automático à entrevista.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php $scorecards = $this->data['scorecards'] ?? []; ?>
    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-clipboard-check me-2"></i>Scorecards</div>
        <div class="card-body">
            <?php if (empty($scorecards)): ?>
                <p class="text-muted mb-0">Nenhum scorecard registrado ainda. Edite a entrevista para lançar sua avaliação.</p>
            <?php else: ?>
                <?php foreach ($scorecards as $sc): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                            <strong><?= htmlspecialchars($sc['avaliador_nome'] ?? ('Avaliador #' . (int) ($sc['avaliador_id'] ?? 0))) ?></strong>
                            <span class="badge bg-<?= ($sc['status'] ?? '') === 'finalizado' ? 'success' : 'secondary' ?>">
                                <?= htmlspecialchars($sc['status'] ?? 'rascunho') ?>
                            </span>
                            <?php if ($sc['nota_ponderada'] !== null && $sc['nota_ponderada'] !== ''): ?>
                                <span class="text-muted">Nota ponderada: <strong><?= number_format((float) $sc['nota_ponderada'], 2, ',', '.') ?></strong></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($sc['parecer'])): ?>
                            <p class="mb-2"><?= nl2br(htmlspecialchars((string) $sc['parecer'])) ?></p>
                        <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Critério</th>
                                        <th>Peso</th>
                                        <th>Nota</th>
                                        <th>Comentário</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (($sc['itens'] ?? []) as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) ($item['criterio_label'] ?? $item['criterio_codigo'] ?? '')) ?></td>
                                            <td><?= (int) ($item['peso'] ?? 0) ?></td>
                                            <td><?= $item['nota'] !== null && $item['nota'] !== '' ? (int) $item['nota'] : '—' ?></td>
                                            <td><?= htmlspecialchars((string) ($item['comentario'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
