<?php
/** @var list<array<string, mixed>> $riscosRelacionados */
$riscosRelacionados = $riscosRelacionados ?? [];
if ($riscosRelacionados === []) {
    return;
}
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header"><h5 class="mb-0">Riscos relacionados</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Risco</th><th>Código</th><th>Obrigatório</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($riscosRelacionados as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nome'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['codigo'] ?? '-') ?></td>
                    <td><?= !empty($r['obrigatorio']) ? 'Sim' : 'Não' ?></td>
                    <td class="text-end">
                        <a href="<?= $_ENV['URL_ADM']; ?>sst-view-risco/<?= (int)($r['id'] ?? 0) ?>#tab-treinamentos" class="btn btn-outline-primary btn-sm">Ver risco</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted small px-3 py-2 mb-0">Vínculos configurados na tela de cada risco (aba Treinamentos).</p>
    </div>
</div>
