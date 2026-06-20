<?php
use App\adms\Helpers\CSRFHelper;
$vistoria = $this->data['vistoria'] ?? [];
$respostas = $this->data['respostas'] ?? [];
$readonly = !empty($this->data['readonly']);
$csrfToken = CSRFHelper::generateCSRFToken('sst_equipamento_vistoria');
$id = (int)($vistoria['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <?php include './app/adms/Views/partials/alerts.php'; ?>
    <div class="mb-1 hstack gap-2 flex-wrap">
        <h2 class="mt-3"><i class="fas fa-clipboard-check me-2"></i>Vistoria <?= htmlspecialchars($vistoria['competencia'] ?? '') ?></h2>
        <ol class="breadcrumb mb-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias">Minhas vistorias</a></li>
            <li class="breadcrumb-item active">Executar</li>
        </ol>
    </div>
    <div class="card mb-3 shadow-sm">
        <div class="card-body row">
            <div class="col-md-3"><strong>Equipamento:</strong><br><?= htmlspecialchars($vistoria['equipamento_codigo'] ?? '') ?></div>
            <div class="col-md-3"><strong>Tipo:</strong><br><?= htmlspecialchars($vistoria['tipo_nome'] ?? '') ?></div>
            <div class="col-md-3"><strong>Local:</strong><br><?= htmlspecialchars($vistoria['localizacao'] ?? '-') ?></div>
            <div class="col-md-3"><strong>Status:</strong><br><?= htmlspecialchars($vistoria['status'] ?? '') ?><?php if (!empty($vistoria['resultado'])): ?> / <?= htmlspecialchars($vistoria['resultado']) ?><?php endif; ?></div>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header"><?= $readonly ? 'Checklist registrado' : 'Checklist de inspeção' ?></div>
        <div class="card-body">
            <form method="POST" action="<?= $_ENV['URL_ADM']; ?>sst-execute-equipamento-vistoria/<?= $id ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Item</th><th>Resposta</th><th>Observação</th></tr></thead>
                        <tbody>
                        <?php foreach ($respostas as $r): ?>
                            <tr class="<?= ($r['resposta'] ?? '') === 'Não conforme' ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($r['descricao_snapshot'] ?? '') ?></td>
                                <td>
                                    <?php if ($readonly): ?>
                                        <?= htmlspecialchars($r['resposta'] ?? '-') ?>
                                    <?php else: ?>
                                        <select name="resposta[<?= (int)$r['id'] ?>]" class="form-select form-select-sm" required>
                                            <option value="">—</option>
                                            <?php foreach (['Conforme', 'Não conforme', 'N/A'] as $opt): ?>
                                            <option value="<?= $opt ?>" <?= ($r['resposta'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($readonly): ?>
                                        <?= htmlspecialchars($r['observacao'] ?? '') ?>
                                    <?php else: ?>
                                        <input type="text" name="obs_item[<?= (int)$r['id'] ?>]" class="form-control form-control-sm" value="<?= htmlspecialchars($r['observacao'] ?? '') ?>">
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="observacao">Observação geral</label>
                    <?php if ($readonly): ?>
                        <p class="form-control-plaintext"><?= nl2br(htmlspecialchars($vistoria['observacao'] ?? '-')) ?></p>
                    <?php else: ?>
                        <textarea name="observacao" id="observacao" class="form-control" rows="2"><?= htmlspecialchars($vistoria['observacao'] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>
                <?php if (!$readonly): ?>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Concluir vistoria</button>
                <?php endif; ?>
                <a href="<?= $_ENV['URL_ADM']; ?>sst-minhas-equipamento-vistorias" class="btn btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
</div>
