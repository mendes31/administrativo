<?php
use App\adms\Helpers\CSRFHelper;
$csrf = $this->data['csrf_token'] ?? CSRFHelper::generateCSRFToken('form_delete_fin_cash_investment');
$filtros = $this->data['filtros'] ?? [];
$typeLabels = [
    'APPLICATION' => 'Aplicação',
    'REDEMPTION' => 'Resgate',
    'YIELD' => 'Rendimento',
];
$catLabels = [
    'STANDARD' => 'Padrão',
    'GUARANTEE' => 'Garantia',
];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3">Aplicações financeiras</h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo $_ENV['URL_ADM']; ?>fin-cash-flow-dashboard" class="text-decoration-none">Fluxo de Caixa SAP</a></li>
            <li class="breadcrumb-item">Aplicações</li>
        </ol>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header hstack gap-2">
            <span>Lançamentos locais (aplicação, resgate e rendimento)</span>
            <span class="ms-auto d-flex gap-2">
                <?php if (in_array('FinCashFlowDashboard', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>fin-cash-flow-dashboard" class="btn btn-info btn-sm"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
                <?php endif; ?>
                <?php if (in_array('CreateFinCashInvestment', $this->data['buttonPermission'] ?? [], true)): ?>
                    <a href="<?php echo $_ENV['URL_ADM']; ?>create-fin-cash-investment" class="btn btn-success btn-sm"><i class="fa-regular fa-square-plus"></i> Cadastrar</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <p class="text-muted small">O SAP registra principalmente os rendimentos mensais. Use esta tela para lançar aplicações e resgates, que entram no saldo de aplicações do dashboard.</p>

            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1" for="bank_label">Banco</label>
                    <input type="text" name="bank_label" id="bank_label" class="form-control" value="<?= htmlspecialchars((string) ($filtros['bank_label'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="movement_type">Tipo</label>
                    <select name="movement_type" id="movement_type" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($typeLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($filtros['movement_type'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="category">Categoria</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($catLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= (($filtros['category'] ?? '') === $k) ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="date_from">De</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars((string) ($filtros['date_from'] ?? '')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="date_to">Até</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars((string) ($filtros['date_to'] ?? '')) ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>

            <?php if (!empty($this->data['investments'])): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Banco</th>
                            <th>Tipo</th>
                            <th>Categoria</th>
                            <th class="text-end">Valor</th>
                            <th>Descrição</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($this->data['investments'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime((string) $row['movement_date']))) ?></td>
                            <td><?= htmlspecialchars((string) $row['bank_label']) ?></td>
                            <td><?= htmlspecialchars($typeLabels[$row['movement_type']] ?? (string) $row['movement_type']) ?></td>
                            <td><?= htmlspecialchars($catLabels[$row['category']] ?? (string) $row['category']) ?></td>
                            <td class="text-end <?= ($row['movement_type'] ?? '') === 'REDEMPTION' ? 'text-danger' : 'text-success' ?>">
                                R$ <?= number_format((float) $row['amount'], 2, ',', '.') ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($row['description'] ?? '')) ?></td>
                            <td class="text-center">
                                <?php if (in_array('UpdateFinCashInvestment', $this->data['buttonPermission'] ?? [], true)): ?>
                                    <a href="<?php echo $_ENV['URL_ADM']; ?>update-fin-cash-investment/<?= (int) $row['id'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <?php endif; ?>
                                <?php if (in_array('DeleteFinCashInvestment', $this->data['buttonPermission'] ?? [], true)): ?>
                                    <form method="POST" action="<?php echo $_ENV['URL_ADM']; ?>delete-fin-cash-investment" class="d-inline" onsubmit="return confirm('Excluir este lançamento?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-2"><?= $this->data['pagination']['html'] ?? '' ?></div>
            <?php else: ?>
                <p>Nenhum lançamento encontrado. Cadastre aplicações, resgates e rendimentos para compor o saldo mensal.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
