<?php

$path = $argv[1] ?? 'public/adms/uploads/users/1/pwa-icon-512.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$h = imagesy($im);
$points = [[0, 0], [$w - 1, 0], [0, $h - 1], [$w - 1, $h - 1], [(int) ($w / 2), (int) ($h / 2)]];
foreach ($points as $p) {
    $c = imagecolorat($im, $p[0], $p[1]);
    $a = 127 - (($c >> 24) & 0x7F);
    printf(
        "%d,%d => r=%d g=%d b=%d alpha=%d\n",
        $p[0],
        $p[1],
        ($c >> 16) & 0xFF,
        ($c >> 8) & 0xFF,
        $c & 0xFF,
        $a
    );
}
