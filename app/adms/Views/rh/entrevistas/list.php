<?php
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
$csrfDeleteEntrevista = CSRFHelper::generateCSRFToken('form_delete_rh_entrevista');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Entrevistas</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item active">Entrevistas</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-calendar-alt me-2"></i>Listar Entrevistas</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php
                $bp = $this->data['buttonPermission'] ?? [];
                $bp = is_array($bp) ? $bp : [];
                $podeCadastrar = in_array('RhEntrevistasCreate', $bp, true) || in_array('RhEntrevistas', $bp, true);
                if ($podeCadastrar):
                ?>
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-create" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                    <i class="fa-solid fa-plus"></i> Cadastrar nova entrevista
                </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-2 mb-2">
                    <label for="data_de" class="form-label mb-1">Data de</label>
                    <input type="date" name="data_de" id="data_de" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['data_de'] ?? '') ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="data_ate" class="form-label mb-1">Data até</label>
                    <input type="date" name="data_ate" id="data_ate" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['data_ate'] ?? '') ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label for="tipo" class="form-label mb-1">Tipo</label>
                    <?php $tipoAtual = $_GET['tipo'] ?? ''; ?>
                    <select name="tipo" id="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="presencial" <?= $tipoAtual === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                        <option value="online" <?= $tipoAtual === 'online' ? 'selected' : '' ?>>Online</option>
                        <option value="telefone" <?= $tipoAtual === 'telefone' ? 'selected' : '' ?>>Telefone</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="resultado" class="form-label mb-1">Resultado</label>
                    <?php $resultadoAtual = $_GET['resultado'] ?? ''; ?>
                    <select name="resultado" id="resultado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pendente" <?= $resultadoAtual === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="agendado" <?= $resultadoAtual === 'agendado' ? 'selected' : '' ?>>Agendado</option>
                        <option value="aprovado" <?= $resultadoAtual === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                        <option value="reprovado" <?= $resultadoAtual === 'reprovado' ? 'selected' : '' ?>>Reprovado</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label for="entrevistador_id" class="form-label mb-1">Entrevistador</label>
                    <select name="entrevistador_id" id="entrevistador_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['users'] ?? [] as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= ($_GET['entrevistador_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas" class="btn btn-secondary btn-sm ms-1"><i class="fas fa-times me-1"></i>Limpar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Candidato</th>
                            <th>Vaga</th>
                            <th>Tipo</th>
                            <th>Data/Hora</th>
                            <th>Entrevistador</th>
                            <th>Resultado</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['entrevistas'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                    Nenhuma entrevista encontrada.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($this->data['entrevistas'] as $e): ?>
                                <tr>
                                    <td><?= (int)$e['id'] ?></td>
                                    <td>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>rh-candidatos-view/<?= (int)$e['rh_candidato_id'] ?>">
                                            <?= htmlspecialchars($e['candidato_nome'] ?? '-') ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($e['vaga_titulo'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(ucfirst($e['tipo'] ?? '-')) ?></td>
                                    <td><?= FormatHelper::formatDateTime($e['data_hora'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($e['entrevistador_nome'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        $res = $e['resultado'] ?? '';
                                        $resClass = match($res) {
                                            'aprovado' => 'badge bg-success',
                                            'reprovado' => 'badge bg-danger',
                                            'agendado' => 'badge bg-info',
                                            'pendente' => 'badge bg-warning text-dark',
                                            default => 'badge bg-secondary',
                                        };
                                        ?>
                                        <span class="<?= $resClass ?>"><?= $res ? htmlspecialchars(ucfirst($res)) : '-' ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array('RhEntrevistasView', $bp, true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-view/<?= (int)$e['id'] ?>" class="btn btn-info btn-sm" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (in_array('RhEntrevistasEdit', $bp, true)): ?>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-edit/<?= (int)$e['id'] ?>" class="btn btn-primary btn-sm" title="Agendar (data, local, entrevistador)">
                                                <i class="fas fa-calendar-check"></i>
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-edit/<?= (int)$e['id'] ?>" class="btn btn-warning btn-sm" title="Executar / Registrar resultado e feedback">
                                                <i class="fas fa-clipboard-check"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if (in_array('RhEntrevistasDelete', $bp, true)): ?>
                                            <button type="button" class="btn btn-danger btn-sm" title="Excluir"
                                                    onclick="confirmarExclusao(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['candidato_nome'] ?? 'Entrevista')) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($this->data['paginator'])): ?>
                <div class="mt-3"><?= $this->data['paginator'] ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Excluir entrevista de <strong id="entrevistaLabel"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExcluir">Excluir</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarExclusao(id, label) {
    document.getElementById('entrevistaLabel').textContent = label;
    document.getElementById('btnConfirmarExcluir').onclick = function() {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', <?= json_encode($csrfDeleteEntrevista) ?>);
        fetch('<?php echo $_ENV['URL_ADM']; ?>rh-entrevistas-delete', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Erro: ' + data.message);
        })
        .catch(() => alert('Erro ao excluir.'));
    };
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}
</script>
