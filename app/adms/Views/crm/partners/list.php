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
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Listar Parceiros</span>
            
            <!-- Botão Novo Parceiro com Dropdown (dentro do card) -->
            <div class="btn-group">
                <a href="<?= $_ENV['URL_ADM'] ?>crm-create-partner" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i>Novo Parceiro
                </a>
                <button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
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
                <div class="col-md-2">
                    <select name="tag_id" class="form-select">
                        <option value="">Todas as tags</option>
                        <?php foreach ($this->data['all_tags'] ?? [] as $tag): ?>
                            <option value="<?php echo $tag['id']; ?>" <?php echo ($this->data['filters']['tag_id'] ?? '') == $tag['id'] ? 'selected' : ''; ?>>
                                🏷️ <?php echo htmlspecialchars($tag['name']); ?>
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
                            <th><i class="fas fa-tags me-1"></i>Tags</th>
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
                                <td>
                                    <?php 
                                    $phone = $partner['phone'] ?? $partner['mobile'] ?? '';
                                    if ($phone && strlen($phone) >= 12) {
                                        // Formatar: +55 (XX) XXXXX-XXXX ou +55 (XX) XXXX-XXXX
                                        $cleanPhone = preg_replace('/\D/', '', $phone);
                                        if (strlen($cleanPhone) == 13) {
                                            // Celular: +55 (XX) 9XXXX-XXXX
                                            $formatted = '+' . substr($cleanPhone, 0, 2) . ' (' . substr($cleanPhone, 2, 2) . ') ' . substr($cleanPhone, 4, 5) . '-' . substr($cleanPhone, 9);
                                        } elseif (strlen($cleanPhone) == 12) {
                                            // Fixo: +55 (XX) XXXX-XXXX
                                            $formatted = '+' . substr($cleanPhone, 0, 2) . ' (' . substr($cleanPhone, 2, 2) . ') ' . substr($cleanPhone, 4, 4) . '-' . substr($cleanPhone, 8);
                                        } else {
                                            $formatted = $phone;
                                        }
                                        echo htmlspecialchars($formatted);
                                    } else {
                                        echo htmlspecialchars($phone ?: '-');
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($partner['responsible_name'] ?? '-'); ?></td>
                                <td>
                                    <?php if (!empty($partner['tags'])): ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($partner['tags'] as $tag): ?>
                                                <span class="badge" style="background-color: <?= htmlspecialchars($tag['color']) ?>; font-size: 0.75rem;">
                                                    <i class="fas fa-tag me-1"></i><?= htmlspecialchars($tag['name']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
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

