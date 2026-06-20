<?php

/** Generate raster favicons (run: php scripts/generate-icons.php) */

function drawIcon(int $size): GdImage
{
    $img = imagecreatetruecolor($size, $size);
    $blue = imagecolorallocate($img, 37, 99, 235);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $blue);

    $s = $size / 32;
    imagefilledrectangle($img, (int)(6 * $s), (int)(14 * $s), (int)(26 * $s), (int)(20 * $s), $white);
    imagefilledrectangle($img, (int)(10 * $s), (int)(10 * $s), (int)(22 * $s), (int)(15 * $s), $white);
    $wr = (int) max(1, 2.5 * $s);
    imagefilledellipse($img, (int)(10 * $s), (int)(21 * $s), (int)(5 * $s), (int)(5 * $s), $white);
    imagefilledellipse($img, (int)(22 * $s), (int)(21 * $s), (int)(5 * $s), (int)(5 * $s), $white);

    return $img;
}

$out = dirname(__DIR__).'/public';

foreach ([16 => 'favicon-16x16.png', 32 => 'favicon-32x32.png', 180 => 'apple-touch-icon.png'] as $px => $file) {
    $img = drawIcon($px);
    imagepng($img, $out.'/'.$file, 9);
}

copy($out.'/favicon-32x32.png', $out.'/favicon.ico');

echo "Icons written.\n";