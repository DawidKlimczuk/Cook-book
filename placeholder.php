<?php
// placeholder.jpg - generuje dynamiczny obraz zastępczy

header('Content-Type: image/jpeg');

// Tworzenie obrazu
$width = 400;
$height = 300;
$image = imagecreatetruecolor($width, $height);

// Kolory
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 150, 150, 150);
$border_color = imagecolorallocate($image, 200, 200, 200);

// Wypełnienie tła
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Ramka
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Tekst
$text = 'Brak zdjęcia';
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Ikona talerza
$plate_color = imagecolorallocate($image, 180, 180, 180);
$cx = $width / 2;
$cy = $height / 2 - 30;

// Rysowanie prostej ikony talerza
imageellipse($image, $cx, $cy, 80, 40, $plate_color);
imageellipse($image, $cx, $cy, 60, 30, $plate_color);

// Sztućce
imageline($image, $cx - 50, $cy - 20, $cx - 50, $cy + 20, $plate_color);
imageline($image, $cx + 50, $cy - 20, $cx + 50, $cy + 20, $plate_color);

// Wyświetlenie obrazu
imagejpeg($image, null, 90);
imagedestroy($image);
?>