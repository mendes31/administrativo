<?php
$plan = $this->data['plan'] ?? [];
// Cabeçalho já incluso pelo controller
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Plano Estratégico</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="text-decoration-none">Planos Estratégicos</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">
            <span><i class="fas fa-eye me-2"></i>Visualizar Plano Estratégico</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <h4 class="text-primary"><?= htmlspecialchars($plan['title'] ?? 'Sem título') ?></h4>
                    <hr>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Departamento:</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($plan['dep_name'] ?? 'N/A') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Responsável:</label>
                    <p class="form-control-plaintext"><?= htmlspecialchars($plan['user_name'] ?? 'N/A') ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Data de Início:</label>
                    <p class="form-control-plaintext"><?= $plan['start_date'] ? date('d/m/Y', strtotime($plan['start_date'])) : 'N/A' ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Data de Término:</label>
                    <p class="form-control-plaintext"><?= $plan['end_date'] ? date('d/m/Y', strtotime($plan['end_date'])) : 'N/A' ?></p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Status:</label>
                    <p class="form-control-plaintext">
                        <?php
                        $status = $plan['status'] ?? 'N/A';
                        $statusClass = match($status) {
                            'Não iniciado' => 'badge bg-secondary',
                            'Em andamento' => 'badge bg-warning',
                            'Concluído' => 'badge bg-success',
                            'Atrasado' => 'badge bg-danger',
                            default => 'badge bg-secondary'
                        };
                        ?>
                        <span class="<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                    </p>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">Percentual de Conclusão:</label>
                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" style="width: <?= $plan['completed'] ?? 0 ?>%" 
                             aria-valuenow="<?= $plan['completed'] ?? 0 ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $plan['completed'] ?? 0 ?>%
                        </div>
                    </div>
                </div>

                <!-- 5W2H -->
                <div class="col-12">
                    <h5 class="text-primary mt-4 mb-3">Metodologia 5W2H</h5>
                    <hr>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">O QUE (What):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['what'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">POR QUE (Why):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['why'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">ONDE (Where):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['where_field'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">QUEM (Who):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['who_field'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">COMO (How):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['how'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-bold">QUANTO (How Much):</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['how_much'] ?? 'Não informado')) ?>
                    </div>
                </div>

                <?php if (!empty($plan['comment'])): ?>
                <div class="col-12">
                    <label class="form-label fw-bold">Comentários:</label>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($plan['comment'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-12">
                    <label class="form-label fw-bold">Criado em:</label>
                    <p class="form-control-plaintext"><?= $plan['created_at'] ? date('d/m/Y H:i:s', strtotime($plan['created_at'])) : 'N/A' ?></p>
                </div>

                <?php if (!empty($plan['updated_at'])): ?>
                <div class="col-12">
                    <label class="form-label fw-bold">Última atualização:</label>
                    <p class="form-control-plaintext"><?= date('d/m/Y H:i:s', strtotime($plan['updated_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?php echo $_ENV['URL_ADM']; ?>edit-strategic-plan/<?= $plan['id'] ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <?php
                        $log_resumo = $this->data['log_resumo'] ?? [];
                        $log_btn_class = 'btn btn-outline-info';
                        include __DIR__ . '/../partials/button_log_alteracoes.php';
                        ?>
                        <a href="<?php echo $_ENV['URL_ADM']; ?>list-strategic-plans" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



