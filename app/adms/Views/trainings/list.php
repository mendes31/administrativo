<?php
use App\adms\Helpers\FormatHelper;
?>
<div class="<?= $responsiveClasses['container'] ?? 'container-fluid px-4' ?>">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Treinamentos</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">Treinamentos</li>
        </ol>
    </div>
    
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-graduation-cap me-2"></i>Listar Treinamentos</span>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <a href="<?php echo $_ENV['URL_ADM']; ?>create-training" class="btn btn-success btn-sm mb-1 btn-min-width-90"><i class="fa-solid fa-plus"></i> Cadastrar</a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>training-kpi-dashboard" class="btn btn-primary btn-sm mb-1 btn-min-width-70"><i class="fas fa-chart-line"></i> KPIs</a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>training-matrix-manager" class="btn btn-warning btn-sm mb-1 btn-min-width-70"><i class="fas fa-table"></i> Matriz</a>
                <a href="<?php echo $_ENV['URL_ADM']; ?>list-training-status" class="btn btn-info btn-sm mb-1 btn-min-width-70"><i class="fas fa-chart-bar"></i> Status</a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="GET" class="<?= $responsiveClasses['filters'] ?? 'row g-2' ?> mb-3 align-items-end">
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-3' ?>">
                    <label for="nome" class="form-label mb-1">Nome</label>
                    <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>">
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-3' ?>">
                    <label for="codigo" class="form-label mb-1">Código</label>
                    <input type="text" name="codigo" id="codigo" class="form-control" value="<?= htmlspecialchars($_GET['codigo'] ?? '') ?>">
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-2' ?>">
                    <label for="instrutor" class="form-label mb-1">Instrutor</label>
                    <input type="text" name="instrutor" class="form-control" placeholder="Digite parte do nome do instrutor" value="<?= htmlspecialchars($_GET['instrutor'] ?? '') ?>">
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-2' ?>">
                    <label for="area_responsavel_id" class="form-label mb-1">Área Responsável</label>
                    <select name="area_responsavel_id" id="area_responsavel_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= (($_GET['area_responsavel_id'] ?? '') == $dep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dep['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-2' ?>">
                    <label for="area_elaborador_id" class="form-label mb-1">Área Elaborador</label>
                    <select name="area_elaborador_id" id="area_elaborador_id" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['listDepartments'] ?? []) as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= (($_GET['area_elaborador_id'] ?? '') == $dep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($dep['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-2' ?>">
                    <label for="tipo_obrigatoriedade" class="form-label mb-1">Tipo de Obrigatoriedade</label>
                    <select name="tipo_obrigatoriedade" id="tipo_obrigatoriedade" class="form-select">
                        <option value="">Todos</option>
                        <option value="Legal" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Legal') ? 'selected' : '' ?>>Legal</option>
                        <option value="Normativa" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Normativa') ? 'selected' : '' ?>>Normativa</option>
                        <option value="Contratual" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Contratual') ? 'selected' : '' ?>>Contratual</option>
                        <option value="Corporativa" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Corporativa') ? 'selected' : '' ?>>Corporativa</option>
                        <option value="Técnica" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Técnica') ? 'selected' : '' ?>>Técnica</option>
                        <option value="Estratégica" <?= (($_GET['tipo_obrigatoriedade'] ?? '') === 'Estratégica') ? 'selected' : '' ?>>Estratégica</option>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-1' ?>">
                    <select name="reciclagem" class="form-select">
                        <option value="">Reciclagem</option>
                        <option value="1" <?= (($_GET['reciclagem'] ?? '') === '1') ? 'selected' : '' ?>>Sim</option>
                        <option value="0" <?= (($_GET['reciclagem'] ?? '') === '0') ? 'selected' : '' ?>>Não</option>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-1' ?>">
                    <select name="tipo" class="form-select">
                        <option value="">Tipo</option>
                        <option value="Presencial" <?= (($_GET['tipo'] ?? '') === 'Presencial') ? 'selected' : '' ?>>Presencial</option>
                        <option value="Online" <?= (($_GET['tipo'] ?? '') === 'Online') ? 'selected' : '' ?>>Online</option>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-1' ?>">
                    <select name="ativo" class="form-select">
                        <option value="">Status</option>
                        <option value="1" <?= (($_GET['ativo'] ?? '') === '1') ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= (($_GET['ativo'] ?? '') === '0') ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-auto mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach (($paginationSettings['options'] ?? [10, 20, 50, 100]) as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($_GET['per_page'] ?? ($paginationSettings['per_page'] ?? 10)) == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
                <div class="col-md-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fa fa-search"></i> Filtrar</button>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>list-trainings" class="btn btn-secondary btn-sm btn-filtros-mobile" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"><i class="fa fa-times"></i> Limpar</a>
                </div>
            </form>
            <!-- Tabela Desktop -->
            <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover w-100" style="min-width: 1200px;">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:6%; text-align:left; padding-left:12px;">Código</th>
                                <th style="text-align:left; padding-left:12px;">Nome</th>
                                <th style="width:6%; text-align:left; padding-left:12px;">Reciclar</th>
                                <th style="text-align:left; padding-left:12px;">Área Resp.</th>
                                <th style="text-align:left; padding-left:12px;">Área Elab.</th>
                                <th style="text-align:left; padding-left:12px;">Obrigatoriedade</th>
                                <th style="width:7%; text-align:left; padding-left:12px;">Categoria</th>
                                <th style="text-align:left; padding-left:12px;">Instrutor</th>
                                <th style="width:7%; text-align:left; padding-left:12px;">Carga Horária</th>
                                <th style="width:7%; text-align:left; padding-left:12px;">Cargos Vinculados</th>
                                <th style="width:7%; text-align:left; padding-left:12px;">Status</th>
                                <th style="width:7%; text-align:left; padding-left:12px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($this->data['trainings'])): ?>
                                <?php foreach ($this->data['trainings'] as $training): ?>
                                    <tr>
                                        <td style="text-align:left; padding-left:12px;"><strong><?php echo is_array($training['codigo']) ? '' : htmlspecialchars($training['codigo']); ?></strong></td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <div>
                                                <strong><?php echo is_array($training['nome']) ? '' : htmlspecialchars($training['nome']); ?></strong>
                                                <?php if (!empty($training['versao']) && !is_array($training['versao'])): ?>
                                                    <br><small class="text-muted" style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 3px; border: 1px solid #dee2e6;">v<?php echo htmlspecialchars($training['versao']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php if (!empty($training['reciclagem']) && !empty($training['reciclagem_periodo'])): ?>
                                                <?php echo is_array($training['reciclagem_periodo']) ? '' : FormatHelper::formatReciclagemPeriodoTable((int)$training['reciclagem_periodo']); ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php echo htmlspecialchars($training['area_responsavel_nome'] ?? '-'); ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php echo htmlspecialchars($training['area_elaborador_nome'] ?? '-'); ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php echo htmlspecialchars($training['tipo_obrigatoriedade'] ?? '-'); ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php if (!empty($training['tipo'])): ?>
                                                <span class="badge bg-primary"><?php echo is_array($training['tipo']) ? '' : htmlspecialchars($training['tipo']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php
                                            if (!empty($training['instructor_name']) && !is_array($training['instructor_name'])) {
                                                echo '<i class="fas fa-user-tie me-1"></i>' . htmlspecialchars($training['instructor_name']);
                                            } elseif (!empty($training['user_name']) && !is_array($training['user_name'])) {
                                                echo '<i class="fas fa-user-tie me-1"></i>' . htmlspecialchars($training['user_name']);
                                            } elseif (!empty($training['instrutor']) && !is_array($training['instrutor'])) {
                                                echo htmlspecialchars($training['instrutor']);
                                            } else {
                                                echo '<span class="text-muted">-</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php if (!empty($training['carga_horaria']) && !is_array($training['carga_horaria'])): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <?php echo htmlspecialchars(substr($training['carga_horaria'], 0, 5)); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php 
                                            $cargosVinculados = (int)($training['cargos_vinculados'] ?? 0);
                                            $colaboradoresVinculados = (int)($training['colaboradores_vinculados'] ?? 0);
                                            if ($cargosVinculados > 0): 
                                            ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-link me-1"></i><?php echo $cargosVinculados; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-unlink me-1"></i>0
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge bg-primary ms-1">
                                                <i class="fas fa-users me-1"></i><?php echo $colaboradoresVinculados; ?>
                                            </span>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <?php if ($training['ativo']): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Inativo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding-left:12px;">
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>view-training/<?php echo $training['id']; ?>" class="btn btn-primary btn-sm" title="Visualizar"><i class="fas fa-eye"></i></a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>update-training/<?php echo $training['id']; ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>training-positions/<?php echo $training['id']; ?>" class="btn btn-info btn-sm" title="Vincular Cargos"><i class="fas fa-link"></i></a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>link-training-users/<?php echo $training['id']; ?>" class="btn btn-secondary btn-sm" title="Vincular Colaboradores"><i class="fas fa-users"></i></a>
                                                <a href="<?php echo $_ENV['URL_ADM']; ?>delete-training/<?php echo $training['id']; ?>" class="btn btn-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este treinamento?');"><i class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="12" class="text-center">Nenhum treinamento encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Cards Mobile -->
            <div class="d-block d-md-none">
                <?php if (!empty($this->data['trainings'])): ?>
                    <?php foreach ($this->data['trainings'] as $i => $training): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= is_array($training['nome']) ? '' : htmlspecialchars($training['nome']); ?></b></h5>
                                        <?php if (!empty($training['versao'])): ?>
                                            <div class="mb-1"><small class="text-muted">v<?= is_array($training['versao']) ? '' : htmlspecialchars($training['versao']); ?></small></div>
                                        <?php endif; ?>
                                        <div class="mb-1"><b>Código:</b> <?= is_array($training['codigo']) ? '' : htmlspecialchars($training['codigo']); ?></div>
                                        <div class="mb-1"><b>Status:</b> <?php if ($training['ativo']): ?><span class="badge bg-success">Ativo</span><?php else: ?><span class="badge bg-danger">Inativo</span><?php endif; ?></div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardTrainingDetails<?= $i ?>" aria-expanded="false" aria-controls="cardTrainingDetails<?= $i ?>">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardTrainingDetails<?= $i ?>">
                                    <div class="mb-1"><b>Categoria:</b> <?= is_array($training['tipo']) ? '' : htmlspecialchars($training['tipo'] ?? '-'); ?></div>
                                    <div class="mb-1"><b>Área Resp.:</b> <?= htmlspecialchars($training['area_responsavel_nome'] ?? '-'); ?></div>
                                    <div class="mb-1"><b>Área Elab.:</b> <?= htmlspecialchars($training['area_elaborador_nome'] ?? '-'); ?></div>
                                    <div class="mb-1"><b>Obrigatoriedade:</b> <?= htmlspecialchars($training['tipo_obrigatoriedade'] ?? '-'); ?></div>
                                    <div class="mb-1"><b>Instrutor:</b> <?php
                                        if (!empty($training['instructor_name']) && !is_array($training['instructor_name'])) {
                                            echo '<i class="fas fa-user-tie me-1"></i>' . htmlspecialchars($training['instructor_name']);
                                        } elseif (!empty($training['user_name']) && !is_array($training['user_name'])) {
                                            echo '<i class="fas fa-user-tie me-1"></i>' . htmlspecialchars($training['user_name']);
                                        } elseif (!empty($training['instrutor']) && !is_array($training['instrutor'])) {
                                            echo htmlspecialchars($training['instrutor']);
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                    ?></div>
                                    <div class="mb-1"><b>Carga Horária:</b> <?php if (!empty($training['carga_horaria']) && !is_array($training['carga_horaria'])): ?><?= htmlspecialchars(substr($training['carga_horaria'], 0, 5)); ?><?php else: ?><span class="text-muted">-</span><?php endif; ?></div>
                                    <div class="mb-1"><b>Cargos Vinculados:</b> <?= (int)($training['cargos_vinculados'] ?? 0); ?></div>
                                    <div class="mb-1"><b>Colaboradores Vinculados:</b> <?= (int)($training['colaboradores_vinculados'] ?? 0); ?></div>
                                    <div class="mt-2">
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>view-training/<?php echo $training['id']; ?>" class="btn btn-primary btn-sm me-1 mb-1" title="Visualizar"><i class="fas fa-eye"></i> </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>update-training/<?php echo $training['id']; ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar"><i class="fas fa-edit"></i> </a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>training-positions/<?php echo $training['id']; ?>" class="btn btn-info btn-sm me-1 mb-1" title="Vincular Cargos"><i class="fas fa-link"></i></a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>link-training-users/<?php echo $training['id']; ?>" class="btn btn-secondary btn-sm me-1 mb-1" title="Vincular Colaboradores"><i class="fas fa-users"></i></a>
                                        <a href="<?php echo $_ENV['URL_ADM']; ?>delete-training/<?php echo $training['id']; ?>" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este treinamento?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">Nenhum treinamento encontrado.</div>
                <?php endif; ?>
            </div>
            <!-- Paginação e informações abaixo da tabela/cards -->
            <div class="w-100 mt-2">
                <!-- Desktop: frase à esquerda, paginação à direita -->
                <div class="d-none d-md-flex justify-content-between align-items-center w-100">
                    <div class="text-secondary small">
                        <?php
                        $firstItem = $this->data['pagination']['first_item'] ?? '';
                        $lastItem = $this->data['pagination']['last_item'] ?? '';
                        $total = $this->data['pagination']['total'] ?? '';
                        if (is_array($firstItem)) $firstItem = '';
                        if (is_array($lastItem)) $lastItem = '';
                        if (is_array($total)) $total = '';
                        ?>
                        <?php if (!empty($total)): ?>
                            Mostrando <?= $firstItem ?> até <?= $lastItem ?> de <?= $total ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= is_array($this->data['trainings']) ? count($this->data['trainings']) : 0; ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php
                        $paginationHtml = $this->data['pagination']['html'] ?? '';
                        if (is_array($paginationHtml)) $paginationHtml = '';
                        echo $paginationHtml;
                        ?>
                    </div>
                </div>
                <!-- Remover o bloco mobile duplicado abaixo -->
                <!-- <div class="d-flex d-md-none flex-column align-items-center w-100"> ... </div> -->
            </div>
        </div>
    </div>
</div> 