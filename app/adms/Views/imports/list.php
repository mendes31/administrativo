<?php

use App\adms\Models\Services\Imports\ImportProfileInterface;

/** @var list<ImportProfileInterface> $profiles */
$profiles = $this->data['profiles'] ?? [];
$jobs = $this->data['jobs'] ?? [];
$urlAdm = $_ENV['URL_ADM'] ?? '';

$profileGroups = [
    'Cadastro geral' => [],
    'SST — catálogos' => [],
    'SST — matrizes' => [],
];
foreach ($profiles as $profile) {
    $k = $profile->key();
    if (
        $k === 'sst_riscos_cargo'
        || str_starts_with($k, 'sst_risco_')
        || str_ends_with($k, '_necessidade')
    ) {
        $profileGroups['SST — matrizes'][] = $profile;
    } elseif (str_starts_with($k, 'sst_')) {
        $profileGroups['SST — catálogos'][] = $profile;
    } else {
        $profileGroups['Cadastro geral'][] = $profile;
    }
}

$opLabel = [
    'insert' => 'Só inserir',
    'update' => 'Só atualizar',
    'upsert' => 'Inserir e atualizar',
];
$statusClass = [
    'uploaded' => 'secondary',
    'running' => 'info',
    'done' => 'success',
    'failed' => 'danger',
];
?>

<div class="container-fluid px-4">
    <div class="mb-1 d-flex flex-column flex-sm-row gap-2">
        <h2 class="mt-3">Central de Importações</h2>
        <ol class="breadcrumb mb-3 mt-0 mt-sm-3 ms-auto">
            <li class="breadcrumb-item"><a class="text-decoration-none" href="<?php echo $urlAdm; ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item">Importações</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">Tipos disponíveis</div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <?php if ($profiles === []): ?>
                <div class="alert alert-warning mb-0">
                    Nenhum tipo de importação liberado no seu nível de acesso. Peça as permissões
                    <em>ImportCenterUsers</em>, <em>ImportCenterDepartments</em>, <em>ImportCenterPositions</em> ou <em>ImportCenterSst</em>.
                </div>
            <?php else: ?>
                <p class="text-muted small">
                    Selecione o tipo e continue. Importe por perfil declarado (não por colunas cruas da tabela). Use simulação antes de gravar.
                    A importação clássica de usuários (<a href="<?php echo $urlAdm; ?>import-users">Importar Usuários</a>) continua disponível.
                </p>
                <form method="get" action="<?php echo htmlspecialchars($urlAdm . 'import-center-create', ENT_QUOTES, 'UTF-8'); ?>" class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-5">
                        <label class="form-label" for="profile">Tipo de importação</label>
                        <select class="form-select" name="profile" id="profile" required>
                            <option value="">Selecione…</option>
                            <?php foreach ($profileGroups as $groupLabel => $groupProfiles): ?>
                                <?php if ($groupProfiles === []) { continue; } ?>
                                <optgroup label="<?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php foreach ($groupProfiles as $profile): ?>
                                        <option value="<?php echo htmlspecialchars($profile->key(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($profile->label(), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-arrow-right"></i> Continuar
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-secondary" formaction="<?php echo htmlspecialchars($urlAdm . 'import-center-template', ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-solid fa-download"></i> Modelo CSV
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header">Jobs recentes</div>
        <div class="card-body">
            <?php if ($jobs === []): ?>
                <p class="text-muted mb-0">Nenhuma importação registrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Arquivo</th>
                                <th>Operação</th>
                                <th>Status</th>
                                <th>Quando</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job):
                                $st = (string) ($job['status'] ?? '');
                                $badge = $statusClass[$st] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><?php echo (int) $job['id']; ?></td>
                                    <td><?php echo htmlspecialchars((string) ($job['profile_key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($job['original_filename'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($job['dry_run'])): ?><span class="badge text-bg-warning">simulação</span><?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($opLabel[$job['operation'] ?? ''] ?? (string) ($job['operation'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="badge text-bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($st, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string) ($job['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (in_array($st, ['done', 'failed'], true)): ?>
                                            <a class="btn btn-outline-primary btn-sm" href="<?php echo $urlAdm; ?>import-center-view/<?php echo (int) $job['id']; ?>">Resultado</a>
                                        <?php elseif ($st === 'uploaded'): ?>
                                            <a class="btn btn-outline-secondary btn-sm" href="<?php echo $urlAdm; ?>import-center-map/<?php echo (int) $job['id']; ?>">Mapear</a>
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
</div>
