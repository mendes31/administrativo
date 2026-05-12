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
</div>
