<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstCidCapituloHelper;

$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_cids');
$responsiveClasses = $this->data['responsiveClasses'] ?? [];
$paginationSettings = $this->data['paginationSettings'] ?? ['per_page' => 10, 'options' => [10, 20, 50, 100]];
$items = $this->data['items'] ?? [];
$filters = $this->data['filters'] ?? [];
?>
<div class="<?= $responsiveClasses['container'] ?? 'container-fluid px-4' ?>">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">CIDs</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard" class="text-decoration-none">SST</a>
            </li>
            <li class="breadcrumb-item">CIDs</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-notes-medical me-2"></i>Listar CIDs</span>
            <small class="text-muted">(<?= (int)($this->data['total_cids'] ?? 0) ?> códigos)</small>
            <span class="ms-auto d-sm-flex flex-row flex-wrap gap-1">
                <?php if (in_array('SstReportCids', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-report-cids" class="btn btn-primary btn-sm mb-1 btn-min-width-70">
                        <i class="fas fa-chart-bar"></i> Relatório
                    </a>
                <?php endif; ?>
                <?php if (in_array('SstCreateCid', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-cid" class="btn btn-success btn-sm mb-1 btn-min-width-90">
                        <i class="fa-solid fa-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="<?= $responsiveClasses['filters'] ?? 'row g-2' ?> mb-3 align-items-end">
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-3' ?>">
                    <label for="search" class="form-label mb-1">Código / Descrição</label>
                    <input type="text" name="search" id="search" class="form-control"
                           placeholder="Ex.: M54 ou lombalgia"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-3' ?>">
                    <label for="capitulo_num" class="form-label mb-1">Capítulo CID-10</label>
                    <select name="capitulo_num" id="capitulo_num" class="form-select">
                        <option value="">Todos os capítulos</option>
                        <?php foreach ($this->data['capitulos'] ?? [] as $cap): ?>
                            <option value="<?= (int)$cap['num'] ?>" <?= ((string)($filters['capitulo_num'] ?? '') === (string)$cap['num']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(SstCidCapituloHelper::label((int)$cap['num'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-2' ?>">
                    <label for="frequente" class="form-label mb-1">Uso frequente</label>
                    <select name="frequente" id="frequente" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" <?= ($filters['frequente'] ?? '') === '1' ? 'selected' : '' ?>>Sim</option>
                    </select>
                </div>
                <div class="<?= $responsiveClasses['filter_cols'] ?? 'col-md-1' ?>">
                    <label for="status" class="form-label mb-1">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="Ativo" <?= ($filters['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="Inativo" <?= ($filters['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
                <div class="col-auto mb-2">
                    <label for="per_page" class="form-label mb-1">Mostrar</label>
                    <div class="d-flex align-items-center">
                        <select name="per_page" id="per_page" class="form-select form-select-sm" style="min-width: 80px;" onchange="this.form.submit()">
                            <?php foreach (($paginationSettings['options'] ?? [10, 20, 50, 100]) as $opt): ?>
                                <option value="<?= $opt ?>" <?= (int)($this->data['per_page'] ?? 10) === (int)$opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-label mb-1 ms-1">registros</span>
                    </div>
                </div>
                <div class="col-md-2 filtros-btns-row w-100 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-filtros-mobile" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                        <i class="fa fa-search"></i> Filtrar
                    </button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-list-cids?limpar=1" class="btn btn-secondary btn-sm btn-filtros-mobile" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                        <i class="fa fa-times"></i> Limpar
                    </a>
                </div>
            </form>

            <!-- Tabela Desktop -->
            <div class="d-none d-md-block list-desktop">
                <div>
                    <table class="table table-bordered table-striped table-hover w-100 table-training-list">
                        <thead class="thead-green">
                            <tr>
                                <th style="width:8%; text-align:left; padding:6px 6px;">Código</th>
                                <th style="width:32%; text-align:left; padding:6px 6px;">Descrição</th>
                                <th style="width:28%; text-align:left; padding:6px 6px;">Capítulo</th>
                                <th style="width:8%; text-align:left; padding:6px 6px;">Frequente</th>
                                <th style="width:8%; text-align:left; padding:6px 6px;">Status</th>
                                <th class="actions-col" style="text-align:left;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $item):
                                    $id = (int)($item['id'] ?? 0);
                                    $capNum = (int)($item['capitulo_num'] ?? 0);
                                    $capNome = trim((string)($item['capitulo_nome'] ?? ''));
                                ?>
                                    <tr>
                                        <td style="text-align:left; padding:6px 6px; word-break:break-word;">
                                            <strong><?= htmlspecialchars((string)($item['codigo'] ?? '')) ?></strong>
                                        </td>
                                        <td style="text-align:left; padding:6px 6px; word-break:break-word;">
                                            <?= htmlspecialchars((string)($item['descricao'] ?? '')) ?>
                                        </td>
                                        <td style="text-align:left; padding:6px 6px; word-break:break-word;">
                                            <?php if ($capNum > 0 && $capNome !== ''): ?>
                                                <span class="badge bg-light text-dark border me-1"><?= $capNum ?></span>
                                                <span class="small"><?= htmlspecialchars($capNome) ?></span>
                                            <?php elseif ($capNum > 0): ?>
                                                <?= htmlspecialchars(SstCidCapituloHelper::label($capNum)) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding:6px 6px;">
                                            <?php if (!empty($item['frequente'])): ?>
                                                <span class="badge bg-info"><i class="fas fa-star me-1"></i>Sim</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding:6px 6px;">
                                            <?php if (($item['status'] ?? '') === 'Ativo'): ?>
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:left; padding:6px 6px;">
                                            <div class="btn-group" role="group">
                                                <?php if (in_array('SstUpdateCid', $perms, true)): ?>
                                                    <a href="<?= $_ENV['URL_ADM']; ?>sst-update-cid/<?= $id ?>" class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array('SstDeleteCid', $perms, true)): ?>
                                                    <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-cid" method="POST" class="d-inline" onsubmit="return confirm('Excluir este CID?');">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                        <input type="hidden" name="id" value="<?= $id ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">Nenhum CID encontrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cards Mobile -->
            <div class="d-block d-md-none list-mobile">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $i => $item):
                        $id = (int)($item['id'] ?? 0);
                        $capNum = (int)($item['capitulo_num'] ?? 0);
                        $capNome = trim((string)($item['capitulo_nome'] ?? ''));
                    ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title mb-1"><b><?= htmlspecialchars((string)($item['codigo'] ?? '')) ?></b></h5>
                                        <div class="small text-muted mb-1"><?= htmlspecialchars((string)($item['descricao'] ?? '')) ?></div>
                                        <div class="mb-1"><b>Status:</b>
                                            <?php if (($item['status'] ?? '') === 'Ativo'): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inativo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#cardCidDetails<?= $i ?>" aria-expanded="false">Ver mais</button>
                                </div>
                                <div class="collapse mt-2" id="cardCidDetails<?= $i ?>">
                                    <div class="mb-1"><b>Capítulo:</b>
                                        <?php if ($capNum > 0): ?>
                                            <?= htmlspecialchars(SstCidCapituloHelper::label($capNum)) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-1"><b>Frequente:</b> <?= !empty($item['frequente']) ? 'Sim' : 'Não' ?></div>
                                    <div class="mt-2">
                                        <?php if (in_array('SstUpdateCid', $perms, true)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-update-cid/<?= $id ?>" class="btn btn-warning btn-sm me-1 mb-1" title="Editar"><i class="fas fa-edit"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstDeleteCid', $perms, true)): ?>
                                            <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-cid" method="POST" class="d-inline" onsubmit="return confirm('Excluir este CID?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm me-1 mb-1" title="Excluir"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">Nenhum CID encontrado.</div>
                <?php endif; ?>
            </div>

            <!-- Paginação -->
            <div class="w-100 mt-2">
                <div class="d-none d-md-flex justify-content-between align-items-center w-100">
                    <div class="text-secondary small">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= (int)$this->data['pagination']['first_item'] ?> até <?= (int)$this->data['pagination']['last_item'] ?> de <?= (int)$this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($items) ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div>
                        <?= $this->data['pagination']['html'] ?? '' ?>
                    </div>
                </div>
                <div class="d-flex d-md-none flex-column align-items-center w-100 mt-2">
                    <div class="text-secondary small w-100 text-center mb-1">
                        <?php if (!empty($this->data['pagination']['total'])): ?>
                            Mostrando <?= (int)$this->data['pagination']['first_item'] ?> até <?= (int)$this->data['pagination']['last_item'] ?> de <?= (int)$this->data['pagination']['total'] ?> registro(s)
                        <?php else: ?>
                            Exibindo <?= count($items) ?> registro(s) nesta página.
                        <?php endif; ?>
                    </div>
                    <div class="w-100 d-flex justify-content-center">
                        <?php
                        $paginationHtml = $this->data['pagination']['html'] ?? '';
                        if ($paginationHtml) {
                            $paginationHtml = str_replace(
                                ['>Primeiro<', '>Anterior<', '>Próximo<', '>Último<'],
                                ['>&laquo;<', '>&lsaquo;<', '>&rsaquo;<', '>&raquo;<'],
                                $paginationHtml
                            );
                            $paginationHtml = preg_replace('/class=\"pagination(.*?)\"/', 'class="pagination pagination-sm$1"', $paginationHtml, 1);
                            echo $paginationHtml;
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
