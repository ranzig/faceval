<?php
 
// set the HTTP header type to PNG
header("Content-type: image/png"); 
 
// set the width and height of the new image in pixels
$width = 10;
if($_GET['h'] != 0) {
	$height = $_GET['h'];
	if($height > 1) {
		$height = $height * (1);
	} else {
		$height = 1;
	}
} else {
	$height = 1;
}

if($_GET['r'] == 1) {
	$temp = $height;
	$height = $width;
	$width = $temp;
}
 
// create a pointer to a new true colour image
$im = ImageCreateTrueColor($width, $height); 
 
// set background color
$c = $_GET['c'];
if($c == 1) {
	$color = ImageColorAllocate($im, 255, 204, 51); 
} else if($c == 2) {
	$color = ImageColorAllocate($im, 255, 153, 51);
} else if($c == 3) {
	$color = ImageColorAllocate($im, 255, 102, 51);
} else if($c == 4) {
	$color = ImageColorAllocate($im, 255, 51, 51);
} else if($c == 5) {
	$color = ImageColorAllocate($im, 204, 0, 0);
} else {
	$color = ImageColorAllocate($im, 153, 0, 0);
}
ImageFillToBorder($im, 0, 0, $color, $color);
 
// send the new PNG image to the browser
ImagePNG($im); 
 
// destroy the reference pointer to the image in memory to free up resources
ImageDestroy($im); 
 
?>