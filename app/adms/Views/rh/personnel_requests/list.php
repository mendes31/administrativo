<?php
/** Listagem de requisições de pessoal (Talentos). */
use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\CSRFHelper;
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Requisições de Pessoal</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item">RH</li>
            <li class="breadcrumb-item active">Requisições</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2 flex-wrap">
            <span><i class="fas fa-user-plus me-2"></i>Listar Requisições</span>
            <span class="ms-auto">
                <a href="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests-create" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus"></i> Nova requisição
                </a>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>

            <form method="GET" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <?php
                        $st = $this->data['filters']['status'] ?? '';
                        $opts = [
                            '' => 'Todos',
                            'pending_approval' => 'Pendente',
                            'approved' => 'Aprovada',
                            'rejected' => 'Rejeitada',
                            'converted' => 'Convertida em vaga',
                        ];
                        foreach ($opts as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $st === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Área</label>
                    <select name="area_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach (($this->data['departments'] ?? []) as $dept): ?>
                            <option value="<?= (int) $dept['id'] ?>" <?= ((string) ($this->data['filters']['area_id'] ?? '') === (string) $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Solicitante</th>
                            <th>Área</th>
                            <th>Cargo</th>
                            <th>Qtd</th>
                            <th>Status</th>
                            <th>Criada em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($this->data['requests'])): ?>
                            <tr><td colspan="8" class="text-muted">Nenhuma requisição encontrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($this->data['requests'] as $r): ?>
                                <tr>
                                    <td>#<?= (int) $r['id'] ?></td>
                                    <td><?= htmlspecialchars($r['requester_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['area_nome'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) ($r['cargo_nome'] ?? '')) ?: '-') ?></td>
                                    <td><?= (int) ($r['quantidade'] ?? 1) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['status'] ?? '') ?></span></td>
                                    <td><?= FormatHelper::formatDateTime($r['created_at'] ?? null) ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-info" href="<?php echo $_ENV['URL_ADM']; ?>rh-personnel-requests-view/<?= (int) $r['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
