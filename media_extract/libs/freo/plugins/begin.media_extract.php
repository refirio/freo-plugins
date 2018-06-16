<?php

/*********************************************************************

 メディア一括登録プラグイン (2012/10/01)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_media_extract()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		return;
	}

	//入力データ取得
	$media = $_SESSION['input']['media'];

	if ($media['exec'] == 'insert') {
		$_SESSION['plugin']['media_extract'] = $media;
	}

	return;
}

?>
