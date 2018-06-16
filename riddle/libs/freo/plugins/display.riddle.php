<?php

/*********************************************************************

 なぞなぞ認証プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_riddle()
{
	global $freo;

	//なぞなぞ取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles ORDER BY RAND() LIMIT 1');
	} else {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles ORDER BY RANDOM() LIMIT 1');
	}
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_riddle = $data;
	} else {
		$plugin_riddle = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_riddle' => $plugin_riddle
	));

	return;
}

?>
