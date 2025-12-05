<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
$feedback = $this->data['feedback'] ?? [];
$typeLabels = [
    'general' => ['label' => 'Geral', 'color' => 'secondary', 'icon' => 'comment'],
    'performance' => ['label' => 'Desempenho', 'color' => 'info', 'icon' => 'chart-line'],
    'recognition' => ['label' => 'Reconhecimento', 'color' => 'success', 'icon' => 'star'],
    'improvement' => ['label' => 'Melhoria', 'color' => 'warning', 'icon' => 'lightbulb']
];
$typeInfo = $typeLabels[$feedback['feedback_type']] ?? ['label' => $feedback['feedback_type'], 'color' => 'secondary', 'icon' => 'comment'];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Visualizar Feedback de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-feedbacks" class="text-decoration-none">Feedbacks</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center bg-<?= $typeInfo['color'] ?> text-white">
            <span>
                <i class="fas fa-<?= $typeInfo['icon'] ?> me-2"></i>
                <strong><?= $typeInfo['label'] ?></strong>
            </span>
            <div>
                <?php if (in_array('ListPerformanceFeedbacks', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-feedbacks" class="btn btn-sm btn-light">
                        <i class="fas fa-list me-1"></i>Listar
                    </a>
                <?php } ?>
                <?php if (in_array('UpdatePerformanceFeedback', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-feedback/<?= $feedback['id'] ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h6 class="text-muted">Texto do Feedback</h6>
                        <div class="p-3 bg-light rounded">
                            <?= nl2br(htmlspecialchars($feedback['feedback_text'] ?? '')) ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted">Para</h6>
                                <p class="mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    <strong><?= htmlspecialchars($feedback['employee_name'] ?? '') ?></strong>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-muted">De</h6>
                                <p class="mb-0">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <?php if ($feedback['is_anonymous']): ?>
                                        <em>Anônimo</em>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($feedback['given_by_name'] ?? '') ?></strong>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-muted">Tipo</h6>
                                <p class="mb-0">
                                    <span class="badge bg-<?= $typeInfo['color'] ?>">
                                        <i class="fas fa-<?= $typeInfo['icon'] ?> me-1"></i>
                                        <?= $typeInfo['label'] ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-muted">Opções</h6>
                                <div>
                                    <?php if ($feedback['is_public']): ?>
                                        <span class="badge bg-info mb-1">Público</span>
                                    <?php endif; ?>
                                    <?php if ($feedback['is_anonymous']): ?>
                                        <span class="badge bg-secondary mb-1">Anônimo</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-muted">Data</h6>
                                <p class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

