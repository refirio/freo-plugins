<?php

/*********************************************************************

 注文管理プラグイン (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_page_catalog_order()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_catalog_order_setup();
			break;
		case 'setup_execute':
			freo_page_catalog_order_setup_execute();
			break;
		case 'form':
			freo_page_catalog_order_form();
			break;
		case 'post':
			freo_page_catalog_order_post();
			break;
		case 'order':
			freo_page_catalog_order_order();
			break;
		case 'order_view':
			freo_page_catalog_order_order_view();
			break;
		case 'cancel':
			freo_page_catalog_order_cancel();
			break;
		case 'cancel_send':
			freo_page_catalog_order_cancel_send();
			break;
		case 'cancel_complete':
			freo_page_catalog_order_cancel_complete();
			break;
		case 'admin':
			freo_page_catalog_order_admin();
			break;
		case 'admin_order':
			freo_page_catalog_order_admin_order();
			break;
		case 'admin_order_form':
			freo_page_catalog_order_admin_order_form();
			break;
		case 'admin_order_post':
			freo_page_catalog_order_admin_order_post();
			break;
		case 'admin_order_delete':
			freo_page_catalog_order_admin_order_delete();
			break;
		case 'admin_order_cart_form':
			freo_page_catalog_order_admin_order_cart_form();
			break;
		case 'admin_order_cart_putin':
			freo_page_catalog_order_admin_order_cart_putin();
			break;
		case 'admin_order_cart_delete':
			freo_page_catalog_order_admin_order_cart_delete();
			break;
		case 'admin_user':
			freo_page_catalog_order_admin_user();
			break;
		case 'admin_user_form':
			freo_page_catalog_order_admin_user_form();
			break;
		case 'admin_user_post':
			freo_page_catalog_order_admin_user_post();
			break;
		case 'admin_user_delete':
			freo_page_catalog_order_admin_user_delete();
			break;
		case 'admin_total':
			freo_page_catalog_order_admin_total();
			break;
		default:
			freo_page_catalog_order_default();
	}

	return;
}

/* セットアップ */
function freo_page_catalog_order_setup()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//エラー確認
		if (!$freo->smarty->get_template_vars('errors')) {
			freo_redirect('catalog_order/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'catalog_order',
		'plugin_name' => FREO_PLUGIN_CATALOG_ORDER_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_catalog_order_setup_execute()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('setup?error=1', true);
	}

	//データベーステーブル存在検証
	if (FREO_DATABASE_TYPE == 'mysql') {
		$query = 'SHOW TABLES';
	} else {
		$query = 'SELECT name FROM sqlite_master WHERE type = \'table\'';
	}
	$stmt = $freo->pdo->query($query);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$table = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$table[$data[0]] = true;
	}

	//データベーステーブル定義
	if (FREO_DATABASE_TYPE == 'mysql') {
		$queries = array(
			'plugin_catalog_orders'      => '(record_id INT UNSIGNED NOT NULL, user_id VARCHAR(80), delivery_id VARCHAR(80) NOT NULL, payment_id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, carriage INT UNSIGNED NOT NULL, charge INT UNSIGNED NOT NULL, discount INT UNSIGNED NOT NULL, preferred_week VARCHAR(80), preferred_time VARCHAR(80), name VARCHAR(255) NOT NULL, kana VARCHAR(255) NOT NULL, mail VARCHAR(80) NOT NULL, tel VARCHAR(80), zipcode VARCHAR(80), prefecture VARCHAR(80), address TEXT, text TEXT, datetime DATETIME NOT NULL, user_text TEXT, admin_text TEXT, PRIMARY KEY(record_id))',
			'plugin_catalog_order_sets'  => '(record_id VARCHAR(80) NOT NULL, catalog_id VARCHAR(80) NOT NULL, price INT UNSIGNED NOT NULL, count INT UNSIGNED NOT NULL)',
			'plugin_catalog_order_users' => '(user_id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, name VARCHAR(255) NOT NULL, kana VARCHAR(255) NOT NULL, mail VARCHAR(80) NOT NULL, tel VARCHAR(80), zipcode VARCHAR(80), prefecture VARCHAR(80), address TEXT, text TEXT, admin_text TEXT, PRIMARY KEY(user_id))',
			'plugin_catalog_order_carts' => '(user_id VARCHAR(80) NOT NULL, data TEXT NOT NULL, PRIMARY KEY(user_id))'
		);
	} else {
		$queries = array(
			'plugin_catalog_orders'      => '(record_id INT UNSIGNED NOT NULL, user_id VARCHAR, delivery_id VARCHAR NOT NULL, payment_id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, carriage INT UNSIGNED NOT NULL, charge INT UNSIGNED NOT NULL, discount INT UNSIGNED NOT NULL, preferred_week VARCHAR, preferred_time VARCHAR, name VARCHAR NOT NULL, kana VARCHAR NOT NULL, mail VARCHAR NOT NULL, tel VARCHAR, zipcode VARCHAR, prefecture VARCHAR, address TEXT, text TEXT, datetime DATETIME NOT NULL, user_text TEXT, admin_text TEXT, PRIMARY KEY(record_id))',
			'plugin_catalog_order_sets'  => '(record_id VARCHAR NOT NULL, catalog_id VARCHAR NOT NULL, price INT UNSIGNED NOT NULL, count INT UNSIGNED NOT NULL)',
			'plugin_catalog_order_users' => '(user_id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, name VARCHAR NOT NULL, kana VARCHAR NOT NULL, mail VARCHAR NOT NULL, tel VARCHAR, zipcode VARCHAR, prefecture VARCHAR, address TEXT, text TEXT, admin_text TEXT, PRIMARY KEY(user_id))',
			'plugin_catalog_order_carts' => '(user_id VARCHAR NOT NULL, data TEXT NOT NULL, PRIMARY KEY(user_id))'
		);
	}

	//データベーステーブル作成
	foreach ($queries as $name => $query) {
		if (empty($table[FREO_DATABASE_PREFIX . $name])) {
			$stmt = $freo->pdo->query('CREATE TABLE ' . FREO_DATABASE_PREFIX . $name . $query);
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log(FREO_PLUGIN_CATALOG_ORDER_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('catalog_order/setup?exec=setup', true);

	return;
}

/* ご注文者情報入力 */
function freo_page_catalog_order_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//名前
			if ($_POST['plugin_catalog_order_user']['name'] == '') {
				$freo->smarty->append('errors', '名前が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前は80文字以内で入力してください。');
			}

			//名前（フリガナ）
			if ($_POST['plugin_catalog_order_user']['name'] == '') {
				$freo->smarty->append('errors', '名前（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_catalog_order_user']['kana'])) {
				$freo->smarty->append('errors', '名前（フリガナ）は全角カタカナで入力してください。');
			}

			//メールアドレス
			if ($_POST['plugin_catalog_order_user']['mail'] == '') {
				$freo->smarty->append('errors', 'メールアドレスが入力されていません。');
			} elseif (!strpos($_POST['plugin_catalog_order_user']['mail'], '@')) {
				$freo->smarty->append('errors', 'メールアドレスの入力内容が正しくありません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['mail'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'メールアドレスは80文字以内で入力してください。');
			}

			//電話番号
			if ($_POST['plugin_catalog_order_user']['tel'] != '') {
				if (!preg_match('/^[\d\-\(\)]+$/', $_POST['plugin_catalog_order_user']['tel'])) {
					$freo->smarty->append('errors', '電話番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order_user']['tel'], 'UTF-8') > 20) {
					$freo->smarty->append('errors', '電話番号は20文字以内で入力してください。');
				}
			}

			//郵便番号
			if ($_POST['plugin_catalog_order_user']['zipcode'] != '') {
				if (!preg_match('/^[\d\-]+$/', $_POST['plugin_catalog_order_user']['zipcode'])) {
					$freo->smarty->append('errors', '郵便番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order_user']['zipcode'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '郵便番号は10文字以内で入力してください。');
				}
			}

			//都道府県
			if ($_POST['plugin_catalog_order_user']['prefecture'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['prefecture'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '都道府県は80文字以内で入力してください。');
				}
			}

			//住所
			if ($_POST['plugin_catalog_order_user']['address'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['address'], 'UTF-8') > 500) {
					$freo->smarty->append('errors', '住所は500文字以内で入力してください。');
				}
			}

			//連絡事項
			if ($_POST['plugin_catalog_order_user']['text'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_order_user = $_POST['plugin_catalog_order_user'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog_order/post?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	} else {
		if ($freo->user['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_order_user = $data;
			} else {
				$plugin_catalog_order_user = array();
			}
		} else {
			freo_error('ユーザー情報を取得できません。');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status(),
		'input' => array(
			'plugin_catalog_order_user' => $plugin_catalog_order_user
		)
	));

	return;
}

/* ご注文者情報登録 */
function freo_page_catalog_order_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog_order/form?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/form?error=1', true);
	}

	//入力データ取得
	$catalog_order_user = $_SESSION['input']['plugin_catalog_order_user'];

	if ($catalog_order_user['tel'] == '') {
		$catalog_order_user['tel'] = null;
	}
	if ($catalog_order_user['zipcode'] == '') {
		$catalog_order_user['zipcode'] = null;
	}
	if ($catalog_order_user['prefecture'] == '') {
		$catalog_order_user['prefecture'] = null;
	}
	if ($catalog_order_user['address'] == '') {
		$catalog_order_user['address'] = null;
	}
	if ($catalog_order_user['text'] == '') {
		$catalog_order_user['text'] = null;
	}

	//データ確認
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

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users SET modified = :now, name = :name, kana = :kana, mail = :mail, tel = :tel, zipcode = :zipcode, prefecture = :prefecture, address = :address, text = :text WHERE user_id = :user_id');
	$stmt->bindValue(':now',        date('Y-m-d H:i:s'));
	$stmt->bindValue(':name',       $catalog_order_user['name']);
	$stmt->bindValue(':kana',       $catalog_order_user['kana']);
	$stmt->bindValue(':mail',       $catalog_order_user['mail']);
	$stmt->bindValue(':tel',        $catalog_order_user['tel']);
	$stmt->bindValue(':zipcode',    $catalog_order_user['zipcode']);
	$stmt->bindValue(':prefecture', $catalog_order_user['prefecture']);
	$stmt->bindValue(':address',    $catalog_order_user['address']);
	$stmt->bindValue(':text',       $catalog_order_user['text']);
	$stmt->bindValue(':user_id',    $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('ご注文者情報を編集しました。');

	//ご注文者情報管理へ移動
	freo_redirect('catalog_order/form?exec=update', true);

	return;
}

/* 注文履歴 */
function freo_page_catalog_order_order()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//注文取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE user_id = :user_id ORDER BY datetime DESC LIMIT :start, :limit');
	$stmt->bindValue(':user_id', $freo->user['id']);
	$stmt->bindValue(':start',   intval($freo->config['plugin']['catalog_order']['default_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit',   intval($freo->config['plugin']['catalog_order']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_orders = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_orders[$data['record_id']] = $data;
	}

	//注文数・ページ数取得
	$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE user_id = :user_id');
	$stmt->bindValue(':user_id', $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$data                       = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_catalog_order_count = $data[0];
	$plugin_catalog_order_page  = ceil($plugin_catalog_order_count / $freo->config['plugin']['catalog_order']['default_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_orders'       => $plugin_catalog_orders,
		'plugin_catalog_order_count'  => $plugin_catalog_order_count,
		'plugin_catalog_order_page'   => $plugin_catalog_order_page,
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status()
	));

	return;
}

/* 注文確認 */
function freo_page_catalog_order_order_view()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_error('注文を指定してください。');
	}

	//注文データ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE record_id = :id AND user_id = :user_id');
	$stmt->bindValue(':id',      $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':user_id', $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order = $data;
	} else {
		freo_error('指定された注文が見つかりません。', '404 Not Found');
	}

	//注文内容データ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets WHERE record_id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_order_sets = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order_sets[$data['catalog_id']] = $data;
	}

	//商品取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($plugin_catalog_order_sets))) . ') ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalogs = $plugin_catalog_order_sets;
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalogs[$data['id']] = $data;
	}

	//価格の小計を計算
	$plugin_catalog_price_subtotals = array();
	foreach ($plugin_catalogs as $plugin_catalog) {
		$plugin_catalog_price_subtotals[$plugin_catalog['id']] = $plugin_catalog['price'] * $plugin_catalog_order_sets[$plugin_catalog['id']]['count'];
	}

	//価格の合計を計算
	$plugin_catalog_price_total = array_sum($plugin_catalog_price_subtotals);

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//支払い方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//配送方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE status = \'publish\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_deliveries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_deliveries[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                          => freo_token('create'),
		'plugin_catalog_order'           => $plugin_catalog_order,
		'plugin_catalog_order_sets'      => $plugin_catalog_order_sets,
		'plugin_catalogs'                => $plugin_catalogs,
		'plugin_catalog_price_subtotals' => $plugin_catalog_price_subtotals,
		'plugin_catalog_price_total'     => $plugin_catalog_price_total,
		'plugin_catalog_categories'      => $plugin_catalog_categories,
		'plugin_catalog_payments'        => $plugin_catalog_payments,
		'plugin_catalog_deliveries'      => $plugin_catalog_deliveries,
		'plugin_catalog_targets'         => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'           => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'     => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status'    => freo_page_catalog_order_get_status()
	));

	return;
}

/* 注文キャンセル */
function freo_page_catalog_order_cancel()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = null;
	}

	//注文確認
	$stmt = $freo->pdo->prepare('SELECT status FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE record_id = :id AND user_id = :user_id');
	$stmt->bindValue(':id',      $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':user_id', $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order = $data;
	} else {
		freo_error('指定された注文が見つかりません。', '404 Not Found');
	}

	$status = freo_page_catalog_order_get_status();

	if (!$status[$plugin_catalog_order['status']]['cancel_ok']) {
		freo_error('この注文はキャンセルできません。');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//連絡事項
			if ($_POST['plugin_catalog_order_cencel']['text'] != '') {
				if ($_POST['plugin_catalog_order_cencel']['text'] == '') {
					$freo->smarty->append('errors', '連絡事項が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_catalog_order_cencel']['text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_contact = $_POST['plugin_catalog_order_cencel'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog_order/cancel_send?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id'], true);
		}
	} else {
		//新規データ設定
		$plugin_catalog_order_cancel = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status(),
		'input' => array(
			'plugin_catalog_order_cancel' => $plugin_catalog_order_cancel
		)
	));

	return;
}

/* 注文キャンセル | メール送信 */
function freo_page_catalog_order_cancel_send()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog_order/order', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/order', true);
	}

	//入力データ取得
	$plugin_catalog_order_cencel = $_SESSION['input']['plugin_catalog_order_cencel'];

	//注文確認
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE record_id = :id AND user_id = :user_id');
	$stmt->bindValue(':id',      $plugin_catalog_order_cencel['id'], PDO::PARAM_INT);
	$stmt->bindValue(':user_id', $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order = $data;
	} else {
		freo_error('指定された注文が見つかりません。');
	}

	$status = freo_page_catalog_order_get_status();

	if (!$status[$plugin_catalog_order['status']]['cancel_ok']) {
		freo_error('この注文はキャンセルできません。');
	}

	//メール本文定義
	$body  = "ご注文ID : " . $freo->config['plugin']['catalog']['order_prefix'] . $plugin_catalog_order['record_id'] . "\n";
	$body .= "連絡事項 : " . $plugin_catalog_order_cencel['text'] . "\n";
	$body .= "\n";

	//管理者向けメールヘッダ定義
	$headers = array(
		'From' => '"' . mb_encode_mimeheader(mb_convert_kana($plugin_catalog_order['name'], 'KV', 'UTF-8')) . '" <' . $plugin_catalog_order['mail'] . '>'
	);

	//管理者向けメール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog_order/inform_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog_order/inform_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	//管理者向けメール送信
	$flag = freo_mail($freo->config['plugin']['catalog']['mail_to'], 'キャンセル依頼を受け付けました', $mail_header . $body . $mail_footer, $headers);
	if (!$flag) {
		freo_error('管理者にメールを送信できません。');
	}

	//注文者向けメールヘッダ定義
	$headers = array(
		'From' => '"' . mb_encode_mimeheader(mb_convert_kana($freo->config['plugin']['catalog']['mail_name'], 'KV', 'UTF-8')) . '" <' . $freo->config['plugin']['catalog']['mail_from'] . '>'
	);

	//注文者向けメール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog_order/cancel_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog_order/cancel_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	//注文者向けメール送信
	$flag = freo_mail($plugin_catalog_order['mail'], $freo->config['plugin']['catalog_order']['mail_subject'], $mail_header . $body . $mail_footer, $headers);
	if (!$flag) {
		freo_error('注文者にメールを送信できません。');
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('注文キャンセル依頼メールを送信しました。');

	//登録完了画面へ移動
	freo_redirect('catalog_order/cancel_complete?id=' . $plugin_catalog_order_cencel['id'], true);

	return;
}

/* 注文キャンセル | メール送信完了 */
function freo_page_catalog_order_cancel_complete()
{
	global $freo;

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status()
	));

	return;
}

/* 管理画面 | ステータス */
function freo_page_catalog_order_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//対応状況数取得
	$stmt = $freo->pdo->query('SELECT status, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders GROUP BY status');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_order_counts = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order_counts[$data['status']] = $data['count'];
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_order_counts' => $plugin_catalog_order_counts,
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status()
	));

	return;
}

/* 管理画面 | 注文履歴 */
function freo_page_catalog_order_admin_order()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['user_id']) and $_GET['user_id'] != '') {
//	if (isset($_GET['user_id'])) {
		$condition .= ' AND user_id = ' . $freo->pdo->quote($_GET['user_id']);
	}
	if (isset($_GET['date']) and $_GET['date'] != '') {
//	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if (isset($_GET['status']) and $_GET['status'] != '') {
//	if (isset($_GET['status'])) {
		$condition .= ' AND status = ' . $freo->pdo->quote($_GET['status']);
	}
	if (isset($_GET['name']) and $_GET['name'] != '') {
//	if (isset($_GET['name'])) {
		$condition .= ' AND name LIKE ' . $freo->pdo->quote('%' . $_GET['name'] . '%');
	}
	if ($condition) {
		$condition = ' WHERE record_id IS NOT NULL ' . $condition;
	}

	//注文取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders ' . $condition . ' ORDER BY datetime DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['catalog_order']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['catalog_order']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_orders = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_orders[$data['record_id']] = $data;
	}

	//注文数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                       = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_catalog_order_count = $data[0];
	$plugin_catalog_order_page  = ceil($plugin_catalog_order_count / $freo->config['plugin']['catalog_order']['admin_limit']);

	//年月取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('SELECT DATE_FORMAT(created, \'%Y-%m\') AS month FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders GROUP BY month ORDER BY month DESC');
	} else {
		$stmt = $freo->pdo->query('SELECT STRFTIME(\'%Y-%m\', created) AS month FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders GROUP BY month ORDER BY month DESC');
	}
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_order_months = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/^(\d\d\d\d)\-(\d\d)$/', $data['month'], $matches)) {
			$plugin_catalog_order_months[] = array(
				'year'  => $matches[1],
				'month' => $matches[2]
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_orders'       => $plugin_catalog_orders,
		'plugin_catalog_order_count'  => $plugin_catalog_order_count,
		'plugin_catalog_order_page'   => $plugin_catalog_order_page,
		'plugin_catalog_order_months' => $plugin_catalog_order_months,
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status()
	));

	return;
}

/* 管理画面 | 注文入力 */
function freo_page_catalog_order_admin_order_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//日時データ取得
		if (is_array($_POST['plugin_catalog_order']['datetime'])) {
			$year   = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['plugin_catalog_order']['datetime']['second'], 'n', 'UTF-8');

			$_POST['plugin_catalog_order']['datetime'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}

		//送料取得
		if ($_POST['plugin_catalog_order']['carriage'] != '') {
			$_POST['plugin_catalog_order']['carriage'] = mb_convert_kana($_POST['plugin_catalog_order']['carriage'], 'n', 'UTF-8');
		}

		//手数料
		if ($_POST['plugin_catalog_order']['charge'] != '') {
			$_POST['plugin_catalog_order']['charge'] = mb_convert_kana($_POST['plugin_catalog_order']['charge'], 'n', 'UTF-8');
		}

		//値引き
		if ($_POST['plugin_catalog_order']['discount'] != '') {
			$_POST['plugin_catalog_order']['discount'] = mb_convert_kana($_POST['plugin_catalog_order']['discount'], 'n', 'UTF-8');
		}

		//価格取得
		if (!empty($_POST['plugin_catalog_order']['price'])) {
			foreach ($_POST['plugin_catalog_order']['price'] as $id => $price) {
				if ($_POST['plugin_catalog_order']['price'][$id] != '') {
					$_POST['plugin_catalog_order']['price'][$id] = mb_convert_kana($price, 'n', 'UTF-8');
				}
			}
		}

		//数量取得
		if (!empty($_POST['plugin_catalog_order']['count'])) {
			foreach ($_POST['plugin_catalog_order']['count'] as $id => $count) {
				if ($_POST['plugin_catalog_order']['count'][$id] != '') {
					$_POST['plugin_catalog_order']['count'][$id] = mb_convert_kana($count, 'n', 'UTF-8');
				}
			}
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//ユーザーID
			if ($_POST['plugin_catalog_order']['user_id'] != '') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog_order']['user_id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
					$freo->smarty->append('errors', '入力されたユーザーIDは存在しません。');
				}
			}

			//配送方法
			if (empty($_POST['plugin_catalog_order']['delivery_id'])) {
				$freo->smarty->append('errors', '配送方法が入力されていません。');
			}

			//支払い方法
			if (empty($_POST['plugin_catalog_order']['payment_id'])) {
				$freo->smarty->append('errors', '支払い方法が入力されていません。');
			}

			//対応状況
			if (empty($_POST['plugin_catalog_order']['status'])) {
				$freo->smarty->append('errors', '対応状況が入力されていません。');
			}

			//送料
			if ($_POST['plugin_catalog_order']['carriage'] == '') {
				$freo->smarty->append('errors', '送料が入力されていません。');
			}

			//手数料
			if ($_POST['plugin_catalog_order']['charge'] == '') {
				$freo->smarty->append('errors', '手数料が入力されていません。');
			}

			//値引き
			if ($_POST['plugin_catalog_order']['discount'] == '') {
				$freo->smarty->append('errors', '値引きが入力されていません。');
			}

			//名前
			if ($_POST['plugin_catalog_order']['name'] == '') {
				$freo->smarty->append('errors', '名前が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前は80文字以内で入力してください。');
			}

			//名前（フリガナ）
			if ($_POST['plugin_catalog_order']['name'] == '') {
				$freo->smarty->append('errors', '名前（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_catalog_order']['kana'])) {
				$freo->smarty->append('errors', '名前（フリガナ）は全角カタカナで入力してください。');
			}

			//メールアドレス
			if ($_POST['plugin_catalog_order']['mail'] == '') {
				$freo->smarty->append('errors', 'メールアドレスが入力されていません。');
			} elseif (!strpos($_POST['plugin_catalog_order']['mail'], '@')) {
				$freo->smarty->append('errors', 'メールアドレスの入力内容が正しくありません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order']['mail'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'メールアドレスは80文字以内で入力してください。');
			}

			//電話番号
			if ($_POST['plugin_catalog_order']['tel'] != '') {
				if (!preg_match('/^[\d\-\(\)]+$/', $_POST['plugin_catalog_order']['tel'])) {
					$freo->smarty->append('errors', '電話番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order']['tel'], 'UTF-8') > 20) {
					$freo->smarty->append('errors', '電話番号は20文字以内で入力してください。');
				}
			}

			//郵便番号
			if ($_POST['plugin_catalog_order']['zipcode'] != '') {
				if (!preg_match('/^[\d\-]+$/', $_POST['plugin_catalog_order']['zipcode'])) {
					$freo->smarty->append('errors', '郵便番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order']['zipcode'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '郵便番号は10文字以内で入力してください。');
				}
			}

			//都道府県
			if (mb_strlen($_POST['plugin_catalog_order']['prefecture'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '都道府県は80文字以内で入力してください。');
			}

			//住所
			if (mb_strlen($_POST['plugin_catalog_order']['address'], 'UTF-8') > 500) {
				$freo->smarty->append('errors', '住所は500文字以内で入力してください。');
			}

			//連絡事項
			if (mb_strlen($_POST['plugin_catalog_order']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
			}

			//注文日時
			if ($_POST['plugin_catalog_order']['datetime'] == '') {
				$freo->smarty->append('errors', '注文日時が入力されていません。');
			} elseif (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $_POST['plugin_catalog_order']['datetime'])) {
				$freo->smarty->append('errors', '注文日時の書式が不正です。');
			}

			//注文者向け連絡事項
			if (mb_strlen($_POST['plugin_catalog_order']['user_text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '注文者向け連絡事項は5000文字以内で入力してください。');
			}

			//管理者用メモ
			if (mb_strlen($_POST['plugin_catalog_order']['admin_text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
			}

			//価格
			if (!empty($_POST['plugin_catalog_order']['price'])) {
				foreach ($_POST['plugin_catalog_order']['price'] as $id => $price) {
					if ($price == '') {
						$freo->smarty->append('errors', '「' . $id . '」の価格が入力されていません。');
					} elseif (!preg_match('/^\d+$/', $price)) {
						$freo->smarty->append('errors', '「' . $id . '」の価格は半角数字で入力してください。');
					} elseif (mb_strlen($price, 'UTF-8') > 10) {
						$freo->smarty->append('errors', '「' . $id . '」の価格は10文字以内で入力してください。');
					}
				}
			}

			//数量
			if (!empty($_POST['plugin_catalog_order']['count'])) {
				foreach ($_POST['plugin_catalog_order']['count'] as $id => $count) {
					if ($count == '') {
						$freo->smarty->append('errors', '「' . $id . '」の数量が入力されていません。');
					} elseif (!preg_match('/^\d+$/', $count)) {
						$freo->smarty->append('errors', '「' . $id . '」の数量は半角数字で入力してください。');
					} elseif (mb_strlen($count, 'UTF-8') > 10) {
						$freo->smarty->append('errors', '「' . $id . '」の数量は10文字以内で入力してください。');
					} elseif ($count == 0) {
						$freo->smarty->append('errors', '「' . $id . '」の数量は1以上を入力してください。');
					}
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_order = $_POST['plugin_catalog_order'];

			$plugin_catalog_order_sets = array();
			if (!empty($_POST['plugin_catalog_order']['count'])) {
				foreach ($_POST['plugin_catalog_order']['count'] as $id => $count) {
					$plugin_catalog_order_sets[$id] = array(
						'record_id'  => $_POST['plugin_catalog_order']['id'],
						'catalog_id' => $id,
						'price'      => $_POST['plugin_catalog_order']['price'][$id],
						'count'      => $count
					);
				}
			}
		} else {
			$_SESSION['input'] = $_POST;

			//プレビューへ移動
			freo_redirect('catalog_order/admin_order_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''), true);
		}
	} else {
		if ($_GET['id']) {
			//注文データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE record_id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_order = $data;
			} else {
				freo_error('指定された注文が見つかりません。', '404 Not Found');
			}

			//注文内容データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets WHERE record_id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			$plugin_catalog_order_sets = array();
			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_order_sets[$data['catalog_id']] = $data;
			}
		} else {
			//新規データ設定
			$plugin_catalog_order = array(
				'datetime' => date('Y-m-d H:i:s')
			);
			$plugin_catalog_order_sets = array();
		}
	}

	//商品取得
	if (empty($plugin_catalog_order_sets)) {
		$plugin_catalogs = array();
	} else {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($plugin_catalog_order_sets))) . ') ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_catalogs = $plugin_catalog_order_sets;
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalogs[$data['id']] = $data;
		}
	}

	//価格の小計を計算
	$plugin_catalog_price_subtotals = array();
	foreach ($plugin_catalogs as $plugin_catalog) {
		$plugin_catalog_price_subtotals[$plugin_catalog['id']] = $plugin_catalog['price'] * $plugin_catalog_order_sets[$plugin_catalog['id']]['count'];
	}

	//価格の合計を計算
	$plugin_catalog_price_total = array_sum($plugin_catalog_price_subtotals);

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//支払い方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//配送方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE status = \'publish\'');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_deliveries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_deliveries[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                          => freo_token('create'),
		'plugin_catalogs'                => $plugin_catalogs,
		'plugin_catalog_price_subtotals' => $plugin_catalog_price_subtotals,
		'plugin_catalog_price_total'     => $plugin_catalog_price_total,
		'plugin_catalog_categories'      => $plugin_catalog_categories,
		'plugin_catalog_payments'        => $plugin_catalog_payments,
		'plugin_catalog_deliveries'      => $plugin_catalog_deliveries,
		'plugin_catalog_targets'         => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'           => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'     => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status'    => freo_page_catalog_order_get_status(),
		'input' => array(
			'plugin_catalog_order'      => $plugin_catalog_order,
			'plugin_catalog_order_sets' => $plugin_catalog_order_sets
		)
	));

	if (isset($_GET['type']) and $_GET['type'] == 'print') {
		//データ出力
		freo_output('plugins/catalog_order/admin_order_print.html');
	}

	return;
}

/* 管理画面 | 注文登録 */
function freo_page_catalog_order_admin_order_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//入力データ取得
	$catalog_order = $_SESSION['input']['plugin_catalog_order'];

	if ($catalog_order['user_id'] == '') {
		$catalog_order['user_id'] = null;
	}
	if ($catalog_order['preferred_week'] == '') {
		$catalog_order['preferred_week'] = null;
	}
	if ($catalog_order['preferred_time'] == '') {
		$catalog_order['preferred_time'] = null;
	}
	if ($catalog_order['tel'] == '') {
		$catalog_order['tel'] = null;
	}
	if ($catalog_order['zipcode'] == '') {
		$catalog_order['zipcode'] = null;
	}
	if ($catalog_order['prefecture'] == '') {
		$catalog_order['prefecture'] = null;
	}
	if ($catalog_order['address'] == '') {
		$catalog_order['address'] = null;
	}
	if ($catalog_order['text'] == '') {
		$catalog_order['text'] = null;
	}
	if ($catalog_order['user_text'] == '') {
		$catalog_order['user_text'] = null;
	}
	if ($catalog_order['admin_text'] == '') {
		$catalog_order['admin_text'] = null;
	}

	if (isset($_GET['id'])) {
		//注文登録
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders SET user_id = :user_id, delivery_id = :delivery_id, payment_id = :payment_id, modified = :now, status = :status, carriage = :carriage, charge = :charge, discount = :discount, preferred_week = :preferred_week, preferred_time = :preferred_time, name = :name, kana = :kana, mail = :mail, tel = :tel, zipcode = :zipcode, prefecture = :prefecture, address = :address, text = :text, datetime = :datetime, user_text = :user_text, admin_text = :admin_text WHERE record_id = :id');
		$stmt->bindValue(':user_id',        $catalog_order['user_id']);
		$stmt->bindValue(':delivery_id',    $catalog_order['delivery_id']);
		$stmt->bindValue(':payment_id',     $catalog_order['payment_id']);
		$stmt->bindValue(':now',            date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',         $catalog_order['status']);
		$stmt->bindValue(':carriage',       $catalog_order['carriage'], PDO::PARAM_INT);
		$stmt->bindValue(':charge',         $catalog_order['charge'], PDO::PARAM_INT);
		$stmt->bindValue(':discount',       $catalog_order['discount'], PDO::PARAM_INT);
		$stmt->bindValue(':preferred_week', $catalog_order['preferred_week']);
		$stmt->bindValue(':preferred_time', $catalog_order['preferred_time']);
		$stmt->bindValue(':name',           $catalog_order['name']);
		$stmt->bindValue(':kana',           $catalog_order['kana']);
		$stmt->bindValue(':mail',           $catalog_order['mail']);
		$stmt->bindValue(':tel',            $catalog_order['tel']);
		$stmt->bindValue(':zipcode',        $catalog_order['zipcode']);
		$stmt->bindValue(':prefecture',     $catalog_order['prefecture']);
		$stmt->bindValue(':address',        $catalog_order['address']);
		$stmt->bindValue(':text',           $catalog_order['text']);
		$stmt->bindValue(':datetime',       $catalog_order['datetime']);
		$stmt->bindValue(':user_text',      $catalog_order['user_text']);
		$stmt->bindValue(':admin_text',     $catalog_order['admin_text']);
		$stmt->bindValue(':id',             $catalog_order['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//注文内容登録
		if (!empty($catalog_order['count'])) {
			foreach ($catalog_order['count'] as $id => $count) {
				$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets SET price = :price, count = :count WHERE record_id = :id AND catalog_id = :catalog_id');
				$stmt->bindValue(':price',      $catalog_order['price'][$id], PDO::PARAM_INT);
				$stmt->bindValue(':count',      $count, PDO::PARAM_INT);
				$stmt->bindValue(':id',         $catalog_order['id'], PDO::PARAM_INT);
				$stmt->bindValue(':catalog_id', $id);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}
			}
		}
	} else {
		//支払い方法取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\' AND id = :id');
		$stmt->bindValue(':id', $catalog_order['payment_id']);
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
		$stmt->bindValue(':id', $catalog_order['delivery_id']);
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
		$stmt = $freo->pdo->prepare('SELECT carriage FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :delivery_id AND prefecture = :prefecture');
		$stmt->bindValue(':delivery_id', $catalog_order['delivery_id']);
		$stmt->bindValue(':prefecture',  $catalog_order['prefecture']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalog_delivery['carriage'] += $data['carriage'];
		}

		//送料・手数料
		$catalog_order['carriage'] = $plugin_catalog_delivery['carriage'];
		$catalog_order['charge']   = $plugin_catalog_payment['charge'];

		//注文履歴登録
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_records VALUES(NULL, :user_id, :now1, :now2, NULL)');
		$stmt->bindValue(':user_id', $catalog_order['user_id']);
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$record_id = $freo->pdo->lastInsertId();

		//注文登録
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders VALUES(:record_id, :user_id, :delivery_id, :payment_id, :now1, :now2, :status, :carriage, :charge, :discount, :preferred_week, :preferred_time, :name, :kana, :mail, :tel, :zipcode, :prefecture, :address, :text, :datetime, :user_text, :admin_text)');
		$stmt->bindValue(':record_id',      $record_id, PDO::PARAM_INT);
		$stmt->bindValue(':user_id',        $catalog_order['user_id']);
		$stmt->bindValue(':delivery_id',    $catalog_order['delivery_id']);
		$stmt->bindValue(':payment_id',     $catalog_order['payment_id']);
		$stmt->bindValue(':now1',           date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',           date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',         $catalog_order['status']);
		$stmt->bindValue(':carriage',       $catalog_order['carriage'], PDO::PARAM_INT);
		$stmt->bindValue(':charge',         $catalog_order['charge'], PDO::PARAM_INT);
		$stmt->bindValue(':discount',       $catalog_order['discount'], PDO::PARAM_INT);
		$stmt->bindValue(':preferred_week', $catalog_order['preferred_week']);
		$stmt->bindValue(':preferred_time', $catalog_order['preferred_time']);
		$stmt->bindValue(':name',           $catalog_order['name']);
		$stmt->bindValue(':kana',           $catalog_order['kana']);
		$stmt->bindValue(':mail',           $catalog_order['mail']);
		$stmt->bindValue(':tel',            $catalog_order['tel']);
		$stmt->bindValue(':zipcode',        $catalog_order['zipcode']);
		$stmt->bindValue(':prefecture',     $catalog_order['prefecture']);
		$stmt->bindValue(':address',        $catalog_order['address']);
		$stmt->bindValue(':text',           $catalog_order['text']);
		$stmt->bindValue(':datetime',       $catalog_order['datetime']);
		$stmt->bindValue(':user_text',      $catalog_order['user_text']);
		$stmt->bindValue(':admin_text',     $catalog_order['admin_text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('注文を編集しました。');
	} else {
		freo_log('注文を新規に登録しました。');
	}

	//注文管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('catalog_order/admin_order?exec=update&id=' . $catalog_order['id'], true);
	} else {
		freo_redirect('catalog_order/admin_order?exec=insert', true);
	}

	return;
}

/* 管理画面 | 注文削除 */
function freo_page_catalog_order_admin_order_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//注文内容削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets WHERE record_id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//注文削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders WHERE record_id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('注文を削除しました。');

	//商品管理へ移動
	freo_redirect('catalog_order/admin_order?exec=delete&id=' . $_GET['id'], true);

	return;
}

/* 管理画面 | 注文商品入力 */
function freo_page_catalog_order_admin_order_cart_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//商品ID
			if ($_POST['plugin_catalog']['catalog_id'] == '') {
				$freo->smarty->append('errors', '商品IDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_catalog']['catalog_id'])) {
				$freo->smarty->append('errors', '商品IDは半角英数字で入力してください。');
			} elseif (preg_match('/^\d+$/', $_POST['plugin_catalog']['catalog_id'])) {
				$freo->smarty->append('errors', '商品IDには半角英字を含んでください。');
			} elseif (mb_strlen($_POST['plugin_catalog']['catalog_id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '商品IDは80文字以内で入力してください。');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog']['catalog_id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
					$freo->smarty->append('errors', '入力された商品IDは存在しません。');
				}
			}

			//数量
			if (!preg_match('/^\d+$/', $_POST['plugin_catalog']['count'])) {
				$freo->smarty->append('errors', '数量は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog']['count'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '数量は10文字以内で入力してください。');
			} elseif ($_POST['plugin_catalog']['count'] == 0) {
				$freo->smarty->append('errors', '数量は1以上を入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog = $_POST['plugin_catalog'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog_order/admin_order_cart_putin?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id'], true);
		}
	} else {
		//新規データ設定
		$plugin_catalog = array(
			'count' => 1
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_catalog' => $plugin_catalog
		)
	));

	return;
}

/* 管理画面 | 注文商品追加 */
function freo_page_catalog_order_admin_order_cart_putin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//入力データ取得
	$plugin_catalog = $_SESSION['input']['plugin_catalog'];

	//価格取得
	$stmt = $freo->pdo->prepare('SELECT price FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id');
	$stmt->bindValue(':id', $plugin_catalog['catalog_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_price = $data['price'];
	} else {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets SET count = count + :count WHERE record_id = :record_id AND catalog_id = :catalog_id');
	$stmt->bindValue(':count',      $plugin_catalog['count'], PDO::PARAM_INT);
	$stmt->bindValue(':record_id',  $plugin_catalog['id'], PDO::PARAM_INT);
	$stmt->bindValue(':catalog_id', $plugin_catalog['catalog_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (!$stmt->rowCount()) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets VALUES(:record_id, :catalog_id, :price, :count)');
		$stmt->bindValue(':record_id',  $plugin_catalog['id'], PDO::PARAM_INT);
		$stmt->bindValue(':catalog_id', $plugin_catalog['catalog_id']);
		$stmt->bindValue(':price',      $plugin_catalog_price, PDO::PARAM_INT);
		$stmt->bindValue(':count',      $plugin_catalog['count'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//ログ記録
	freo_log('注文商品を追加しました。');

	//注文入力へ移動
	freo_redirect('catalog_order/admin_order_form?exec=cart_putin&id=' . $plugin_catalog['id'], true);

	return;
}

/* 管理画面 | 注文商品削除 */
function freo_page_catalog_order_admin_order_cart_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}
	if (!isset($_GET['catalog_id']) or !preg_match('/^[\w\-]+$/', $_GET['catalog_id'])) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets WHERE record_id = :record_id AND catalog_id = :catalog_id');
	$stmt->bindValue(':record_id',  $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':catalog_id', $_GET['catalog_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('注文商品を削除しました。');

	//注文入力へ移動
	freo_redirect('catalog_order/admin_order_form?exec=cart_delete&id=' . $_GET['id'], true);

	return;
}

/* 管理画面 | 注文者管理 */
function freo_page_catalog_order_admin_user()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ユーザー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$users = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$users[$data['id']] = $data;
	}

	//ユーザーID取得
	$user_keys = array_keys($users);

	//ユーザー関連データ取得
	$user_associates = freo_associate_user('get', $user_keys);

	//注文者取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users ORDER BY user_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_order_users = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order_users[$data['user_id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'users'                      => $users,
		'user_associates'            => $user_associates,
		'plugin_catalog_order_users' => $plugin_catalog_order_users
	));

	return;
}

/* 管理画面 | 注文者入力 */
function freo_page_catalog_order_admin_user_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//名前
			if ($_POST['plugin_catalog_order_user']['name'] == '') {
				$freo->smarty->append('errors', '名前が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前は80文字以内で入力してください。');
			}

			//名前（フリガナ）
			if ($_POST['plugin_catalog_order_user']['name'] == '') {
				$freo->smarty->append('errors', '名前（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_catalog_order_user']['kana'])) {
				$freo->smarty->append('errors', '名前（フリガナ）は全角カタカナで入力してください。');
			}

			//メールアドレス
			if ($_POST['plugin_catalog_order_user']['mail'] == '') {
				$freo->smarty->append('errors', 'メールアドレスが入力されていません。');
			} elseif (!strpos($_POST['plugin_catalog_order_user']['mail'], '@')) {
				$freo->smarty->append('errors', 'メールアドレスの入力内容が正しくありません。');
			} elseif (mb_strlen($_POST['plugin_catalog_order_user']['mail'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'メールアドレスは80文字以内で入力してください。');
			}

			//電話番号
			if ($_POST['plugin_catalog_order_user']['tel'] != '') {
				if (!preg_match('/^[\d\-\(\)]+$/', $_POST['plugin_catalog_order_user']['tel'])) {
					$freo->smarty->append('errors', '電話番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order_user']['tel'], 'UTF-8') > 20) {
					$freo->smarty->append('errors', '電話番号は20文字以内で入力してください。');
				}
			}

			//郵便番号
			if ($_POST['plugin_catalog_order_user']['zipcode'] != '') {
				if (!preg_match('/^[\d\-]+$/', $_POST['plugin_catalog_order_user']['zipcode'])) {
					$freo->smarty->append('errors', '郵便番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order_user']['zipcode'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '郵便番号は10文字以内で入力してください。');
				}
			}

			//都道府県
			if ($_POST['plugin_catalog_order_user']['prefecture'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['prefecture'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '都道府県は80文字以内で入力してください。');
				}
			}

			//住所
			if ($_POST['plugin_catalog_order_user']['address'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['address'], 'UTF-8') > 500) {
					$freo->smarty->append('errors', '住所は500文字以内で入力してください。');
				}
			}

			//連絡事項
			if ($_POST['plugin_catalog_order_user']['text'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
				}
			}

			//連絡事項
			if ($_POST['plugin_catalog_order_user']['admin_text'] != '') {
				if (mb_strlen($_POST['plugin_catalog_order_user']['admin_text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '管理者用メモは5000文字以内で入力してください。');
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_order_user = $_POST['plugin_catalog_order_user'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog_order/admin_user_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''), true);
		}
	} else {
		if ($_GET['id']) {
			//ユーザー確認
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
				freo_error('指定されたユーザーが見つかりません。', '404 Not Found');
			}

			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_order_user = $data;
			} else {
				$plugin_catalog_order_user = array();
			}
		} else {
			freo_error('ユーザーを指定してください。');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_targets'      => freo_page_catalog_order_get_target(),
		'plugin_catalog_sizes'        => freo_page_catalog_order_get_size(),
		'plugin_catalog_prefectures'  => freo_page_catalog_order_get_prefecture(),
		'plugin_catalog_order_status' => freo_page_catalog_order_get_status(),
		'input' => array(
			'plugin_catalog_order_user' => $plugin_catalog_order_user
		)
	));

	return;
}

/* 管理画面 | 注文者登録 */
function freo_page_catalog_order_admin_user_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog_order/admin_user?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_user?error=1', true);
	}

	//入力データ取得
	$catalog_order_user = $_SESSION['input']['plugin_catalog_order_user'];

	if ($catalog_order_user['tel'] == '') {
		$catalog_order_user['tel'] = null;
	}
	if ($catalog_order_user['zipcode'] == '') {
		$catalog_order_user['zipcode'] = null;
	}
	if ($catalog_order_user['prefecture'] == '') {
		$catalog_order_user['prefecture'] = null;
	}
	if ($catalog_order_user['address'] == '') {
		$catalog_order_user['address'] = null;
	}
	if ($catalog_order_user['text'] == '') {
		$catalog_order_user['text'] = null;
	}
	if ($catalog_order_user['admin_text'] == '') {
		$catalog_order_user['admin_text'] = null;
	}

	//データ確認
	$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :user_id');
	$stmt->bindValue(':user_id', $catalog_order_user['user_id']);
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
		$stmt->bindValue(':user_id', $catalog_order_user['user_id']);
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users SET modified = :now, name = :name, kana = :kana, mail = :mail, tel = :tel, zipcode = :zipcode, prefecture = :prefecture, address = :address, text = :text, admin_text = :admin_text WHERE user_id = :user_id');
	$stmt->bindValue(':now',        date('Y-m-d H:i:s'));
	$stmt->bindValue(':name',       $catalog_order_user['name']);
	$stmt->bindValue(':kana',       $catalog_order_user['kana']);
	$stmt->bindValue(':mail',       $catalog_order_user['mail']);
	$stmt->bindValue(':tel',        $catalog_order_user['tel']);
	$stmt->bindValue(':zipcode',    $catalog_order_user['zipcode']);
	$stmt->bindValue(':prefecture', $catalog_order_user['prefecture']);
	$stmt->bindValue(':address',    $catalog_order_user['address']);
	$stmt->bindValue(':text',       $catalog_order_user['text']);
	$stmt->bindValue(':admin_text', $catalog_order_user['admin_text']);
	$stmt->bindValue(':user_id',    $catalog_order_user['user_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('注文者を編集しました。');

	//注文者管理へ移動
	freo_redirect('catalog_order/admin_user?exec=update&id=' . $_GET['id'], true);

	return;
}

/* 管理画面 | 注文者削除 */
function freo_page_catalog_order_admin_user_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('catalog_order/admin_user?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog_order/admin_order?error=1', true);
	}

	//注文削除
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders SET user_id = NULL WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//注文者削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_users WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('注文者を削除しました。');

	//注文者管理へ移動
	freo_redirect('catalog_order/admin_user?exec=delete&id=' . $_GET['id'], true);

	return;
}

/* 管理画面 | 注文集計 */
function freo_page_catalog_order_admin_total()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//対応状況取得
	$status = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_order_defines/status.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $new, $complete, $cancel, $cancel_ok) = explode(',', chop($line), 6);

			if ($complete == 1) {
				$status = array(
					'id'        => $id,
					'name'      => $name,
					'new'       => $new,
					'complete'  => $complete,
					'cancel'    => $cancel,
					'cancel_ok' => $cancel_ok
				);

				break;
			}
		}
		fclose($fp);
	}
	if (empty($status)) {
		freo_error('完了の対応状況が見つかりません。');
	}

	//注文内容データ取得
	$stmt = $freo->pdo->prepare('SELECT sets.catalog_id AS catalog_id, SUM(sets.count) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_order_sets AS sets LEFT JOIN ' . FREO_DATABASE_PREFIX . 'plugin_catalog_orders AS orders ON sets.record_id = orders.record_id WHERE orders.status = :status GROUP BY catalog_id ORDER BY count DESC LIMIT 100');
	$stmt->bindValue(':status', $status['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_order_sets = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_order_sets[$data['catalog_id']] = $data;
	}

	//商品取得
	if (empty($plugin_catalog_order_sets)) {
		$plugin_catalogs = array();
	} else {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($plugin_catalog_order_sets))) . ')');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_catalogs = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalogs[$data['id']] = $data;
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                       => freo_token('create'),
		'plugin_catalog_order_status' => $status,
		'plugin_catalog_order_sets'   => $plugin_catalog_order_sets,
		'plugin_catalogs'             => $plugin_catalogs
	));

	return;
}

/* 注文管理画面 */
function freo_page_catalog_order_default()
{
	global $freo;

	freo_redirect('catalog_order/admin');

	return;
}

/* 対象を取得 */
function freo_page_catalog_order_get_target()
{
	global $freo;

	$targets = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/targets.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $value) = explode(',', chop($line), 3);

			$targets[$id] = array(
				'id'    => $id,
				'name'  => $name,
				'value' => $value
			);
		}
		fclose($fp);
	} else {
		freo_error('対象定義ファイルを読み込めません。');
	}

	return $targets;
}

/* サイズを取得 */
function freo_page_catalog_order_get_size()
{
	global $freo;

	$sizes = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/sizes.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $short, $long) = explode(',', chop($line), 4);

			$sizes[$id] = array(
				'id'    => $id,
				'name'  => $name,
				'short' => $short,
				'long'  => $long
			);
		}
		fclose($fp);
	} else {
		freo_error('サイズ定義ファイルを読み込めません。');
	}

	return $sizes;
}

/* 都道府県を取得 */
function freo_page_catalog_order_get_prefecture()
{
	global $freo;

	$prefectures = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/prefectures.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name) = explode(',', chop($line), 2);

			$prefectures[$id] = array(
				'id'   => $id,
				'name' => $name
			);
		}
		fclose($fp);
	} else {
		freo_error('都道府県定義ファイルを読み込めません。');
	}

	return $prefectures;
}

/* 対応状況を取得 */
function freo_page_catalog_order_get_status()
{
	global $freo;

	$status = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_order_defines/status.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $new, $complete, $cancel, $cancel_ok) = explode(',', chop($line), 6);

			$status[$id] = array(
				'id'        => $id,
				'name'      => $name,
				'new'       => $new,
				'complete'  => $complete,
				'cancel'    => $cancel,
				'cancel_ok' => $cancel_ok
			);
		}
		fclose($fp);
	} else {
		freo_error('対応状況ファイルを読み込めません。');
	}

	return $status;
}

?>
