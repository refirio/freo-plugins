<?php

/*********************************************************************

 投稿キープラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_postkey()
{
	global $freo;

	//投稿キー決定
	$key = rand(1000, 9999);

	//画像作成
	$image = imagecreate(50, 20);

	$brack = imagecolorallocate($image, 0, 0, 0);
	$white = imagecolorallocate($image, 255, 255, 255);

	$start_x = 25 - (strlen($key) * imagefontwidth(4) / 2);
	$start_y = 10 - imagefontheight(4) / 2;

	imagestring($image, 4, $start_x, $start_y, $key, $white);

	//投稿キー保持
	$_SESSION['plugin']['postkey']['key'] = $key;

	//画像表示
	header('Content-Type: image/png');
	imagepng($image);
	imagedestroy($image);

	return;
}

?>
