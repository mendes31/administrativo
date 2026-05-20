<?php

declare(strict_types=1);

/**
 * Gera ícones PWA (tela inicial + maskable) e assets de push (badge + icon).
 * Uso: php scripts/generate_pwa_notification_assets.php
 */

$root = dirname(__DIR__);
$source = $root . '/public/adms/uploads/users/1/pwa-icon-512.png';

if (!is_file($source)) {
    fwrite(STDERR, "Arquivo fonte não encontrado: {$source}\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extensão GD não disponível.\n");
    exit(1);
}

function loadSourceImage(string $path)
{
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
    return $r >= 140 && $g >= 65 && $g <= 215 && $b <= 130 && ($r - $g) >= 25;
}

function isLeafPixel(int $r, int $g, int $b, int $opacity = 255): bool
{
    if ($opacity < 40) {
        return false;
    }

    if (isOrangeBackground($r, $g, $b)) {
        return false;
    }

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);

    return $max >= 155 && ($max - $min) <= 110;
}

/** @return array{0:int,1:int,2:int} */
function sampleBrandOrange(GdImage $src, int $w, int $h): array
{
    $rs = [];
    $gs = [];
    $bs = [];

    for ($y = 0; $y < $h; $y += 4) {
        for ($x = 0; $x < $w; $x += 4) {
            $rgba = imagecolorat($src, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (isOrangeBackground($r, $g, $b)) {
                $rs[] = $r;
                $gs[] = $g;
                $bs[] = $b;
            }
        }
    }

    if ($rs === []) {
        return [232, 119, 34];
    }

    sort($rs);
    sort($gs);
    sort($bs);
    $mid = (int) floor(count($rs) / 2);

    return [$rs[$mid], $gs[$mid], $bs[$mid]];
}

/** @return array{0:int,1:int,2:int,3:int} */
function getLeafBoundingBox(GdImage $src, int $w, int $h): array
{
    $minX = $w;
    $minY = $h;
    $maxX = 0;
    $maxY = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $a = 127 - (($rgba >> 24) & 0x7F);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (!isLeafPixel($r, $g, $b, $a)) {
                continue;
            }
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }

    if ($maxX <= $minX) {
        return [0, 0, $w - 1, $h - 1];
    }

    return [$minX, $minY, $maxX, $maxY];
}

/**
 * Ícone quadrado com laranja de ponta a ponta (preenche círculo do launcher Android).
 */
function renderFullBleedIcon(GdImage $src, int $srcW, int $srcH, int $size, array $orange, float $leafRatio): GdImage
{
    [$minX, $minY, $maxX, $maxY] = getLeafBoundingBox($src, $srcW, $srcH);
    $leafW = max(1, $maxX - $minX + 1);
    $leafH = max(1, $maxY - $minY + 1);

    $dst = imagecreatetruecolor($size, $size);
    $orangeColor = imagecolorallocate($dst, $orange[0], $orange[1], $orange[2]);
    imagefill($dst, 0, 0, $orangeColor);
    $white = imagecolorallocate($dst, 255, 255, 255);

    $target = (int) round($size * $leafRatio);
    $dstX0 = (int) floor(($size - $target) / 2);
    $dstY0 = (int) floor(($size - $target) / 2);

    for ($dy = 0; $dy < $target; $dy++) {
        for ($dx = 0; $dx < $target; $dx++) {
            $sx = $minX + (int) floor($dx * ($leafW - 1) / max(1, $target - 1));
            $sy = $minY + (int) floor($dy * ($leafH - 1) / max(1, $target - 1));
            $rgba = imagecolorat($src, $sx, $sy);
            $a = 127 - (($rgba >> 24) & 0x7F);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (isLeafPixel($r, $g, $b, $a)) {
                imagesetpixel($dst, $dstX0 + $dx, $dstY0 + $dy, $white);
            }
        }
    }

    return $dst;
}

function renderBadge(GdImage $src, int $srcW, int $srcH, int $size): GdImage
{
    [$minX, $minY, $maxX, $maxY] = getLeafBoundingBox($src, $srcW, $srcH);
    $leafW = max(1, $maxX - $minX + 1);
    $leafH = max(1, $maxY - $minY + 1);

    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    $white = imagecolorallocatealpha($dst, 255, 255, 255, 0);

    $padding = (int) max(2, round($size * 0.08));
    $target = $size - ($padding * 2);
    $dstX0 = $padding;
    $dstY0 = $padding;

    for ($dy = 0; $dy < $target; $dy++) {
        for ($dx = 0; $dx < $target; $dx++) {
            $sx = $minX + (int) floor($dx * ($leafW - 1) / max(1, $target - 1));
            $sy = $minY + (int) floor($dy * ($leafH - 1) / max(1, $target - 1));
            $rgba = imagecolorat($src, $sx, $sy);
            $a = 127 - (($rgba >> 24) & 0x7F);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (isLeafPixel($r, $g, $b, $a)) {
                imagesetpixel($dst, $dstX0 + $dx, $dstY0 + $dy, $white);
            }
        }
    }

    return $dst;
}

$src = loadSourceImage($source);
if ($src === false) {
    fwrite(STDERR, "Não foi possível ler imagem fonte.\n");
    exit(1);
}

$srcW = imagesx($src);
$srcH = imagesy($src);
$orange = sampleBrandOrange($src, $srcW, $srcH);

$badgeOut = $root . '/public/adms/image/pwa-badge-96.png';
$icon192Out = $root . '/public/adms/image/pwa-icon-192.png';
$icon512Out = $root . '/public/adms/uploads/users/1/pwa-icon-512.png';
$iconMaskableOut = $root . '/public/adms/image/pwa-icon-maskable-512.png';

@mkdir(dirname($badgeOut), 0775, true);
@mkdir(dirname($icon512Out), 0775, true);

$icon192 = renderFullBleedIcon($src, $srcW, $srcH, 192, $orange, 0.72);
$icon512 = renderFullBleedIcon($src, $srcW, $srcH, 512, $orange, 0.78);
$iconMaskable = renderFullBleedIcon($src, $srcW, $srcH, 512, $orange, 0.58);
$badge96 = renderBadge($src, $srcW, $srcH, 96);
$badge72 = renderBadge($src, $srcW, $srcH, 72);

imagepng($badge96, $badgeOut, 9);
imagepng($badge72, $root . '/public/adms/image/pwa-badge-72.png', 9);
imagepng($icon192, $icon192Out, 9);
imagepng($icon512, $icon512Out, 9);
imagepng($iconMaskable, $iconMaskableOut, 9);

imagedestroy($src);
imagedestroy($badge96);
imagedestroy($badge72);
imagedestroy($icon192);
imagedestroy($icon512);
imagedestroy($iconMaskable);

$hex = sprintf('#%02x%02x%02x', $orange[0], $orange[1], $orange[2]);
echo "Cor laranja detectada: {$hex}\n";
echo "Gerado: {$badgeOut}\n";
echo "Gerado: {$root}/public/adms/image/pwa-badge-72.png\n";
echo "Gerado: {$icon192Out}\n";
echo "Gerado: {$icon512Out}\n";
echo "Gerado: {$iconMaskableOut}\n";
