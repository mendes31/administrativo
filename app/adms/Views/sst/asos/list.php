<?php

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstAsoStatusHelper;

function formatCellValue(string $col, mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    if (is_bool($value) || $col === 'obrigatorio' || $col === 'termo_assinado') {
        return ($value === true || $value === 1 || $value === '1') ? 'Sim' : 'Não';
    }
    if (str_contains($col, 'data_') && is_string($value)) {
        return strlen($value) > 10 ? date('d/m/Y H:i', strtotime($value)) : date('d/m/Y', strtotime($value));
    }
    return htmlspecialchars((string) $value);
}

$entity = $this->data['entity'];
$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_asos');
$filtersId = 'sstFiltersAso';
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-file-medical me-2"></i>ASOs — fila de resultados</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><?= htmlspecialchars('ASOs') ?></li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span>Fila de lançamento</span>
            <?php $aguardando = (int) ($this->data['aguardando_count'] ?? 0); ?>
            <?php if ($aguardando > 0): ?>
                <span class="badge bg-warning text-dark"><?= $aguardando ?> aguardando resultados</span>
            <?php endif; ?>
            <span class="ms-auto d-flex gap-1">
                <?php if (in_array('SstEncaminhamentoAso', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-encaminhamento-aso" class="btn btn-primary btn-sm"><i class="fas fa-file-export"></i> Encaminhamento</a>
                <?php endif; ?>
                <?php if (in_array('SstCreateAso', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-aso" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small mb-3">
                Solicitações são <strong>geradas automaticamente</strong> pelos vínculos (cargo → risco → exame).
                Acesse cada linha com status <em>Aguardando exames</em> e use o botão
                <i class="fas fa-clipboard-check"></i> para lançar apenas os resultados.
            </p>
            <div class="d-md-none mb-2">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $filtersId ?>">
                    <i class="fa fa-filter me-1"></i> Filtros
                </button>
            </div>
            <div class="collapse d-md-block" id="<?= $filtersId ?>">
                <form method="get" class="row g-2 mb-3 align-items-end">
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label" style="font-size:.7rem;">Pesquisar</label>
                        <input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>">
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
            <label for="adms_user_id" class="form-label" style="font-size:.7rem;">Colaborador</label>
            <select name="adms_user_id" id="adms_user_id" class="form-select form-select-sm">
                <option value="">Todos</option>
                <?php foreach ($this->data['users'] ?? [] as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= ((string)($this->data['filters']['adms_user_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['name'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <label for="status" class="form-label" style="font-size:.7rem;">Status</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="Aguardando exames" <?= ($this->data['filters']['status'] ?? '') === 'Aguardando exames' ? 'selected' : '' ?>>Aguardando exames</option>
                            <option value="Concluído" <?= ($this->data['filters']['status'] ?? '') === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Filtrar</button>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-list-asos" class="btn btn-secondary btn-sm">Limpar</a>
                    </div>
                </form>
            </div>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            <th>Id</th>
<th>Colaborador</th>
                            <th>Tipo</th>
<th>Status</th>
<th>Realização</th>
<th>Validade</th>
<th>Resultado</th>

                            <th class="text-center">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item):
                            $id = (int)($item['id'] ?? 0);
                        ?>
                            <tr>
                                <td><?= formatCellValue('id', $item['id'] ?? null) ?></td>
<td><?= formatCellValue('colaborador_nome', $item['colaborador_nome'] ?? null) ?></td>
<td><?= formatCellValue('tipo', $item['tipo'] ?? null) ?></td>
<td><span class="badge bg-<?= SstAsoStatusHelper::badgeClass($item['status'] ?? SstAsoStatusHelper::CONCLUIDO) ?>"><?= htmlspecialchars(SstAsoStatusHelper::label($item['status'] ?? null)) ?></span></td>
<td><?= formatCellValue('data_realizacao', $item['data_realizacao'] ?? null) ?></td>
<td><?= formatCellValue('data_validade', $item['data_validade'] ?? null) ?></td>
<td><?= formatCellValue('resultado', $item['resultado'] ?? null) ?></td>

                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (in_array('SstRegistrarResultadosAso', $perms, true) && SstAsoStatusHelper::isAguardando($item)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-registrar-resultados-aso/<?= $id ?>" class="btn btn-warning btn-sm" title="Registrar resultados"><i class="fas fa-clipboard-check"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstViewAso', $perms)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-view-aso/<?= $id ?>" class="btn btn-info btn-sm" title="Visualizar"><i class="fa-regular fa-eye"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstUpdateAso', $perms) && !SstAsoStatusHelper::isAguardando($item)): ?>
                                            <a href="<?= $_ENV['URL_ADM']; ?>sst-update-aso/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array('SstDeleteAso', $perms)): ?>
                                            <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-aso" method="POST" class="d-inline" onsubmit="return confirm('Excluir registro?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                                <input type="hidden" name="id" value="<?= $id ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['items'] as $item):
                        $id = (int)($item['id'] ?? 0);
                        $canView = in_array('SstViewAso', $perms);
                        $viewUrl = $_ENV['URL_ADM'] . 'sst-view-aso/' . $id;
                    ?>
                        <div class="card mb-2 shadow-sm"<?php if ($canView): ?> onclick="window.location.href='<?= $viewUrl ?>';" style="cursor:pointer;"<?php endif; ?>>
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= formatCellValue('colaborador_nome', $item['colaborador_nome'] ?? $id) ?></div>
                                <?php if (!empty($item['tipo'])): ?>
                                    <div class="small text-muted"><?= formatCellValue('tipo', $item['tipo']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($item['status'])): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['status']) ?></span>
                                <?php endif; ?>
                                <div class="d-flex gap-1 mt-2 pt-2 border-top" onclick="event.stopPropagation();">
                                    <?php if (in_array('SstRegistrarResultadosAso', $perms, true) && SstAsoStatusHelper::isAguardando($item)): ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-registrar-resultados-aso/<?= $id ?>" class="btn btn-warning btn-sm flex-fill"><i class="fas fa-clipboard-check"></i> Resultados</a>
                                    <?php elseif ($canView): ?>
                                        <a href="<?= $viewUrl ?>" class="btn btn-outline-info btn-sm flex-fill">Ver</a>
                                    <?php endif; ?>
                                    <?php if (in_array('SstUpdateAso', $perms) && !SstAsoStatusHelper::isAguardando($item)): ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-update-aso/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill">Editar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
                <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-info mb-2">
                    Nenhum ASO aguardando resultados no momento.
                </div>
                <p class="text-muted small mb-0">
                    Se há pendências no dashboard, aguarde a sincronização ou recarregue a página.
                    Use o filtro <strong>Status → Todos</strong> para ver ASOs já concluídos, ou
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-aso">cadastre manualmente</a>.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>