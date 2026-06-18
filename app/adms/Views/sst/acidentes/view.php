<?php
use App\adms\Helpers\CSRFHelper;

$item = $this->data['item'];
$perms = $this->data['buttonPermission'] ?? [];
$planos = $this->data['planos_acao'] ?? [];
$planosPendentes = (int) ($this->data['planos_pendentes'] ?? 0);
$csrfDeletePlano = CSRFHelper::generateCSRFToken('form_delete_sst_planos_acao');
$csrfEsocial = CSRFHelper::generateCSRFToken('sst_esocial_actions');
$uid = (int) ($item['adms_user_id'] ?? 0);
$hoje = date('Y-m-d');

function formatCellValue(string $col, mixed $value): string {
    if ($value === null || $value === '') return '-';
    if (str_contains($col, 'data_') && is_string($value)) return strlen($value) > 10 ? date('d/m/Y H:i', strtotime($value)) : date('d/m/Y', strtotime($value));
    return htmlspecialchars((string)$value);
}

$statusBadge = match ($item['status'] ?? '') {
    'Encerrado' => 'success',
    'Em investigação' => 'warning',
    default => 'danger',
};
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-ambulance me-2"></i>Acidente/Incidente #<?= (int)$item['id'] ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-list-acidentes">Acidentes</a></li>
            <li class="breadcrumb-item active">#<?= (int)$item['id'] ?></li>
        </ol>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <span class="badge bg-<?= $statusBadge ?> me-2"><?= htmlspecialchars($item['status'] ?? '') ?></span>
                <strong><?= htmlspecialchars($item['tipo'] ?? '') ?></strong>
                <span class="text-muted ms-2"><?= formatCellValue('data_ocorrencia', $item['data_ocorrencia'] ?? null) ?></span>
            </div>
            <div class="d-flex flex-wrap gap-1">
                <?php if (in_array('SstEmployeeProfile', $perms, true) && $uid > 0): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-employee-profile/<?= $uid ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-user-shield"></i> Perfil SST</a>
                <?php endif; ?>
                <?php if (in_array('SstGenerateEsocialEvento', $perms, true)): ?>
                    <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-generate-esocial-evento" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $csrfEsocial ?>">
                        <input type="hidden" name="tipo_evento" value="S-2210">
                        <input type="hidden" name="origem_tabela" value="adms_sst_acidentes">
                        <input type="hidden" name="origem_id" value="<?= (int)$item['id'] ?>">
                        <input type="hidden" name="redirect" value="<?= $_ENV['URL_ADM']; ?>sst-view-acidente/<?= (int)$item['id'] ?>">
                        <button type="submit" class="btn btn-outline-info btn-sm"><i class="fas fa-cloud"></i> eSocial S-2210</button>
                    </form>
                <?php endif; ?>
                <?php if (in_array('SstUpdateAcidente', $perms, true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-update-acidente/<?= (int)$item['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Editar</a>
                <?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-list-acidentes" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-2"><div class="text-muted small">Colaborador</div><div class="fw-semibold"><?= htmlspecialchars($item['colaborador_nome'] ?? '-') ?></div></div></div></div>
        <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-2"><div class="text-muted small">Local</div><div><?= htmlspecialchars($item['local'] ?? '-') ?></div></div></div></div>
        <div class="col-md-4"><div class="card h-100 border-0 shadow-sm"><div class="card-body py-2"><div class="text-muted small">Planos de ação</div><div class="fw-semibold"><?= count($planos) ?> total · <?= $planosPendentes ?> pendente(s)</div></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0">Ocorrência</h5></div>
                <div class="card-body"><table class="table table-sm mb-0">
                    <tr><th width="30%">CID</th><td><?= htmlspecialchars($item['cid_nome'] ?? '-') ?></td></tr>
                    <tr><th>Natureza / nexo</th><td><?= htmlspecialchars($item['natureza'] ?? '-') ?></td></tr>
                    <tr><th>Parte do corpo</th><td><?= htmlspecialchars($item['parte_corpo'] ?? '-') ?></td></tr>
                    <tr><th>Descrição</th><td><?= nl2br(htmlspecialchars($item['descricao'] ?? '-')) ?></td></tr>
                    <tr><th>Nº CAT</th><td><?= formatCellValue('cat_numero', $item['cat_numero'] ?? null) ?></td></tr>
                    <tr><th>Data CAT</th><td><?= formatCellValue('cat_data', $item['cat_data'] ?? null) ?></td></tr>
                </table></div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Investigação</h5>
                    <?php if (in_array('SstUpdateAcidente', $perms, true)): ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-update-acidente/<?= (int)$item['id'] ?>" class="btn btn-outline-warning btn-sm">Editar investigação</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($item['investigacao'])): ?>
                        <div class="mb-0"><?= nl2br(htmlspecialchars($item['investigacao'])) ?></div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Investigação ainda não registrada. Altere o status para "Em investigação" e preencha os achados.</p>
                    <?php endif; ?>
                    <?php if (!empty($item['plano_acao'])): ?>
                        <hr class="my-3">
                        <div class="small text-muted mb-1">Resumo legado (texto livre)</div>
                        <div><?= nl2br(htmlspecialchars($item['plano_acao'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Planos de ação</h5>
                    <?php if (in_array('SstCreatePlanoAcao', $perms, true)): ?>
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-create-plano-acao?adms_sst_acidente_id=<?= (int)$item['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nova ação</a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($planos)): ?>
                        <p class="text-muted p-3 mb-0">Nenhum plano de ação cadastrado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Ação</th><th>Responsável</th><th>Prazo</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($planos as $p):
                                    $prazo = $p['prazo'] ?? null;
                                    $vencido = $prazo && $prazo < $hoje && !in_array($p['status'] ?? '', ['Concluído', 'Cancelado'], true);
                                    $statusPlano = match ($p['status'] ?? '') {
                                        'Concluído' => 'success',
                                        'Em andamento' => 'primary',
                                        'Cancelado' => 'secondary',
                                        default => $vencido ? 'danger' : 'warning',
                                    };
                                ?>
                                    <tr class="<?= $vencido ? 'table-danger' : '' ?>">
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($p['titulo'] ?? '') ?></div>
                                            <?php if (!empty($p['descricao'])): ?><div class="small text-muted"><?= htmlspecialchars(mb_substr((string)$p['descricao'], 0, 80)) ?></div><?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['responsavel_nome'] ?? '-') ?></td>
                                        <td><?= $prazo ? date('d/m/Y', strtotime($prazo)) : '-' ?><?php if ($vencido): ?> <span class="badge bg-danger">Vencido</span><?php endif; ?></td>
                                        <td><span class="badge bg-<?= $statusPlano ?>"><?= htmlspecialchars($p['status'] ?? '') ?></span></td>
                                        <td class="text-nowrap">
                                            <?php if (in_array('SstUpdatePlanoAcao', $perms, true)): ?>
                                                <a href="<?= $_ENV['URL_ADM']; ?>sst-update-plano-acao/<?= (int)$p['id'] ?>" class="btn btn-warning btn-sm"><i class="fa-regular fa-pen-to-square"></i></a>
                                            <?php endif; ?>
                                            <?php if (in_array('SstDeletePlanoAcao', $perms, true)): ?>
                                                <form action="<?= $_ENV['URL_ADM']; ?>sst-delete-plano-acao" method="POST" class="d-inline" onsubmit="return confirm('Excluir plano de ação?');">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfDeletePlano ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                                    <input type="hidden" name="adms_sst_acidente_id" value="<?= (int)$item['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-paperclip me-2"></i>Anexos</h5></div>
                <div class="card-body">
                    <?php if (empty($this->data['anexos'])): ?>
                        <p class="text-muted mb-0">Nenhum anexo.</p>
                    <?php else: foreach ($this->data['anexos'] as $anexo): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span><?= htmlspecialchars($anexo['file_name'] ?? '') ?></span>
                            <small class="text-muted"><?= !empty($anexo['created_at']) ? date('d/m/Y H:i', strtotime($anexo['created_at'])) : '' ?></small>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h6 class="mb-0">Fluxo da investigação</h6></div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item d-flex justify-content-between align-items-start <?= ($item['status'] ?? '') === 'Aberto' ? 'list-group-item-primary' : '' ?>">
                            <span>Registro aberto</span>
                            <?php if (($item['status'] ?? '') === 'Aberto'): ?><span class="badge bg-primary">Atual</span><?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start <?= ($item['status'] ?? '') === 'Em investigação' ? 'list-group-item-warning' : '' ?>">
                            <span>Em investigação</span>
                            <?php if (($item['status'] ?? '') === 'Em investigação'): ?><span class="badge bg-warning text-dark">Atual</span><?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start <?= ($item['status'] ?? '') === 'Encerrado' ? 'list-group-item-success' : '' ?>">
                            <span>Encerrado</span>
                            <?php if (($item['status'] ?? '') === 'Encerrado'): ?><span class="badge bg-success">Atual</span><?php endif; ?>
                        </li>
                    </ol>
                    <?php if ($planosPendentes > 0 && ($item['status'] ?? '') === 'Encerrado'): ?>
                        <div class="alert alert-warning py-2 mt-3 mb-0 small">Há <?= $planosPendentes ?> plano(s) ainda pendente(s) com o acidente encerrado.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($this->data['log_resumo'])): $log_resumo = $this->data['log_resumo']; $log_btn_class = 'btn btn-outline-info w-100 mb-4'; include './app/adms/Views/partials/button_log_alteracoes.php'; endif; ?>
            <div class="card shadow-sm">
                <div class="card-body small text-muted">
                    Cadastrado: <?= !empty($item['created_at']) ? date('d/m/Y H:i', strtotime($item['created_at'])) : '-' ?><br>
                    Atualizado: <?= !empty($item['updated_at']) ? date('d/m/Y H:i', strtotime($item['updated_at'])) : '-' ?>
                </div>
            </div>
        </div>
    </div>
</div>
