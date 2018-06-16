<?php

/*********************************************************************

 メニュー登録プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_menu()
{
	global $freo;

	//メニュー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_menus ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_menus = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_menus[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_menus' => $plugin_menus
	));

	return;
}

?>
