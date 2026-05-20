<?php

declare(strict_types=1);

/**
 * Gera assets de notificação push a partir do ícone PWA colorido.
 * Uso: php scripts/generate_pwa_notification_assets.php
 */

$root = dirname(__DIR__);
$source = $root . '/public/adms/uploads/users/1/pwa-icon-512.png';
$badgeOut = $root . '/public/adms/image/pwa-badge-96.png';
$icon192Out = $root . '/public/adms/image/pwa-icon-192.png';

if (!is_file($source)) {
    fwrite(STDERR, "Arquivo fonte não encontrado: {$source}\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extensão GD não disponível.\n");
    exit(1);
}

$src = loadSourceImage($source);
if ($src === false) {
    fwrite(STDERR, "Não foi possível ler imagem fonte (PNG/JPEG/WebP): {$source}\n");
    exit(1);
}

$srcW = imagesx($src);
$srcH = imagesy($src);

function loadSourceImage(string $path)
{
    if (!is_file($path)) {
        return false;
    }

    $bytes = file_get_contents($path);
    if ($bytes === false) {
        return false;
    }

    $img = @imagecreatefromstring($bytes);
    if ($img !== false) {
        return $img;
    }

    $info = @getimagesize($path);
    if ($info === false) {
        return false;
    }

    return match ($info[2]) {
        IMAGETYPE_PNG => imagecreatefrompng($path),
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
        default => false,
    };
}

function isOrangeBackground(int $r, int $g, int $b): bool
{
    return $r >= 150 && $g >= 70 && $g <= 210 && $b <= 120 && ($r - $g) >= 30;
}

function isLeafPixel(int $r, int $g, int $b, int $a): bool
{
    if ($a < 20) {
        return false;
    }

    if (isOrangeBackground($r, $g, $b)) {
        return false;
    }

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);

    if ($max >= 200 && ($max - $min) <= 55) {
        return true;
    }

    if ($max >= 160 && ($max - $min) <= 100 && $r >= $g && $g >= $b) {
        return true;
    }

    return false;
}

function leafAlpha(int $r, int $g, int $b): int
{
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $whiteness = ($max / 255) * (1 - min(1, ($max - $min) / 120));

    return (int) max(0, min(127, 127 - round($whiteness * 127)));
}

function renderBadge($src, int $srcW, int $srcH, int $size): GdImage
{
    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    $white = imagecolorallocatealpha($dst, 255, 255, 255, 0);

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            $sx = (int) floor($x * ($srcW - 1) / max(1, $size - 1));
            $sy = (int) floor($y * ($srcH - 1) / max(1, $size - 1));
            $rgba = imagecolorat($src, $sx, $sy);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            if (!isLeafPixel($r, $g, $b, 127 - $a)) {
                continue;
            }

            $alpha = leafAlpha($r, $g, $b);
            if ($alpha >= 120) {
                continue;
            }

            if ($alpha <= 5) {
                imagesetpixel($dst, $x, $y, $white);
            } else {
                $px = imagecolorallocatealpha($dst, 255, 255, 255, $alpha);
                imagesetpixel($dst, $x, $y, $px);
            }
        }
    }

    return $dst;
}

function renderResized($src, int $srcW, int $srcH, int $size): GdImage
{
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $srcW, $srcH);

    return $dst;
}

$icon512Out = $root . '/public/adms/uploads/users/1/pwa-icon-512.png';

@mkdir(dirname($badgeOut), 0775, true);
@mkdir(dirname($icon512Out), 0775, true);

$badge96 = renderBadge($src, $srcW, $srcH, 96);
$badge72 = renderBadge($src, $srcW, $srcH, 72);
$icon192 = renderResized($src, $srcW, $srcH, 192);
$icon512 = renderResized($src, $srcW, $srcH, 512);

imagepng($badge96, $badgeOut, 9);
imagepng($badge72, $root . '/public/adms/image/pwa-badge-72.png', 9);
imagepng($icon192, $icon192Out, 9);
imagepng($icon512, $icon512Out, 9);

imagedestroy($src);
imagedestroy($badge96);
imagedestroy($badge72);
imagedestroy($icon192);
imagedestroy($icon512);

echo "Gerado: {$badgeOut}\n";
echo "Gerado: {$root}/public/adms/image/pwa-badge-72.png\n";
echo "Gerado: {$icon192Out}\n";
echo "Gerado: {$icon512Out}\n";
