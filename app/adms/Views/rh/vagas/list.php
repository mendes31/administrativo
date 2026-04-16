<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Vagas de Emprego</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item active">Vagas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-briefcase me-2"></i>Listar Vagas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-create" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                    <i class="fa-solid fa-plus"></i> Cadastrar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3 mb-2">
                    <label for="titulo" class="form-label mb-1">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['titulo'] ?? '') ?>" placeholder="Buscar por título">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="status" class="form-label mb-1">Status</label>
                    <?php
                    $statusAtual = $_GET['status'] ?? '';
                    $statusLista = [
                        ''         => 'Todos',
                        'aberta'   => 'Aberta',
                        'pausada'  => 'Pausada',
                        'fechada'  => 'Fechada',
                        'cancelada'=> 'Cancelada',
                    ];
                    ?>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <?php foreach ($statusLista as $valor => $label): ?>
                            <option value="<?= $valor ?>" <?= $statusAtual === $valor ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="area_id" class="form-label mb-1">Área/Departamento</label>
                    <select name="area_id" id="area_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($this->data['departments'] as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($_GET['area_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="cargo_id" class="form-label mb-1">Cargo</label>
                    <select name="cargo_id" id="cargo_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['positions'] as $pos): ?>
                            <option value="<?= $pos['id'] ?>" <?= ($_GET['cargo_id'] ?? '') == $pos['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pos['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas" class="btn btn-secondary btn-sm btn-filtros-mobile ms-1">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Cargo</th>
                            <th>Tipo Contrato</th>
                            <th>Status</th>
                            <th>Candidatos</th>
                            <th>Data Abertura</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['vagas'])): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Nenhuma vaga encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['vagas'] as $vaga): ?>
                                <tr>
                                    <td><?= $vaga['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($vaga['titulo']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($vaga['area_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string)($vaga['cargo_nome'] ?? '')) ?: '-') ?></td>
                                    <td><?= htmlspecialchars($vaga['tipo_contrato'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($vaga['status']) {
                                            'aberta'   => 'badge bg-success',
                                            'pausada'  => 'badge bg-warning text-dark',
                                            'fechada'  => 'badge bg-secondary',
                                            'cancelada'=> 'badge bg-danger',
                                            default    => 'badge bg-secondary',
                                        };
                                        ?>
                                        <span class="<?= $statusClass ?>">
                                            <?= htmlspecialchars(ucfirst($vaga['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">
                                            <?= (int)($vaga['total_candidatos'] ?? 0) ?>
                                        </span>
                                    </td>
                                    <td><?= FormatHelper::formatDateTime($vaga['data_abertura'] ?? '') ?></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-view/<?= $vaga['id'] ?>" 
                                               class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-candidatos/<?= $vaga['id'] ?>" 
                                               class="btn btn-success btn-sm" title="Vincular Candidatos">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-vagas-edit/<?= $vaga['id'] ?>" 
                                               class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    title="Excluir"
                                                    onclick="confirmarExclusao(<?= $vaga['id'] ?>, '<?= htmlspecialchars(addslashes($vaga['titulo'])) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($this->data['paginator'])): ?>
                <div class="mt-3">
                    <?= $this->data['paginator'] ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formExcluir" method="POST" action="">
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a vaga <strong id="vagaTitulo"></strong>?</p>
                    <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha *</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="motivo" class="form-label">Justificativa *</label>
                        <textarea name="motivo" id="motivo" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, titulo) {
    document.getElementById('vagaTitulo').textContent = titulo;
    document.getElementById('formExcluir').action = '<?php echo $_ENV['URL_ADM']; ?>rh-vagas-delete/' + id;
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

document.getElementById('formExcluir').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('id', this.action.split('/').pop());
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir vaga.');
    });
});
</script>

