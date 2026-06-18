<?php
$report = $this->data['report'] ?? [];
$filters = $this->data['filters'] ?? [];
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><i class="fas fa-notes-medical me-2"></i>Relatório por CID</h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item active">Relatório CID</li>
        </ol>
    </div>
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Início de</label>
                    <input type="date" name="data_inicio_de" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['data_inicio_de'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Início até</label>
                    <input type="date" name="data_inicio_ate" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['data_inicio_ate'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Capítulo</label>
                    <select name="capitulo_num" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($this->data['capitulos'] ?? [] as $cap): ?>
                            <option value="<?= (int)$cap['num'] ?>" <?= ((string)($filters['capitulo_num'] ?? '') === (string)$cap['num']) ? 'selected' : '' ?>>
                                <?= (int)$cap['num'] ?> — <?= htmlspecialchars($cap['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Natureza</label>
                    <select name="natureza" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach (\App\adms\Helpers\SstNaturezaLesaoHelper::all() as $n): ?>
                            <option value="<?= htmlspecialchars($n) ?>" <?= (($filters['natureza'] ?? '') === $n) ? 'selected' : '' ?>><?= htmlspecialchars($n) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filtrar</button>
                    <a href="<?= $_ENV['URL_ADM']; ?>sst-report-cids" class="btn btn-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header">Top 15 CIDs (afastamentos)</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>CID</th><th>Descrição</th><th class="text-end">Qtd</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['top_cids'] ?? [] as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['codigo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['descricao'] ?? '') ?></td>
                                <td class="text-end"><?= (int)($r['total_afastamentos'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($report['top_cids'])): ?><tr><td colspan="3" class="text-muted p-3">Sem dados no período.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header">Dias perdidos por CID</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>CID</th><th>Descrição</th><th class="text-end">Dias</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['dias_por_cid'] ?? [] as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['codigo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($r['descricao'] ?? '') ?></td>
                                <td class="text-end"><?= (int)($r['total_dias'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($report['dias_por_cid'])): ?><tr><td colspan="3" class="text-muted p-3">Sem dados no período.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header">Por capítulo CID-10</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Cap.</th><th>Nome</th><th class="text-end">Afast.</th><th class="text-end">Dias</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['por_capitulo'] ?? [] as $r): ?>
                            <tr>
                                <td><?= (int)($r['capitulo_num'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($r['capitulo_nome'] ?? '') ?></td>
                                <td class="text-end"><?= (int)($r['total_afastamentos'] ?? 0) ?></td>
                                <td class="text-end"><?= (int)($r['total_dias'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header">Por setor (departamento)</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr><th>Setor</th><th class="text-end">Afast.</th><th class="text-end">Dias</th></tr></thead>
                        <tbody>
                        <?php foreach ($report['por_setor'] ?? [] as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['departamento_nome'] ?? 'Sem setor') ?></td>
                                <td class="text-end"><?= (int)($r['total_afastamentos'] ?? 0) ?></td>
                                <td class="text-end"><?= (int)($r['total_dias'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
