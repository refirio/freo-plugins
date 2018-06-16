<?php

/*********************************************************************

 注文管理プラグイン (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_catalog_order()
{
	global $freo;

	$plugin_catalog_order = array();

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	} else {
		if (empty($_GET['session'])) {
			if ($freo->user['id']) {
				//データ取得
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :user_id');
				$stmt->bindValue(':user_id', $freo->user['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$plugin_catalog_order = $data;
				} else {
					$plugin_catalog_order = array();
				}
			}
		}
	}

	if (empty($plugin_catalog_order)) {
		return;
	}

	//データ割当
	$freo->smarty->assign(array(
		'input' => array(
			'plugin_catalog_order' => $plugin_catalog_order
		)
	));

	return;
}

?>
