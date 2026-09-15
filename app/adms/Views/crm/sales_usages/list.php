<?php
use App\adms\Models\Repository\crm\CrmSalesUsageNatureRepository;

$usages = $this->data['usages'] ?? [];
$natures = $this->data['natures'] ?? CrmSalesUsageNatureRepository::NATURES;
$unclassified = (int) ($this->data['unclassified'] ?? 0);
$csrf = htmlspecialchars((string) ($this->data['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8');
$dashUrl = htmlspecialchars(($_ENV['URL_ADM'] ?? '') . 'crm-sales-dashboard', ENT_QUOTES, 'UTF-8');
?>

<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-3 flex-wrap gap-2">
        <h1 class="mt-2 mb-0">
            <i class="fas fa-tags text-success me-2"></i>
            Utilizações de venda SAP
        </h1>
        <a href="<?= $dashUrl ?>" class="btn btn-outline-success">
            <i class="fas fa-chart-area me-1"></i>Dashboard de Vendas SAP
        </a>
    </div>

    <p class="text-muted">
        O sync SAP traz <strong>todas</strong> as utilizações (OUSG). Aqui você define se cada uma é venda, bonificação, brinde ou deve ser ignorada.
        Utilizações novas entram como <em>não classificada</em> e, até você classificar, entram nos cards de faturamento como venda.
        Alterar a natureza <strong>não exige</strong> nova sincronização.
    </p>

    <div class="alert alert-info">
        Utilizações cujo nome começa com <strong>E</strong> (ex.: E Amostra, E Demonstração, E Outras entradas) são <em>entradas</em> no SAP, não o fluxo comercial de saída.
        No dashboard elas só aparecem se a linha estiver numa fatura (OINV) ou devolução (ORIN).
        Recomendação: classifique como <strong>Ignorar</strong>, exceto <strong>E Dev Venda</strong> — essa é a devolução comercial e deve permanecer como <strong>Venda</strong> para somar no card de devoluções.
        Use o botão <em>Sugerir entradas (E*)</em> e revise antes de salvar outras alterações.
    </div>

    <?php if ($unclassified > 0): ?>
        <div class="alert alert-warning">
            <?= $unclassified ?> utilização(ões) ainda não classificada(s). Classifique para separar bonificações e brindes dos cards de faturamento.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if ($usages === []): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">Nenhuma utilização no cache ainda. Rode o sync completo:
                        <code>php scripts/sync_crm_sales_sap.php --full</code>
                    </p>
                </div>
            <?php else: ?>
                <form method="post" action="<?= htmlspecialchars(($_ENV['URL_ADM'] ?? '') . 'crm-list-sales-usages', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width:90px;">Código</th>
                                    <th>Utilização (SAP)</th>
                                    <th style="min-width:280px;">Natureza no dashboard</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usages as $row): ?>
                                    <?php
                                    $id = (int) ($row['usage_id'] ?? 0);
                                    $rawName = (string) ($row['usage_name'] ?? '');
                                    $name = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');
                                    $nat = (string) ($row['natureza'] ?? 'nao_classificada');
                                    $isEntrada = CrmSalesUsageNatureRepository::isEntradaUsage($rawName);
                                    $sug = CrmSalesUsageNatureRepository::suggestEntradasNature($rawName);
                                    ?>
                                    <tr<?= $isEntrada ? ' class="table-warning"' : '' ?>>
                                        <td><code><?= $id ?></code></td>
                                        <td>
                                            <?= $name !== '' ? $name : 'Sem utilização' ?>
                                            <?php if ($isEntrada): ?>
                                                <span class="badge text-bg-warning ms-1">Entrada</span>
                                                <?php if ($sug === 'venda'): ?>
                                                    <span class="badge text-bg-success">sugerir venda</span>
                                                <?php elseif ($sug === 'ignorar'): ?>
                                                    <span class="badge text-bg-secondary">sugerir ignorar</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="natureza[<?= $id ?>]">
                                                <?php foreach ($natures as $value => $label): ?>
                                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                                                        <?= $nat === $value ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success" name="acao" value="salvar">
                            <i class="fas fa-save me-1"></i>Salvar naturezas
                        </button>
                        <button type="submit" class="btn btn-outline-warning" name="acao" value="sugerir_entradas"
                            onclick="return confirm('Aplicar agora: E Dev Venda = Venda; demais utilizações iniciadas com E = Ignorar?');">
                            <i class="fas fa-magic me-1"></i>Sugerir entradas (E*)
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
