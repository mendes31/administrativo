<!-- CSS Moderno para Formulários de Avaliação -->
<link rel="stylesheet" href="<?= $_ENV['URL_ADM'] ?>public/adms/css/evaluation-forms-modern.css">

<?php
$pendentes = $this->data['pendentes'] ?? [];
$concluidas = $this->data['concluidas'] ?? [];
$canceladas = $this->data['canceladas'] ?? [];

function getStatusColor($status) {
    return match($status) {
        'pendente' => 'warning',
        'em_andamento' => 'info',
        'aprovado' => 'success',
        'reprovado' => 'danger',
        'concluido' => 'secondary',
        'cancelado' => 'dark',
        default => 'secondary'
    };
}

function getStatusLabel($status) {
    return match($status) {
        'pendente' => 'Pendente',
        'em_andamento' => 'Em Andamento',
        'aprovado' => 'Aprovado',
        'reprovado' => 'Reprovado - Refazer',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
        default => $status
    };
}

function podeResponder($avaliacao) {
    if ($avaliacao['status'] === 'pendente' || $avaliacao['status'] === 'em_andamento') {
        return true;
    }
    if ($avaliacao['status'] === 'reprovado' && $avaliacao['permitir_refazer']) {
        if ($avaliacao['max_tentativas'] === null) return true;
        return $avaliacao['tentativas'] < $avaliacao['max_tentativas'];
    }
    return false;
}
?>

<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-clipboard-list"></i> Minhas Avaliações
        </h2>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-pendentes">
                <i class="fas fa-clock"></i> Pendentes
                <?php if (count($pendentes) > 0): ?>
                    <span class="badge bg-danger"><?= count($pendentes) ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-concluidas">
                <i class="fas fa-check-circle"></i> Concluídas
                <span class="badge bg-secondary"><?= count($concluidas) ?></span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-canceladas">
                <i class="fas fa-ban"></i> Canceladas
                <span class="badge bg-secondary"><?= count($canceladas) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB: PENDENTES -->
        <div class="tab-pane fade show active" id="tab-pendentes">
            <?php if (empty($pendentes)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Você não possui avaliações pendentes no momento.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($pendentes as $avaliacao): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-<?= getStatusColor($avaliacao['status']) ?> shadow-sm">
                                <div class="card-header bg-<?= getStatusColor($avaliacao['status']) ?> text-white">
                                    <h6 class="mb-0"><?= htmlspecialchars($avaliacao['titulo']) ?></h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($avaliacao['descricao'])): ?>
                                        <p class="card-text small"><?= htmlspecialchars(substr($avaliacao['descricao'], 0, 100)) ?>...</p>
                                    <?php endif; ?>
                                    
                                    <ul class="list-unstyled small mb-0">
                                        <li><i class="fas fa-book"></i> <?= htmlspecialchars($avaliacao['training_name']) ?></li>
                                        <li><i class="fas fa-list-ol"></i> <?= $avaliacao['total_questoes'] ?> questões</li>
                                        <li><i class="fas fa-trophy"></i> Nota mín: <?= $avaliacao['nota_minima_aprovacao'] ?></li>
                                        
                                        <?php if ($avaliacao['data_limite']): ?>
                                            <?php
                                            $hoje = new DateTime();
                                            $prazo = new DateTime($avaliacao['data_limite']);
                                            $vencido = $prazo < $hoje;
                                            $diff = $hoje->diff($prazo);
                                            ?>
                                            <li>
                                                <i class="fas fa-calendar"></i> 
                                                <span class="badge bg-<?= $vencido ? 'danger' : 'warning' ?>">
                                                    <?= date('d/m/Y', strtotime($avaliacao['data_limite'])) ?>
                                                    <?php if (!$vencido): ?>
                                                        (<?= $diff->days ?> dias)
                                                    <?php else: ?>
                                                        (VENCIDO)
                                                    <?php endif; ?>
                                                </span>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($avaliacao['tentativas'] > 0): ?>
                                            <li><i class="fas fa-redo"></i> Tentativas: <?= $avaliacao['tentativas'] ?>
                                                <?php if ($avaliacao['max_tentativas']): ?>
                                                    / <?= $avaliacao['max_tentativas'] ?>
                                                <?php endif; ?>
                                            </li>
                                            <li><i class="fas fa-star"></i> Melhor nota: 
                                                <strong class="text-<?= $avaliacao['nota_maxima'] >= $avaliacao['nota_minima_aprovacao'] ? 'success' : 'danger' ?>">
                                                    <?= number_format($avaliacao['nota_maxima'], 2) ?>
                                                </strong>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="card-footer bg-light">
                                    <?php if (podeResponder($avaliacao)): ?>
                                        <a href="<?= $_ENV['URL_ADM'] ?>answer-evaluation/<?= $avaliacao['id'] ?>" 
                                           class="btn btn-primary btn-sm w-100 mb-2">
                                            <i class="fas fa-edit"></i> 
                                            <?= $avaliacao['tentativas'] > 0 ? 'Refazer' : 'Responder' ?> Avaliação
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($avaliacao['tentativas'] > 0): ?>
                                        <a href="<?= $_ENV['URL_ADM'] ?>evaluation-history/<?= $avaliacao['id'] ?>" 
                                           class="btn btn-info btn-sm w-100">
                                            <i class="fas fa-history"></i> Ver Histórico
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: CONCLUÍDAS -->
        <div class="tab-pane fade" id="tab-concluidas">
            <?php if (empty($concluidas)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Você ainda não concluiu nenhuma avaliação.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($concluidas as $avaliacao): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-success shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-check-circle"></i> 
                                        <?= htmlspecialchars($avaliacao['titulo']) ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled small mb-0">
                                        <li><i class="fas fa-book"></i> <?= htmlspecialchars($avaliacao['training_name']) ?></li>
                                        <li><i class="fas fa-star"></i> Nota: 
                                            <strong class="text-success"><?= number_format($avaliacao['nota_maxima'], 2) ?></strong>
                                        </li>
                                        <li><i class="fas fa-redo"></i> Tentativas: <?= $avaliacao['tentativas'] ?></li>
                                    </ul>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="<?= $_ENV['URL_ADM'] ?>evaluation-history/<?= $avaliacao['id'] ?>" 
                                       class="btn btn-info btn-sm w-100">
                                        <i class="fas fa-history"></i> Ver Histórico
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: CANCELADAS -->
        <div class="tab-pane fade" id="tab-canceladas">
            <?php if (empty($canceladas)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Nenhuma avaliação cancelada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Questionário</th>
                                <th>Treinamento</th>
                                <th>Tentativas</th>
                                <th>Cancelado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($canceladas as $avaliacao): ?>
                                <tr>
                                    <td><?= htmlspecialchars($avaliacao['titulo']) ?></td>
                                    <td><?= htmlspecialchars($avaliacao['training_name']) ?></td>
                                    <td><?= $avaliacao['tentativas'] ?></td>
                                    <td><?= $avaliacao['cancelado_em'] ? date('d/m/Y', strtotime($avaliacao['cancelado_em'])) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 