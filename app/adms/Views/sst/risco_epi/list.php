<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrf_token = CSRFHelper::generateCSRFToken('form_delete_sst_risco_epi');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-link me-2"></i>EPIs por Risco</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">EPIs por Risco</li>
        </ol>
    </div>
    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Matriz Risco → EPI</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreateRiscoEpi', $perms)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-risco-epi" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <?php if (!empty($this->data['items'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead><tr>
                            <th>Risco</th><th>EPI</th><th>Obrig.</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($this->data['items'] as $item): $id = (int)($item['id'] ?? 0); ?>
                            <tr>
                                <td><?= htmlspecialchars($item['risco_nome'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['epi_nome'] ?? '-') ?></td>
                                <td><?= !empty($item['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                                <td class="text-nowrap">
                                    <?php if (in_array('SstUpdateRiscoEpi', $perms)): ?>
                                        <a href="<?= $_ENV['URL_ADM']; ?>sst-update-risco-epi/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                    <?php endif; ?>
                                    <?php if (in_array('SstDeleteRiscoEpi', $perms)): ?>
                                        <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-risco-epi" method="POST" class="d-inline" onsubmit="return confirm('Excluir?');">
                                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Nenhum vínculo cadastrado. Vincule EPIs aos riscos na visualização do risco ou cadastre aqui.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
