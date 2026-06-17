<?php
use App\adms\Helpers\CSRFHelper;
$perms = $this->data['buttonPermission'] ?? [];
$csrfDelete = CSRFHelper::generateCSRFToken('form_delete_sst_programas');
$hoje = date('Y-m-d');
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-file-contract me-2"></i>Programas (PGR / PCMSO)</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item">Programas</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Listar</span>
            <span class="ms-auto">
                <?php if (in_array('SstCreatePrograma', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-create-programa" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3"><label class="form-label small">Busca</label><input type="text" name="search" class="form-control form-control-sm" value="<?= htmlspecialchars($this->data['filters']['search'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small">Tipo</label><select name="tipo" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach (['PGR', 'PCMSO', 'PPRA', 'LTCAT', 'Outro'] as $t): ?><option value="<?= $t ?>" <?= ($this->data['filters']['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-2"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="">Todos</option>
                    <?php foreach (['Rascunho', 'Vigente', 'Revogado'] as $s): ?><option value="<?= $s ?>" <?= ($this->data['filters']['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-2"><label class="form-label small">Vigência</label><select name="vigencia" class="form-select form-select-sm"><option value="">Todas</option><option value="vencendo" <?= ($this->data['filters']['vigencia'] ?? '') === 'vencendo' ? 'selected' : '' ?>>A vencer (60d)</option><option value="vencido" <?= ($this->data['filters']['vigencia'] ?? '') === 'vencido' ? 'selected' : '' ?>>Vencidos</option></select></div>
                <div class="col-auto"><button class="btn btn-primary btn-sm">Filtrar</button></div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Tipo</th><th>Título</th><th>Versão</th><th>Vigência</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($this->data['items'] ?? [] as $r):
                        $fim = $r['vigencia_fim'] ?? null;
                        $rowClass = $fim && $fim < $hoje ? 'table-danger' : ($fim && $fim <= date('Y-m-d', strtotime('+60 days')) ? 'table-warning' : '');
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['tipo'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($r['titulo'] ?? '') ?></td>
                            <td><?= htmlspecialchars($r['versao'] ?? '-') ?></td>
                            <td><?= !empty($r['vigencia_inicio']) ? date('d/m/Y', strtotime($r['vigencia_inicio'])) : '-' ?> — <?= !empty($r['vigencia_fim']) ? date('d/m/Y', strtotime($r['vigencia_fim'])) : 'indeterminado' ?></td>
                            <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstViewPrograma', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-view-programa/<?= (int)$r['id'] ?>" class="btn btn-info btn-sm"><i class="fa-regular fa-eye"></i></a><?php endif; ?>
                                <?php if (in_array('SstUpdatePrograma', $perms, true)): ?><a href="<?= $_ENV['URL_ADM']; ?>sst-update-programa/<?= (int)$r['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a><?php endif; ?>
                                <?php if (in_array('SstDeletePrograma', $perms, true)): ?>
                                    <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-programa" method="POST" class="d-inline" onsubmit="return confirm('Excluir programa?');">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfDelete ?>">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-2 d-none d-md-flex"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <div class="d-flex justify-content-center mt-2 d-md-none"><?= $this->data['pagination']['html'] ?? '' ?></div>
        </div>
    </div>
</div>
