<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Competência</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies" class="text-decoration-none">Competências</a>
            </li>
            <li class="breadcrumb-item">Visualizar</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-star me-2"></i><?= htmlspecialchars($this->data['competency']['name']) ?></span>
            <span class="ms-auto">
                <?php if (in_array('UpdateCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-competency/<?= $this->data['competency']['id'] ?>" 
                       class="btn btn-sm btn-warning">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                <?php } ?>
                <?php
                $log_resumo = $this->data['log_resumo'] ?? [];
                $log_btn_class = 'btn btn-outline-info btn-sm';
                include __DIR__ . '/../partials/button_log_alteracoes.php';
                ?>
                <?php if (in_array('ListCompetencies', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies" class="btn btn-sm btn-secondary">
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
                            <td width="40%"><strong>Nome:</strong></td>
                            <td><?= htmlspecialchars($this->data['competency']['name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Tipo:</strong></td>
                            <td>
                                <?php 
                                $typeLabels = [
                                    'technical' => 'Técnica',
                                    'behavioral' => 'Comportamental',
                                    'leadership' => 'Liderança'
                                ];
                                $type = $this->data['competency']['competency_type'] ?? '';
                                $typeLabel = $typeLabels[$type] ?? $type;
                                ?>
                                <span class="badge bg-info"><?= htmlspecialchars($typeLabel) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Categoria:</strong></td>
                            <td><?= htmlspecialchars($this->data['competency']['category'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Status:</strong></td>
                            <td>
                                <?php if (!empty($this->data['competency']['status']) && $this->data['competency']['status']): ?>
                                    <span class="badge bg-success">Ativa</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inativa</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Criado em:</strong></td>
                            <td>
                                <?php 
                                $createdAt = $this->data['competency']['created_at'] ?? '';
                                if (!empty($createdAt)) {
                                    try {
                                        echo date('d/m/Y H:i', strtotime($createdAt));
                                    } catch (Exception $e) {
                                        echo htmlspecialchars($createdAt);
                                    }
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Atualizado em:</strong></td>
                            <td>
                                <?php 
                                $updatedAt = $this->data['competency']['updated_at'] ?? '';
                                if (!empty($updatedAt)) {
                                    try {
                                        echo date('d/m/Y H:i', strtotime($updatedAt));
                                    } catch (Exception $e) {
                                        echo htmlspecialchars($updatedAt);
                                    }
                                } else {
                                    echo '<span class="text-muted">-</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if (!empty($this->data['competency']['description'])): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-align-left me-2"></i>Descrição</h5>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($this->data['competency']['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Níveis de Proficiência -->
            <?php 
            $hasLevels = false;
            for ($i = 1; $i <= 5; $i++) {
                if (!empty($this->data['competency']['level_' . $i . '_description'])) {
                    $hasLevels = true;
                    break;
                }
            }
            ?>
            
            <?php if ($hasLevels): ?>
                <div class="mb-4">
                    <h5><i class="fas fa-layer-group me-2"></i>Níveis de Proficiência</h5>
                    <div class="row g-3">
                        <?php 
                        $levelLabels = [
                            1 => ['label' => 'Iniciante', 'color' => 'danger'],
                            2 => ['label' => 'Básico', 'color' => 'warning'],
                            3 => ['label' => 'Intermediário', 'color' => 'info'],
                            4 => ['label' => 'Avançado', 'color' => 'primary'],
                            5 => ['label' => 'Especialista', 'color' => 'success']
                        ];
                        
                        for ($i = 1; $i <= 5; $i++): 
                            $levelKey = 'level_' . $i . '_description';
                            $levelData = $this->data['competency'][$levelKey] ?? '';
                            if (!empty($levelData)):
                        ?>
                            <div class="col-md-6">
                                <div class="card border-<?= $levelLabels[$i]['color'] ?> shadow-sm">
                                    <div class="card-header bg-<?= $levelLabels[$i]['color'] ?> text-white">
                                        <strong>
                                            <span class="badge bg-light text-<?= $levelLabels[$i]['color'] ?> me-2">Nível <?= $i ?></span>
                                            <?= $levelLabels[$i]['label'] ?>
                                        </strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($levelData)) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endfor; 
                        ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum nível de proficiência definido para esta competência. 
                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-competency/<?= $this->data['competency']['id'] ?>">Edite a competência</a> para adicionar níveis.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

