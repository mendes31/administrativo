<?php
$path = $argv[1] ?? 'public/adms/image/pwa-badge-96.png';
$im = imagecreatefrompng($path);
$c = imagecolorat($im, 0, 0);
$a = 127 - (($c >> 24) & 0x7F);
echo "corner alpha opacity: {$a}\n";
$c2 = imagecolorat($im, 48, 48);
echo 'center rgb=' . (($c2 >> 16) & 255) . ',' . (($c2 >> 8) & 255) . ',' . ($c2 & 255);
echo ' alpha=' . (127 - (($c2 >> 24) & 0x7F)) . "\n";
