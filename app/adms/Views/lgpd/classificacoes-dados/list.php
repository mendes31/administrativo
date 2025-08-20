<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Classificações de Dados LGPD</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">LGPD</li>
            <li class="breadcrumb-item">Classificações de Dados</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-tags me-2"></i>Listar Classificações de Dados</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-create" class="btn btn-success btn-sm mb-1 btn-min-width-90"><i class="fa-solid fa-plus"></i> Cadastrar</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            
            <!-- Filtros -->
            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="classificacao" class="form-label mb-1">Classificação</label>
                    <input type="text" name="classificacao" id="classificacao" class="form-control" value="<?= htmlspecialchars($_GET['classificacao'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label for="base_legal_id" class="form-label mb-1">Base Legal</label>
                    <select name="base_legal_id" id="base_legal_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['bases_legais'] ?? []) as $baseLegal): ?>
                            <option value="<?= $baseLegal['id'] ?>" <?= (($_GET['base_legal_id'] ?? '') == $baseLegal['id']) ? 'selected' : '' ?>><?= htmlspecialchars($baseLegal['base_legal']) ?></option>
                        <?php endforeach; ?>
                    </select>
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
                    <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados" class="btn btn-secondary btn-sm btn-filtros-mobile">
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
                                    <th>Classificação</th>
                                    <th>Exemplos</th>
                                    <th>Base Legal</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->data['registros'] as $classificacao): ?>
                                    <tr>
                                        <td><?= $classificacao['id'] ?></td>
                                        <td><?= htmlspecialchars($classificacao['classificacao']) ?></td>
                                        <td>
                                            <?php if (!empty($classificacao['exemplos'])): ?>
                                                <?= htmlspecialchars($classificacao['exemplos']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($classificacao['base_legal'])): ?>
                                                <?= htmlspecialchars($classificacao['base_legal']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($classificacao['status'] === 'Ativo'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Inativo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= FormatHelper::formatDate($classificacao['created_at']) ?></td>
                                        <td>
                                            <div class="btn-group tabela-acoes" role="group">
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-view/<?= $classificacao['id'] ?>" class="btn btn-info btn-sm" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-edit/<?= $classificacao['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-delete/<?= $classificacao['id'] ?>" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta classificação de dados?')">
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
                    <?php foreach ($this->data['registros'] as $i => $classificacao): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars($classificacao['classificacao']) ?></b></h5>
                                        <div class="mb-1"><b>ID:</b> <?= $classificacao['id'] ?></div>
                                        <div class="mb-1"><b>Status:</b> 
                                            <?php if ($classificacao['status'] === 'Ativo'): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardClassificacaoDetails<?= $i ?>" aria-expanded="false" aria-controls="cardClassificacaoDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardClassificacaoDetails<?= $i ?>">
                                    <div class="mb-1"><b>Exemplos:</b> 
                                        <?php if (!empty($classificacao['exemplos'])): ?>
                                            <?= htmlspecialchars($classificacao['exemplos']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-1"><b>Base Legal:</b> 
                                        <?php if (!empty($classificacao['base_legal'])): ?>
                                            <?= htmlspecialchars($classificacao['base_legal']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-1"><b>Criado em:</b> <?= FormatHelper::formatDate($classificacao['created_at']) ?></div>
                                    <div class="mt-2">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-view/<?= $classificacao['id'] ?>" class="btn btn-info btn-sm me-1 mb-1" title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-edit/<?= $classificacao['id'] ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>lgpd-classificacoes-dados-delete/<?= $classificacao['id'] ?>" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta classificação de dados?')">
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
                            Exibindo <?= count($this->data['registros']) ?> registro(s) nesta página.
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
                    Nenhuma classificação de dados encontrada.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 