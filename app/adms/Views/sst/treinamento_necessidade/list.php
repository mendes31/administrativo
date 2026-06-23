<?php
use App\adms\Helpers\CSRFHelper;

$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_treinamento_necessidade');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-clipboard-list me-2"></i>Necessidades de Treinamento SST</h2>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateTreinamentoNecessidade', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-treinamento-necessidade" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3"><input type="text" name="search" class="form-control form-control-sm" placeholder="Pesquisar" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <?php if (!empty($this->data['items'])): ?>
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr><th>Cargo</th><th>Departamento</th><th>Treinamento</th><th>Validade</th><th>Obrig.</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item): $id = (int)($item['id'] ?? 0); ?>
                            <tr>
                                <td><?= htmlspecialchars($item['cargo_nome'] ?? 'Todos') ?></td>
                                <td><?= htmlspecialchars($item['departamento_nome'] ?? 'Todos') ?></td>
                                <td><?= htmlspecialchars($item['treinamento_nome'] ?? '-') ?></td>
                                <td><?= !empty($item['validade_meses']) ? (int)$item['validade_meses'] . ' meses' : '-' ?></td>
                                <td><?= !empty($item['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                                <td class="text-nowrap">
                                    <?php if (in_array('SstUpdateTreinamentoNecessidade', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-treinamento-necessidade/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                    <?php if (in_array('SstDeleteTreinamentoNecessidade', $perms)): ?>
                                        <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-treinamento-necessidade" method="POST" class="d-inline" onsubmit="return confirm('Excluir?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>"><input type="hidden" name="id" value="<?= $id ?>">
                                            <button class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-block d-md-none">
                    <?php foreach ($this->data['items'] as $item): $id = (int)($item['id'] ?? 0); ?>
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body py-2 px-3">
                                <div class="fw-bold small"><?= htmlspecialchars($item['treinamento_nome'] ?? '') ?></div>
                                <div class="small text-muted"><?= htmlspecialchars($item['cargo_nome'] ?? 'Todos') ?> / <?= htmlspecialchars($item['departamento_nome'] ?? 'Todos') ?></div>
                                <div class="d-flex gap-1 mt-2">
                                    <?php if (in_array('SstUpdateTreinamentoNecessidade', $perms)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-treinamento-necessidade/<?= $id ?>" class="btn btn-outline-warning btn-sm flex-fill">Editar</a><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning">Nenhum registro encontrado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
