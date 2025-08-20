<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Categorias de Titulares LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Categorias de Titulares</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-users me-2"></i>Listar Categorias de Titulares</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-create" class="btn btn-success btn-sm mb-1 btn-min-width-90"><i class="fa-solid fa-plus"></i> Cadastrar</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <label for="titular" class="form-label mb-1">Titular</label>
                    <input type="text" name="titular" id="titular" class="form-control" value="<?= htmlspecialchars($_GET['titular'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Status</option>
                        <option value="1" <?= (($_GET['status'] ?? '') === '1') ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= (($_GET['status'] ?? '') === '0') ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
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
                <div class="col-md-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares" class="btn btn-secondary btn-sm btn-filtros-mobile">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </form>

            <?php if (!empty($this->data['registros'])): ?>
                <!-- Tabela Desktop -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Titular</th>
                                    <th>Exemplo</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['registros'] as $categoria): ?>
                                    <tr>
                                        <td><?= $categoria['id'] ?></td>
                                        <td><?= htmlspecialchars($categoria['titular']) ?></td>
                                        <td>
                                            <?php if (!empty($categoria['exemplo'])): ?>
                                                <?= htmlspecialchars($categoria['exemplo']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($categoria['status'] === 'Ativo'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Inativo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= FormatHelper::formatDate($categoria['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group tabela-acoes" role="group">
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-view/<?= $categoria['id'] ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-edit/<?= $categoria['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-delete/<?= $categoria['id'] ?>" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta categoria de titular?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cards Mobile -->
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['registros'] as $i => $categoria): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($categoria['titular']) ?></b></h5>
                                        <div class="mb-1"><b>ID:</b> <?= $categoria['id'] ?></div>
                                        <div class="mb-1"><b>Status:</b> 
                                            <?php if ($categoria['status'] === 'Ativo'): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardCategoriaDetails<?= $i ?>" aria-expanded="false" aria-controls="cardCategoriaDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardCategoriaDetails<?= $i ?>">
                                    <div class="mb-1"><b>Exemplo:</b> 
                                        <?php if (!empty($categoria['exemplo'])): ?>
                                            <?= htmlspecialchars($categoria['exemplo']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-1"><b>Criado em:</b> <?= FormatHelper::formatDate($categoria['created_at']) ?></div>
                                    <div class="mt-2">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-view/<?= $categoria['id'] ?>" class="btn btn-info btn-sm me-1 mb-1" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-edit/<?= $categoria['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-categorias-titulares-delete/<?= $categoria['id'] ?>" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta categoria de titular?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginação e informações -->
                <div class="w-100 mt-2">
                    <!-- Desktop: frase à esquerda, paginação à direita -->
                    <div class="d-none d-md-flex justify-content-between align-items-center w-100">
                        <div class="text-secondary small">
                            <?php if (!empty($this->data['paginator'])): ?>
                                Exibindo <?= count($this->data['registros']) ?> registro(s) nesta página.
                            <?php else: ?>
                                Exibindo <?= count($this->data['registros']) ?> registro(s) nesta página.
                            <?php endif; ?>
                        </div>
                        <div>
                            <?= $this->data['paginator'] ?? '' ?>
                        </div>
                    </div>
                    <!-- Mobile: paginação centralizada -->
                    <div class="d-flex d-md-none flex-column align-items-center w-100">
                        <div class="text-secondary small w-100 text-center mb-1">
                            Exibindo <?= count($this->data['registros']) ?> registro(s) nesta página.
                        </div>
                        <div class="w-100 d-flex justify-content-center">
                            <?= $this->data['paginator'] ?? '' ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhuma categoria de titular encontrada.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 