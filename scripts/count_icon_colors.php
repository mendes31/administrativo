<?php

$path = $argv[1] ?? 'public/adms/uploads/users/1/pwa-icon-512.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$white = 0;
$orange = 0;
for ($y = 0; $y < $w; $y += 2) {
    for ($x = 0; $x < $w; $x += 2) {
        $c = imagecolorat($im, $x, $y);
        $r = ($c >> 16) & 255;
        $g = ($c >> 8) & 255;
        $b = $c & 255;
        if ($r > 240 && $g > 240 && $b > 240) {
            $white++;
        } elseif ($r > 140 && $g > 65 && $b < 130) {
            $orange++;
        }
    }
}
echo "white={$white} orange={$orange}\n";
