<?php

$path = $argv[1] ?? 'public/adms/image/pwa-badge-72.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$transparent = 0;
$white = 0;
$other = 0;
for ($y = 0; $y < $w; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $c = imagecolorat($im, $x, $y);
        $a = 127 - (($c >> 24) & 0x7F);
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        if ($a < 20) {
            $transparent++;
        } elseif ($r > 200 && $g > 200 && $b > 200) {
            $white++;
        } else {
            $other++;
        }
    }
}
echo "transparent={$transparent} white={$white} other={$other}\n";
