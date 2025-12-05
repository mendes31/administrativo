<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Competências</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Gestão de Pessoas</li>
            <li class="breadcrumb-item">Competências</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-star me-2"></i>Listar Competências</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('CreateCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-competency" class="btn btn-success btn-sm mb-1">
                        <i class="fa-solid fa-plus"></i> Nova Competência
                    </a>
                <?php } ?>
                <?php if (in_array('CompetencyMatrix', $this->data['buttonPermission'] ?? [])) { ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>competency-matrix" class="btn btn-primary btn-sm mb-1">
                        <i class="fas fa-table"></i> Matriz
                    </a>
                <?php } ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="competency_type" class="form-label mb-1">Tipo</label>
                    <select name="competency_type" id="competency_type" class="form-select">
                        <option value="">Todos</option>
                        <option value="technical" <?= (($this->data['filters']['competency_type'] ?? '') === 'technical') ? 'selected' : '' ?>>Técnica</option>
                        <option value="behavioral" <?= (($this->data['filters']['competency_type'] ?? '') === 'behavioral') ? 'selected' : '' ?>>Comportamental</option>
                        <option value="leadership" <?= (($this->data['filters']['competency_type'] ?? '') === 'leadership') ? 'selected' : '' ?>>Liderança</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label mb-1">Buscar</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Nome ou descrição..." 
                           value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-competencies?limpar=1" class="btn btn-secondary w-100">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>

            <?php if (empty($this->data['competencies'])): ?>
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>Nenhuma competência encontrada.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Níveis</th>
                                <th>Descrição</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['competencies'] as $competency): 
                                // Contar quantos níveis estão definidos
                                $levelsCount = 0;
                                $levelsDefined = [];
                                for ($i = 1; $i <= 5; $i++) {
                                    if (!empty($competency['level_' . $i . '_description'])) {
                                        $levelsCount++;
                                        $levelsDefined[] = $i;
                                    }
                                }
                                
                                // Traduzir tipo
                                $typeLabels = [
                                    'technical' => 'Técnica',
                                    'behavioral' => 'Comportamental',
                                    'leadership' => 'Liderança'
                                ];
                                $typeLabel = $typeLabels[$competency['competency_type']] ?? $competency['competency_type'];
                            ?>
                                <tr>
                                    <td><?= $competency['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($competency['name']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($typeLabel) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($competency['category'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($levelsCount > 0): ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <?php for ($i = 1; $i <= 5; $i++): 
                                                    $colors = ['', 'danger', 'warning', 'info', 'primary', 'success'];
                                                    $color = $colors[$i] ?? 'secondary';
                                                    $hasLevel = in_array($i, $levelsDefined);
                                                ?>
                                                    <span class="badge bg-<?= $hasLevel ? $color : 'light text-muted' ?>" 
                                                          style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;"
                                                          title="<?= $hasLevel ? 'Nível ' . $i . ' definido' : 'Nível ' . $i . ' não definido' ?>">
                                                        <?= $i ?>
                                                    </span>
                                                <?php endfor; ?>
                                                <small class="text-muted ms-1">(<?= $levelsCount ?>/5)</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary" title="Nenhum nível definido">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Sem níveis
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($competency['description'])): ?>
                                            <?= htmlspecialchars(mb_substr($competency['description'], 0, 50)) ?><?= mb_strlen($competency['description']) > 50 ? '...' : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <?php if (in_array('ViewCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-competency/<?= $competency['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('UpdateCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-competency/<?= $competency['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (in_array('DeleteCompetency', $this->data['buttonPermission'] ?? [])) { ?>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-competency/<?= $competency['id'] ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Apagar"
                                                   onclick="return confirm('Tem certeza que deseja apagar esta competência?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

