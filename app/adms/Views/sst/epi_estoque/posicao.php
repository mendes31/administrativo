<?php
use App\adms\Helpers\SstEpiTamanhoHelper;

$items = $this->data['items'] ?? [];
$filters = $this->data['filters'] ?? [];
$perms = $this->data['buttonPermission'] ?? [];
$urlAdm = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
?>
<div class="container-fluid px-3 px-md-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3 mobile-hide-page-title"><i class="fas fa-boxes me-2"></i>Posição de estoque EPI</h2>
        <ol class="breadcrumb mb-3 ms-auto mobile-hide-breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $urlAdm ?>sst-dashboard">SST</a></li>
            <li class="breadcrumb-item"><a href="<?= $urlAdm ?>sst-list-epis">EPIs</a></li>
            <li class="breadcrumb-item active">Estoque</li>
        </ol>
    </div>
    <div class="card shadow-sm">
        <div class="card-header hstack gap-2 flex-wrap">
            <span>Saldo por EPI, tamanho e CA</span>
            <span class="ms-auto d-flex gap-1 flex-wrap">
                <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                <a href="<?= $urlAdm ?>sst-create-epi-movimento?tipo=Entrada" class="btn btn-success btn-sm"><i class="fas fa-arrow-down"></i> Entrada</a>
                <?php endif; ?>
                <?php if (in_array('SstListEpiMovimentos', $perms, true)): ?>
                <a href="<?= $urlAdm ?>sst-list-epi-movimentos" class="btn btn-outline-secondary btn-sm">Histórico</a>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php include './app/adms/Views/partials/alerts.php'; ?>
            <form method="get" class="row g-2 mb-3 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Nome do EPI" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="estoque_baixo" value="1" id="eb" <?= !empty($filters['estoque_baixo']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="eb">Só estoque baixo</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>EPI</th>
                            <th>Tamanho / nº</th>
                            <th>Saldo</th>
                            <th>Mín.</th>
                            <th>CA (neste tamanho)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $r):
                        $id = (int) ($r['id'] ?? 0);
                        $saldo = (int) ($r['estoque_calculado'] ?? $r['estoque_atual'] ?? 0);
                        $min = (int) ($r['estoque_minimo'] ?? 0);
                        $baixo = !empty($r['estoque_baixo']);
                        $saldosTam = $r['saldos_tamanho'] ?? [];
                        $controla = !empty($r['controla_tamanho']);
                    ?>
                        <tr class="<?= $baixo ? 'table-warning' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($r['nome'] ?? '') ?></strong>
                                <div class="small text-muted"><?= htmlspecialchars($r['categoria'] ?? '') ?>
                                    <?php if ($min > 0): ?>
                                        · <?= $controla ? 'mín. padrão ' . $min . ' / tam.' : 'mín. ' . $min ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-muted">Total</td>
                            <td class="fw-semibold"><?= $saldo ?><?php if ($baixo): ?> <span class="badge bg-warning text-dark">Comprar</span><?php endif; ?></td>
                            <td class="small text-muted"><?= $controla ? 'por nº' : ($min > 0 ? (string) $min : '—') ?></td>
                            <td class="small text-muted">—</td>
                            <td class="text-nowrap">
                                <?php if (in_array('SstCreateEpiMovimento', $perms, true)): ?>
                                <a href="<?= $urlAdm ?>sst-create-epi-movimento?adms_sst_epi_id=<?= $id ?>&tipo=Entrada" class="btn btn-sm btn-outline-success">+</a>
                                <?php endif; ?>
                                <a href="<?= $urlAdm ?>sst-view-epi/<?= $id ?>" class="btn btn-sm btn-outline-secondary">EPI</a>
                            </td>
                        </tr>
                        <?php foreach ($saldosTam as $st):
                            $saldoTam = (int) ($st['saldo'] ?? 0);
                            $minTam = (int) ($st['minimo'] ?? 0);
                            $baixoTam = !empty($st['estoque_baixo']);
                            if ($saldoTam === 0 && $minTam <= 0) {
                                continue;
                            }
                            $casTxt = [];
                            foreach ($st['cas'] ?? [] as $c) {
                                $casTxt[] = ($c['ca_numero'] ?? '') . ': ' . (int) ($c['saldo'] ?? 0);
                            }
                        ?>
                        <tr class="<?= $baixoTam ? 'table-warning' : '' ?>">
                            <td class="ps-4 small text-muted"><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                            <td><strong><?= htmlspecialchars(SstEpiTamanhoHelper::label($st['tamanho'] ?? '')) ?></strong></td>
                            <td><?= $saldoTam ?><?php if ($baixoTam): ?> <span class="badge bg-warning text-dark">Comprar</span><?php endif; ?></td>
                            <td><?= $minTam > 0 ? $minTam : '—' ?></td>
                            <td class="small"><?= htmlspecialchars($casTxt !== [] ? implode(' · ', $casTxt) : '—') ?></td>
                            <td></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0 mt-2">
                O cadastro continua único (ex.: Calçado de segurança). A numeração aparece nas linhas abaixo do total.
                Com grade, o alerta <strong>Comprar</strong> é por tamanho (mínimo padrão ou o valor específico do número).
                EPIs sem grade usam o mínimo no total. O saldo sai das movimentações (entrada/saída/ficha).
            </p>
        </div>
    </div>
</div>
