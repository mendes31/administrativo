<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Feedbacks de Desempenho</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Feedbacks</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-comments me-2"></i>Feedbacks de Desempenho</span>
            <div>
                <?php if (in_array('CreatePerformanceFeedback', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-performance-feedback" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Novo Feedback
                    </a>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>list-performance-feedbacks" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="employee_id" class="form-label small">Colaborador</label>
                    <select name="employee_id" id="employee_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['employees'] ?? [] as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($this->data['filters']['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="feedback_type" class="form-label small">Tipo</label>
                    <select name="feedback_type" id="feedback_type" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="general" <?= ($this->data['filters']['feedback_type'] ?? '') == 'general' ? 'selected' : '' ?>>Geral</option>
                        <option value="performance" <?= ($this->data['filters']['feedback_type'] ?? '') == 'performance' ? 'selected' : '' ?>>Desempenho</option>
                        <option value="recognition" <?= ($this->data['filters']['feedback_type'] ?? '') == 'recognition' ? 'selected' : '' ?>>Reconhecimento</option>
                        <option value="improvement" <?= ($this->data['filters']['feedback_type'] ?? '') == 'improvement' ? 'selected' : '' ?>>Melhoria</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label small">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" 
                           placeholder="Texto do feedback..." value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-performance-feedbacks?limpar=1" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>

            <!-- Lista de Feedbacks -->
            <?php if (empty($this->data['feedbacks'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum feedback encontrado.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($this->data['feedbacks'] as $feedback): 
                        $typeLabels = [
                            'general' => ['label' => 'Geral', 'color' => 'secondary', 'icon' => 'comment'],
                            'performance' => ['label' => 'Desempenho', 'color' => 'info', 'icon' => 'chart-line'],
                            'recognition' => ['label' => 'Reconhecimento', 'color' => 'success', 'icon' => 'star'],
                            'improvement' => ['label' => 'Melhoria', 'color' => 'warning', 'icon' => 'lightbulb']
                        ];
                        $typeInfo = $typeLabels[$feedback['feedback_type']] ?? ['label' => $feedback['feedback_type'], 'color' => 'secondary', 'icon' => 'comment'];
                    ?>
                        <div class="col-md-6">
                            <div class="card h-100 border-<?= $typeInfo['color'] ?>">
                                <div class="card-header bg-<?= $typeInfo['color'] ?> text-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-<?= $typeInfo['icon'] ?> me-2"></i>
                                        <strong><?= $typeInfo['label'] ?></strong>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (in_array('ViewPerformanceFeedback', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>view-performance-feedback/<?= $feedback['id'] ?>" 
                                               class="btn btn-light btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array('UpdatePerformanceFeedback', $this->data['buttonPermission'] ?? [])) { ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>update-performance-feedback/<?= $feedback['id'] ?>" 
                                               class="btn btn-light btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <strong>Para:</strong> <?= htmlspecialchars($feedback['employee_name'] ?? '') ?>
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-user-tie me-1"></i>
                                            <strong>De:</strong>
                                            <?= htmlspecialchars($feedback['author_display'] ?? ($feedback['given_by_name'] ?? '—')) ?>
                                        </small>
                                    </div>
                                    <p class="card-text">
                                        <?= nl2br(htmlspecialchars(mb_substr($feedback['feedback_text'], 0, 150))) ?>
                                        <?= mb_strlen($feedback['feedback_text']) > 150 ? '...' : '' ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?>
                                        </small>
                                        <div>
                                            <?php if ($feedback['is_public']): ?>
                                                <span class="badge bg-info">Público</span>
                                            <?php endif; ?>
                                            <?php if ($feedback['is_anonymous']): ?>
                                                <span class="badge bg-secondary">Anônimo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação -->
                <?php if (!empty($this->data['pagination'])): ?>
                    <div class="mt-3">
                        <?= $this->data['pagination'] ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

