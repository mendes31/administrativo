<?php
use App\adms\Helpers\FormatHelper;
?>

<div class="container-fluid px-4">
    
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">
            <i class="fas fa-users me-2"></i>Gestão de Parceiros
        </h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard">Dashboard</a>
            </li>
            <li class="breadcrumb-item">CRM</li>
            <li class="breadcrumb-item active">Parceiros</li>
        </ol>
        <div class="btn-group ms-auto">
            <a href="<?= $_ENV['URL_ADM'] ?>crm-create-partner" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Novo Parceiro
            </a>
            <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                <span class="visually-hidden">Toggle</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>crm-import-partners">
                    <i class="fas fa-file-import text-primary me-2"></i>Importar Excel
                </a></li>
                <li><a class="dropdown-item" href="<?= $_ENV['URL_ADM'] ?>crm-export-partners">
                    <i class="fas fa-file-excel text-success me-2"></i>Exportar Excel
                </a></li>
            </ul>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-list me-2"></i>Listar Parceiros</span>
            <?php if (in_array('CrmCreatePartner', $this->data['buttonPermission'] ?? [])): ?>
            <a href="<?php echo $_ENV['URL_ADM']; ?>crm-create-partner" class="btn btn-success btn-sm ms-auto">
                <i class="fas fa-plus me-1"></i>Novo Parceiro
            </a>
            <?php endif; ?>
        </div>
        
        <div class="card-body">
            <!-- Filtros -->
            <form method="GET" action="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Buscar..." 
                           value="<?php echo htmlspecialchars($this->data['filters']['search'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <select name="segment" class="form-select">
                        <option value="">Todos os segmentos</option>
                        <?php foreach ($this->data['segments'] as $seg): ?>
                            <option value="<?php echo $seg; ?>" <?php echo ($this->data['filters']['segment'] ?? '') == $seg ? 'selected' : ''; ?>>
                                <?php echo $seg; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="partner_type" class="form-select">
                        <option value="">Todos os tipos</option>
                        <?php foreach ($this->data['partner_types'] as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo ($this->data['filters']['partner_type'] ?? '') == $type ? 'selected' : ''; ?>>
                                <?php echo $type; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <?php foreach ($this->data['statuses'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($this->data['filters']['status'] ?? '') == $status ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>crm-list-partners" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <!-- Tabela -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="thead-green">
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Segmento</th>
                            <th>Tipo</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Responsável</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['partners'])): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Nenhum parceiro encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['partners'] as $partner): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($partner['code']); ?></code></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($partner['name']); ?></strong>
                                    <?php if ($partner['trading_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($partner['trading_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $partner['segment']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $typeColor = $partner['partner_type'] == 'Cliente' ? 'success' : ($partner['partner_type'] == 'Lead' ? 'primary' : 'secondary');
                                    ?>
                                    <span class="badge bg-<?php echo $typeColor; ?>"><?php echo $partner['partner_type']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($partner['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($partner['phone'] ?? $partner['mobile'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($partner['responsible_name'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $statusColor = $partner['status'] == 'Ativo' ? 'success' : ($partner['status'] == 'Inativo' ? 'secondary' : 'danger');
                                    ?>
                                    <span class="badge bg-<?php echo $statusColor; ?>"><?php echo $partner['status']; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>crm-view-partner/<?php echo $partner['id']; ?>" 
                                           class="btn btn-outline-primary" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>crm-update-partner/<?php echo $partner['id']; ?>" 
                                           class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if (!empty($this->data['pagination']['html'])): ?>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= $this->data['pagination']['first_item'] ?> até <?= $this->data['pagination']['last_item'] ?> de <?= $this->data['pagination']['total'] ?> registro(s)
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php echo $this->data['pagination']['html']; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

