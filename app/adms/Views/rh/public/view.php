<?php

declare(strict_types=1);

use App\adms\Helpers\FormatHelper;
use App\adms\Helpers\PositionDisplayHelper;

/**
 * @var array<string, mixed> $vaga
 * @var string $base_url
 */
$v = $vaga ?? [];
$base_url = rtrim((string) ($base_url ?? ''), '/');
$mostrarSalario = !empty($v['mostrar_salario']);
?>
<article class="vp-card">
    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
        <h2 class="mb-0"><?= htmlspecialchars((string) ($v['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if (!empty($v['tipo_contrato'])): ?>
            <span class="vp-badge"><?= htmlspecialchars((string) $v['tipo_contrato'], ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>

    <p class="vp-meta mb-3">
        <?= htmlspecialchars((string) ($v['area_nome'] ?? 'Área a definir'), ENT_QUOTES, 'UTF-8') ?>
        <?php
        $cargo = PositionDisplayHelper::formatForDisplay((string) ($v['cargo_nome'] ?? ''));
        if ($cargo !== ''): ?>
            · <?= htmlspecialchars($cargo, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
    </p>

    <dl class="row mb-3">
        <?php if (!empty($v['local_trabalho'])): ?>
            <dt class="col-sm-4">Local</dt>
            <dd class="col-sm-8"><?= htmlspecialchars((string) $v['local_trabalho'], ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <?php if (!empty($v['jornada_trabalho'])): ?>
            <dt class="col-sm-4">Jornada</dt>
            <dd class="col-sm-8"><?= htmlspecialchars((string) $v['jornada_trabalho'], ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
        <dt class="col-sm-4">Quantidade</dt>
        <dd class="col-sm-8"><?= (int) ($v['quantidade_vagas'] ?? 1) ?></dd>
        <?php if ($mostrarSalario && (!empty($v['salario_min']) || !empty($v['salario_max']))): ?>
            <dt class="col-sm-4">Faixa salarial</dt>
            <dd class="col-sm-8">
                <?php
                $salMin = !empty($v['salario_min']) ? 'R$ ' . number_format((float) $v['salario_min'], 2, ',', '.') : '';
                $salMax = !empty($v['salario_max']) ? 'R$ ' . number_format((float) $v['salario_max'], 2, ',', '.') : '';
                if ($salMin !== '' && $salMax !== '') {
                    echo htmlspecialchars($salMin . ' — ' . $salMax, ENT_QUOTES, 'UTF-8');
                } elseif ($salMin !== '') {
                    echo 'A partir de ' . htmlspecialchars($salMin, ENT_QUOTES, 'UTF-8');
                } else {
                    echo 'Até ' . htmlspecialchars($salMax, ENT_QUOTES, 'UTF-8');
                }
                ?>
            </dd>
        <?php endif; ?>
        <?php if (!empty($v['data_limite_inscricao'])): ?>
            <dt class="col-sm-4">Inscrições até</dt>
            <dd class="col-sm-8"><?= htmlspecialchars(FormatHelper::formatDateTime($v['data_limite_inscricao']), ENT_QUOTES, 'UTF-8') ?></dd>
        <?php endif; ?>
    </dl>

    <?php if (!empty($v['descricao'])): ?>
        <h3 class="h6 text-uppercase text-muted">Descrição</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['descricao'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>
    <?php if (!empty($v['requisitos'])): ?>
        <h3 class="h6 text-uppercase text-muted">Requisitos</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['requisitos'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>
    <?php if (!empty($v['beneficios'])): ?>
        <h3 class="h6 text-uppercase text-muted">Benefícios</h3>
        <div class="vp-prose mb-3"><?= nl2br(htmlspecialchars((string) $v['beneficios'], ENT_QUOTES, 'UTF-8')) ?></div>
    <?php endif; ?>

    <p class="vp-meta mb-0">
        A candidatura online ainda não está disponível nesta página.
        Em caso de interesse, entre em contato com o RH pelos canais oficiais da empresa.
    </p>
</article>

<p class="mt-3 mb-0">
    <a class="vp-link" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">&larr; Voltar às vagas</a>
</p>
