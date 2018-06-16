<?php

/*********************************************************************

 注文管理プラグイン (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_catalog_order()
{
	global $freo;

	if ($_REQUEST['freo']['mode'] == 'admin' and $_REQUEST['freo']['work'] == 'user_delete') {
		//注文削除
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders SET user_id = NULL WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//ユーザー削除
		$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} elseif ($_REQUEST['freo']['mode'] == 'catalog' and ($_REQUEST['freo']['work'] == 'cart' or $_REQUEST['freo']['work'] == 'cart_putin' or $_REQUEST['freo']['work'] == 'cart_update' or $_REQUEST['freo']['work'] == 'cart_delete' or $_REQUEST['freo']['work'] == 'cart_clear')) {
		if ($freo->user['id']) {
			//カートの内容確認
			$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts WHERE user_id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$flag = true;
			} else {
				$flag = false;
			}

			if (empty($_SESSION['plugin']['catalog']['cart'])) {
				if ($flag == true) {
					//カートの内容削除
					$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts WHERE user_id = :id');
					$stmt->bindValue(':id', $freo->user['id']);
					$flag = $stmt->execute();
					if (!$flag) {
						freo_error($stmt->errorInfo());
					}
				}
			} else {
				if ($flag == false) {
					$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts VALUES(:user_id, \'data\')');
					$stmt->bindValue(':user_id', $freo->user['id']);
					$flag = $stmt->execute();
					if (!$flag) {
						freo_error($stmt->errorInfo());
					}
				}

				//カートの内容登録
				$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts SET data = :data WHERE user_id = :user_id');
				$stmt->bindValue(':data',    serialize($_SESSION['plugin']['catalog']['cart']));
				$stmt->bindValue(':user_id', $freo->user['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			}
		}
	} else {
		//入力データ取得
		$catalog = $_SESSION['plugin']['catalog_order']['input']['plugin_catalog_order'];

		//注文内容取得
		$orders = $_SESSION['plugin']['catalog_order']['order'];

		//支払い方法取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\' AND id = :id');
		$stmt->bindValue(':id', $catalog['payment_id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalog_payment = $data;
		} else {
			freo_error('指定された支払い方法が見つかりません。');
		}

		//配送方法取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE status = \'publish\' AND id = :id');
		$stmt->bindValue(':id', $catalog['delivery_id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalog_delivery = $data;
		} else {
			freo_error('指定された配送方法が見つかりません。');
		}

		//地域別送料取得
		if ($catalog['prefecture'] != '') {
			$stmt = $freo->pdo->prepare('SELECT carriage FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :delivery_id AND prefecture = :prefecture');
			$stmt->bindValue(':delivery_id', $catalog['delivery_id']);
			$stmt->bindValue(':prefecture',  $catalog['prefecture']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_delivery['carriage'] += $data['carriage'];
			}
		}

		//注文内容取得
		$order = freo_page_catalog_get_cart($orders);

		//送料無料判定
		if ($freo->config['plugin']['catalog']['free_shipping'] and $order['catalog_price_total'] >= $freo->config['plugin']['catalog']['free_shipping']) {
			$plugin_catalog_delivery['carriage'] = 0;
		}

		//対応状況取得
		$status = null;
		if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_order_defines/status.csv', 'r')) {
			while ($line = fgets($fp)) {
				list($id, $name, $new, $complete, $cancel, $cancel_ok) = explode(',', chop($line), 6);

				if ($new == 1) {
					$status = $id;

					break;
				}
			}
			fclose($fp);
		}
		if ($status == null) {
			freo_error('初期の対応状況が見つかりません。');
		}

		if ($catalog['preferred_week'] == '') {
			$catalog['preferred_week'] = null;
		}
		if ($catalog['preferred_time'] == '') {
			$catalog['preferred_time'] = null;
		}
		if ($catalog['tel'] == '') {
			$catalog['tel'] = null;
		}
		if ($catalog['zipcode'] == '') {
			$catalog['zipcode'] = null;
		}
		if ($catalog['prefecture'] == '') {
			$catalog['prefecture'] = null;
		}
		if ($catalog['address'] == '') {
			$catalog['address'] = null;
		}
		if ($catalog['text'] == '') {
			$catalog['text'] = null;
		}
		if ($plugin_catalog_delivery['carriage'] == '') {
			$plugin_catalog_delivery['carriage'] = 0;
		}
		if ($plugin_catalog_payment['charge'] == '') {
			$plugin_catalog_payment['charge'] = 0;
		}

		//入力データ登録
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders VALUES(:record_id, :user_id, :delivery_id, :payment_id, :now1, :now2, :status, :carriage, :charge, :discount, :preferred_week, :preferred_time, :name, :kana, :mail, :tel, :zipcode, :prefecture, :address, :text, :datetime, NULL, NULL)');
		$stmt->bindValue(':record_id',      $_SESSION['plugin']['catalog']['record_id'], PDO::PARAM_INT);
		$stmt->bindValue(':user_id',        $freo->user['id']);
		$stmt->bindValue(':delivery_id',    $catalog['delivery_id']);
		$stmt->bindValue(':payment_id',     $catalog['payment_id']);
		$stmt->bindValue(':now1',           date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',           date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',         $status);
		$stmt->bindValue(':carriage',       $plugin_catalog_delivery['carriage'], PDO::PARAM_INT);
		$stmt->bindValue(':charge',         $plugin_catalog_payment['charge'], PDO::PARAM_INT);
		$stmt->bindValue(':discount',       0, PDO::PARAM_INT);
		$stmt->bindValue(':preferred_week', $catalog['preferred_week']);
		$stmt->bindValue(':preferred_time', $catalog['preferred_time']);
		$stmt->bindValue(':name',           $catalog['name']);
		$stmt->bindValue(':kana',           $catalog['kana']);
		$stmt->bindValue(':mail',           $catalog['mail']);
		$stmt->bindValue(':tel',            $catalog['tel']);
		if ($order['catalog_short_max'] > 0 and $order['catalog_long_max'] > 0) {
			$stmt->bindValue(':zipcode',    $catalog['zipcode']);
			$stmt->bindValue(':prefecture', $catalog['prefecture']);
			$stmt->bindValue(':address',    $catalog['address']);
		} else {
			$stmt->bindValue(':zipcode',    null);
			$stmt->bindValue(':prefecture', null);
			$stmt->bindValue(':address',    null);
		}
		$stmt->bindValue(':text',           $catalog['text']);
		$stmt->bindValue(':datetime',       date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//注文商品取得
		$stmt = $freo->pdo->prepare('SELECT id, price FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($orders))) . ') AND status = \'publish\' AND (close IS NULL OR close >= :now)');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$plugin_catalogs = $orders;
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalogs[$data['id']] = $data;
		}

		foreach ($plugin_catalogs as $id => $data) {
			if (!is_array($data)) {
				unset($plugin_catalogs[$id]);
			}
		}

		//注文内容登録
		foreach ($plugin_catalogs as $plugin_catalog) {
			$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets VALUES(:record_id, :catalog_id, :price, :count)');
			$stmt->bindValue(':record_id',  $_SESSION['plugin']['catalog']['record_id'], PDO::PARAM_INT);
			$stmt->bindValue(':catalog_id', $plugin_catalog['id']);
			$stmt->bindValue(':price',      $plugin_catalog['price'], PDO::PARAM_INT);
			$stmt->bindValue(':count',      $orders[$plugin_catalog['id']], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		if ($freo->user['id']) {
			//注文者確認
			$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$flag = true;
			} else {
				$flag = false;
			}

			if ($flag == false) {
				$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users VALUES(:user_id, :now1, :now2, \'name\', \'kana\', \'mail\', NULL, NULL, NULL, NULL, NULL, NULL)');
				$stmt->bindValue(':user_id', $freo->user['id']);
				$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
				$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			}

			//注文者登録
			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users SET modified = :now, name = :name, kana = :kana, mail = :mail, tel = :tel, zipcode = :zipcode, prefecture = :prefecture, address = :address, text = :text WHERE user_id = :user_id');
			$stmt->bindValue(':now',        date('Y-m-d H:i:s'));
			$stmt->bindValue(':name',       $catalog['name']);
			$stmt->bindValue(':kana',       $catalog['kana']);
			$stmt->bindValue(':mail',       $catalog['mail']);
			$stmt->bindValue(':tel',        $catalog['tel']);
			$stmt->bindValue(':zipcode',    $catalog['zipcode']);
			$stmt->bindValue(':prefecture', $catalog['prefecture']);
			$stmt->bindValue(':address',    $catalog['address']);
			$stmt->bindValue(':text',       $catalog['text']);
			$stmt->bindValue(':user_id',    $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			//カートの内容削除
			$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_carts WHERE user_id = :id');
			$stmt->bindValue(':id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		//入力データ破棄
		unset($_SESSION['plugin']['catalog_order']['input']);
		unset($_SESSION['plugin']['catalog_order']['order']);
	}

	return;
}

?>
