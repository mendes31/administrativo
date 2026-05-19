<?php
/** @var list<array<string, mixed>> $birthdayMonthItems */
/** @var string $urlAdm */
$urlAdm = rtrim((string) ($urlAdm ?? $_ENV['URL_ADM'] ?? ''), '/') . '/';
$birthdayMonthItems = $birthdayMonthItems ?? [];
foreach ($birthdayMonthItems as $aniv):
?>
<div class="col-12 col-md-6 col-lg-4 d-flex aniversariante-mes-item">
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
        <div class="text-muted small mt-1 birthday-date-pill">
            <i class="fas fa-birthday-cake text-warning me-1"></i>
            <span class="fw-bold" style="color:#ff9800;"><?php echo htmlspecialchars((string) ($aniv['aniversario'] ?? '')); ?></span>
        </div>
        <div class="mt-2">
            <a href="<?php echo htmlspecialchars($urlAdm); ?>timeline-profile/<?php echo (int) ($aniv['id'] ?? 0); ?>?from=birthday"
               class="btn btn-outline-primary btn-sm">
                <i class="fas fa-comment-dots me-1"></i>Parabenizar na Timeline
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
