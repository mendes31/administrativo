<?php

declare(strict_types=1);

/**
 * Converte folha branca sobre fundo preto → badges Android (192 + 96 + 72).
 * Uso: php scripts/convert_badge_from_leaf_png.php <origem.png>
 */

$source = $argv[1] ?? '';
if ($source === '' || !is_file($source)) {
    fwrite(STDERR, "Uso: php scripts/convert_badge_from_leaf_png.php <origem.png>\n");
    exit(1);
}

passthru('php ' . escapeshellarg(__DIR__ . '/generate_pwa_notification_assets.php'), $code);
exit($code);
