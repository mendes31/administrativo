<?php

$im = imagecreatefrompng($argv[1] ?? 'public/adms/image/pwa-badge-96.png');
$w = imagesx($im);
$cx = (int) ($w / 2);
for ($y = $cx - 5; $y <= $cx + 5; $y++) {
    for ($x = $cx - 5; $x <= $cx + 5; $x++) {
        $c = imagecolorat($im, $x, $y);
        printf(
            "%d,%d rgba=%d,%d,%d alpha=%d\n",
            $x,
            $y,
            ($c >> 16) & 0xFF,
            ($c >> 8) & 0xFF,
            $c & 0xFF,
            127 - (($c >> 24) & 0x7F)
        );
    }
}
