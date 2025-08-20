<?php
use App\adms\Helpers\CSRFHelper;
?>

<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM'] ?>lgpd-dashboard">Dashboard LGPD</a></li>
            <li class="breadcrumb-item active" aria-current="page">Relatórios RIPD</li>
        </ol>
    </nav>

    <!-- Card Principal -->
    <div class="card border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-file-alt me-2"></i>
                Relatórios de Impacto à Proteção de Dados (RIPD)
            </h5>
            <div>
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-export-pdf-list" class="btn btn-warning btn-sm me-2" target="_blank">
                    <i class="fas fa-file-pdf me-1"></i>Exportar Lista PDF
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-dashboard" class="btn btn-info btn-sm me-2">
                    <i class="fas fa-chart-bar me-1"></i>Dashboard
                </a>
                <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i>Novo RIPD
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Estatísticas Rápidas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h4><?= $this->data['total_ripds'] ?? 0 ?></h4>
                            <small>Total de RIPDs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h4><?= $this->data['estatisticas']['aprovados'] ?? 0 ?></h4>
                            <small>RIPDs Aprovados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h4><?= $this->data['estatisticas']['em_revisao'] ?? 0 ?></h4>
                            <small>Em Revisão</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h4><?= ($this->data['estatisticas']['total'] ?? 0) - ($this->data['estatisticas']['aprovados'] ?? 0) - ($this->data['estatisticas']['em_revisao'] ?? 0) ?></h4>
                            <small>Em Rascunho</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($this->data['ripds'])): ?>
                <!-- Mensagem quando não há RIPDs -->
                <div class="text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum RIPD encontrado</h5>
                    <p class="text-muted">Comece criando seu primeiro Relatório de Impacto à Proteção de Dados.</p>
                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Criar Primeiro RIPD
                    </a>
                </div>
            <?php else: ?>
                <!-- Tabela de RIPDs -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Título</th>
                                <th>AIPD Relacionada</th>
                                <th>Versão</th>
                                <th>Status</th>
                                <th>Data Elaboração</th>
                                <th>Elaborador</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['ripds'] as $ripd): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?= $ripd['codigo'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ripd['titulo']) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($ripd['aipd_codigo']) ?> - 
                                            <?= htmlspecialchars(substr($ripd['aipd_titulo'], 0, 50)) ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= $ripd['versao'] ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = match($ripd['status']) {
                                            'Aprovado' => 'bg-success',
                                            'Em Revisão' => 'bg-warning',
                                            'Rejeitado' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $ripd['status'] ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($ripd['data_elaboracao'])) ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($ripd['elaborador_nome']) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-view/<?= $ripd['id'] ?>" 
                                               class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-edit/<?= $ripd['id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($ripd['status'] !== 'Aprovado'): ?>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="confirmarExclusao(<?= $ripd['id'] ?>)" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Cards Mobile -->
                <div class="d-md-none">
                    <?php foreach ($this->data['ripds'] as $ripd): ?>
                        <div class="card mb-3 border-primary">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= $ripd['codigo'] ?></strong>
                                    <?php
                                    $statusClass = match($ripd['status']) {
                                        'Aprovado' => 'bg-success',
                                        'Em Revisão' => 'bg-warning',
                                        'Rejeitado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $ripd['status'] ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($ripd['titulo']) ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <strong>AIPD:</strong> <?= htmlspecialchars($ripd['aipd_codigo']) ?><br>
                                        <strong>Versão:</strong> <?= $ripd['versao'] ?><br>
                                        <strong>Data:</strong> <?= date('d/m/Y', strtotime($ripd['data_elaboracao'])) ?><br>
                                        <strong>Elaborador:</strong> <?= htmlspecialchars($ripd['elaborador_nome']) ?>
                                    </small>
                                </p>
                                <div class="btn-group w-100" role="group">
                                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-view/<?= $ripd['id'] ?>" 
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-eye me-1"></i>Ver
                                    </a>
                                    <a href="<?= $_ENV['URL_ADM'] ?>lgpd-ripd-edit/<?= $ripd['id'] ?>" 
                                       class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit me-1"></i>Editar
                                    </a>
                                    <?php if ($ripd['status'] !== 'Aprovado'): ?>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="confirmarExclusao(<?= $ripd['id'] ?>)">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExclusao" tabindex="-1" aria-labelledby="modalExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExclusaoLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este RIPD?</p>
                <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formExclusao" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('ripd_delete') ?>">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(ripdId) {
    const modal = new bootstrap.Modal(document.getElementById('modalExclusao'));
    const form = document.getElementById('formExclusao');
    form.action = '<?= $_ENV['URL_ADM'] ?>lgpd-ripd-delete/' + ripdId;
    modal.show();
}
</script>
