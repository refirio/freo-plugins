<?php

/*********************************************************************

 ブログパーツ管理プラグイン (2013/03/25)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_parts()
{
	global $freo;

	//ブログパーツ取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_parts ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_parts = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_parts[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_parts' => $plugin_parts
	));

	return;
}

?>
