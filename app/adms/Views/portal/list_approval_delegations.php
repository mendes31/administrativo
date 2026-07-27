<?php
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\FormatHelper;
$delegations = $this->data['delegations'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Delegações de Aprovação</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>pending-approvals" class="text-decoration-none">Aprovações</a></li>
            <li class="breadcrumb-item">Delegações</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span><i class="fas fa-user-clock me-2"></i>Ausência do gestor → substituto temporário</span>
            <span class="ms-auto">
                <?php if (in_array('CreateApprovalDelegation', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?= $_ENV['URL_ADM']; ?>create-approval-delegation" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i>Nova delegação
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">
                Enquanto a delegação estiver vigente, o substituto aprova no lugar do gestor.
                Se não houver delegação e o prazo da etapa vencer, o sistema escala para o nível acima e, por último, ao RH.
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Gestor</th>
                            <th>Substituto</th>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Obs.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($delegations)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma delegação cadastrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($delegations as $row): ?>
                                <?php $active = strtotime((string) $row['ends_at']) >= time(); ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['delegator_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['delegate_name'] ?? '') ?></td>
                                    <td><?= FormatHelper::formatDate($row['starts_at'] ?? '') ?></td>
                                    <td>
                                        <?= FormatHelper::formatDate($row['ends_at'] ?? '') ?>
                                        <?php if ($active): ?>
                                            <span class="badge bg-success ms-1">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary ms-1">Encerrada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['notes'] ?? '') ?></td>
                                    <td class="text-end">
                                        <?php if (in_array('DeleteApprovalDelegation', $this->data['buttonPermission'] ?? [], true) && $active): ?>
                                            <form method="post" action="<?= $_ENV['URL_ADM']; ?>delete-approval-delegation/<?= (int) $row['id'] ?>" class="d-inline"
                                                  onsubmit="return confirm('Remover esta delegação?');">
                                                <input type="hidden" name="csrf_token" value="<?= CSRFHelper::generateCSRFToken('form_delete_approval_delegation'); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        <?php endif; ?>
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
