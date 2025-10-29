<div class="container-fluid px-4">
            
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h1 class="mt-2">
                    <i class="fas fa-bullseye text-primary me-2"></i>
                    Oportunidades
                </h1>
                <div class="btn-group">
                    <a href="<?= $_ENV['URL_ADM'] ?>crm-create-opportunity" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Nova Oportunidade
                    </a>
                    <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                        <span class="visually-hidden">Toggle</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>crm-import-opportunities">
                            <i class="fas fa-file-import text-primary me-2"></i>Importar Excel
                        </a></li>
                        <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>crm-export-opportunities">
                            <i class="fas fa-file-excel text-success me-2"></i>Exportar Excel
                        </a></li>
                    </ul>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Pesquisar</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Código, título, parceiro..." 
                                       value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Etapa</label>
                                <select name="stage_id" class="form-select">
                                    <option value="">Todas</option>
                                    <?php foreach ($this->data['stages'] as $stage): ?>
                                        <option value="<?= $stage['id'] ?>" 
                                                <?= ($this->data['filters']['stage_id'] ?? '') == $stage['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($stage['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Responsável</label>
                                <select name="responsible_user_id" class="form-select">
                                    <option value="">Todos</option>
                                    <?php foreach ($this->data['users'] as $user): ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= ($this->data['filters']['responsible_user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Aberta" <?= ($this->data['filters']['status'] ?? '') === 'Aberta' ? 'selected' : '' ?>>Aberta</option>
                                    <option value="Ganha" <?= ($this->data['filters']['status'] ?? '') === 'Ganha' ? 'selected' : '' ?>>Ganha</option>
                                    <option value="Perdida" <?= ($this->data['filters']['status'] ?? '') === 'Perdida' ? 'selected' : '' ?>>Perdida</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search me-1"></i> Filtrar
                                </button>
                                <a href="<?= $_ENV['URL_ADM'] ?>crm-list-opportunities" class="btn btn-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabela -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (!empty($this->data['opportunities'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-green">
                                    <tr>
                                        <th>Código</th>
                                        <th>Título</th>
                                        <th>Parceiro</th>
                                        <th>Etapa</th>
                                        <th>Valor</th>
                                        <th>Probabilidade</th>
                                        <th>Responsável</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($this->data['opportunities'] as $opp): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($opp['code']) ?></code></td>
                                            <td><?= htmlspecialchars($opp['title']) ?></td>
                                            <td><?= htmlspecialchars($opp['partner_name']) ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= htmlspecialchars($opp['stage_color']) ?>">
                                                    <?= htmlspecialchars($opp['stage_name']) ?>
                                                </span>
                                            </td>
                                            <td class="text-success fw-bold">R$ <?= number_format($opp['value'], 2, ',', '.') ?></td>
                                            <td><?= $opp['probability'] ?>%</td>
                                            <td><?= htmlspecialchars($opp['responsible_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $opp['status'] === 'Aberta' ? 'success' : ($opp['status'] === 'Ganha' ? 'primary' : 'danger') ?>">
                                                    <?= htmlspecialchars($opp['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-view-opportunity/<?= $opp['id'] ?>" 
                                                   class="btn btn-sm btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-update-opportunity/<?= $opp['id'] ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= $_ENV['URL_ADM'] ?>crm-delete-opportunity/<?= $opp['id'] ?>" 
                                                   class="btn btn-sm btn-danger" title="Excluir"
                                                   onclick="return confirm('Tem certeza que deseja excluir esta oportunidade?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if (!empty($this->data['pagination']['html'])): ?>
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                                </div>
                                <div>
                                    <?php echo $this->data['pagination']['html']; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <p class="text-muted">Nenhuma oportunidade encontrada.</p>
                            <a href="<?= $_ENV['URL_ADM'] ?>crm-create-opportunity" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Nova Oportunidade
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

