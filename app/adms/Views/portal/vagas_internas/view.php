<?php
use App\adms\Helpers\FormatHelper;
$v = $this->data['vaga'] ?? [];
$form = $this->data['form'] ?? [];
$termo = $this->data['lgpd_termo'] ?? null;
$ja = !empty($this->data['ja_candidatou']);
$csrf = (string) ($this->data['csrf_token'] ?? '');
$base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
$id = (int) ($v['id'] ?? 0);
?>
<div class="container-fluid px-4">
    <div class="mb-1 hstack gap-2">
        <h2 class="mt-3"><?= htmlspecialchars((string) ($v['titulo'] ?? 'Vaga')) ?></h2>
        <ol class="breadcrumb mb-3 mt-3 ms-auto">
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>dashboard" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= htmlspecialchars($base) ?>vagas-internas" class="text-decoration-none">Vagas internas</a></li>
            <li class="breadcrumb-item active">Detalhe</li>
        </ol>
    </div>

    <?php include './app/adms/Views/partials/alerts.php'; ?>

    <div class="card mb-4 border-light shadow">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <span><i class="fas fa-briefcase me-2"></i>Vaga #<?= $id ?></span>
            <a href="<?= htmlspecialchars($base) ?>vagas-internas" class="btn btn-sm btn-outline-secondary">Voltar</a>
        </div>
        <div class="card-body">
            <dl class="row mb-3">
                <dt class="col-sm-3">Área</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($v['area_nome'] ?? '—')) ?></dd>
                <dt class="col-sm-3">Cargo</dt>
                <dd class="col-sm-9"><?= htmlspecialchars(\App\adms\Helpers\PositionDisplayHelper::formatForDisplay((string) ($v['cargo_nome'] ?? '')) ?: '—') ?></dd>
                <dt class="col-sm-3">Contrato</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($v['tipo_contrato'] ?? '—')) ?></dd>
                <dt class="col-sm-3">Local</dt>
                <dd class="col-sm-9"><?= htmlspecialchars((string) ($v['local_trabalho'] ?? '—')) ?></dd>
                <dt class="col-sm-3">Prazo</dt>
                <dd class="col-sm-9"><?= FormatHelper::formatDateTime($v['data_limite_inscricao'] ?? null) ?: 'Sem prazo' ?></dd>
                <?php if (!empty($v['mostrar_salario']) && (!empty($v['salario_min']) || !empty($v['salario_max']))): ?>
                    <dt class="col-sm-3">Faixa salarial</dt>
                    <dd class="col-sm-9">
                        <?php
                        $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float) $v['salario_min'], 2, ',', '.') : '';
                        $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float) $v['salario_max'], 2, ',', '.') : '';
                        echo htmlspecialchars(trim($salMin . ($salMin && $salMax ? ' – ' : '') . $salMax));
                        ?>
                    </dd>
                <?php endif; ?>
            </dl>

            <?php if (!empty($v['descricao'])): ?>
                <h5>Descrição</h5>
                <div class="mb-3"><?= nl2br(htmlspecialchars((string) $v['descricao'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($v['requisitos'])): ?>
                <h5>Requisitos</h5>
                <div class="mb-3"><?= nl2br(htmlspecialchars((string) $v['requisitos'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($v['beneficios'])): ?>
                <h5>Benefícios</h5>
                <div class="mb-3"><?= nl2br(htmlspecialchars((string) $v['beneficios'])) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4 border-light shadow">
        <div class="card-header"><i class="fas fa-user-check me-2"></i>Candidatar-se</div>
        <div class="card-body">
            <?php if ($ja): ?>
                <div class="alert alert-success mb-0">Você já se candidatou a esta vaga. O RH acompanhará o processo.</div>
            <?php elseif ($termo === null): ?>
                <div class="alert alert-warning mb-0">Candidatura temporariamente indisponível (termo LGPD não configurado).</div>
            <?php else: ?>
                <p class="small text-muted">
                    Usaremos nome e e-mail do seu usuário logado. Não há upload de currículo neste formulário.
                </p>
                <form method="post" action="<?= htmlspecialchars($base) ?>vagas-internas/<?= $id ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <div class="mb-3">
                        <label for="mensagem" class="form-label">Mensagem (opcional)</label>
                        <textarea name="mensagem" id="mensagem" class="form-control" rows="3"
                                  maxlength="1000"><?= htmlspecialchars((string) ($form['mensagem'] ?? '')) ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" id="lgpd_consent" name="lgpd_consent" required
                            <?= (($form['lgpd_consent'] ?? '') === '1') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="lgpd_consent">
                            Li e aceito o termo LGPD
                            <?php if (!empty($termo['titulo'])): ?>
                                (<em><?= htmlspecialchars((string) $termo['titulo']) ?></em>
                            <?php endif; ?>
                            (versão <?= htmlspecialchars((string) ($termo['versao'] ?? '1.0')) ?>).
                        </label>
                    </div>
                    <?php if (!empty($termo['conteudo'])): ?>
                        <details class="mb-3">
                            <summary class="small">Ver texto do termo</summary>
                            <div class="border rounded p-2 small bg-light mt-2" style="max-height: 200px; overflow: auto;">
                                <?= nl2br(htmlspecialchars((string) $termo['conteudo'])) ?>
                            </div>
                        </details>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-success">Enviar candidatura</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
