<?php

/*********************************************************************

 漫画表示プラグイン (2012/09/19)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_media_comic()
{
	global $freo;

	//漫画格納ディレクトリを確認
	if (!preg_match('/^' . preg_quote(FREO_PLUGIN_MEDIA_COMIC_DIR, '/') . '/', $_GET['path'])) {
		return;
	}

	//漫画の存在を確認
	$flag = false;

	if ($dir = scandir(FREO_FILE_DIR . 'medias/' . $_GET['path'])) {
		foreach ($dir as $entry) {
			if (is_file(FREO_FILE_DIR . 'medias/' . $_GET['path'] . $entry) and preg_match('/^(0|1)\.(gif|jpeg|jpg|jpe|png)$/i', $entry)) {
				$flag = true;

				break;
			}
		}
	} else {
		freo_error('メディア格納ディレクトリ ' . FREO_FILE_DIR . 'medias/' . $_GET['path'] . ' を開けません。');
	}

	//データ割当
	$freo->smarty->assign('plugin_media_comic', $flag);

	return;
}

?>
