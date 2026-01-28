<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Termos LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Termos</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-file-contract me-2"></i>Listar Termos LGPD</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-create" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                    <i class="fa-solid fa-plus"></i> Cadastrar
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-auto mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? 10) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
            </form>

            <?php if (!empty($this->data['termos'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Versão</th>
                                <th class="col-descricao"><div>Título</div></th>
                                <th>Tipo</th>
                                <th>Início Vigência</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($this->data['termos'] as $termo): ?>
                                <tr>
                                    <td><?= $termo['id'] ?></td>
                                    <td><?= htmlspecialchars($termo['versao']) ?></td>
                                    <td class="col-descricao">
                                        <div><?= htmlspecialchars($termo['titulo']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($termo['tipo']) ?></td>
                                    <td><?= FormatHelper::formatDate($termo['data_inicio_vigencia'] ?? null, 'd/m/Y H:i') ?></td>
                                    <td>
                                        <?php if ($termo['status'] === 'Ativo'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-ban me-1"></i>Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group tabela-acoes" role="group">
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-view/<?= $termo['id'] ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-edit/<?= $termo['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-termos-delete/<?= $termo['id'] ?>" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este termo?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center w-100 mt-2">
                    <div class="text-secondary small">
                        Exibindo <?= count($this->data['termos']) ?> registro(s) nesta página.
                    </div>
                    <div>
                        <?= $this->data['paginator'] ?? '' ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Nenhum termo cadastrado.</p>
            <?php endif; ?>
        </div>
    </div>
</div>


