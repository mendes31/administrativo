<?php

declare(strict_types=1);

/**
 * Converte folha branca sobre fundo preto → badge Android (branco + transparente).
 * Uso: php scripts/convert_badge_from_leaf_png.php <origem.png> [tamanho]
 */

$source = $argv[1] ?? '';
$size = (int) ($argv[2] ?? 96);
if ($source === '' || !is_file($source)) {
    fwrite(STDERR, "Uso: php scripts/convert_badge_from_leaf_png.php <origem.png> [96|72]\n");
    exit(1);
}

$bytes = file_get_contents($source);
$src = $bytes !== false ? @imagecreatefromstring($bytes) : false;
if ($src === false) {
    fwrite(STDERR, "Imagem inválida.\n");
    exit(1);
}

$srcW = imagesx($src);
$srcH = imagesy($src);

$dst = imagecreatetruecolor($size, $size);
imagealphablending($dst, false);
imagesavealpha($dst, true);
$transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
imagefill($dst, 0, 0, $transparent);
imagealphablending($dst, true);
$white = imagecolorallocate($dst, 255, 255, 255);

$scale = 0.76;
$drawSize = max(1, (int) round($size * $scale));
$x0 = (int) floor(($size - $drawSize) / 2);
$y0 = (int) floor(($size - $drawSize) / 2);

for ($dy = 0; $dy < $drawSize; $dy++) {
    for ($dx = 0; $dx < $drawSize; $dx++) {
        $sx = (int) floor($dx * ($srcW - 1) / max(1, $drawSize - 1));
        $sy = (int) floor($dy * ($srcH - 1) / max(1, $drawSize - 1));
        $c = imagecolorat($src, $sx, $sy);
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($r >= 160 && $g >= 160 && $b >= 160) {
            imagesetpixel($dst, $x0 + $dx, $y0 + $dy, $white);
        }
    }
}

imagealphablending($dst, false);
imagesavealpha($dst, true);

$root = dirname(__DIR__);
$out96 = $root . '/public/adms/image/pwa-badge-96.png';
$out72 = $root . '/public/adms/image/pwa-badge-72.png';

if ($size === 72) {
    imagepng($dst, $out72, 9);
    echo "Gerado: {$out72}\n";
} else {
    imagepng($dst, $out96, 9);
    echo "Gerado: {$out96}\n";
    $dst72 = imagecreatetruecolor(72, 72);
    imagealphablending($dst72, false);
    imagesavealpha($dst72, true);
    imagefill($dst72, 0, 0, $transparent);
    imagecopyresampled($dst72, $dst, 0, 0, 0, 0, 72, 72, $size, $size);
    imagepng($dst72, $out72, 9);
    imagedestroy($dst72);
    echo "Gerado: {$out72}\n";
}

imagedestroy($src);
imagedestroy($dst);
