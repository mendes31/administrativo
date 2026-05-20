<?php

declare(strict_types=1);

/**
 * Gera ícones PWA (tela inicial + maskable) e assets de push (badge + icon).
 * Uso: php scripts/generate_pwa_notification_assets.php
 */

$root = dirname(__DIR__);
$sourceCandidates = [
    $root . '/public/adms/uploads/users/1/pwa-icon-source.png',
    $root . '/public/adms/uploads/users/1/pwa-icon-source.jpg',
    $root . '/public/adms/uploads/users/1/pwa-icon-source.jpeg',
    $root . '/public/adms/uploads/users/1/pwa-icon-512.png',
];
$source = $sourceCandidates[array_key_last($sourceCandidates)];

$sourceArg = $argv[1] ?? null;
if ($sourceArg !== null && is_file($sourceArg)) {
    $source = $sourceArg;
} else {
    foreach ($sourceCandidates as $candidate) {
        if (is_file($candidate)) {
            $source = $candidate;
            break;
        }
    }
}

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

/**
 * Máscara da folha central (BFS em pixels brancos a partir do centro).
 * Ignora o anel branco da moldura do JPEG, que não está ligado à folha.
 *
 * @return bool[] índice = y * w + x
 */
function buildCentralLeafMask(GdImage $src, int $w, int $h): array
{
    $mask = array_fill(0, $w * $h, false);
    $cx = (int) floor($w / 2);
    $cy = (int) floor($h / 2);
    $seed = null;
    $maxRadius = (int) floor(min($w, $h) / 2);

    for ($radius = 0; $radius <= $maxRadius; $radius++) {
        for ($y = max(0, $cy - $radius); $y <= min($h - 1, $cy + $radius); $y++) {
            for ($x = max(0, $cx - $radius); $x <= min($w - 1, $cx + $radius); $x++) {
                if (abs($x - $cx) + abs($y - $cy) > $radius) {
                    continue;
                }
                $rgba = imagecolorat($src, $x, $y);
                $a = 127 - (($rgba >> 24) & 0x7F);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                if (isLeafPixel($r, $g, $b, $a)) {
                    $seed = [$x, $y];
                    break 3;
                }
            }
        }
    }

    if ($seed === null) {
        return $mask;
    }

    $queue = [$seed];
    $mask[$seed[1] * $w + $seed[0]] = true;
    $dirs = [[0, 1], [0, -1], [1, 0], [-1, 0]];

    while ($queue !== []) {
        [$x, $y] = array_shift($queue);
        foreach ($dirs as [$dx, $dy]) {
            $nx = $x + $dx;
            $ny = $y + $dy;
            if ($nx < 0 || $ny < 0 || $nx >= $w || $ny >= $h) {
                continue;
            }
            $idx = $ny * $w + $nx;
            if ($mask[$idx]) {
                continue;
            }
            $rgba = imagecolorat($src, $nx, $ny);
            $a = 127 - (($rgba >> 24) & 0x7F);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (!isLeafPixel($r, $g, $b, $a)) {
                continue;
            }
            $mask[$idx] = true;
            $queue[] = [$nx, $ny];
        }
    }

    return $mask;
}

function colorRgb(int $color): int
{
    return $color & 0xFFFFFF;
}

/** Preenche o miolo da folha no destino (contorno branco + interior laranja na fonte). */
function floodFillLeafInterior(
    GdImage $dst,
    int $x0,
    int $y0,
    int $x1,
    int $y1,
    int $orangeColor,
    int $white
): void {
    $cx = (int) floor(($x0 + $x1) / 2);
    $cy = (int) floor(($y0 + $y1) / 2);
    $seed = null;

    for ($radius = 0; $radius <= max($x1 - $x0, $y1 - $y0); $radius++) {
        for ($y = max($y0, $cy - $radius); $y <= min($y1, $cy + $radius); $y++) {
            for ($x = max($x0, $cx - $radius); $x <= min($x1, $cx + $radius); $x++) {
                if (colorRgb((int) imagecolorat($dst, $x, $y)) === colorRgb($orangeColor)) {
                    $seed = [$x, $y];
                    break 3;
                }
            }
        }
    }

    if ($seed === null) {
        return;
    }

    $queue = [$seed];
    $visited = [];
    $dirs = [[0, 1], [0, -1], [1, 0], [-1, 0]];

    while ($queue !== []) {
        [$x, $y] = array_shift($queue);
        $key = $y * 10000 + $x;
        if (isset($visited[$key])) {
            continue;
        }
        $visited[$key] = true;

        if ($x < $x0 || $y < $y0 || $x > $x1 || $y > $y1) {
            continue;
        }
        if (colorRgb((int) imagecolorat($dst, $x, $y)) !== colorRgb($orangeColor)) {
            continue;
        }

        imagesetpixel($dst, $x, $y, $white);
        foreach ($dirs as [$dx, $dy]) {
            $queue[] = [$x + $dx, $y + $dy];
        }
    }
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

/** Ícone já em full-bleed laranja (cantos opacos laranja). */
function isCleanFullBleedIcon(GdImage $src, int $w, int $h): bool
{
    $orangeCorners = 0;
    $points = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1]];

    foreach ($points as [$x, $y]) {
        $rgba = imagecolorat($src, $x, $y);
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;
        if (isOrangeBackground($r, $g, $b)) {
            $orangeCorners++;
        }
    }

    return $orangeCorners >= 3;
}

/**
 * Redimensiona ícone limpo para caber em máscara circular (Android maskable ~80% útil).
 *
 * @param float $scale Fração do canvas (ex.: 1.0 = borda a borda, 0.72 = círculo seguro)
 */
function renderFromCleanIcon(GdImage $src, int $srcW, int $srcH, int $size, array $orange, float $scale): GdImage
{
    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, false);
    imagealphablending($dst, true);

    $orangeColor = imagecolorallocate($dst, $orange[0], $orange[1], $orange[2]);
    imagefill($dst, 0, 0, $orangeColor);

    $drawSize = max(1, (int) round($size * $scale));
    $x0 = (int) floor(($size - $drawSize) / 2);
    $y0 = (int) floor(($size - $drawSize) / 2);

    imagecopyresampled($dst, $src, $x0, $y0, 0, 0, $drawSize, $drawSize, $srcW, $srcH);

    return $dst;
}

/** @return array{0:int,1:int,2:int,3:int} */
function getLeafBoundingBox(array $leafMask, int $w, int $h): array
{
    $minX = $w;
    $minY = $h;
    $maxX = 0;
    $maxY = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if (!$leafMask[$y * $w + $x]) {
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
 * Ícone quadrado laranja opaco (sem canal alpha — evita borda branca no launcher).
 */
function renderFullBleedIcon(GdImage $src, int $srcW, int $srcH, int $size, array $orange, float $leafRatio, array $leafMask): GdImage
{
    [$minX, $minY, $maxX, $maxY] = getLeafBoundingBox($leafMask, $srcW, $srcH);
    $leafW = max(1, $maxX - $minX + 1);
    $leafH = max(1, $maxY - $minY + 1);

    $dst = imagecreatetruecolor($size, $size);
    imagesavealpha($dst, false);
    imagealphablending($dst, true);

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
            if ($leafMask[$sy * $srcW + $sx]) {
                imagesetpixel($dst, $dstX0 + $dx, $dstY0 + $dy, $white);
            }
        }
    }

    floodFillLeafInterior(
        $dst,
        $dstX0,
        $dstY0,
        $dstX0 + $target - 1,
        $dstY0 + $target - 1,
        $orangeColor,
        $white
    );

    return $dst;
}

/** Tamanho mestre do badge (Android usa o PNG inteiro; 192px = mais nitidez). */
const BADGE_MASTER_SIZE = 192;

/** Folha dentro do quadrado mantendo proporção (evita achatamento). */
const BADGE_LEAF_SCALE = 0.92;

/** Sem dilatação — evitava distorcer a silhueta na barra do Android. */
const BADGE_DILATE_RADIUS = 0;

function isBadgeWhitePixel(int $r, int $g, int $b): bool
{
    return $r >= 150 && $g >= 150 && $b >= 150 && !isOrangeBackground($r, $g, $b);
}

/** @return array{0:int,1:int,2:int,3:int} */
function getWhiteShapeBoundingBox(GdImage $src, int $w, int $h): array
{
    $minX = $w;
    $minY = $h;
    $maxX = 0;
    $maxY = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($src, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (!isBadgeWhitePixel($r, $g, $b)) {
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
 * Recorta a folha e redimensiona com proporção preservada (fit dentro do quadrado).
 */
function renderBadgeFitProportional(GdImage $src, int $srcW, int $srcH, int $size, float $scale = BADGE_LEAF_SCALE): GdImage
{
    [$minX, $minY, $maxX, $maxY] = getWhiteShapeBoundingBox($src, $srcW, $srcH);
    $cropW = max(1, $maxX - $minX + 1);
    $cropH = max(1, $maxY - $minY + 1);

    $maxSide = max(1, (int) round($size * $scale));
    if ($cropW >= $cropH) {
        $dstW = $maxSide;
        $dstH = max(1, (int) round($maxSide * $cropH / $cropW));
    } else {
        $dstH = $maxSide;
        $dstW = max(1, (int) round($maxSide * $cropW / $cropH));
    }

    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);

    $crop = imagecreatetruecolor($cropW, $cropH);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    imagefill($crop, 0, 0, $transparent);
    imagealphablending($crop, true);
    $white = imagecolorallocate($crop, 255, 255, 255);

    for ($y = 0; $y < $cropH; $y++) {
        for ($x = 0; $x < $cropW; $x++) {
            $rgba = imagecolorat($src, $minX + $x, $minY + $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            if (isBadgeWhitePixel($r, $g, $b)) {
                imagesetpixel($crop, $x, $y, $white);
            }
        }
    }

    imagealphablending($dst, true);
    $x0 = (int) floor(($size - $dstW) / 2);
    $y0 = (int) floor(($size - $dstH) / 2);
    imagecopyresampled($dst, $crop, $x0, $y0, 0, 0, $dstW, $dstH, $cropW, $cropH);
    imagedestroy($crop);

    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    return $dst;
}

function finalizeBadgeImage(GdImage $img, int $size): GdImage
{
    if (BADGE_DILATE_RADIUS <= 0) {
        return $img;
    }

    $white = imagecolorallocate($img, 255, 255, 255);
    $snapshot = imagecreatetruecolor($size, $size);
    imagealphablending($snapshot, false);
    imagesavealpha($snapshot, true);
    imagecopy($snapshot, $img, 0, 0, 0, 0, $size, $size);
    imagealphablending($img, true);

    for ($pass = 0; $pass < BADGE_DILATE_RADIUS; $pass++) {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $c = imagecolorat($snapshot, $x, $y);
                if (($c & 0xFF) < 200) {
                    continue;
                }
                foreach ([[0, 1], [0, -1], [1, 0], [-1, 0]] as [$dx, $dy]) {
                    $nx = $x + $dx;
                    $ny = $y + $dy;
                    if ($nx >= 0 && $ny >= 0 && $nx < $size && $ny < $size) {
                        imagesetpixel($img, $nx, $ny, $white);
                        imagesetpixel($snapshot, $nx, $ny, $white);
                    }
                }
            }
        }
    }

    imagedestroy($snapshot);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    return $img;
}

function downscaleBadge(GdImage $src, int $srcSize, int $dstSize): GdImage
{
    $dst = imagecreatetruecolor($dstSize, $dstSize);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $transparent);
    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstSize, $dstSize, $srcSize, $srcSize);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    return $dst;
}

/**
 * Gera badge 192px (principal) + versões 96 e 72.
 *
 * @param callable(int): GdImage $renderFn
 */
function exportBadgeVariants(callable $renderFn, string $root): void
{
    $master = $renderFn(BADGE_MASTER_SIZE);
    finalizeBadgeImage($master, BADGE_MASTER_SIZE);

    $out192 = $root . '/public/adms/image/pwa-badge-192.png';
    $out96 = $root . '/public/adms/image/pwa-badge-96.png';
    $out72 = $root . '/public/adms/image/pwa-badge-72.png';

    imagepng($master, $out192, 9);
    $badge96 = downscaleBadge($master, BADGE_MASTER_SIZE, 96);
    $badge72 = downscaleBadge($master, BADGE_MASTER_SIZE, 72);
    imagepng($badge96, $out96, 9);
    imagepng($badge72, $out72, 9);

    imagedestroy($master);
    imagedestroy($badge96);
    imagedestroy($badge72);

    echo "Gerado: {$out192}\n";
    echo "Gerado: {$out96}\n";
    echo "Gerado: {$out72}\n";
}

function renderBadgeFromBlackBgLeaf(GdImage $src, int $srcW, int $srcH, int $size, float $scale = BADGE_LEAF_SCALE): GdImage
{
    return renderBadgeFitProportional($src, $srcW, $srcH, $size, $scale);
}

function renderBadgeFromCleanIcon(GdImage $src, int $srcW, int $srcH, int $size, float $scale = BADGE_LEAF_SCALE): GdImage
{
    return renderBadgeFitProportional($src, $srcW, $srcH, $size, $scale);
}

function renderBadge(GdImage $src, int $srcW, int $srcH, int $size, array $leafMask): GdImage
{
    if ($leafMask !== []) {
        $tmp = imagecreatetruecolor($srcW, $srcH);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefill($tmp, 0, 0, $transparent);
        $white = imagecolorallocate($tmp, 255, 255, 255);
        for ($y = 0; $y < $srcH; $y++) {
            for ($x = 0; $x < $srcW; $x++) {
                if ($leafMask[$y * $srcW + $x]) {
                    imagesetpixel($tmp, $x, $y, $white);
                }
            }
        }
        $badge = renderBadgeFitProportional($tmp, $srcW, $srcH, $size);
        imagedestroy($tmp);

        return $badge;
    }

    return renderBadgeFitProportional($src, $srcW, $srcH, $size);
}

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') !== basename(__FILE__)) {
    return;
}

$src = loadSourceImage($source);
if ($src === false) {
    fwrite(STDERR, "Não foi possível ler imagem fonte.\n");
    exit(1);
}

$srcW = imagesx($src);
$srcH = imagesy($src);
$orange = sampleBrandOrange($src, $srcW, $srcH);
$isCleanSource = isCleanFullBleedIcon($src, $srcW, $srcH);
$leafMask = $isCleanSource ? [] : buildCentralLeafMask($src, $srcW, $srcH);

$badgeOut = $root . '/public/adms/image/pwa-badge-96.png';
$icon192Out = $root . '/public/adms/image/pwa-icon-192.png';
$icon512Out = $root . '/public/adms/uploads/users/1/pwa-icon-512.png';
$iconMaskableOut = $root . '/public/adms/image/pwa-icon-maskable-512.png';

@mkdir(dirname($badgeOut), 0775, true);
@mkdir(dirname($icon512Out), 0775, true);

$badgeSourcePath = $root . '/public/adms/image/folha-badge-source.png';
$badgeSrc = is_file($badgeSourcePath) ? loadSourceImage($badgeSourcePath) : false;

if ($isCleanSource) {
    // any: preenche quadrado; maskable: ~72% para não cortar folha no círculo Android
    $icon192 = renderFromCleanIcon($src, $srcW, $srcH, 192, $orange, 1.0);
    $icon512 = renderFromCleanIcon($src, $srcW, $srcH, 512, $orange, 1.0);
    $iconMaskable = renderFromCleanIcon($src, $srcW, $srcH, 512, $orange, 0.72);
} else {
    $icon192 = renderFullBleedIcon($src, $srcW, $srcH, 192, $orange, 0.72, $leafMask);
    $icon512 = renderFullBleedIcon($src, $srcW, $srcH, 512, $orange, 0.78, $leafMask);
    $iconMaskable = renderFullBleedIcon($src, $srcW, $srcH, 512, $orange, 0.68, $leafMask);
}

if ($badgeSrc !== false) {
    $bW = imagesx($badgeSrc);
    $bH = imagesy($badgeSrc);
    $badgeSrcCopy = $badgeSrc;
    exportBadgeVariants(
        static fn (int $size) => renderBadgeFromBlackBgLeaf($badgeSrcCopy, $bW, $bH, $size),
        $root
    );
    imagedestroy($badgeSrc);
    echo 'Badge: folha-badge-source.png (master ' . BADGE_MASTER_SIZE . 'px, escala '
        . BADGE_LEAF_SCALE . ', dilate ' . BADGE_DILATE_RADIUS . ")\n";
} elseif ($isCleanSource) {
    exportBadgeVariants(
        static fn (int $size) => renderBadgeFromCleanIcon($src, $srcW, $srcH, $size),
        $root
    );
} else {
    exportBadgeVariants(
        static fn (int $size) => renderBadge($src, $srcW, $srcH, $size, $leafMask),
        $root
    );
}

imagepng($icon192, $icon192Out, 9);
imagepng($icon512, $icon512Out, 9);
imagepng($iconMaskable, $iconMaskableOut, 9);

imagedestroy($src);
imagedestroy($icon192);
imagedestroy($icon512);
imagedestroy($iconMaskable);

$hex = sprintf('#%02x%02x%02x', $orange[0], $orange[1], $orange[2]);
echo 'Fonte: ' . basename($source) . "\n";
echo 'Modo: ' . ($isCleanSource ? 'icone_limpo (resize)' : 'extracao_folha') . "\n";
echo "Cor laranja detectada: {$hex}\n";
echo "Gerado: {$icon192Out}\n";
echo "Gerado: {$icon512Out}\n";
echo "Gerado: {$iconMaskableOut}\n";
