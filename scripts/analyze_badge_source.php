<?php

$path = $argv[1] ?? '';
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Uso: php scripts/analyze_badge_source.php <arquivo.png>\n");
    exit(1);
}

$bytes = file_get_contents($path);
if ($bytes === false) {
    fwrite(STDERR, "Não foi possível ler arquivo.\n");
    exit(1);
}

$im = @imagecreatefromstring($bytes);
if ($im === false) {
    $info = @getimagesizefromstring($bytes);
    fwrite(STDERR, 'Não foi possível decodificar imagem. tipo=' . ($info[2] ?? 'desconhecido') . "\n");
    exit(1);
}

$w = imagesx($im);
$h = imagesy($im);
$transparent = 0;
$white = 0;
$orange = 0;
$black = 0;
$other = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($im, $x, $y);
        $a = 127 - (($c >> 24) & 0x7F);
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;

        if ($a < 25) {
            $transparent++;
            continue;
        }
        if ($r > 200 && $g > 200 && $b > 200) {
            $white++;
        } elseif ($r < 40 && $g < 40 && $b < 40) {
            $black++;
        } elseif ($r >= 140 && $g >= 65 && $g <= 215 && $b <= 130 && ($r - $g) >= 25) {
            $orange++;
        } else {
            $other++;
        }
    }
}

$total = $w * $h;
echo "Arquivo: {$path}\n";
echo "Tamanho: {$w}x{$h}\n";
echo "transparent={$transparent} (" . round(100 * $transparent / $total, 1) . "%)\n";
echo "white={$white} (" . round(100 * $white / $total, 1) . "%)\n";
echo "black={$black} (" . round(100 * $black / $total, 1) . "%)\n";
echo "orange={$orange}\n";
echo "other={$other}\n";

$okBadge = $white > 50 && $transparent > ($total * 0.3) && $orange === 0 && $black < ($total * 0.1);
echo $okBadge
    ? "RESULTADO: SIM — adequada como badge Android (folha branca + fundo transparente).\n"
    : "RESULTADO: NAO — precisa ajuste antes de usar como badge.\n";
