<?php
use App\adms\Helpers\CSRFHelper;

$dashboards = $this->data['dashboards'] ?? [];
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mt-3">
            <i class="fas fa-chart-pie text-primary"></i> Meus Dashboards
        </h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Meus Dashboards</li>
            </ol>
        </nav>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> Dashboards Disponíveis</h5>
            <div class="btn-group">
                <a href="<?= $_ENV['URL_ADM'] ?>create-dashboard" class="btn btn-light btn-sm">
                    <i class="fas fa-plus-circle"></i> Criar Dashboard
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>list-dynamic-reports" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-plus"></i> Criar a partir de Relatório
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($dashboards)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Nenhum dashboard encontrado. 
                    Você pode <a href="<?= $_ENV['URL_ADM'] ?>create-dashboard" class="alert-link">criar um dashboard em branco</a>
                    e depois escolher se quer usar relatórios ou planilhas como fonte de dados.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($dashboards as $dashboard): ?>
                        <?php
                            $dataSourceType = $dashboard['data_source_type'] ?? 'report';
                            $isSpreadsheet = $dataSourceType === 'spreadsheet';
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-<?= $dashboard['is_public'] ? 'success' : 'primary' ?> shadow-sm">
                                <div class="card-header bg-<?= $dashboard['is_public'] ? 'success' : 'primary' ?> text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-bar"></i> 
                                        <?= htmlspecialchars($dashboard['name']) ?>
                                        <?php if ($dashboard['is_public']): ?>
                                            <span class="badge bg-light text-success ms-2">Público</span>
                                        <?php endif; ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($dashboard['description'])): ?>
                                        <p class="card-text text-muted small">
                                            <?= nl2br(htmlspecialchars($dashboard['description'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <?php if ($isSpreadsheet): ?>
                                                <i class="fas fa-file-excel"></i>
                                                Fonte: <strong><?= htmlspecialchars($dashboard['spreadsheet_name'] ?? 'Planilha') ?></strong>
                                            <?php else: ?>
                                                <i class="fas fa-file-alt"></i> 
                                                Relatório: <strong><?= htmlspecialchars($dashboard['report_name'] ?? 'Não definido') ?></strong>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    
                                    <?php if (!empty($dashboard['category'])): ?>
                                        <div class="mb-2">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($dashboard['category']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="small text-muted">
                                        <i class="fas fa-eye"></i> <?= number_format($dashboard['views_count']) ?> visualizações
                                        <br>
                                        <i class="fas fa-user"></i> Por: <?= htmlspecialchars($dashboard['creator_name']) ?>
                                        <br>
                                        <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($dashboard['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex gap-2">
                                        <a href="<?= $_ENV['URL_ADM'] ?>view-dashboard/<?= $dashboard['id'] ?>" 
                                           class="btn btn-primary btn-sm flex-fill">
                                            <i class="fas fa-eye"></i> Abrir
                                        </a>
                                        <?php if ($dashboard['created_by'] == ($_SESSION['user_id'] ?? 0) || ($_SESSION['user_access_level_id'] ?? 0) == 1): 
                                            // Gerar token único para este formulário
                                            $delete_token = CSRFHelper::generateCSRFToken('form_delete_dashboard_' . $dashboard['id']);
                                        ?>
                                            <form method="POST" action="<?= $_ENV['URL_ADM'] ?>delete-dashboard" 
                                                  class="d-inline"
                                                  id="formDelete<?= $dashboard['id'] ?>"
                                                  onsubmit="return confirm('Deseja realmente deletar este dashboard?')">
                                                <input type="hidden" name="csrf_token" value="<?= $delete_token ?>">
                                                <input type="hidden" name="dashboard_id" value="<?= $dashboard['id'] ?>">
                                                <input type="hidden" name="id" value="<?= $dashboard['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
