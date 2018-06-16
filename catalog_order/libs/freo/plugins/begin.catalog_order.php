<?php

/*********************************************************************

 注文管理プラグイン (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_catalog_order()
{
	global $freo;

	if ($_REQUEST['freo']['mode'] == 'catalog' and ($_REQUEST['freo']['work'] == 'cart' or $_REQUEST['freo']['work'] == 'cart_putin')) {
		if ($freo->user['id']) {
			if (empty($_SESSION['plugin']['catalog_order']['cart'])) {
				//カートの内容を取得
				$stmt = $freo->pdo->prepare('SELECT data FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts WHERE user_id = :user_id');
				$stmt->bindValue(':user_id', $freo->user['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$carts = unserialize($data['data']);
				} else {
					$carts = array();
				}

				//カートの内容を復元
				if (!empty($_SESSION['plugin']['catalog']['cart'])) {
					foreach ($_SESSION['plugin']['catalog']['cart'] as $id => $count) {
						if (isset($carts[$id])) {
							$carts[$id] += $count;
						} else {
							$carts[$id] = $count;
						}
					}
				}

				$_SESSION['plugin']['catalog']['cart'] = $carts;

				//カートの内容を復元済み
				$_SESSION['plugin']['catalog_order']['cart'] = true;
			}
		}
	} else {
		//入力データ記録
		$_SESSION['plugin']['catalog_order']['input'] = $_SESSION['input'];

		//注文内容記録
		$_SESSION['plugin']['catalog_order']['order'] = $_SESSION['plugin']['catalog']['order'];
	}

	return;
}

?>
