<?php
use App\adms\Helpers\CSRFHelper;
$item = $this->data['item'] ?? [];
$membros = $this->data['membros'] ?? [];
$reunioes = $this->data['reunioes'] ?? [];
$users = $this->data['users'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$csrfManage = CSRFHelper::generateCSRFToken('sst_cipa_manage');
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_cipa');
$id = (int)($item['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-users-cog me-2"></i><?= htmlspecialchars($item['titulo'] ?? 'Mandato CIPA') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-cipa-mandatos">CIPA</a></li>
            <li class="breadcrumb-item active">Mandato</li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-header hstack gap-2">
            <span>Dados do mandato</span>
            <span class="ms-auto">
                <?php if (in_array('SstUpdateCipaMandato', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-cipa-mandato/<?= $id ?>" class="btn btn-warning btn-sm">Editar</a><?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Status:</strong> <?= htmlspecialchars($item['status'] ?? '') ?></div>
                <div class="col-md-4"><strong>Início:</strong> <?= !empty($item['data_inicio']) ? date('d/m/Y', strtotime($item['data_inicio'])) : '-' ?></div>
                <div class="col-md-4"><strong>Fim:</strong> <?= !empty($item['data_fim']) ? date('d/m/Y', strtotime($item['data_fim'])) : '—' ?></div>
                <?php if (!empty($item['observacoes'])): ?><div class="col-12 mt-2"><?= nl2br(htmlspecialchars($item['observacoes'])) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">Membros</div>
                <div class="card-body">
                    <?php if (in_array('SstManageCipa', $perms, true)): ?>
                    <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-cipa" class="row g-2 mb-3">
                        <input type="hidden" name="csrf_token" value="<?= $csrfManage ?>">
                        <input type="hidden" name="adms_sst_cipa_mandato_id" value="<?= $id ?>">
                        <input type="hidden" name="entity" value="membro">
                        <input type="hidden" name="action" value="add">
                        <div class="col-md-6">
                            <select name="adms_user_id" class="form-select form-select-sm" required>
                                <option value="">Colaborador</option>
                                <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['name'] ?? '') ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="cargo" class="form-select form-select-sm">
                                <?php foreach (['Presidente', 'Vice-presidente', 'Secretário', 'Titular', 'Suplente'] as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><button class="btn btn-success btn-sm w-100">+</button></div>
                    </form>
                    <?php endif; ?>
                    <table class="table table-sm">
                        <thead><tr><th>Nome</th><th>Cargo</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($membros as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['colaborador_nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($m['cargo'] ?? '') ?></td>
                                <td>
                                    <?php if (in_array('SstManageCipa', $perms, true)): ?>
                                    <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-cipa" class="d-inline" onsubmit="return confirm('Remover membro?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfManage ?>">
                                        <input type="hidden" name="adms_sst_cipa_mandato_id" value="<?= $id ?>">
                                        <input type="hidden" name="entity" value="membro">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item_id" value="<?= (int)$m['id'] ?>">
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
        <div class="col-lg-6 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-header">Reuniões / atas</div>
                <div class="card-body">
                    <?php if (in_array('SstManageCipa', $perms, true)): ?>
                    <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-cipa" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= $csrfManage ?>">
                        <input type="hidden" name="adms_sst_cipa_mandato_id" value="<?= $id ?>">
                        <input type="hidden" name="entity" value="reuniao">
                        <input type="hidden" name="action" value="add">
                        <div class="row g-2">
                            <div class="col-md-4"><input type="datetime-local" name="data_reuniao" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i') ?>" required></div>
                            <div class="col-md-3">
                                <select name="tipo" class="form-select form-select-sm">
                                    <option value="Ordinária">Ordinária</option>
                                    <option value="Extraordinária">Extraordinária</option>
                                </select>
                            </div>
                            <div class="col-md-5"><input type="text" name="pauta" class="form-control form-control-sm" placeholder="Pauta"></div>
                            <div class="col-12"><textarea name="ata" class="form-control form-control-sm" rows="2" placeholder="Ata / deliberações"></textarea></div>
                            <div class="col-12"><button class="btn btn-success btn-sm">Registrar reunião</button></div>
                        </div>
                    </form>
                    <?php endif; ?>
                    <?php foreach ($reunioes as $r): ?>
                        <div class="border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong><?= !empty($r['data_reuniao']) ? date('d/m/Y H:i', strtotime($r['data_reuniao'])) : '-' ?></strong>
                                <span class="badge bg-secondary"><?= htmlspecialchars($r['tipo'] ?? '') ?></span>
                            </div>
                            <?php if (!empty($r['pauta'])): ?><div class="small mt-1"><strong>Pauta:</strong> <?= htmlspecialchars($r['pauta']) ?></div><?php endif; ?>
                            <?php if (!empty($r['ata'])): ?><div class="small mt-1"><?= nl2br(htmlspecialchars($r['ata'])) ?></div><?php endif; ?>
                            <?php if (in_array('SstManageCipa', $perms, true)): ?>
                            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-manage-cipa" class="mt-1" onsubmit="return confirm('Remover reunião?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfManage ?>">
                                <input type="hidden" name="adms_sst_cipa_mandato_id" value="<?= $id ?>">
                                <input type="hidden" name="entity" value="reuniao">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-outline-danger btn-sm">Excluir</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
