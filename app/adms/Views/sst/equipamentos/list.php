<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\SstEquipamentoPeriodicidadeHelper;
use App\adms\Helpers\SstEquipamentoRecargaHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_equipamentos');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-fire-extinguisher me-2"></i>Equipamentos de segurança</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Equipamentos</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Cadastro</span>
            <span class="ms-auto d-flex gap-1">
                <?php if (in_array('SstScanEquipamento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-scan-equipamento" class="btn btn-success btn-sm"><i class="fas fa-qrcode"></i> Ler QR</a>
                <?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias" class="btn btn-outline-primary btn-sm"><i class="fas fa-tasks"></i> Minhas vistorias</a>
                <?php if (in_array('SstCreateEquipamento', $perms, true)): ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-create-equipamento" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Novo</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3"><label class="form-label small">Busca</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Grupo</label><select name="tipo_id" class="form-select form-select-sm"><option value="">Todos</option><?php foreach ($this->data['tipos'] ?? [] as $t): ?><option value="<?= (int)$t['id'] ?>" <?= (string)($this->data['filters']['adms_sst_equipamento_tipo_id'] ?? '') === (string)$t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nome']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label small">Filial</label><select name="empresa_contratante" class="form-select form-select-sm"><option value="">Todas</option><?php foreach ($this->data['empresas_contratantes'] ?? [] as $slug => $empLabel): ?><option value="<?= htmlspecialchars((string)$slug) ?>" <?= (string)($this->data['filters']['empresa_contratante'] ?? '') === (string)$slug ? 'selected' : '' ?>><?= htmlspecialchars((string)$empLabel) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option><?php foreach (['Ativo','Inativo','Baixado'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2">
                    <label class="form-label small">Recarga</label>
                    <select name="recarga_alerta" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" <?= !empty($this->data['filters']['recarga_alerta']) ? 'selected' : '' ?>>Vencida / a vencer (30d)</option>
                    </select>
                </div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Código</th><th>Grupo</th><th>Filial</th><th>Localização</th><th>Periodicidade</th><th>Próx. recarga</th><th>Responsável</th><th>Pend.</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r):
                        $st = SstEquipamentoRecargaHelper::status(
                            $r['data_proxima_recarga'] ?? null,
                            !empty($r['controla_recarga'])
                        );
                    ?>
                        <tr class="<?= $st === 'vencido' ? 'table-danger' : ($st === 'a_vencer' ? 'table-warning' : '') ?>">
                            <td><strong><?= htmlspecialchars($r['codigo'] ?? '') ?></strong></td>
                            <td><?= htmlspecialchars($r['tipo_nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars(\App\adms\Helpers\UserFormHelper::empresaContratanteLabel($r['empresa_contratante'] ?? null)) ?></td>
                            <td><?= htmlspecialchars($r['localizacao'] ?? '-') ?></td>
                            <td><?= htmlspecialchars(SstEquipamentoPeriodicidadeHelper::label((int)($r['periodicidade_meses'] ?? 1))) ?></td>
                            <td>
                                <?php if ($st === null): ?>
                                    <span class="text-muted">—</span>
                                <?php elseif ($st === 'sem_data'): ?>
                                    <span class="badge bg-secondary">Sem data</span>
                                <?php else: ?>
                                    <?= !empty($r['data_proxima_recarga']) ? date('d/m/Y', strtotime($r['data_proxima_recarga'])) : '—' ?>
                                    <span class="badge <?= SstEquipamentoRecargaHelper::statusBadgeClass($st) ?>"><?= htmlspecialchars(SstEquipamentoRecargaHelper::statusLabel($st)) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['responsavel_nome'] ?? '—') ?></td>
                            <td><?= (int)($r['vistorias_pendentes'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewEquipamento', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-equipamento/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstUpdateEquipamento', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-equipamento/<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>
