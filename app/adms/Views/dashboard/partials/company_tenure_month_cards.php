<?php
/** @var list<array<string, mixed>> $companyTenureMonthItems */
/** @var string $urlAdm */
$urlAdm = rtrim((string) ($urlAdm ?? $_ENV['URL_ADM'] ?? ''), '/') . '/';
$companyTenureMonthItems = $companyTenureMonthItems ?? [];
foreach ($companyTenureMonthItems as $aniv):
    $anosEmpresa = isset($aniv['anos_empresa']) ? (int) $aniv['anos_empresa'] : null;
?>
<div class="col-12 col-md-6 col-lg-4 d-flex aniversariante-empresa-mes-item">
    <div class="card birthday-person-card text-center p-4 flex-fill d-flex flex-column align-items-center justify-content-center">
        <div class="mb-2">
            <?php if (\App\adms\Helpers\ImageHelper::userImageExists((int) ($aniv['id'] ?? 0), (string) ($aniv['image'] ?? ''))): ?>
                <?php
                $avatarPath = 'users/' . $aniv['id'] . '/' . $aniv['image'];
                echo \App\adms\Helpers\ImageHelper::displayImage($avatarPath, [
                    'class' => 'rounded-circle mb-2 birthday-avatar',
                    'style' => 'width: 86px; height: 86px; object-fit: cover;',
                ], 'icon_user.png', 'users');
                ?>
            <?php else: ?>
                <?php echo \App\adms\Helpers\ImageHelper::renderInitialsAvatar((string) ($aniv['name'] ?? ''), 86, [
                    'class' => 'mb-2 birthday-avatar',
                ]); ?>
            <?php endif; ?>
        </div>
        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars((string) ($aniv['name'] ?? '')); ?></h6>
        <div class="text-muted small mb-1"><?php echo htmlspecialchars((string) ($aniv['departamento'] ?? '')); ?></div>
        <div class="text-muted small mt-1">
            <i class="fas fa-briefcase text-primary me-1"></i>
            <span class="fw-bold" style="color:#1976d2;">
                <?php echo htmlspecialchars((string) ($aniv['aniversario_empresa'] ?? '')); ?>
            </span>
            <?php if ($anosEmpresa === 0): ?>
                <br>
                <span class="small fw-semibold"
                      style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:999px;background:#e8f5e9;color:#2e7d32;">
                    Novo Colaborador
                </span>
            <?php elseif ($anosEmpresa !== null && $anosEmpresa > 0): ?>
                <br>
                <span class="small text-muted">
                    <?php echo $anosEmpresa; ?> ano(s) de casa
                </span>
            <?php endif; ?>
            <div class="mt-2">
                <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-profile/<?php echo (int) ($aniv['id'] ?? 0); ?>?from=tenure&amp;years=<?php echo $anosEmpresa === null ? '' : (int) $anosEmpresa; ?>"
                   class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-comment-dots me-1"></i>Reconhecer na Timeline
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
