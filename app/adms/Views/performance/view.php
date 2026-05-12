<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Avaliação de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="text-decoration-none">Avaliações</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-clipboard-check me-2"></i>Avaliação #<?= $this->data['review']['id'] ?></span>
            <span class="ms-auto">
                <?php if (in_array('RecordReviewResults', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>record-review-results/<?= $this->data['review']['id'] ?>" 
                       class="btn btn-sm btn-success">
                        <i class="fas fa-clipboard-list me-1"></i>Registrar Resultados
                    </a>
                <?php } ?>
                <?php if (in_array('UpdatePerformanceReview', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-review/<?= $this->data['review']['id'] ?>" 
                       class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
                <?php if (in_array('ListPerformanceReviews', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-reviews" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Voltar
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Colaborador:</strong></td>
                            <td><?= htmlspecialchars($this->data['review']['employee_name'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Avaliador:</strong></td>
                            <td><?= htmlspecialchars($this->data['review']['reviewer_name'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($this->data['review']['review_type']) ?>°</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Período:</strong></td>
                            <td>
                                <?= FormatHelper::formatDate($this->data['review']['review_period_start'] ?? '') ?> a 
                                <?= FormatHelper::formatDate($this->data['review']['review_period_end'] ?? '') ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Data da Avaliação:</strong></td>
                            <td><?= FormatHelper::formatDate($this->data['review']['review_date'] ?? '') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                <?php
                                $statusClass = match($this->data['review']['status']) {
                                    'draft' => 'secondary',
                                    'in_progress' => 'warning',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                                $statusLabel = match($this->data['review']['status']) {
                                    'draft' => 'Rascunho',
                                    'in_progress' => 'Em Andamento',
                                    'completed' => 'Concluída',
                                    'cancelled' => 'Cancelada',
                                    default => $this->data['review']['status']
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Nota Geral:</strong></td>
                            <td>
                                <?php if ($this->data['review']['overall_score']): ?>
                                    <span class="badge bg-primary fs-6"><?= number_format((float)$this->data['review']['overall_score'], 1) ?>/10</span>
                                <?php else: ?>
                                    <span class="text-muted">Não avaliado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if (!empty($this->data['review']['strengths'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-thumbs-up text-success me-2"></i>Pontos Fortes</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['review']['strengths'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['review']['improvements'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-arrow-up text-warning me-2"></i>Pontos de Melhoria</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['review']['improvements'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['review']['comments'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-comment me-2"></i>Comentários Gerais</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['review']['comments'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['competencies'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-star me-2"></i>Competências Avaliadas</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Competência</th>
                                    <th>Tipo</th>
                                    <th>Nível Atual</th>
                                    <th>Nível Alvo</th>
                                    <th>Nível Avaliado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['competencies'] as $comp): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($comp['competency_name'] ?? '') ?></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($comp['competency_type'] ?? '') ?></span></td>
                                        <td><?= $comp['current_level'] ?? '-' ?></td>
                                        <td><?= $comp['target_level'] ?? '-' ?></td>
                                        <td>
                                            <?php if ($comp['assessed_level']): ?>
                                                <span class="badge bg-primary"><?= $comp['assessed_level'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($this->data['goals'])): ?>
                <div class="mb-3">
                    <h5><i class="fas fa-bullseye me-2"></i>Metas</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Meta</th>
                                    <th>Tipo</th>
                                    <th>Valor Atual</th>
                                    <th>Valor Alvo</th>
                                    <th>Prazo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['goals'] as $goal): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($goal['goal_title'] ?? '') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($goal['goal_type'] ?? '') ?></span></td>
                                        <td><?= $goal['current_value'] ?? '0' ?></td>
                                        <td><?= $goal['target_value'] ?? '-' ?></td>
                                        <td><?= FormatHelper::formatDate($goal['deadline'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $goalStatusClass = match($goal['status']) {
                                                'pending' => 'secondary',
                                                'in_progress' => 'warning',
                                                'achieved' => 'success',
                                                'failed' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $goalStatusClass ?>"><?= htmlspecialchars($goal['status'] ?? '') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

