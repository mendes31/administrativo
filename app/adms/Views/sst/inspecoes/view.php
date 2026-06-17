<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$itens = $this->data['itens'] ?? [];
$users = $this->data['users'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrfItem = CSRFHelper::generateCSRFToken('sst_inspecao_itens');
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_inspecoes');
$id = (int)($item['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-search me-2"></i><?= htmlspecialchars($item['titulo'] ?? 'Inspeção') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-inspecoes">Inspeções</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Dados gerais</span>
            <span class="ms-auto">
                <?php if (in_array('SstUpdateInspecao', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-inspecao/<?= $id ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i> Editar</a><?php endif; ?>
                <?php if (in_array('SstDeleteInspecao', $perms, true)): ?>
                    <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-inspecao" method="POST" class="d-inline" onsubmit="return confirm('Excluir inspeção?');">
                        <input type="hidden" name="csrf_token" value="<?= $csrfDelete ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                    </form>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Data:</strong> <?= !empty($item['data_inspecao']) ? date('d/m/Y', strtotime($item['data_inspecao'])) : '-' ?></div>
                <div class="col-md-3"><strong>Tipo:</strong> <?= htmlspecialchars($item['tipo'] ?? '') ?></div>
                <div class="col-md-3"><strong>Status:</strong> <?= htmlspecialchars($item['status'] ?? '') ?></div>
                <div class="col-md-3"><strong>Inspetor:</strong> <?= htmlspecialchars($item['inspetor_nome'] ?? '-') ?></div>
                <div class="col-md-4 mt-2"><strong>Departamento:</strong> <?= htmlspecialchars($item['departamento_nome'] ?? '-') ?></div>
                <div class="col-md-4 mt-2"><strong>Local:</strong> <?= htmlspecialchars($item['local'] ?? '-') ?></div>
                <div class="col-md-4 mt-2"><strong>Participantes:</strong> <?= htmlspecialchars($item['participantes'] ?? '-') ?></div>
                <?php if (!empty($item['descricao'])): ?><div class="col-12 mt-2"><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($item['descricao'])) ?></div><?php endif; ?>
                <?php if (!empty($item['conclusao'])): ?><div class="col-12 mt-2"><strong>Conclusão:</strong><br><?= nl2br(htmlspecialchars($item['conclusao'])) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header">Itens verificados</div>
        <div class="card-body">
            <?php if (in_array('SstManageInspecaoItem', $perms, true)): ?>
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-inspecao-item" class="row g-2 mb-3 border-bottom pb-3">
                <input type="hidden" name="csrf_token" value="<?= $csrfItem ?>">
                <input type="hidden" name="adms_sst_inspecao_id" value="<?= $id ?>">
                <input type="hidden" name="action" value="add">
                <div class="col-md-5"><input type="text" name="descricao" class="form-control form-control-sm" placeholder="Descrição do item *" required></div>
                <div class="col-md-2">
                    <select name="classificacao" class="form-select form-select-sm">
                        <?php foreach (['Conforme', 'Não conforme', 'Observação'] as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="prazo" class="form-control form-control-sm" placeholder="Prazo"></div>
                <div class="col-md-2">
                    <select name="responsavel_adms_user_id" class="form-select form-select-sm">
                        <option value="">Responsável</option>
                        <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1"><button class="btn btn-success btn-sm w-100">+</button></div>
                <div class="col-12"><textarea name="acao_corretiva" class="form-control form-control-sm" rows="1" placeholder="Ação corretiva (se aplicável)"></textarea></div>
            </form>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Descrição</th><th>Classificação</th><th>Responsável</th><th>Prazo</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($itens as $i):
                        $cls = ($i['classificacao'] ?? '') === 'Não conforme' ? 'table-danger' : (($i['classificacao'] ?? '') === 'Observação' ? 'table-warning' : '');
                    ?>
                        <tr class="<?= $cls ?>">
                            <td><?= htmlspecialchars($i['descricao'] ?? '') ?><?php if (!empty($i['acao_corretiva'])): ?><br><small class="text-muted">Ação: <?= htmlspecialchars($i['acao_corretiva']) ?></small><?php endif; ?></td>
                            <td><?= htmlspecialchars($i['classificacao'] ?? '') ?></td>
                            <td><?= htmlspecialchars($i['responsavel_nome'] ?? '-') ?></td>
                            <td><?= !empty($i['prazo']) ? date('d/m/Y', strtotime($i['prazo'])) : '-' ?></td>
                            <td><?= htmlspecialchars($i['status'] ?? '') ?></td>
                            <td>
                                <?php if (in_array('SstManageInspecaoItem', $perms, true)): ?>
                                <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-inspecao-item" class="d-inline" onsubmit="return confirm('Remover item?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfItem ?>">
                                    <input type="hidden" name="adms_sst_inspecao_id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= (int)$i['id'] ?>">
                                    <button class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
