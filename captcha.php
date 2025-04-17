<?php
session_start();

$captcha_code = substr(md5(rand()), 0, 6);
$_SESSION['captcha_code'] = $captcha_code;

$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);

$bg_color = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg_color);

$text_color = imagecolorallocate($image, 0, 0, 0);
$font_path = 'lib\fonts\Punktype.ttf';

if (file_exists($font_path)) {
    imagettftext($image, 20, 0, 10, 30, $text_color, $font_path, $captcha_code);
} else {
    die('Error: Font file not found. Path: ' . $font_path);
}

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
exit;
?>
