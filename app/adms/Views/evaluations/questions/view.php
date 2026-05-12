<?php
$q = $this->data['question'] ?? [];
$model = $this->data['model'] ?? [];
$tipoLegivel = $this->data['tipo_legivel'] ?? ($q['tipo'] ?? '');
$urlAdm = $_ENV['URL_ADM'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Pergunta de Avaliação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>list-evaluation-questions" class="text-decoration-none">Perguntas</a>
            </li>
            <li class="breadcrumb-item active">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-question-circle me-2"></i>Pergunta #<?= (int)($q['id'] ?? 0) ?></span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('ListEvaluationQuestions', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>list-evaluation-questions" class="btn btn-secondary btn-sm mb-1">
                        <i class="fas fa-list me-1"></i>Listar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdateEvaluationQuestion', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>update-evaluation-question/<?= (int)($q['id'] ?? 0) ?>" class="btn btn-warning btn-sm mb-1">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm mb-1';
                include __DIR__ . '/../../partials/button_log_alteracoes.php';
                ?>
                <?php if (in_array('DeleteEvaluationQuestion', $this->data['buttonPermission'] ?? [], true)) { ?>
                    <a href="<?= htmlspecialchars($urlAdm, ENT_QUOTES, 'UTF-8') ?>delete-evaluation-question/<?= (int)($q['id'] ?? 0) ?>"
                       class="btn btn-danger btn-sm mb-1"
                       onclick="return confirm('Tem certeza que deseja excluir esta pergunta?');">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if (!empty($model['titulo'])): ?>
                <p class="text-muted mb-3"><strong>Modelo:</strong> <?= htmlspecialchars((string) $model['titulo'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <dl class="row">
                <dt class="col-sm-3">Ordem</dt>
                <dd class="col-sm-9"><?= (int)($q['ordem'] ?? 0) ?></dd>
                <dt class="col-sm-3">Tipo</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) $tipoLegivel, ENT_QUOTES, 'UTF-8') ?></dd>
                <dt class="col-sm-3">Pontos</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string)($q['pontos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
                <dt class="col-sm-3">Pergunta</dt>
                <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)($q['pergunta'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></dd>
                <?php if (!empty($this->data['opcoes_array']) && is_array($this->data['opcoes_array'])): ?>
                    <dt class="col-sm-3">Opções</dt>
                    <dd class="col-sm-9">
                        <ul class="mb-0">
                            <?php foreach ($this->data['opcoes_array'] as $opt): ?>
                                <li><?= htmlspecialchars(trim((string) $opt), ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div>
