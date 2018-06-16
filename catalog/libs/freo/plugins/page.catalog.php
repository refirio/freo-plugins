<?php

/*********************************************************************

 ショッピングカートプラグイン (2013/04/15)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_catalog()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_catalog_setup();
			break;
		case 'setup_execute':
			freo_page_catalog_setup_execute();
			break;
		case 'view':
			freo_page_catalog_view();
			break;
		case 'target':
			freo_page_catalog_target();
			break;
		case 'target_post':
			freo_page_catalog_target_post();
			break;
		case 'cart':
			freo_page_catalog_cart();
			break;
		case 'cart_putin':
			freo_page_catalog_cart_putin();
			break;
		case 'cart_update':
			freo_page_catalog_cart_update();
			break;
		case 'cart_delete':
			freo_page_catalog_cart_delete();
			break;
		case 'cart_clear':
			freo_page_catalog_cart_clear();
			break;
		case 'order':
			freo_page_catalog_order();
			break;
		case 'order_preview':
			freo_page_catalog_order_preview();
			break;
		case 'order_send':
			freo_page_catalog_order_send();
			break;
		case 'order_complete':
			freo_page_catalog_order_complete();
			break;
		case 'delivery_prefecture':
			freo_page_catalog_delivery_prefecture();
			break;
		case 'admin':
			freo_page_catalog_admin();
			break;
		case 'admin_form':
			freo_page_catalog_admin_form();
			break;
		case 'admin_post':
			freo_page_catalog_admin_post();
			break;
		case 'admin_update':
			freo_page_catalog_admin_update();
			break;
		case 'admin_delete':
			freo_page_catalog_admin_delete();
			break;
		case 'admin_category':
			freo_page_catalog_admin_category();
			break;
		case 'admin_category_form':
			freo_page_catalog_admin_category_form();
			break;
		case 'admin_category_post':
			freo_page_catalog_admin_category_post();
			break;
		case 'admin_category_update':
			freo_page_catalog_admin_category_update();
			break;
		case 'admin_category_delete':
			freo_page_catalog_admin_category_delete();
			break;
		case 'admin_payment':
			freo_page_catalog_admin_payment();
			break;
		case 'admin_payment_form':
			freo_page_catalog_admin_payment_form();
			break;
		case 'admin_payment_post':
			freo_page_catalog_admin_payment_post();
			break;
		case 'admin_payment_update':
			freo_page_catalog_admin_payment_update();
			break;
		case 'admin_payment_delete':
			freo_page_catalog_admin_payment_delete();
			break;
		case 'admin_delivery':
			freo_page_catalog_admin_delivery();
			break;
		case 'admin_delivery_form':
			freo_page_catalog_admin_delivery_form();
			break;
		case 'admin_delivery_post':
			freo_page_catalog_admin_delivery_post();
			break;
		case 'admin_delivery_update':
			freo_page_catalog_admin_delivery_update();
			break;
		case 'admin_delivery_delete':
			freo_page_catalog_admin_delivery_delete();
			break;
		case 'admin_delivery_prefecture_form':
			freo_page_catalog_admin_delivery_prefecture_form();
			break;
		case 'admin_delivery_prefecture_post':
			freo_page_catalog_admin_delivery_prefecture_post();
			break;
		default:
			freo_page_catalog_default();
	}

	return;
}

/* セットアップ */
function freo_page_catalog_setup()
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
			freo_redirect('catalog/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'catalog',
		'plugin_name' => FREO_PLUGIN_CATALOG_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_catalog_setup_execute()
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
			'plugin_catalogs'                     => '(id VARCHAR(80) NOT NULL, category_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, display VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, price INT UNSIGNED NOT NULL, target VARCHAR(20), stock INT UNSIGNED, maximum INT UNSIGNED, unit VARCHAR(255), parallel VARCHAR(20) NOT NULL, size VARCHAR(20) NOT NULL, size_short INT UNSIGNED, size_long INT UNSIGNED, thickness INT UNSIGNED NOT NULL, weight INT UNSIGNED NOT NULL, packing_short INT UNSIGNED NOT NULL, packing_long INT UNSIGNED NOT NULL, packing_thickness INT UNSIGNED NOT NULL, packing_weight INT UNSIGNED NOT NULL, tag VARCHAR(255), datetime DATETIME NOT NULL, close DATETIME, text LONGTEXT, option01 TEXT, option02 TEXT, option03 TEXT, option04 TEXT, option05 TEXT, option06 TEXT, option07 TEXT, option08 TEXT, option09 TEXT, option10 TEXT, PRIMARY KEY(id))',
			'plugin_catalog_categories'           => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'plugin_catalog_payments'             => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, text TEXT NOT NULL, charge INT UNSIGNED, PRIMARY KEY(id))',
			'plugin_catalog_deliveries'           => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, preferred_week VARCHAR(20) NOT NULL, preferred_time VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, short_min INT UNSIGNED NOT NULL, short_max INT UNSIGNED NOT NULL, long_min INT UNSIGNED NOT NULL, long_max INT UNSIGNED NOT NULL, thickness_min INT UNSIGNED NOT NULL, thickness_max INT UNSIGNED NOT NULL, total_min INT UNSIGNED NOT NULL, total_max INT UNSIGNED NOT NULL, weight_min INT UNSIGNED NOT NULL, weight_max INT UNSIGNED NOT NULL, packing_short INT UNSIGNED NOT NULL, packing_long INT UNSIGNED NOT NULL, packing_thickness INT UNSIGNED NOT NULL, packing_total INT UNSIGNED NOT NULL, packing_weight INT UNSIGNED NOT NULL, carriage INT UNSIGNED, text TEXT, PRIMARY KEY(id))',
			'plugin_catalog_delivery_sets'        => '(delivery_id VARCHAR(80) NOT NULL, payment_id VARCHAR(80) NOT NULL)',
			'plugin_catalog_delivery_prefectures' => '(delivery_id VARCHAR(80) NOT NULL, prefecture VARCHAR(80) NOT NULL, carriage INT UNSIGNED NOT NULL)',
			'plugin_catalog_records'              => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, user_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR(80), PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_catalogs'                     => '(id VARCHAR NOT NULL, category_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, display VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, price INTEGER UNSIGNED NOT NULL, target VARCHAR, stock INTEGER UNSIGNED, maximum INTEGER UNSIGNED, unit VARCHAR, parallel VARCHAR NOT NULL, size VARCHAR NOT NULL, size_short INTEGER UNSIGNED, size_long INTEGER UNSIGNED, thickness INTEGER UNSIGNED NOT NULL, weight INTEGER UNSIGNED NOT NULL, packing_short INTEGER UNSIGNED NOT NULL, packing_long INTEGER UNSIGNED NOT NULL, packing_thickness INTEGER UNSIGNED NOT NULL, packing_weight INTEGER UNSIGNED NOT NULL, tag VARCHAR, datetime DATETIME NOT NULL, close DATETIME, text LONGTEXT, option01 TEXT, option02 TEXT, option03 TEXT, option04 TEXT, option05 TEXT, option06 TEXT, option07 TEXT, option08 TEXT, option09 TEXT, option10 TEXT, PRIMARY KEY(id))',
			'plugin_catalog_categories'           => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))',
			'plugin_catalog_payments'             => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, text TEXT NOT NULL, charge INTEGER UNSIGNED, PRIMARY KEY(id))',
			'plugin_catalog_deliveries'           => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, preferred_week VARCHAR NOT NULL, preferred_time VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, short_min INTEGER UNSIGNED NOT NULL, short_max INTEGER UNSIGNED NOT NULL, long_min INTEGER UNSIGNED NOT NULL, long_max INTEGER UNSIGNED NOT NULL, thickness_min INTEGER UNSIGNED NOT NULL, thickness_max INTEGER UNSIGNED NOT NULL, total_min INTEGER UNSIGNED NOT NULL, total_max INTEGER UNSIGNED NOT NULL, weight_min INTEGER UNSIGNED NOT NULL, weight_max INTEGER UNSIGNED NOT NULL, packing_short INTEGER UNSIGNED NOT NULL, packing_long INTEGER UNSIGNED NOT NULL, packing_thickness INTEGER UNSIGNED NOT NULL, packing_total INTEGER UNSIGNED NOT NULL, packing_weight INTEGER UNSIGNED NOT NULL, carriage INTEGER UNSIGNED, text TEXT, PRIMARY KEY(id))',
			'plugin_catalog_delivery_sets'        => '(delivery_id VARCHAR NOT NULL, payment_id VARCHAR NOT NULL)',
			'plugin_catalog_delivery_prefectures' => '(delivery_id VARCHAR NOT NULL, prefecture VARCHAR NOT NULL, carriage INTEGER UNSIGNED NOT NULL)',
			'plugin_catalog_records'              => '(id INTEGER, user_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR, PRIMARY KEY(id))'
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

	//仮データ登録
	$queries = array();

	if (empty($table[FREO_DATABASE_PREFIX . 'plugin_catalog_payments'])) {
		$queries[] = "plugin_catalog_payments VALUES('bank','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','1','銀行振込','銀行振込の口座をお知らせします。\n振り込み手数料が必要な場合、注文者様の負担となります。\n振込名義がご注文者の名前と異なる場合、連絡事項欄で振込名義をお知らせください。',NULL);";
		$queries[] = "plugin_catalog_payments VALUES('delivery','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','2','代金引換','商品のお届け時、配送業者が代金を回収します。\n配送料や代引手数料を含む合計金額を、配送業者に現金でお支払いください。','250');";
	}
	if (empty($table[FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries'])) {
		$queries[] = "plugin_catalog_deliveries VALUES('ordinary_25','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','1','普通郵便（～25g）','1','120','1','235','1','10','1','365','1','25','0','0','0','0','0','80',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('ordinary_50','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','2','普通郵便（～50g）','1','120','1','235','1','10','1','365','25','50','0','0','0','0','0','90',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('mail_10','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','3','メール便（～10mm）','1','400','1','400','1','10','1','700','1','1000','0','0','0','0','0','80',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('mail_20','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','4','メール便（～20mm）','1','400','1','400','10','20','1','700','1','1000','0','0','0','0','0','160',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('letterpack_light','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','5','レターパックライト','1','248','1','340','1','30','1','618','1','4000','0','0','0','0','0','350',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('letterpack_plus','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','6','レターパックプラス','1','248','1','340','1','50','1','638','1','4000','0','0','0','0','0','500',NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('yupack_60','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','yes','yes','7','ゆうパック（60サイズ）','1','600','1','600','1','600','1','600','1','30000','0','0','0','0','0',NULL,NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('yupack_80','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','yes','yes','8','ゆうパック（80サイズ）','600','800','600','800','600','800','600','800','1','30000','0','0','0','0','0',NULL,NULL);";
		$queries[] = "plugin_catalog_deliveries VALUES('entrust','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','9','おまかせ','1','9999','1','9999','1','9999','1','9999','1','99999','0','0','0','0','0',NULL,'注文完了後、\n配送方法を判断してお知らせします。');";
		$queries[] = "plugin_catalog_deliveries VALUES('download','" . date('Y-m-d H:i:s') . "','" . date('Y-m-d H:i:s') . "','publish','no','no','10','ダウンロード','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','0','ダウンロード販売の商品のみを購入される場合、\nこの配送方法を選択してください。');";
	}
	if (empty($table[FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets'])) {
		$queries[] = "plugin_catalog_delivery_sets VALUES('ordinary_25','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('ordinary_50','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('mail_10','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('mail_20','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('letterpack_light','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('letterpack_plus','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('yupack_60','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('yupack_60','delivery');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('yupack_80','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('yupack_80','delivery');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('entrust','bank');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('entrust','delivery');";
		$queries[] = "plugin_catalog_delivery_sets VALUES('download','bank');";
	}
	if (empty($table[FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures'])) {
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','hokkaido','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','aomori','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','iwate','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','miyagi','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','akita','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','yamagata','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','fukushima','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','ibaraki','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','tochigi','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','gunma','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','saitama','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','chiba','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','tokyo','600');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kanagawa','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','niigata','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','toyama','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','ishikawa','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','fukui','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','yamanashi','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','nagano','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','gifu','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','shizuoka','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','aichi','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','mie','700');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','shiga','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kyoto','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','osaka','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','hyogo','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','nara','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','wakayama','800');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','tottori','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','shimane','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','okayama','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','hiroshima','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','yamaguchi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','tokushima','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kagawa','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','ehime','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kochi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','fukuoka','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','saga','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','nagasaki','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kumamoto','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','oita','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','miyazaki','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','kagoshima','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_60','okinawa','1200');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','hokkaido','1200');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','aomori','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','iwate','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','miyagi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','akita','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','yamagata','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','fukushima','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','ibaraki','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','tochigi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','gunma','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','saitama','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','chiba','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','tokyo','600');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kanagawa','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','niigata','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','toyama','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','ishikawa','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','fukui','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','yamanashi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','nagano','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','gifu','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','shizuoka','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','aichi','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','mie','900');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','shiga','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kyoto','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','osaka','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','hyogo','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','nara','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','wakayama','1000');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','tottori','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','shimane','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','okayama','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','hiroshima','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','yamaguchi','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','tokushima','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kagawa','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','ehime','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kochi','1100');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','fukuoka','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','saga','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','nagasaki','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kumamoto','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','oita','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','miyazaki','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','kagoshima','1300');";
		$queries[] = "plugin_catalog_delivery_prefectures VALUES('yupack_80','okinawa','1200');";
	}

	foreach ($queries as $query) {
		$stmt = $freo->pdo->query('INSERT INTO ' . FREO_DATABASE_PREFIX . $query);
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log(FREO_PLUGIN_CATALOG_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('catalog/setup?exec=setup', true);

	return;
}

/* 商品表示 */
function freo_page_catalog_view()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[2])) {
		$_GET['id'] = $freo->parameters[2];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_error('表示したい商品を指定してください。');
	}

	//商品取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id AND status = \'publish\'');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog = $data;
	} else {
		freo_error('指定された商品が見つかりません。', '404 Not Found');
	}

	//対象の初期値
	if (!isset($_SESSION['plugin']['catalog']['target']) and $freo->config['plugin']['catalog']['target_default']) {
		$_SESSION['plugin']['catalog']['target'] = $freo->config['plugin']['catalog']['target_default'];
	}

	//対象確認
	if ($plugin_catalog['target'] and (!isset($_GET['type']) or $_GET['type'] != 'embed')) {
		$plugin_catalog_targets = freo_page_catalog_get_target();

		if (empty($_SESSION['plugin']['catalog']['target']) or $plugin_catalog_targets[$_SESSION['plugin']['catalog']['target']]['value'] < $plugin_catalog_targets[$plugin_catalog['target']]['value']) {
			//対象確認実行へ移動
			freo_redirect('catalog/target/' . $plugin_catalog['target'] . '?catalog_id=' . $plugin_catalog['id']);
		}
	}

	//商品タグ取得
	if ($plugin_catalog['tag']) {
		$plugin_catalog_tags = explode(',', $plugin_catalog['tag']);
	} else {
		$plugin_catalog_tags = array();
	}

	//商品ファイル取得
	$plugin_catalog_files = array();

	$file_dir = FREO_FILE_DIR . 'plugins/catalog_files/' . $_GET['id'] . '/';

	if (file_exists($file_dir)) {
		if ($dir = scandir($file_dir)) {
			foreach ($dir as $data) {
				if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
					$plugin_catalog_files[$matches[1]] = $data;
				}
			}
		} else {
			freo_error('商品ファイル保存ディレクトリを開けません。');
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog'             => $plugin_catalog,
		'plugin_catalog_tags'        => $plugin_catalog_tags,
		'plugin_catalog_files'       => $plugin_catalog_files,
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	if (isset($_GET['type']) and $_GET['type'] == 'embed') {
		//データ出力
		freo_output('plugins/catalog/embed.html');
	}

	return;
}

/* 対象確認 */
function freo_page_catalog_target()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[2])) {
		$_GET['id'] = $freo->parameters[2];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_error('対象を指定してください。');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		$_SESSION['input'] = $_POST;

		//対象確認実行へ移動
		freo_redirect('catalog/target_post?freo%5Btoken%5D=' . freo_token('create'));
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 対象確認実行 */
function freo_page_catalog_target_post()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('target?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/target?error=1');
	}

	//入力データ取得
	$plugin_catalog_target = $_SESSION['input']['plugin_catalog_target'];
	$plugin_catalog        = $_SESSION['input']['plugin_catalog'];

	//データ登録
	$_SESSION['plugin']['catalog']['target'] = $plugin_catalog_target;

	//入力データ破棄
	$_SESSION['input'] = array();

	if ($plugin_catalog) {
		//商品表示へ移動
		freo_redirect('catalog/view/' . $plugin_catalog);
	} else {
		//商品一覧へ移動
		freo_redirect('catalog');
	}

	return;
}

/* カート */
function freo_page_catalog_cart()
{
	global $freo;

	//カートの内容を取得
	if (empty($_SESSION['plugin']['catalog']['cart'])) {
		$plugin_catalog_cart = array();
	} else {
		$plugin_catalog_cart = freo_page_catalog_get_cart($_SESSION['plugin']['catalog']['cart']);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_cart'        => $plugin_catalog_cart,
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* カート | 商品追加 */
function freo_page_catalog_cart_putin()
{
	global $freo;

	//入力データ検証
	if (!isset($_POST['plugin_catalog']['id'])) {
		freo_error('カートに入れたい商品を指定してください。');
	}
	if (!isset($_POST['plugin_catalog']['count'])) {
		freo_error('カートに入れたい数量を指定してください。');
	}

	//商品を確認
	if (isset($_SESSION['plugin']['catalog']['cart'][$_POST['plugin_catalog']['id']])) {
		$count = $_SESSION['plugin']['catalog']['cart'][$_POST['plugin_catalog']['id']];
	} else {
		$count = 0;
	}

	$message = freo_page_catalog_check_quantity(array(
		$_POST['plugin_catalog']['id'] => $count + $_POST['plugin_catalog']['count']
	));
	if ($message) {
		freo_error($message);
	}

	//商品追加
	if (isset($_SESSION['plugin']['catalog']['cart'][$_POST['plugin_catalog']['id']])) {
		$_SESSION['plugin']['catalog']['cart'][$_POST['plugin_catalog']['id']] += $_POST['plugin_catalog']['count'];
	} else {
		$_SESSION['plugin']['catalog']['cart'][$_POST['plugin_catalog']['id']] = 1;
	}

	//カートへ移動
	freo_redirect('catalog/cart?exec=putin');

	return;
}

/* カート | 数量更新 */
function freo_page_catalog_cart_update()
{
	global $freo;

	//商品を確認
	$message = freo_page_catalog_check_quantity($_POST['count']);
	if ($message) {
		freo_error($message);
	}

	//データ登録
	if (isset($_POST['count'])) {
		foreach ($_POST['count'] as $id => $count) {
			if (!preg_match('/^[\w\-]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $count)) {
				continue;
			}

			$_SESSION['plugin']['catalog']['cart'][$id] = $count;
		}
	}

	//カートへ移動
	freo_redirect('catalog/cart?exec=update');

	return;
}

/* カート | 商品削除 */
function freo_page_catalog_cart_delete()
{
	global $freo;

	//入力データ検証
	if (!isset($_GET['id'])) {
		freo_error('削除したい商品を指定してください。');
	}

	//商品削除
	unset($_SESSION['plugin']['catalog']['cart'][$_GET['id']]);

	//カートへ移動
	freo_redirect('catalog/cart?exec=delete&id=' . $_GET['id']);

	return;
}

/* カート | クリア */
function freo_page_catalog_cart_clear()
{
	global $freo;

	//商品削除
	unset($_SESSION['plugin']['catalog']['cart']);

	//カートへ移動
	freo_redirect('catalog/cart?exec=clear');

	return;
}

/* 注文 */
function freo_page_catalog_order()
{
	global $freo;

	//注文の一時休止確認
	if ($freo->config['plugin']['catalog']['closed']) {
		freo_error('現在、すべての商品の注文を休止しています。');
	}

	//入力データ確認
	if (empty($_SESSION['plugin']['catalog']['cart'])) {
		freo_redirect('catalog');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//商品を確認
		$message = freo_page_catalog_check_quantity($_SESSION['plugin']['catalog']['order']);
		if ($message) {
			freo_error($message);
		}

		//カートの内容を取得
		$plugin_catalog_cart = freo_page_catalog_get_cart($_SESSION['plugin']['catalog']['order']);

		//配送希望曜日・配送希望時間設定
		if (!empty($_POST['plugin_catalog_order']['delivery_id'])) {
			$stmt = $freo->pdo->prepare('SELECT preferred_week, preferred_time FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id AND status = \'publish\'');
			$stmt->bindValue(':id', $_POST['plugin_catalog_order']['delivery_id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				if ($data['preferred_week'] == 'no') {
					$_POST['plugin_catalog_order']['preferred_week'] = null;
				}
				if ($data['preferred_time'] == 'no') {
					$_POST['plugin_catalog_order']['preferred_time'] = null;
				}
			}
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//配送方法
			if (empty($_POST['plugin_catalog_order']['delivery_id'])) {
				$freo->smarty->append('errors', '配送方法が入力されていません。');
			}

			//支払い方法
			if (empty($_POST['plugin_catalog_order']['payment_id'])) {
				$freo->smarty->append('errors', '支払い方法が入力されていません。');
			}

			//利用できる支払い方法
			if (!empty($_POST['plugin_catalog_order']['delivery_id']) and !empty($_POST['plugin_catalog_order']['payment_id'])) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets WHERE delivery_id = :delivery_id AND payment_id = :payment_id');
				$stmt->bindValue(':delivery_id', $_POST['plugin_catalog_order']['delivery_id']);
				$stmt->bindValue(':payment_id',  $_POST['plugin_catalog_order']['payment_id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if (!($data = $stmt->fetch(PDO::FETCH_ASSOC))) {
					$freo->smarty->append('errors', 'その配送方法と支払い方法の組み合わせは利用できません。');
				}
			}

			//対象確認
			if (isset($_POST['plugin_catalog_order']['target'])) {
				if (empty($_POST['plugin_catalog_order']['confirm']) or $_POST['plugin_catalog_order']['confirm'] != $_POST['plugin_catalog_order']['target']) {
					$freo->smarty->append('errors', '対象確認が入力されていません。');
				}
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

			if ($plugin_catalog_cart['catalog_short_max'] > 0 and $plugin_catalog_cart['catalog_long_max'] > 0) {
				//郵便番号
				if ($_POST['plugin_catalog_order']['zipcode'] == '') {
					$freo->smarty->append('errors', '郵便番号が入力されていません。');
				} elseif (!preg_match('/^[\d\-]+$/', $_POST['plugin_catalog_order']['zipcode'])) {
					$freo->smarty->append('errors', '郵便番号は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_order']['zipcode'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '郵便番号は10文字以内で入力してください。');
				}

				//都道府県
				if ($_POST['plugin_catalog_order']['prefecture'] == '') {
					$freo->smarty->append('errors', '都道府県が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_catalog_order']['prefecture'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '都道府県は80文字以内で入力してください。');
				}

				//住所
				if ($_POST['plugin_catalog_order']['address'] == '') {
					$freo->smarty->append('errors', '住所が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_catalog_order']['address'], 'UTF-8') > 500) {
					$freo->smarty->append('errors', '住所は500文字以内で入力してください。');
				}
			}

			//連絡事項
			if (mb_strlen($_POST['plugin_catalog_order']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '連絡事項は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_order = $_POST['plugin_catalog_order'];
		} else {
			$_SESSION['input'] = $_POST;

			//プレビューへ移動
			freo_redirect('catalog/order_preview', true);
		}
	} else {
		//商品を確認
		$message = freo_page_catalog_check_quantity($_SESSION['plugin']['catalog']['cart']);
		if ($message) {
			freo_error($message);
		}

		//カートの内容を取得
		$plugin_catalog_cart = freo_page_catalog_get_cart($_SESSION['plugin']['catalog']['cart']);

		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_catalog_order = $_SESSION['input']['plugin_catalog_order'];
		} else {
			//カートの内容を複製
			$_SESSION['plugin']['catalog']['order'] = $_SESSION['plugin']['catalog']['cart'];

			//新規データ設定
			$plugin_catalog_order = array();
		}
	}

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
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//配送方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE status = \'publish\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_deliveries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_deliveries[$data['id']] = $data;
	}

	//並列梱包できる商品を探す
	$sizes = freo_page_catalog_get_size();

	$plugin_catalog_parallel_availables = array();
	foreach ($plugin_catalog_deliveries as $plugin_catalog_delivery) {
		foreach ($plugin_catalog_cart['catalogs'] as $catalog) {
			if ($catalog['parallel'] == 'no') {	//並列梱包の許可を確認
				continue;
			}

			if ($catalog['size'] == 'direct') {	//商品の短辺を取得
				$short = $catalog['size_short'];
			} elseif ($catalog['size'] == 'data') {
				$short = 0;
			} else {
				$short = $sizes[$catalog['size']]['short'];
			}
			$short += $catalog['packing_short'];

			if ($catalog['size'] == 'direct') {	//商品の長辺を取得
				$long = $catalog['size_long'];
			} elseif ($catalog['size'] == 'data') {
				$long = 0;
			} else {
				$long = $sizes[$catalog['size']]['long'];
			}
			$long += $catalog['packing_long'];

			if ($short > $plugin_catalog_delivery['long_max'] / 2 or $long > $plugin_catalog_delivery['short_max']) {	//梱包の長辺の半分に商品の短辺が収まり、梱包の短辺に商品の長辺が収まるか確認
				continue;
			}

			for ($i = 0; $i < $plugin_catalog_cart['catalog_counts'][$catalog['id']]; $i++) {	//カートにある商品の数だけ、並列梱包できる商品リストに追加
				$plugin_catalog_parallel_availables[$plugin_catalog_delivery['id']][] = array(
					'id'        => $catalog['id'],
					'thickness' => $catalog['thickness'] + $catalog['packing_thickness']
				);
			}
		}
	}

	//並列梱包を確認
	$plugin_catalog_parallels       = array();
	$plugin_catalog_parallel_totals = array();
	foreach ($plugin_catalog_parallel_availables as $delivery_id => $orders) {
		foreach($orders as $key => $data){	//並列梱包できる商品リストを、厚さの大きい順に並べる
			$tmp[$key] = $data['thickness'];
		}
		array_multisort($tmp, SORT_DESC, $orders);

		while (count($orders) >= 2) {	//並列梱包できる商品の数が2つ以上あるか調べる
			$thick = array_shift($orders);
			$thin  = array_shift($orders);

			$plugin_catalog_parallels[$delivery_id][] = array(	//厚さの大きい順に2つ取り出して並列梱包にする
				'thick'     => $thick['id'],
				'thin'      => $thin['id'],
				'thickness' => $thin['thickness']
			);

			if (isset($plugin_catalog_parallel_totals[$delivery_id])) {	//並列梱包により、合計何mm薄くなったか
				$plugin_catalog_parallel_totals[$delivery_id] += $thin['thickness'];
			} else {
				$plugin_catalog_parallel_totals[$delivery_id] = $thin['thickness'];
			}
		}
	}

	//利用できる配送方法
	$plugin_catalog_delivery_availables = array();
	foreach ($plugin_catalog_deliveries as $plugin_catalog_delivery) {
		$thickness_total = $plugin_catalog_cart['catalog_thickness_total'];

		if (isset($plugin_catalog_parallel_totals[$plugin_catalog_delivery['id']])) {
			$thickness_total -= $plugin_catalog_parallel_totals[$plugin_catalog_delivery['id']];	//並列梱包による厚さ調整
		}

		if ($plugin_catalog_cart['catalog_short_max'] < $plugin_catalog_delivery['short_min'] or $plugin_catalog_cart['catalog_short_max'] > $plugin_catalog_delivery['short_max'] - $plugin_catalog_delivery['packing_short']) {
			continue;
		}
		if ($plugin_catalog_cart['catalog_long_max'] < $plugin_catalog_delivery['long_min'] or $plugin_catalog_cart['catalog_long_max'] > $plugin_catalog_delivery['long_max'] - $plugin_catalog_delivery['packing_long']) {
			continue;
		}
		if ($thickness_total < $plugin_catalog_delivery['thickness_min'] or $thickness_total > $plugin_catalog_delivery['thickness_max'] - $plugin_catalog_delivery['packing_thickness']) {
			continue;
		}
		if ($plugin_catalog_cart['catalog_short_max'] + $plugin_catalog_cart['catalog_long_max'] + $thickness_total < $plugin_catalog_delivery['total_min'] or $plugin_catalog_cart['catalog_short_max'] + $plugin_catalog_cart['catalog_long_max'] + $thickness_total > $plugin_catalog_delivery['total_max'] - $plugin_catalog_delivery['packing_total']) {
			continue;
		}
		if ($plugin_catalog_cart['catalog_weight_total'] < $plugin_catalog_delivery['weight_min'] or $plugin_catalog_cart['catalog_weight_total'] > $plugin_catalog_delivery['weight_max'] - $plugin_catalog_delivery['packing_weight']) {
			continue;
		}

		$plugin_catalog_delivery_availables[$plugin_catalog_delivery['id']] = true;
	}

	//配送方法ごとの支払い方法
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_delivery_sets = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (empty($plugin_catalog_payments[$data['payment_id']])) {
			continue;
		}

		$plugin_catalog_delivery_sets[$data['delivery_id']][]  = $data;
	}

	//利用できる支払い方法
	$plugin_catalog_payment_availables = array();
	foreach ($plugin_catalog_deliveries as $plugin_catalog_delivery) {
		foreach ($plugin_catalog_delivery_sets[$plugin_catalog_delivery['id']] as $plugin_catalog_delivery_set) {
			$plugin_catalog_payment_availables[$plugin_catalog_delivery_set['payment_id']] = true;
		}
	}

	//地域別送料取得
	$stmt = $freo->pdo->query('SELECT delivery_id, SUM(carriage) AS carriage FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures GROUP BY delivery_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_delivery_prefectures = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_delivery_prefectures[$data['delivery_id']] = $data['carriage'] ? true : false;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                               => freo_token('create'),
		'plugin_catalog_cart'                 => $plugin_catalog_cart,
		'plugin_catalog_categories'           => $plugin_catalog_categories,
		'plugin_catalog_payments'             => $plugin_catalog_payments,
		'plugin_catalog_deliveries'           => $plugin_catalog_deliveries,
		'plugin_catalog_delivery_availables'  => $plugin_catalog_delivery_availables,
		'plugin_catalog_parallels'            => array_shift($plugin_catalog_parallels),
		'plugin_catalog_parallel_totals'      => $plugin_catalog_parallel_totals,
		'plugin_catalog_parallel_total'       => array_shift($plugin_catalog_parallel_totals),
		'plugin_catalog_delivery_sets'        => $plugin_catalog_delivery_sets,
		'plugin_catalog_payment_availables'   => $plugin_catalog_payment_availables,
		'plugin_catalog_delivery_prefectures' => $plugin_catalog_delivery_prefectures,
		'plugin_catalog_targets'              => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'                => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures'          => freo_page_catalog_get_prefecture(),
		'input' => array(
			'plugin_catalog_order' => $plugin_catalog_order
		)
	));

	return;
}

/* 注文 | 入力内容確認 */
function freo_page_catalog_order_preview()
{
	global $freo;

	//注文の一時休止確認
	if ($freo->config['plugin']['catalog']['closed']) {
		freo_error('現在、すべての商品の注文を休止しています。');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog');
	}

	//商品を確認
	$message = freo_page_catalog_check_quantity($_SESSION['plugin']['catalog']['order']);
	if ($message) {
		freo_error($message);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('catalog');
		}

		//登録処理へ移動
		freo_redirect('catalog/order_send?freo%5Btoken%5D=' . freo_token('create'), true);
	}

	//注文内容取得
	$plugin_catalog_cart = freo_page_catalog_get_cart($_SESSION['plugin']['catalog']['order']);

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
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\' AND id = :id');
	$stmt->bindValue(':id', $_SESSION['input']['plugin_catalog_order']['payment_id']);
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
	$stmt->bindValue(':id', $_SESSION['input']['plugin_catalog_order']['delivery_id']);
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
	if ($_SESSION['input']['plugin_catalog_order']['prefecture'] != '') {
		$stmt = $freo->pdo->prepare('SELECT carriage FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :delivery_id AND prefecture = :prefecture');
		$stmt->bindValue(':delivery_id', $_SESSION['input']['plugin_catalog_order']['delivery_id']);
		$stmt->bindValue(':prefecture',  $_SESSION['input']['plugin_catalog_order']['prefecture']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalog_delivery['carriage'] += $data['carriage'];
		}
	}

	//送料無料判定
	if ($freo->config['plugin']['catalog']['free_shipping'] and $plugin_catalog_cart['catalog_price_total'] >= $freo->config['plugin']['catalog']['free_shipping']) {
		$plugin_catalog_delivery['carriage'] = 0;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_order'       => $_SESSION['input']['plugin_catalog_order'],
		'plugin_catalog_cart'        => $plugin_catalog_cart,
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_payment'     => $plugin_catalog_payment,
		'plugin_catalog_delivery'    => $plugin_catalog_delivery,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 注文 | メール送信 */
function freo_page_catalog_order_send()
{
	global $freo;

	//注文の一時休止確認
	if ($freo->config['plugin']['catalog']['closed']) {
		freo_error('現在、すべての商品の注文を休止しています。');
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog');
	}

	//トランザクション開始
	$freo->pdo->beginTransaction();

	//商品を確認
	$message = freo_page_catalog_check_quantity($_SESSION['plugin']['catalog']['order']);
	if ($message) {
		freo_error($message);
	}

	//入力データ取得
	$catalog = $_SESSION['input']['plugin_catalog_order'];

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

	//対象を取得
	$plugin_catalog_targets = freo_page_catalog_get_target();

	//サイズを取得
	$plugin_catalog_sizes = freo_page_catalog_get_size();

	//都道府県を取得
	$plugin_catalog_prefectures = freo_page_catalog_get_prefecture();

	//注文内容取得
	$order = freo_page_catalog_get_cart($_SESSION['plugin']['catalog']['order']);

	//送料無料判定
	if ($freo->config['plugin']['catalog']['free_shipping'] and $order['catalog_price_total'] >= $freo->config['plugin']['catalog']['free_shipping']) {
		$plugin_catalog_delivery['carriage'] = 0;
	}

	//在庫調整
	foreach ($_SESSION['plugin']['catalog']['order'] as $id => $count) {
		if (!preg_match('/^[\w\-]+$/', $id)) {
			continue;
		}
		if (!preg_match('/^\d+$/', $count)) {
			continue;
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalogs SET stock = stock - :stock WHERE id = :id AND stock IS NOT NULL');
		$stmt->bindValue(':stock', $count, PDO::PARAM_INT);
		$stmt->bindValue(':id',    $id);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//注文履歴登録
	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_records VALUES(NULL, :user_id, :now1, :now2, :ip)');
	$stmt->bindValue(':user_id', $freo->user['id']);
	$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
	$stmt->bindValue(':ip',      $_SERVER['REMOTE_ADDR']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//注文ID取得
	$_SESSION['plugin']['catalog']['record_id'] = $freo->pdo->lastInsertId();

	//メール本文定義
	$body  = "==================================================\n";
	$body .= "■ご注文内容\n";
	$body .= "==================================================\n";
	$body .= "\n";
	$body .= "ご注文ID : " . $freo->config['plugin']['catalog']['order_prefix'] . $_SESSION['plugin']['catalog']['record_id'] . "\n";
	$body .= "\n";

	foreach ($order['catalogs'] as $order_catalog) {
		$body .= "商品ID : " . $order_catalog['id'] . "\n";
		$body .= "商品名 : " . $order_catalog['name'] . "\n";
		$body .= "価格   : " . $order_catalog['price'] . "円\n";
		$body .= "数量   : " . $order['catalog_counts'][$order_catalog['id']] . $order_catalog['unit'] . "\n";
		$body .= "小計   : " . $order['catalog_price_subtotals'][$order_catalog['id']] . "円\n";
		$body .= "\n";
	}

	$body .= "--------------------------------------------------\n";
	$body .= "\n";
	$body .= "商品合計 : " . $order['catalog_price_total'] . "円\n";

	if ($plugin_catalog_delivery['carriage'] != '') {
		$body .= "送料     : " . $plugin_catalog_delivery['carriage'] . "円\n";
	}
	if ($plugin_catalog_payment['charge'] != '') {
		$body .= "手数料   : " . $plugin_catalog_payment['charge'] . "円\n";
	}

	$body .= "\n";
	$body .= "--------------------------------------------------\n";
	$body .= "\n";
	$body .= "お支払い額合計 : " . ($order['catalog_price_total'] + $plugin_catalog_delivery['carriage'] + $plugin_catalog_payment['charge']) . "円（税込）\n";
	$body .= "\n";
	$body .= "==================================================\n";
	$body .= "■希望配送方法\n";
	$body .= "==================================================\n";
	$body .= "\n";
	$body .= $plugin_catalog_delivery['name'] . "\n";

	if ($plugin_catalog_delivery['text']) {
		$body .= "  " . str_replace("\n", "\n  ", $plugin_catalog_delivery['text']) . "\n";
	}

	if ($catalog['preferred_week']) {
		$body .= "\n";
		$body .= "==================================================\n";
		$body .= "■希望配送曜日\n";
		$body .= "==================================================\n";
		$body .= "\n";
		$body .= $catalog['preferred_week'] . "\n";
	}

	if ($catalog['preferred_time']) {
		$body .= "\n";
		$body .= "==================================================\n";
		$body .= "■希望配送時間\n";
		$body .= "==================================================\n";
		$body .= "\n";
		$body .= $catalog['preferred_time'] . "\n";
	}

	$body .= "\n";
	$body .= "==================================================\n";
	$body .= "■お支払い方法\n";
	$body .= "==================================================\n";
	$body .= "\n";
	$body .= $plugin_catalog_payment['name'] . "\n";

	if ($plugin_catalog_payment['text']) {
		$body .= "  " . str_replace("\n", "\n  ", $plugin_catalog_payment['text']) . "\n";
	}

	$body .= "\n";
	$body .= "==================================================\n";
	$body .= "■ご注文者\n";
	$body .= "==================================================\n";
	$body .= "\n";
	$body .= "名前     : " . $catalog['name'] . "（" . $catalog['kana'] . "）\n";
	$body .= "Ｅメール : " . $catalog['mail'] . "\n";

	if ($catalog['tel']) {
		$body .= "電話番号 : " . $catalog['tel'] . "\n";
	}

	if ($order['catalog_short_max'] > 0 and $order['catalog_long_max'] > 0) {
		$body .= "\n";
		$body .= "==================================================\n";
		$body .= "■配送先住所\n";
		$body .= "==================================================\n";
		$body .= "\n";
		$body .= "郵便番号 : " . $catalog['zipcode'] . "\n";
		$body .= "都道府県 : " . $plugin_catalog_prefectures[$catalog['prefecture']]['name'] . "\n";
		$body .= "住所     : " . $catalog['address'] . "\n";
	}

	if ($catalog['text']) {
		$body .= "\n";
		$body .= "==================================================\n";
		$body .= "■連絡事項\n";
		$body .= "==================================================\n";
		$body .= "\n";
		$body .= $catalog['text'] . "\n";
	}

	//管理者向けメールヘッダ定義
	$headers = array(
		'From' => '"' . mb_encode_mimeheader(mb_convert_kana($catalog['name'], 'KV', 'UTF-8')) . '" <' . $catalog['mail'] . '>'
	);

	//管理者向けメール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog/inform_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog/inform_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	//管理者向けメール送信
	$flag = freo_mail($freo->config['plugin']['catalog']['mail_to'], '注文を受け付けました', $mail_header . $body . $mail_footer, $headers);
	if (!$flag) {
		freo_error('管理者にメールを送信できません。');
	}

	//注文者向けメールヘッダ定義
	$headers = array(
		'From' => '"' . mb_encode_mimeheader(mb_convert_kana($freo->config['plugin']['catalog']['mail_name'], 'KV', 'UTF-8')) . '" <' . $freo->config['plugin']['catalog']['mail_from'] . '>'
	);

	//注文者向けメール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog/order_header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/catalog/order_footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	//注文者向けメール送信
	$flag = freo_mail($catalog['mail'], $freo->config['plugin']['catalog']['mail_subject'], $mail_header . $body . $mail_footer, $headers);
	if (!$flag) {
		freo_error('注文者にメールを送信できません。');
	}

	//トランザクション終了
	$freo->pdo->commit();

	//入力データ破棄
	$_SESSION['input'] = array();

	unset($_SESSION['plugin']['catalog']['cart']);
	unset($_SESSION['plugin']['catalog']['order']);

	//ログ記録
	freo_log('注文メールを送信しました。');

	//登録完了画面へ移動
	freo_redirect('catalog/order_complete', true);

	return;
}

/* 注文 | メール送信完了 */
function freo_page_catalog_order_complete()
{
	global $freo;

	//注文の一時休止確認
	if ($freo->config['plugin']['catalog']['closed']) {
		freo_error('現在、すべての商品の注文を休止しています。');
	}

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
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE status = \'publish\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//配送方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE status = \'publish\' ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_deliveries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_deliveries[$data['id']] = $data;
	}

	//注文ID取得
	$record_id = $_SESSION['plugin']['catalog']['record_id'];

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_payments'    => $plugin_catalog_payments,
		'plugin_catalog_deliveries'  => $plugin_catalog_deliveries,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture(),
		'plugin_catalog_record_id'   => $record_id
	));

	return;
}

/* 地域別送料表示 */
function freo_page_catalog_delivery_prefecture()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//配送方法取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_delivery = $data;
	} else {
		freo_error('指定された配送方法が見つかりません。', '404 Not Found');
	}

	//地域別送料取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_delivery_prefectures = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_delivery_prefectures[$data['prefecture']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                               => freo_token('create'),
		'plugin_catalog_targets'              => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'                => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures'          => freo_page_catalog_get_prefecture(),
		'plugin_catalog_delivery'             => $plugin_catalog_delivery,
		'plugin_catalog_delivery_prefectures' => $plugin_catalog_delivery_prefectures
	));

	return;
}

/* 管理画面 | 商品管理 */
function freo_page_catalog_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['category_id']) or !preg_match('/^[\w\-]+$/', $_GET['category_id'])) {
		$_GET['category_id'] = null;
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$words = explode(' ', str_replace('　', ' ', $_GET['word']));

		foreach ($words as $word) {
			$condition .= ' AND (name LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option01 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option02 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option03 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option04 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option05 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option06 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option07 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option08 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option09 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option10 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
		}
	}
	if (isset($_GET['category_id'])) {
		$condition .= ' AND category_id = ' . $freo->pdo->quote($_GET['category_id']);
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//商品取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs ' . $condition . ' ORDER BY sort, id LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['catalog']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['catalog']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalogs = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalogs[$data['id']] = $data;
	}

	//商品数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                 = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_catalog_count = $data[0];
	$plugin_catalog_page  = ceil($plugin_catalog_count / $freo->config['plugin']['catalog']['admin_limit']);

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalogs'            => $plugin_catalogs,
		'plugin_catalog_count'       => $plugin_catalog_count,
		'plugin_catalog_page'        => $plugin_catalog_page,
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 管理画面 | 商品入力 */
function freo_page_catalog_admin_form()
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

	//ファイル番号定義
	$numbers = array('', '_thumbnail', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//日時データ取得
		if (is_array($_POST['plugin_catalog']['datetime'])) {
			$year   = mb_convert_kana($_POST['plugin_catalog']['datetime']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['plugin_catalog']['datetime']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['plugin_catalog']['datetime']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['plugin_catalog']['datetime']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['plugin_catalog']['datetime']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['plugin_catalog']['datetime']['second'], 'n', 'UTF-8');

			$_POST['plugin_catalog']['datetime'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}
		if (!$_POST['plugin_catalog']['close_set']) {
			$_POST['plugin_catalog']['close'] = null;
		} elseif (is_array($_POST['plugin_catalog']['close'])) {
			$year   = mb_convert_kana($_POST['plugin_catalog']['close']['year'], 'n', 'UTF-8');
			$month  = mb_convert_kana($_POST['plugin_catalog']['close']['month'], 'n', 'UTF-8');
			$day    = mb_convert_kana($_POST['plugin_catalog']['close']['day'], 'n', 'UTF-8');
			$hour   = mb_convert_kana($_POST['plugin_catalog']['close']['hour'], 'n', 'UTF-8');
			$minute = mb_convert_kana($_POST['plugin_catalog']['close']['minute'], 'n', 'UTF-8');
			$second = mb_convert_kana($_POST['plugin_catalog']['close']['second'], 'n', 'UTF-8');

			$_POST['plugin_catalog']['close'] = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
		}

		//並び順取得
		if ($_POST['plugin_catalog']['sort'] != '') {
			$_POST['plugin_catalog']['sort'] = mb_convert_kana($_POST['plugin_catalog']['sort'], 'n', 'UTF-8');
		}

		//価格取得
		if ($_POST['plugin_catalog']['price'] != '') {
			$_POST['plugin_catalog']['price'] = mb_convert_kana($_POST['plugin_catalog']['price'], 'n', 'UTF-8');
		}

		//在庫数取得
		if ($_POST['plugin_catalog']['stock'] != '') {
			$_POST['plugin_catalog']['stock'] = mb_convert_kana($_POST['plugin_catalog']['stock'], 'n', 'UTF-8');
		}

		//厚さ・重さ設定
		if ($_POST['plugin_catalog']['size'] == 'data') {
			$_POST['plugin_catalog']['thickness'] = 0;
			$_POST['plugin_catalog']['weight']    = 0;
		}

		//厚さ取得
		if ($_POST['plugin_catalog']['thickness'] != '') {
			$_POST['plugin_catalog']['thickness'] = mb_convert_kana($_POST['plugin_catalog']['thickness'], 'n', 'UTF-8');
		}

		//重さ取得
		if ($_POST['plugin_catalog']['weight'] != '') {
			$_POST['plugin_catalog']['weight'] = mb_convert_kana($_POST['plugin_catalog']['weight'], 'n', 'UTF-8');
		}

		//並列梱包の許可設定
		if ($_POST['plugin_catalog']['size'] == 'data') {
			$_POST['plugin_catalog']['parallel'] = 'no';
		}

		//梱包によって追加される短辺・長辺・厚さ・重さ設定
		if ($_POST['plugin_catalog']['size'] == 'data') {
			$_POST['plugin_catalog']['packing_short']     = 0;
			$_POST['plugin_catalog']['packing_long']      = 0;
			$_POST['plugin_catalog']['packing_thickness'] = 0;
			$_POST['plugin_catalog']['packing_weight']    = 0;
		}

		//梱包によって追加される短辺取得
		if ($_POST['plugin_catalog']['packing_short'] != '') {
			$_POST['plugin_catalog']['packing_short'] = mb_convert_kana($_POST['plugin_catalog']['packing_short'], 'n', 'UTF-8');
		}

		//梱包によって追加される長辺取得
		if ($_POST['plugin_catalog']['packing_long'] != '') {
			$_POST['plugin_catalog']['packing_long'] = mb_convert_kana($_POST['plugin_catalog']['packing_long'], 'n', 'UTF-8');
		}

		//梱包によって追加される厚さ取得
		if ($_POST['plugin_catalog']['packing_thickness'] != '') {
			$_POST['plugin_catalog']['packing_thickness'] = mb_convert_kana($_POST['plugin_catalog']['packing_thickness'], 'n', 'UTF-8');
		}

		//梱包によって追加される重さ取得
		if ($_POST['plugin_catalog']['packing_weight'] != '') {
			$_POST['plugin_catalog']['packing_weight'] = mb_convert_kana($_POST['plugin_catalog']['packing_weight'], 'n', 'UTF-8');
		}

		//アップロードデータ初期化
		foreach ($numbers as $number) {
			if (!isset($_FILES['plugin_catalog']['tmp_name']['file' . $number])) {
				$_FILES['plugin_catalog']['tmp_name']['file' . $number] = null;
			}
		}

		//アップロードデータ取得
		foreach ($numbers as $number) {
			if (is_uploaded_file($_FILES['plugin_catalog']['tmp_name']['file' . $number])) {
				$_POST['plugin_catalog']['file' . $number] = $_FILES['plugin_catalog']['name']['file' . $number];
			} elseif (!isset($_POST['page']['file' . $number])) {
				$_POST['plugin_catalog']['file' . $number] = null;
			}
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//商品ID
			if ($_POST['plugin_catalog']['id'] == '') {
				$freo->smarty->append('errors', '商品IDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_catalog']['id'])) {
				$freo->smarty->append('errors', '商品IDは半角英数字で入力してください。');
			} elseif (preg_match('/^\d+$/', $_POST['plugin_catalog']['id'])) {
				$freo->smarty->append('errors', '商品IDには半角英字を含んでください。');
			} elseif (mb_strlen($_POST['plugin_catalog']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '商品IDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力された商品IDはすでに使用されています。');
				}
			}

			//状態
			if ($_POST['plugin_catalog']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//商品の表示
			if ($_POST['plugin_catalog']['display'] == '') {
				$freo->smarty->append('errors', '商品の表示が入力されていません。');
			}

			//並び順
			if ($_POST['plugin_catalog']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//商品名
			if ($_POST['plugin_catalog']['name'] == '') {
				$freo->smarty->append('errors', '商品名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '商品名は80文字以内で入力してください。');
			}

			//価格
			if ($_POST['plugin_catalog']['price'] == '') {
				$freo->smarty->append('errors', '価格が入力されていません。');
			} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['price'])) {
				$freo->smarty->append('errors', '価格は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog']['price'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '価格は10文字以内で入力してください。');
			}

			//在庫数
			if ($_POST['plugin_catalog']['stock'] != '') {
				if (!preg_match('/^\d+$/', $_POST['plugin_catalog']['stock'])) {
					$freo->smarty->append('errors', '在庫数は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['stock'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '在庫数は10文字以内で入力してください。');
				}
			}

			//一度に購入できる最大数
			if ($_POST['plugin_catalog']['maximum'] != '') {
				if (!preg_match('/^\d+$/', $_POST['plugin_catalog']['maximum'])) {
					$freo->smarty->append('errors', '一度に購入できる最大数は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['maximum'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '一度に購入できる最大数は10文字以内で入力してください。');
				} elseif ($_POST['plugin_catalog']['maximum'] == 0) {
					$freo->smarty->append('errors', '一度に購入できる最大数は1以上を入力してください。');
				}
			}

			//単位
			if (mb_strlen($_POST['plugin_catalog']['unit'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '単位は80文字以内で入力してください。');
			}

			//並列梱包の許可
			if ($_POST['plugin_catalog']['parallel'] == '') {
				$freo->smarty->append('errors', '並列梱包の許可が入力されていません。');
			}

			//サイズ
			if ($_POST['plugin_catalog']['size'] == '') {
				$freo->smarty->append('errors', 'サイズが入力されていません。');
			}

			if ($_POST['plugin_catalog']['size'] == 'direct') {
				//短辺
				if ($_POST['plugin_catalog']['size_short'] == '') {
					$freo->smarty->append('errors', 'サイズの短辺が入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['size_short'])) {
					$freo->smarty->append('errors', 'サイズの短辺は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['size_short'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', 'サイズの短辺は10文字以内で入力してください。');
				}

				//長辺
				if ($_POST['plugin_catalog']['size_long'] == '') {
					$freo->smarty->append('errors', 'サイズの長辺が入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['size_long'])) {
					$freo->smarty->append('errors', 'サイズの長辺は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['size_long'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', 'サイズの長辺は10文字以内で入力してください。');
				}

				//短辺・長辺
				if ($_POST['plugin_catalog']['size_short'] > $_POST['plugin_catalog']['size_long']) {
					$freo->smarty->append('errors', 'サイズの短辺にサイズの長辺より大きい値が入力されています。');
				}
			}

			if ($_POST['plugin_catalog']['size'] != 'data') {
				//厚さ
				if ($_POST['plugin_catalog']['thickness'] == '') {
					$freo->smarty->append('errors', '厚さが入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['thickness'])) {
					$freo->smarty->append('errors', '厚さは半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['thickness'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '厚さは10文字以内で入力してください。');
				} elseif ($_POST['plugin_catalog']['thickness'] == 0) {
					$freo->smarty->append('errors', '厚さは1以上を入力してください。');
				}

				//重さ
				if ($_POST['plugin_catalog']['weight'] == '') {
					$freo->smarty->append('errors', '重さが入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['weight'])) {
					$freo->smarty->append('errors', '重さは半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['weight'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '重さは10文字以内で入力してください。');
				} elseif ($_POST['plugin_catalog']['weight'] == 0) {
					$freo->smarty->append('errors', '重さは1以上を入力してください。');
				}

				//梱包によって追加される短辺
				if ($_POST['plugin_catalog']['packing_short'] == '') {
					$freo->smarty->append('errors', '梱包によって追加される短辺が入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['packing_short'])) {
					$freo->smarty->append('errors', '梱包によって追加される短辺は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['packing_short'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '梱包によって追加される短辺は10文字以内で入力してください。');
				}

				//梱包によって追加される長辺
				if ($_POST['plugin_catalog']['packing_long'] == '') {
					$freo->smarty->append('errors', '梱包によって追加される長辺が入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['packing_long'])) {
					$freo->smarty->append('errors', '梱包によって追加される長辺は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['packing_long'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '梱包によって追加される長辺は10文字以内で入力してください。');
				}

				//梱包によって追加される厚さ
				if ($_POST['plugin_catalog']['packing_thickness'] == '') {
					$freo->smarty->append('errors', '梱包によって追加される厚さが入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['packing_thickness'])) {
					$freo->smarty->append('errors', '梱包によって追加される厚さは半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['packing_thickness'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '梱包によって追加される厚さは10文字以内で入力してください。');
				}

				//梱包によって追加される重さ
				if ($_POST['plugin_catalog']['packing_weight'] == '') {
					$freo->smarty->append('errors', '梱包によって追加される重さが入力されていません。');
				} elseif (!preg_match('/^\d+$/', $_POST['plugin_catalog']['packing_weight'])) {
					$freo->smarty->append('errors', '梱包によって追加される重さは半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog']['packing_weight'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '梱包によって追加される重さは10文字以内で入力してください。');
				}
			}

			//タグ
			if (mb_strlen($_POST['plugin_catalog']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//発行日
			if ($_POST['plugin_catalog']['datetime'] == '') {
				$freo->smarty->append('errors', '発行日が入力されていません。');
			} elseif (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $_POST['plugin_catalog']['datetime'])) {
				$freo->smarty->append('errors', '発行日の書式が不正です。');
			}

			//販売終了日時
			if ($_POST['plugin_catalog']['close'] != '') {
				if (!preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/', $_POST['plugin_catalog']['close'])) {
					$freo->smarty->append('errors', '販売終了日時の書式が不正です。');
				}
			}

			//本文
			if (mb_strlen($_POST['plugin_catalog']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}

			//商品画像
			if ($_POST['plugin_catalog']['file'] != '') {
				if (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_catalog']['file'])) {
					$freo->smarty->append('errors', 'アップロードできる商品画像はGIF、JPEG、PNGのみです。');
				}
			}
			if ($_POST['plugin_catalog']['file_thumbnail'] != '') {
				if (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_catalog']['file_thumbnail'])) {
					$freo->smarty->append('errors', 'アップロードできる商品画像はGIF、JPEG、PNGのみです。');
				}
			}
		}

		//ファイルアップロード
		foreach ($numbers as $number) {
			$file_flag  = false;

			if (!$freo->smarty->get_template_vars('errors')) {
				if (is_uploaded_file($_FILES['plugin_catalog']['tmp_name']['file' . $number])) {
					$temporary_dir  = FREO_FILE_DIR . 'temporaries/plugins/catalog_files/';
					$temporary_file = $_FILES['plugin_catalog']['name']['file' . $number];

					if (move_uploaded_file($_FILES['plugin_catalog']['tmp_name']['file' . $number], $temporary_dir . $temporary_file)) {
						if (preg_match('/\.(.*)$/', $temporary_file, $matches)) {
							$_POST['plugin_catalog']['file' . $number] = 'file' . $number . '.' . $matches[1];

							if (rename($temporary_dir . $temporary_file, $temporary_dir . $_POST['plugin_catalog']['file' . $number])) {
								$temporary_file = $_POST['plugin_catalog']['file' . $number];
							} else {
								$freo->smarty->append('errors', 'ファイル ' . $temporary_dir . $temporary_file . ' の名前を変更できません。');
							}
						} else {
							$freo->smarty->append('errors', 'ファイル ' . $temporary_file . ' の拡張子を取得できません。');
						}

						chmod($temporary_dir . $temporary_file, FREO_PERMISSION_FILE);

						$file_flag = true;
					} else {
						$freo->smarty->append('errors', '商品ファイルをアップロードできません。');
					}
				}
			}

			if (is_uploaded_file($_FILES['plugin_catalog']['tmp_name']['file' . $number]) and !$file_flag) {
				$_POST['plugin_catalog']['file' . $number] = null;
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog = $_POST['plugin_catalog'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog = $data;
			} else {
				freo_error('指定された商品が見つかりません。', '404 Not Found');
			}

			//ファイル取得
			$file_dir = FREO_FILE_DIR . 'plugins/catalog_files/' . $_GET['id'] . '/';

			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$plugin_catalog[$matches[1]] = $data;
						}
					}
				} else {
					freo_error('商品ファイル保存ディレクトリを開けません。');
				}
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_catalog_category = array(
			);
			//新規データ設定
			$plugin_catalog = array(
				'sort'              => $sort,
				'price'             => 0,
				'packing_short'     => 0,
				'packing_long'      => 0,
				'packing_thickness' => 0,
				'packing_weight'    => 0,
				'datetime'          => date('Y-m-d 00:00:00')
			);
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture(),
		'input' => array(
			'plugin_catalog' => $plugin_catalog
		)
	));

	return;
}

/* 管理画面 | 商品登録 */
function freo_page_catalog_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin?error=1');
	}

	//ファイル番号定義
	$numbers = array('', '_thumbnail', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//入力データ取得
	$catalog = $_SESSION['input']['plugin_catalog'];

	if ($catalog['category_id'] == '') {
		$catalog['category_id'] = null;
	}
	if ($catalog['target'] == '') {
		$catalog['target'] = null;
	}
	if ($catalog['stock'] == '') {
		$catalog['stock'] = null;
	}
	if ($catalog['unit'] == '') {
		$catalog['unit'] = null;
	}
	if ($catalog['maximum'] == '') {
		$catalog['maximum'] = null;
	}
	if ($catalog['size_short'] == '') {
		$catalog['size_short'] = null;
	}
	if ($catalog['size_long'] == '') {
		$catalog['size_long'] = null;
	}
	if ($catalog['tag'] == '') {
		$catalog['tag'] = null;
	}
	if ($catalog['close'] == '') {
		$catalog['close'] = null;
	}
	if ($catalog['text'] == '') {
		$catalog['text'] = null;
	}
	if ($catalog['option01'] == '') {
		$catalog['option01'] = null;
	}
	if ($catalog['option02'] == '') {
		$catalog['option02'] = null;
	}
	if ($catalog['option03'] == '') {
		$catalog['option03'] = null;
	}
	if ($catalog['option04'] == '') {
		$catalog['option04'] = null;
	}
	if ($catalog['option05'] == '') {
		$catalog['option05'] = null;
	}
	if ($catalog['option06'] == '') {
		$catalog['option06'] = null;
	}
	if ($catalog['option07'] == '') {
		$catalog['option07'] = null;
	}
	if ($catalog['option08'] == '') {
		$catalog['option08'] = null;
	}
	if ($catalog['option09'] == '') {
		$catalog['option09'] = null;
	}
	if ($catalog['option10'] == '') {
		$catalog['option10'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalogs SET category_id = :category_id, modified = :now, status = :status, display = :display, sort = :sort, name = :name, price = :price, target = :target, stock = :stock, maximum = :maximum, unit = :unit, parallel = :parallel, size = :size, size_short = :size_short, size_long = :size_long, thickness = :thickness, weight = :weight, packing_short = :packing_short, packing_long = :packing_long, packing_thickness = :packing_thickness, packing_weight = :packing_weight, tag = :tag, datetime = :datetime, close = :close, text = :text, option01 = :option01, option02 = :option02, option03 = :option03, option04 = :option04, option05 = :option05, option06 = :option06, option07 = :option07, option08 = :option08, option09 = :option09, option10 = :option10 WHERE id = :id');
		$stmt->bindValue(':category_id',       $catalog['category_id']);
		$stmt->bindValue(':now',               date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',            $catalog['status']);
		$stmt->bindValue(':display',           $catalog['display']);
		$stmt->bindValue(':sort',              $catalog['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',              $catalog['name']);
		$stmt->bindValue(':price',             $catalog['price'], PDO::PARAM_INT);
		$stmt->bindValue(':target',            $catalog['target']);
		$stmt->bindValue(':stock',             $catalog['stock'], PDO::PARAM_INT);
		$stmt->bindValue(':maximum',           $catalog['maximum'], PDO::PARAM_INT);
		$stmt->bindValue(':unit',              $catalog['unit']);
		$stmt->bindValue(':parallel',          $catalog['parallel']);
		$stmt->bindValue(':size',              $catalog['size']);
		$stmt->bindValue(':size_short',        $catalog['size_short'], PDO::PARAM_INT);
		$stmt->bindValue(':size_long',         $catalog['size_long'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness',         $catalog['thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':weight',            $catalog['weight'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_short',     $catalog['packing_short'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_long',      $catalog['packing_long'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_thickness', $catalog['packing_thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_weight',    $catalog['packing_weight'], PDO::PARAM_INT);
		$stmt->bindValue(':tag',               $catalog['tag']);
		$stmt->bindValue(':datetime',          $catalog['datetime']);
		$stmt->bindValue(':close',             $catalog['close']);
		$stmt->bindValue(':text',              $catalog['text']);
		$stmt->bindValue(':option01',          $catalog['option01']);
		$stmt->bindValue(':option02',          $catalog['option02']);
		$stmt->bindValue(':option03',          $catalog['option03']);
		$stmt->bindValue(':option04',          $catalog['option04']);
		$stmt->bindValue(':option05',          $catalog['option05']);
		$stmt->bindValue(':option06',          $catalog['option06']);
		$stmt->bindValue(':option07',          $catalog['option07']);
		$stmt->bindValue(':option08',          $catalog['option08']);
		$stmt->bindValue(':option09',          $catalog['option09']);
		$stmt->bindValue(':option10',          $catalog['option10']);
		$stmt->bindValue(':id',                $catalog['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalogs VALUES(:id, :category_id, :now1, :now2, :status, :display, :sort, :name, :price, :target, :stock, :maximum, :unit, :parallel, :size, :size_short, :size_long, :thickness, :weight, :packing_short, :packing_long, :packing_thickness, :packing_weight, :tag, :datetime, :close, :text, :option01, :option02, :option03, :option04, :option05, :option06, :option07, :option08, :option09, :option10)');
		$stmt->bindValue(':id',                $catalog['id']);
		$stmt->bindValue(':category_id',       $catalog['category_id']);
		$stmt->bindValue(':now1',              date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',              date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',            $catalog['status']);
		$stmt->bindValue(':display',           $catalog['display']);
		$stmt->bindValue(':sort',              $catalog['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',              $catalog['name']);
		$stmt->bindValue(':price',             $catalog['price'], PDO::PARAM_INT);
		$stmt->bindValue(':target',            $catalog['target']);
		$stmt->bindValue(':stock',             $catalog['stock'], PDO::PARAM_INT);
		$stmt->bindValue(':maximum',           $catalog['maximum'], PDO::PARAM_INT);
		$stmt->bindValue(':unit',              $catalog['unit']);
		$stmt->bindValue(':parallel',          $catalog['parallel']);
		$stmt->bindValue(':size',              $catalog['size']);
		$stmt->bindValue(':size_short',        $catalog['size_short'], PDO::PARAM_INT);
		$stmt->bindValue(':size_long',         $catalog['size_long'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness',         $catalog['thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':weight',            $catalog['weight'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_short',     $catalog['packing_short'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_long',      $catalog['packing_long'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_thickness', $catalog['packing_thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_weight',    $catalog['packing_weight'], PDO::PARAM_INT);
		$stmt->bindValue(':tag',               $catalog['tag']);
		$stmt->bindValue(':datetime',          $catalog['datetime']);
		$stmt->bindValue(':close',             $catalog['close']);
		$stmt->bindValue(':text',              $catalog['text']);
		$stmt->bindValue(':option01',          $catalog['option01']);
		$stmt->bindValue(':option02',          $catalog['option02']);
		$stmt->bindValue(':option03',          $catalog['option03']);
		$stmt->bindValue(':option04',          $catalog['option04']);
		$stmt->bindValue(':option05',          $catalog['option05']);
		$stmt->bindValue(':option06',          $catalog['option06']);
		$stmt->bindValue(':option07',          $catalog['option07']);
		$stmt->bindValue(':option08',          $catalog['option08']);
		$stmt->bindValue(':option09',          $catalog['option09']);
		$stmt->bindValue(':option10',          $catalog['option10']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//商品ファイル保存
	$file_dir      = FREO_FILE_DIR . 'plugins/catalog_files/' . $catalog['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/catalog_files/';

	foreach ($numbers as $number) {
		if (($catalog['file' . $number] and file_exists($temporary_dir . $catalog['file' . $number])) or isset($catalog['file' . $number . '_remove'])) {
			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$filename = $matches[1];

							if ($filename == 'file' . $number) {
								unlink($file_dir . $data);
							}
						}
					}
				} else {
					freo_error('商品ファイル保存ディレクトリを開けません。');
				}
			}

			if ($catalog['file' . $number]) {
				if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
					freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
				}

				if (rename($temporary_dir . $catalog['file' . $number], $file_dir . $catalog['file' . $number])) {
					chmod($file_dir . $catalog['file' . $number], FREO_PERMISSION_FILE);
				} else {
					freo_error('ファイル ' . $temporary_dir . $catalog['file' . $number] . ' を移動できません。');
				}
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('商品を編集しました。');
	} else {
		freo_log('商品を新規に登録しました。');
	}

	//商品管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('catalog/admin?exec=update&id=' . $catalog['id']);
	} else {
		freo_redirect('catalog/admin?exec=insert');
	}

	return;
}

/* 管理画面 | 商品一括編集 */
function freo_page_catalog_admin_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//データ登録
	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $id => $sort) {
			if (!preg_match('/^[\w\-\/]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $sort)) {
				continue;
			}

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalogs SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('商品を並び替えました。');

	//カテゴリー管理へ移動
	freo_redirect('catalog/admin?exec=sort');

	return;
}

/* 管理画面 | 商品削除 */
function freo_page_catalog_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('catalog/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin?error=1');
	}

	//ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'plugins/catalog_files/' . $_GET['id'] . '/');

	//商品削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('商品を削除しました。');

	//商品管理へ移動
	freo_redirect('catalog/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | カテゴリー管理 */
function freo_page_catalog_admin_category()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 管理画面 | カテゴリー入力 */
function freo_page_catalog_admin_category_form()
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

		//並び順取得
		if ($_POST['plugin_catalog_category']['sort'] != '') {
			$_POST['plugin_catalog_category']['sort'] = mb_convert_kana($_POST['plugin_catalog_category']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//カテゴリーID
			if ($_POST['plugin_catalog_category']['id'] == '') {
				$freo->smarty->append('errors', 'カテゴリーIDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_catalog_category']['id'])) {
				$freo->smarty->append('errors', 'カテゴリーIDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_category']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリーIDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog_category']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力されたカテゴリーIDはすでに使用されています。');
				}
			}

			//並び順
			if ($_POST['plugin_catalog_category']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_category']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_category']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//カテゴリー名
			if ($_POST['plugin_catalog_category']['name'] == '') {
				$freo->smarty->append('errors', 'カテゴリー名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_category']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリー名は80文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_catalog_category']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_category = $_POST['plugin_catalog_category'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog/admin_category_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_category = $data;
			} else {
				freo_error('指定されたカテゴリーが見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_catalog_category = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture(),
		'input' => array(
			'plugin_catalog_category' => $plugin_catalog_category
		)
	));

	return;
}

/* 管理画面 | カテゴリー登録 */
function freo_page_catalog_admin_category_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//入力データ取得
	$plugin_catalog_category = $_SESSION['input']['plugin_catalog_category'];

	if ($plugin_catalog_category['memo'] == '') {
		$plugin_catalog_category['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories SET modified = :now, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_catalog_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_catalog_category['name']);
		$stmt->bindValue(':memo', $plugin_catalog_category['memo']);
		$stmt->bindValue(':id',   $plugin_catalog_category['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories VALUES(:id, :now1, :now2, :sort, :name, :memo)');
		$stmt->bindValue(':id',   $plugin_catalog_category['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_catalog_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_catalog_category['name']);
		$stmt->bindValue(':memo', $plugin_catalog_category['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('カテゴリーを編集しました。');
	} else {
		freo_log('カテゴリーを新規に登録しました。');
	}

	//カテゴリー管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('catalog/admin_category?exec=update&id=' . $plugin_catalog_category['id']);
	} else {
		freo_redirect('catalog/admin_category?exec=insert');
	}

	return;
}

/* 管理画面 | カテゴリー一括編集 */
function freo_page_catalog_admin_category_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//データ登録
	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $id => $sort) {
			if (!preg_match('/^[\w\-\/]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $sort)) {
				continue;
			}

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('カテゴリーを並び替えました。');

	//カテゴリー管理へ移動
	freo_redirect('catalog/admin_category?exec=sort');

	return;
}

/* 管理画面 | カテゴリー削除 */
function freo_page_catalog_admin_category_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_category?error=1');
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('カテゴリーを削除しました。');

	//カテゴリー管理へ移動
	freo_redirect('catalog/admin_category?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | 支払い方法管理 */
function freo_page_catalog_admin_payment()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//支払い方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_payments'    => $plugin_catalog_payments,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 管理画面 | 支払い方法入力 */
function freo_page_catalog_admin_payment_form()
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

		//並び順取得
		if ($_POST['plugin_catalog_payment']['sort'] != '') {
			$_POST['plugin_catalog_payment']['sort'] = mb_convert_kana($_POST['plugin_catalog_payment']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//支払い方法ID
			if ($_POST['plugin_catalog_payment']['id'] == '') {
				$freo->smarty->append('errors', '支払い方法IDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_catalog_payment']['id'])) {
				$freo->smarty->append('errors', '支払い方法IDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_payment']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '支払い方法IDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog_payment']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力された支払い方法IDはすでに使用されています。');
				}
			}

			//状態
			if ($_POST['plugin_catalog_payment']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//並び順
			if ($_POST['plugin_catalog_payment']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_payment']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_payment']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//支払い方法名
			if ($_POST['plugin_catalog_payment']['name'] == '') {
				$freo->smarty->append('errors', '支払い方法名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_payment']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '支払い方法名は80文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_catalog_payment']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}

			//手数料
			if ($_POST['plugin_catalog_payment']['charge'] != '') {
				if (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_payment']['charge'])) {
					$freo->smarty->append('errors', '手数料は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_payment']['charge'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '手数料は10文字以内で入力してください。');
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_payment = $_POST['plugin_catalog_payment'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog/admin_payment_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_payment = $data;
			} else {
				freo_error('指定された支払い方法が見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_catalog_payment = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_catalog_payment' => $plugin_catalog_payment
		)
	));

	return;
}

/* 管理画面 | 支払い方法登録 */
function freo_page_catalog_admin_payment_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog/admin_payment?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_payment?error=1');
	}

	//入力データ取得
	$plugin_catalog_payment = $_SESSION['input']['plugin_catalog_payment'];

	if ($plugin_catalog_payment['charge'] == '') {
		$plugin_catalog_payment['charge'] = null;
	}
	if ($plugin_catalog_payment['text'] == '') {
		$plugin_catalog_payment['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments SET modified = :now, status = :status, sort = :sort, name = :name, text = :text, charge = :charge WHERE id = :id');
		$stmt->bindValue(':now',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':status', $plugin_catalog_payment['status']);
		$stmt->bindValue(':sort',   $plugin_catalog_payment['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',   $plugin_catalog_payment['name']);
		$stmt->bindValue(':text',   $plugin_catalog_payment['text']);
		$stmt->bindValue(':charge', $plugin_catalog_payment['charge']);
		$stmt->bindValue(':id',     $plugin_catalog_payment['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments VALUES(:id, :now1, :now2, :status, :sort, :name, :text, :charge)');
		$stmt->bindValue(':id',     $plugin_catalog_payment['id']);
		$stmt->bindValue(':now1',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':status', $plugin_catalog_payment['status']);
		$stmt->bindValue(':sort',   $plugin_catalog_payment['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',   $plugin_catalog_payment['name']);
		$stmt->bindValue(':text',   $plugin_catalog_payment['text']);
		$stmt->bindValue(':charge', $plugin_catalog_payment['charge']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('支払い方法を編集しました。');
	} else {
		freo_log('支払い方法を新規に登録しました。');
	}

	//支払い方法管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('catalog/admin_payment?exec=update&id=' . $plugin_catalog_payment['id']);
	} else {
		freo_redirect('catalog/admin_payment?exec=insert');
	}

	return;
}

/* 管理画面 | 支払い方法一括編集 */
function freo_page_catalog_admin_payment_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_payment?error=1');
	}

	//データ登録
	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $id => $sort) {
			if (!preg_match('/^[\w\-\/]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $sort)) {
				continue;
			}

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('支払い方法を並び替えました。');

	//支払い方法管理へ移動
	freo_redirect('catalog/admin_payment?exec=sort');

	return;
}

/* 管理画面 | 支払い方法削除 */
function freo_page_catalog_admin_payment_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('catalog/admin_payment?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_payment?error=1');
	}

	//配送方法ごとの支払い方法削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets WHERE payment_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//支払い方法削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('支払い方法を削除しました。');

	//支払い方法管理へ移動
	freo_redirect('catalog/admin_payment?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | 配送方法管理 */
function freo_page_catalog_admin_delivery()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//配送方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_deliveries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_deliveries[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_deliveries'  => $plugin_catalog_deliveries,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 管理画面 | 配送方法入力 */
function freo_page_catalog_admin_delivery_form()
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

		//並び順取得
		if ($_POST['plugin_catalog_delivery']['sort'] != '') {
			$_POST['plugin_catalog_delivery']['sort'] = mb_convert_kana($_POST['plugin_catalog_delivery']['sort'], 'n', 'UTF-8');
		}

		//短辺最小取得
		if ($_POST['plugin_catalog_delivery']['short_min'] != '') {
			$_POST['plugin_catalog_delivery']['short_min'] = mb_convert_kana($_POST['plugin_catalog_delivery']['short_min'], 'n', 'UTF-8');
		}

		//短辺最大取得
		if ($_POST['plugin_catalog_delivery']['short_max'] != '') {
			$_POST['plugin_catalog_delivery']['short_max'] = mb_convert_kana($_POST['plugin_catalog_delivery']['short_max'], 'n', 'UTF-8');
		}

		//長辺最小取得
		if ($_POST['plugin_catalog_delivery']['long_min'] != '') {
			$_POST['plugin_catalog_delivery']['long_min'] = mb_convert_kana($_POST['plugin_catalog_delivery']['long_min'], 'n', 'UTF-8');
		}

		//長辺最大取得
		if ($_POST['plugin_catalog_delivery']['long_max'] != '') {
			$_POST['plugin_catalog_delivery']['long_max'] = mb_convert_kana($_POST['plugin_catalog_delivery']['long_max'], 'n', 'UTF-8');
		}

		//厚さ最小取得
		if ($_POST['plugin_catalog_delivery']['thickness_min'] != '') {
			$_POST['plugin_catalog_delivery']['thickness_min'] = mb_convert_kana($_POST['plugin_catalog_delivery']['thickness_min'], 'n', 'UTF-8');
		}

		//厚さ最大取得
		if ($_POST['plugin_catalog_delivery']['thickness_max'] != '') {
			$_POST['plugin_catalog_delivery']['thickness_max'] = mb_convert_kana($_POST['plugin_catalog_delivery']['thickness_max'], 'n', 'UTF-8');
		}

		//3辺合計最小取得
		if ($_POST['plugin_catalog_delivery']['total_min'] != '') {
			$_POST['plugin_catalog_delivery']['total_min'] = mb_convert_kana($_POST['plugin_catalog_delivery']['total_min'], 'n', 'UTF-8');
		}

		//3辺合計最大取得
		if ($_POST['plugin_catalog_delivery']['total_max'] != '') {
			$_POST['plugin_catalog_delivery']['total_max'] = mb_convert_kana($_POST['plugin_catalog_delivery']['total_max'], 'n', 'UTF-8');
		}

		//重さ最小取得
		if ($_POST['plugin_catalog_delivery']['weight_min'] != '') {
			$_POST['plugin_catalog_delivery']['weight_min'] = mb_convert_kana($_POST['plugin_catalog_delivery']['weight_min'], 'n', 'UTF-8');
		}

		//重さ最大取得
		if ($_POST['plugin_catalog_delivery']['weight_max'] != '') {
			$_POST['plugin_catalog_delivery']['weight_max'] = mb_convert_kana($_POST['plugin_catalog_delivery']['weight_max'], 'n', 'UTF-8');
		}

		//梱包材による短辺の増加分取得
		if ($_POST['plugin_catalog_delivery']['packing_short'] != '') {
			$_POST['plugin_catalog_delivery']['packing_short'] = mb_convert_kana($_POST['plugin_catalog_delivery']['packing_short'], 'n', 'UTF-8');
		}

		//梱包材による長辺の増加分取得
		if ($_POST['plugin_catalog_delivery']['packing_long'] != '') {
			$_POST['plugin_catalog_delivery']['packing_long'] = mb_convert_kana($_POST['plugin_catalog_delivery']['packing_long'], 'n', 'UTF-8');
		}

		//梱包材による厚さの増加分取得
		if ($_POST['plugin_catalog_delivery']['packing_thickness'] != '') {
			$_POST['plugin_catalog_delivery']['packing_thickness'] = mb_convert_kana($_POST['plugin_catalog_delivery']['packing_thickness'], 'n', 'UTF-8');
		}

		//梱包材による3辺合計の増加分取得
		if ($_POST['plugin_catalog_delivery']['packing_total'] != '') {
			$_POST['plugin_catalog_delivery']['packing_total'] = mb_convert_kana($_POST['plugin_catalog_delivery']['packing_total'], 'n', 'UTF-8');
		}

		//梱包材による重さの増加分取得
		if ($_POST['plugin_catalog_delivery']['packing_weight'] != '') {
			$_POST['plugin_catalog_delivery']['packing_weight'] = mb_convert_kana($_POST['plugin_catalog_delivery']['packing_weight'], 'n', 'UTF-8');
		}

		//送料取得
		if ($_POST['plugin_catalog_delivery']['carriage'] != '') {
			$_POST['plugin_catalog_delivery']['carriage'] = mb_convert_kana($_POST['plugin_catalog_delivery']['carriage'], 'n', 'UTF-8');
		}

		//利用できる支払い方法
		if (empty($_POST['plugin_catalog_delivery']['payments'])) {
			$_POST['plugin_catalog_delivery']['payments'] = array();
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//配送方法ID
			if ($_POST['plugin_catalog_delivery']['id'] == '') {
				$freo->smarty->append('errors', '配送方法IDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_catalog_delivery']['id'])) {
				$freo->smarty->append('errors', '配送方法IDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '配送方法IDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_catalog_delivery']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力された配送方法IDはすでに使用されています。');
				}
			}

			//状態
			if ($_POST['plugin_catalog_delivery']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//並び順
			if ($_POST['plugin_catalog_delivery']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//配送方法名
			if ($_POST['plugin_catalog_delivery']['name'] == '') {
				$freo->smarty->append('errors', '配送方法名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '配送方法名は80文字以内で入力してください。');
			}

			//短辺最小
			if ($_POST['plugin_catalog_delivery']['short_min'] == '') {
				$freo->smarty->append('errors', '短辺の最小が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['short_min'])) {
				$freo->smarty->append('errors', '短辺の最小は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['short_min'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '短辺の最小は10文字以内で入力してください。');
			}

			//短辺最大
			if ($_POST['plugin_catalog_delivery']['short_max'] == '') {
				$freo->smarty->append('errors', '短辺の最大が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['short_max'])) {
				$freo->smarty->append('errors', '短辺の最大は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['short_max'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '短辺の最大は10文字以内で入力してください。');
			}

			//短辺最小・短辺最大
			if ($_POST['plugin_catalog_delivery']['short_min'] > $_POST['plugin_catalog_delivery']['short_max']) {
				$freo->smarty->append('errors', '短辺の最小に短辺の最大より大きい値が入力されています。');
			}

			//長辺最小
			if ($_POST['plugin_catalog_delivery']['long_min'] == '') {
				$freo->smarty->append('errors', '長辺の最小が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['long_min'])) {
				$freo->smarty->append('errors', '長辺の最小は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['long_min'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '長辺の最小は10文字以内で入力してください。');
			}

			//長辺最大
			if ($_POST['plugin_catalog_delivery']['long_max'] == '') {
				$freo->smarty->append('errors', '長辺の最大が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['long_max'])) {
				$freo->smarty->append('errors', '長辺の最大は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['long_max'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '長辺の最大は10文字以内で入力してください。');
			}

			//長辺最小・長辺最大
			if ($_POST['plugin_catalog_delivery']['long_min'] > $_POST['plugin_catalog_delivery']['long_max']) {
				$freo->smarty->append('errors', '長辺の最小に長辺の最大より大きい値が入力されています。');
			}

			//厚さ最小
			if ($_POST['plugin_catalog_delivery']['thickness_min'] == '') {
				$freo->smarty->append('errors', '厚さの最小が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['thickness_min'])) {
				$freo->smarty->append('errors', '厚さの最小は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['thickness_min'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '厚さの最小は10文字以内で入力してください。');
			}

			//厚さ最大
			if ($_POST['plugin_catalog_delivery']['thickness_max'] == '') {
				$freo->smarty->append('errors', '厚さの最大が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['thickness_max'])) {
				$freo->smarty->append('errors', '厚さの最大は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['thickness_max'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '厚さの最大は10文字以内で入力してください。');
			}

			//厚さ最小・厚さ最大
			if ($_POST['plugin_catalog_delivery']['thickness_min'] > $_POST['plugin_catalog_delivery']['thickness_max']) {
				$freo->smarty->append('errors', '厚さの最小に厚さの最大より大きい値が入力されています。');
			}

			//3辺合計最小
			if ($_POST['plugin_catalog_delivery']['total_min'] == '') {
				$freo->smarty->append('errors', '3辺合計の最小が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['total_min'])) {
				$freo->smarty->append('errors', '3辺合計の最小は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['total_min'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '3辺合計の最小は10文字以内で入力してください。');
			}

			//3辺合計最大
			if ($_POST['plugin_catalog_delivery']['total_max'] == '') {
				$freo->smarty->append('errors', '3辺合計の最大が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['total_max'])) {
				$freo->smarty->append('errors', '3辺合計の最大は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['total_max'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '3辺合計の最大は10文字以内で入力してください。');
			}

			//3辺合計最小・3辺合計最大
			if ($_POST['plugin_catalog_delivery']['total_min'] > $_POST['plugin_catalog_delivery']['total_max']) {
				$freo->smarty->append('errors', '3辺合計の最小に3辺合計の最大より大きい値が入力されています。');
			}

			//重さ最小
			if ($_POST['plugin_catalog_delivery']['weight_min'] == '') {
				$freo->smarty->append('errors', '重さの最小が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['weight_min'])) {
				$freo->smarty->append('errors', '重さの最小は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['weight_min'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '重さの最小は10文字以内で入力してください。');
			}

			//重さ最大
			if ($_POST['plugin_catalog_delivery']['weight_max'] == '') {
				$freo->smarty->append('errors', '重さの最大が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['weight_max'])) {
				$freo->smarty->append('errors', '重さの最大は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['weight_max'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '重さの最大は10文字以内で入力してください。');
			}

			//重さ最小・重さ最大
			if ($_POST['plugin_catalog_delivery']['weight_min'] > $_POST['plugin_catalog_delivery']['weight_max']) {
				$freo->smarty->append('errors', '重さの最小に重さの最大より大きい値が入力されています。');
			}

			//梱包材による短辺の増加分
			if ($_POST['plugin_catalog_delivery']['packing_short'] == '') {
				$freo->smarty->append('errors', '梱包材による短辺の増加分が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['packing_short'])) {
				$freo->smarty->append('errors', '梱包材による短辺の増加分は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['packing_short'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '梱包材による短辺の増加分は10文字以内で入力してください。');
			}

			//梱包材による長辺の増加分
			if ($_POST['plugin_catalog_delivery']['packing_long'] == '') {
				$freo->smarty->append('errors', '梱包材による長辺の増加分が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['packing_long'])) {
				$freo->smarty->append('errors', '梱包材による長辺の増加分は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['packing_long'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '梱包材による長辺の増加分は10文字以内で入力してください。');
			}

			//梱包材による厚さの増加分
			if ($_POST['plugin_catalog_delivery']['packing_thickness'] == '') {
				$freo->smarty->append('errors', '梱包材による厚さの増加分が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['packing_thickness'])) {
				$freo->smarty->append('errors', '梱包材による厚さの増加分は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['packing_thickness'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '梱包材による厚さの増加分は10文字以内で入力してください。');
			}

			//梱包材による3辺合計の増加分
			if ($_POST['plugin_catalog_delivery']['packing_total'] == '') {
				$freo->smarty->append('errors', '梱包材による3辺合計の増加分が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['packing_total'])) {
				$freo->smarty->append('errors', '梱包材による3辺合計の増加分は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['packing_total'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '梱包材による3辺合計の増加分は10文字以内で入力してください。');
			}

			//梱包材による重さの増加分
			if ($_POST['plugin_catalog_delivery']['packing_weight'] == '') {
				$freo->smarty->append('errors', '梱包材による重さの増加分が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['packing_weight'])) {
				$freo->smarty->append('errors', '梱包材による重さの増加分は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_catalog_delivery']['packing_weight'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '梱包材による重さの増加分は10文字以内で入力してください。');
			}

			//送料
			if ($_POST['plugin_catalog_delivery']['carriage'] != '') {
				if (!preg_match('/^[\d]+$/', $_POST['plugin_catalog_delivery']['carriage'])) {
					$freo->smarty->append('errors', '送料は半角数字で入力してください。');
				} elseif (mb_strlen($_POST['plugin_catalog_delivery']['carriage'], 'UTF-8') > 10) {
					$freo->smarty->append('errors', '送料は10文字以内で入力してください。');
				}
			}

			//説明
			if (mb_strlen($_POST['plugin_catalog_delivery']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}

			//利用できる支払い方法
			if (empty($_POST['plugin_catalog_delivery']['payments'])) {
				$freo->smarty->append('errors', '利用できる支払い方法が入力されていません。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_catalog_delivery = $_POST['plugin_catalog_delivery'];
			$plugin_catalog_delivery_sets = $_POST['plugin_catalog_delivery']['payments'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('catalog/admin_delivery_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_delivery = $data;
			} else {
				freo_error('指定された配送方法が見つかりません。', '404 Not Found');
			}

			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets WHERE delivery_id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			$plugin_catalog_delivery_sets = array();
			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_catalog_delivery_sets[$data['payment_id']] = $data;
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_catalog_delivery = array(
				'sort'              => $sort,
				'short_min'         => 1,
				'short_max'         => 1,
				'long_min'          => 1,
				'long_max'          => 1,
				'thickness_min'     => 1,
				'thickness_max'     => 1,
				'total_min'         => 1,
				'total_max'         => 1,
				'weight_min'        => 1,
				'weight_max'        => 1,
				'packing_short'     => 0,
				'packing_long'      => 0,
				'packing_thickness' => 0,
				'packing_total'     => 0,
				'packing_weight'    => 0

			);
			$plugin_catalog_delivery_sets = array();
		}
	}

	//支払い方法取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_payments ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_payments = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_payments[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_payments'    => $plugin_catalog_payments,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture(),
		'input' => array(
			'plugin_catalog_delivery'      => $plugin_catalog_delivery,
			'plugin_catalog_delivery_sets' => $plugin_catalog_delivery_sets
		)
	));

	return;
}

/* 管理画面 | 配送方法登録 */
function freo_page_catalog_admin_delivery_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//入力データ取得
	$plugin_catalog_delivery = $_SESSION['input']['plugin_catalog_delivery'];

	if ($plugin_catalog_delivery['carriage'] == '') {
		$plugin_catalog_delivery['carriage'] = null;
	}
	if ($plugin_catalog_delivery['text'] == '') {
		$plugin_catalog_delivery['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries SET modified = :now, status = :status, preferred_week = :preferred_week, preferred_time = :preferred_time, sort = :sort, name = :name, short_min = :short_min, short_max = :short_max, long_min = :long_min, long_max = :long_max, thickness_min = :thickness_min, thickness_max = :thickness_max, total_min = :total_min, total_max = :total_max, weight_min = :weight_min, weight_max = :weight_max, packing_short = :packing_short, packing_long = :packing_long, packing_thickness = :packing_thickness, packing_total = :packing_total, packing_weight = :packing_weight, carriage = :carriage, text = :text WHERE id = :id');
		$stmt->bindValue(':now',               date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',            $plugin_catalog_delivery['status']);
		$stmt->bindValue(':preferred_week',    $plugin_catalog_delivery['preferred_week']);
		$stmt->bindValue(':preferred_time',    $plugin_catalog_delivery['preferred_time']);
		$stmt->bindValue(':sort',              $plugin_catalog_delivery['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',              $plugin_catalog_delivery['name']);
		$stmt->bindValue(':short_min',         $plugin_catalog_delivery['short_min'], PDO::PARAM_INT);
		$stmt->bindValue(':short_max',         $plugin_catalog_delivery['short_max'], PDO::PARAM_INT);
		$stmt->bindValue(':long_min',          $plugin_catalog_delivery['long_min'], PDO::PARAM_INT);
		$stmt->bindValue(':long_max',          $plugin_catalog_delivery['long_max'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness_min',     $plugin_catalog_delivery['thickness_min'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness_max',     $plugin_catalog_delivery['thickness_max'], PDO::PARAM_INT);
		$stmt->bindValue(':total_min',         $plugin_catalog_delivery['total_min'], PDO::PARAM_INT);
		$stmt->bindValue(':total_max',         $plugin_catalog_delivery['total_max'], PDO::PARAM_INT);
		$stmt->bindValue(':weight_min',        $plugin_catalog_delivery['weight_min'], PDO::PARAM_INT);
		$stmt->bindValue(':weight_max',        $plugin_catalog_delivery['weight_max'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_short',     $plugin_catalog_delivery['packing_short'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_long',      $plugin_catalog_delivery['packing_long'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_thickness', $plugin_catalog_delivery['packing_thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_total',     $plugin_catalog_delivery['packing_total'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_weight',    $plugin_catalog_delivery['packing_weight'], PDO::PARAM_INT);
		$stmt->bindValue(':carriage',          $plugin_catalog_delivery['carriage'], PDO::PARAM_INT);
		$stmt->bindValue(':text',              $plugin_catalog_delivery['text']);
		$stmt->bindValue(':id',                $plugin_catalog_delivery['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries VALUES(:id, :now1, :now2, :status, :preferred_week, :preferred_time, :sort, :name, :short_min, :short_max, :long_min, :long_max, :thickness_min, :thickness_max, :total_min, :total_max, :weight_min, :weight_max, :packing_short, :packing_long, :packing_thickness, :packing_total, :packing_weight, :carriage, :text)');
		$stmt->bindValue(':id',                $plugin_catalog_delivery['id']);
		$stmt->bindValue(':now1',              date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',              date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',            $plugin_catalog_delivery['status']);
		$stmt->bindValue(':preferred_week',    $plugin_catalog_delivery['preferred_week']);
		$stmt->bindValue(':preferred_time',    $plugin_catalog_delivery['preferred_time']);
		$stmt->bindValue(':sort',              $plugin_catalog_delivery['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name',              $plugin_catalog_delivery['name']);
		$stmt->bindValue(':short_min',         $plugin_catalog_delivery['short_min'], PDO::PARAM_INT);
		$stmt->bindValue(':short_max',         $plugin_catalog_delivery['short_max'], PDO::PARAM_INT);
		$stmt->bindValue(':long_min',          $plugin_catalog_delivery['long_min'], PDO::PARAM_INT);
		$stmt->bindValue(':long_max',          $plugin_catalog_delivery['long_max'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness_min',     $plugin_catalog_delivery['thickness_min'], PDO::PARAM_INT);
		$stmt->bindValue(':thickness_max',     $plugin_catalog_delivery['thickness_max'], PDO::PARAM_INT);
		$stmt->bindValue(':total_min',         $plugin_catalog_delivery['total_min'], PDO::PARAM_INT);
		$stmt->bindValue(':total_max',         $plugin_catalog_delivery['total_max'], PDO::PARAM_INT);
		$stmt->bindValue(':weight_min',        $plugin_catalog_delivery['weight_min'], PDO::PARAM_INT);
		$stmt->bindValue(':weight_max',        $plugin_catalog_delivery['weight_max'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_short',     $plugin_catalog_delivery['packing_short'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_long',      $plugin_catalog_delivery['packing_long'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_thickness', $plugin_catalog_delivery['packing_thickness'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_total',     $plugin_catalog_delivery['packing_total'], PDO::PARAM_INT);
		$stmt->bindValue(':packing_weight',    $plugin_catalog_delivery['packing_weight'], PDO::PARAM_INT);
		$stmt->bindValue(':carriage',          $plugin_catalog_delivery['carriage'], PDO::PARAM_INT);
		$stmt->bindValue(':text',              $plugin_catalog_delivery['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets WHERE delivery_id = :delivery_id');
	$stmt->bindValue(':delivery_id', $plugin_catalog_delivery['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	foreach ($plugin_catalog_delivery['payments'] as $payment_id => $value) {
		if ($value == '') {
			continue;
		}

		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets VALUES(:delivery_id, :payment_id)');
		$stmt->bindValue(':delivery_id', $plugin_catalog_delivery['id']);
		$stmt->bindValue(':payment_id',  $payment_id);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('配送方法を編集しました。');
	} else {
		freo_log('配送方法を新規に登録しました。');
	}

	//配送方法管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('catalog/admin_delivery?exec=update&id=' . $plugin_catalog_delivery['id']);
	} else {
		freo_redirect('catalog/admin_delivery?exec=insert');
	}

	return;
}

/* 管理画面 | 配送方法一括編集 */
function freo_page_catalog_admin_delivery_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//データ登録
	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $id => $sort) {
			if (!preg_match('/^[\w\-\/]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $sort)) {
				continue;
			}

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('配送方法を並び替えました。');

	//配送方法管理へ移動
	freo_redirect('catalog/admin_delivery?exec=sort');

	return;
}

/* 管理画面 | 配送方法削除 */
function freo_page_catalog_admin_delivery_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//地域別送料削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//配送方法ごとの支払い方法削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_sets WHERE delivery_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//配送方法削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('配送方法を削除しました。');

	//配送方法管理へ移動
	freo_redirect('catalog/admin_delivery?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | 地域別送料入力 */
function freo_page_catalog_admin_delivery_prefecture_form()
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

	//配送方法取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_deliveries WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_delivery = $data;
	} else {
		freo_error('指定された配送方法が見つかりません。', '404 Not Found');
	}

	//編集データ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalog_delivery_prefectures = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_delivery_prefectures[$data['prefecture']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture(),
		'input' => array(
			'plugin_catalog_delivery'             => $plugin_catalog_delivery,
			'plugin_catalog_delivery_prefectures' => $plugin_catalog_delivery_prefectures
		)
	));

	return;
}

/* 管理画面 | 地域別送料登録 */
function freo_page_catalog_admin_delivery_prefecture_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('catalog/admin_delivery?error=1');
	}

	//データ登録
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures WHERE delivery_id = :delivery_id');
	$stmt->bindValue(':delivery_id', $_POST['plugin_catalog_delivery_prefecture']['delivery_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $prefecture => $carriage) {
			if (!preg_match('/^[\w\-]+$/', $prefecture)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $carriage) or $carriage <= 0) {
				continue;
			}

			$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_catalog_delivery_prefectures VALUES(:delivery_id, :prefecture, :carriage)');
			$stmt->bindValue(':delivery_id', $_POST['plugin_catalog_delivery_prefecture']['delivery_id']);
			$stmt->bindValue(':prefecture',  $prefecture);
			$stmt->bindValue(':carriage',    $carriage, PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('地域別送料を登録しました。');
	}

	//配送方法管理へ移動
	freo_redirect('catalog/admin_delivery?exec=prefecture&id=' . $_POST['plugin_catalog_delivery_prefecture']['delivery_id']);

	return;
}

/* 商品一覧 */
function freo_page_catalog_default()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['category_id']) or !preg_match('/^[\w\-]+$/', $_GET['category_id'])) {
		$_GET['category_id'] = null;
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$words = explode(' ', str_replace('　', ' ', $_GET['word']));

		foreach ($words as $word) {
			$condition .= ' AND (name LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option01 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option02 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option03 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option04 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option05 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option06 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option07 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option08 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option09 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR option10 LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
		}
	}
	if (isset($_GET['category_id'])) {
		$condition .= ' AND category_id = ' . $freo->pdo->quote($_GET['category_id']);
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if (isset($_GET['option01'])) {
		$condition .= ' AND option01 = ' . $freo->pdo->quote($_GET['option01']);
	}
	if (isset($_GET['option02'])) {
		$condition .= ' AND option02 = ' . $freo->pdo->quote($_GET['option02']);
	}
	if (isset($_GET['option03'])) {
		$condition .= ' AND option03 = ' . $freo->pdo->quote($_GET['option03']);
	}
	if (isset($_GET['option04'])) {
		$condition .= ' AND option04 = ' . $freo->pdo->quote($_GET['option04']);
	}
	if (isset($_GET['option05'])) {
		$condition .= ' AND option05 = ' . $freo->pdo->quote($_GET['option05']);
	}
	if (isset($_GET['option06'])) {
		$condition .= ' AND option06 = ' . $freo->pdo->quote($_GET['option06']);
	}
	if (isset($_GET['option07'])) {
		$condition .= ' AND option07 = ' . $freo->pdo->quote($_GET['option07']);
	}
	if (isset($_GET['option08'])) {
		$condition .= ' AND option08 = ' . $freo->pdo->quote($_GET['option08']);
	}
	if (isset($_GET['option09'])) {
		$condition .= ' AND option09 = ' . $freo->pdo->quote($_GET['option09']);
	}
	if (isset($_GET['option10'])) {
		$condition .= ' AND option10 = ' . $freo->pdo->quote($_GET['option10']);
	}

	if (isset($_GET['condition'])) {
		if ($_GET['condition'] == 'prerelease') {
			$condition .= ' AND datetime >= ' . $freo->pdo->quote(date('Y-m-d H:i:s'));
		} elseif ($_GET['condition'] == 'new') {
			$condition .= ' AND datetime >= ' . $freo->pdo->quote(date('Y-m-d H:i:s', time() - (60 * 60 * 24 * $freo->config['plugin']['catalog']['new_days'])));
		} elseif ($_GET['condition'] == 'soldout') {
			$condition .= ' AND (stock IS NOT NULL AND stock = 0)';
		} elseif ($_GET['condition'] == 'end') {
			$condition .= ' AND (close IS NOT NULL AND close < ' . $freo->pdo->quote(date('Y-m-d H:i:s')) . ')';
		}
	} else {
		if (!$freo->config['plugin']['catalog']['soldout_display']) {
			$condition .= ' AND (stock IS NULL OR stock != 0)';
		}
		if (!$freo->config['plugin']['catalog']['close_display']) {
			$condition .= ' AND (close IS NULL OR close >= ' . $freo->pdo->quote(date('Y-m-d H:i:s')) . ')';
		}
	}

	//対象の初期値
	if (!isset($_SESSION['plugin']['catalog']['target']) and $freo->config['plugin']['catalog']['target_default']) {
		$_SESSION['plugin']['catalog']['target'] = $freo->config['plugin']['catalog']['target_default'];
	}

	//対象確認
	if (isset($_SESSION['plugin']['catalog']['target'])) {
		$plugin_catalog_targets = freo_page_catalog_get_target();

		$targets = array();
		foreach ($plugin_catalog_targets as $plugin_catalog_target) {
			if ($plugin_catalog_targets[$_SESSION['plugin']['catalog']['target']]['value'] >= $plugin_catalog_target['value']) {
				$targets[] = 'target = ' . $freo->pdo->quote($plugin_catalog_target['id']);
			}
		}

		$condition .= ' AND (target IS NULL OR ' . implode(' OR ', $targets) . ')';
	} else {
		$condition .= ' AND target IS NULL';
	}

	//並び順設定
	$order = null;
	if (isset($_GET['sort'])) {
		if ($_GET['sort'] == 'price') {
			$order = 'price';
		} elseif ($_GET['sort'] == 'price_desc') {
			$order = 'price DESC';
		} elseif ($_GET['sort'] == 'datetime') {
			$order = 'datetime';
		} elseif ($_GET['sort'] == 'datetime_desc') {
			$order = 'datetime DESC';
		}
	}
	if (empty($order)) {
		$order = 'sort, id';
	}

	//商品取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE status = \'publish\' AND display = \'publish\' ' . $condition . ' ORDER BY ' . $order . ' LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['catalog']['default_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['catalog']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalogs = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalogs[$data['id']] = $data;
	}

	//商品ID取得
	$plugin_catalog_keys = array_keys($plugin_catalogs);

	//商品タグ取得
	$plugin_catalog_tags = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		if (!$plugin_catalogs[$plugin_catalog]['tag']) {
			continue;
		}

		$plugin_catalog_tags[$plugin_catalog] = explode(',', $plugin_catalogs[$plugin_catalog]['tag']);
	}

	//商品ファイル取得
	$plugin_catalog_files = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		$file_dir = FREO_FILE_DIR . 'plugins/catalog_files/' . $plugin_catalog . '/';

		if (file_exists($file_dir)) {
			if ($dir = scandir($file_dir)) {
				foreach ($dir as $data) {
					if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
						$plugin_catalog_files[$plugin_catalog][$matches[1]] = $data;
					}
				}
			} else {
				freo_error('商品ファイル保存ディレクトリを開けません。');
			}
		}
	}

	//商品数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE status = \'publish\' AND display = \'publish\' ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                 = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_catalog_count = $data[0];
	$plugin_catalog_page  = ceil($plugin_catalog_count / $freo->config['plugin']['catalog']['default_limit']);

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_catalogs'            => $plugin_catalogs,
		'plugin_catalog_tags'        => $plugin_catalog_tags,
		'plugin_catalog_files'       => $plugin_catalog_files,
		'plugin_catalog_count'       => $plugin_catalog_count,
		'plugin_catalog_page'        => $plugin_catalog_page,
		'plugin_catalog_categories'  => $plugin_catalog_categories,
		'plugin_catalog_targets'     => freo_page_catalog_get_target(),
		'plugin_catalog_sizes'       => freo_page_catalog_get_size(),
		'plugin_catalog_prefectures' => freo_page_catalog_get_prefecture()
	));

	return;
}

/* 商品を確認 */
function freo_page_catalog_check_quantity($quantities)
{
	global $freo;

	//在庫数と一度に購入できる最大数を確認
	$stmt = $freo->pdo->prepare('SELECT id, name, stock, maximum, unit FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($quantities))) . ') AND status = \'publish\' AND (close IS NULL OR close >= :now)');
	$stmt->bindValue(':now', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$exists = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if ($data['stock'] != null and $quantities[$data['id']] > $data['stock']) {
			return '「' . $data['name'] . '」は在庫数が' . $data['stock'] . $data['unit'] . 'です。この商品を' . $data['stock'] . $data['unit'] . '以上カートに入れることはできません。';
		} elseif ($data['maximum'] != null and $quantities[$data['id']] > $data['maximum']) {
			return '「' . $data['name'] . '」は一度に購入できる最大数が' . $data['maximum'] . $data['unit'] . 'です。この商品を' . $data['maximum'] . $data['unit'] . '以上カートに入れることはできません。';
		}

		$exists[$data['id']] = true;
	}

	//存在しない商品を削除
	foreach ($quantities as $id => $count) {
		if (empty($exists[$id])) {
			unset($_SESSION['plugin']['catalog']['cart'][$id]);
		}
	}

	return null;
}

/* カートの内容を取得 */
function freo_page_catalog_get_cart($carts)
{
	global $freo;

	//商品取得
	if (empty($carts)) {
		$plugin_catalogs = array();
	} else {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE id IN(' . implode(',', array_map(array($freo->pdo, 'quote'), array_keys($carts))) . ') AND status = \'publish\' AND (close IS NULL OR close >= :now) ORDER BY sort, id');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$plugin_catalogs = $carts;
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_catalogs[$data['id']] = $data;
		}
	}

	foreach ($plugin_catalogs as $id => $data) {
		if (!is_array($data)) {
			unset($plugin_catalogs[$id]);
		}
	}

	//商品ID取得
	$plugin_catalog_keys = array_keys($plugin_catalogs);

	//商品数取得
	$plugin_catalog_counts = $carts;

	//商品タグ取得
	$plugin_catalog_tags = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		if (!$plugin_catalogs[$plugin_catalog]['tag']) {
			continue;
		}

		$plugin_catalog_tags[$plugin_catalog] = explode(',', $plugin_catalogs[$plugin_catalog]['tag']);
	}

	//商品ファイル取得
	$plugin_catalog_files = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		$file_dir = FREO_FILE_DIR . 'plugins/catalog_files/' . $plugin_catalog . '/';

		if (file_exists($file_dir)) {
			if ($dir = scandir($file_dir)) {
				foreach ($dir as $data) {
					if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
						$plugin_catalog_files[$plugin_catalog][$matches[1]] = $data;
					}
				}
			} else {
				freo_error('商品ファイル保存ディレクトリを開けません。');
			}
		}
	}

	//価格の小計を計算
	$plugin_catalog_price_subtotals = array();
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			$plugin_catalog_price_subtotals[$id] = $plugin_catalogs[$id]['price'] * $count;
		}
	}

	//価格の合計を計算
	$plugin_catalog_price_total = array_sum($plugin_catalog_price_subtotals);

	//対象の最大値を取得
	$targets = freo_page_catalog_get_target();

	$plugin_catalog_target_max = null;
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			if (isset($targets[$plugin_catalogs[$id]['target']]) and $targets[$plugin_catalogs[$id]['target']]['value'] > $plugin_catalog_target_max) {
				$plugin_catalog_target_max = $targets[$plugin_catalogs[$id]['target']]['id'];
			}
		}
	}

	//最大短辺・最大長辺を取得
	$sizes = freo_page_catalog_get_size();

	$plugin_catalog_short_max = 0;
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			if ($plugin_catalogs[$id]['size'] == 'direct') {
				$short = $plugin_catalogs[$id]['size_short'];
			} elseif ($plugin_catalogs[$id]['size'] == 'data') {
				$short = 0;
			} else {
				$short = $sizes[$plugin_catalogs[$id]['size']]['short'];
			}
			$short += $plugin_catalogs[$id]['packing_short'];

			if ($short > $plugin_catalog_short_max) {
				$plugin_catalog_short_max = $short;
			}
		}
	}

	$plugin_catalog_long_max = 0;
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			if ($plugin_catalogs[$id]['size'] == 'direct') {
				$long = $plugin_catalogs[$id]['size_long'];
			} elseif ($plugin_catalogs[$id]['size'] == 'data') {
				$long = 0;
			} else {
				$long = $sizes[$plugin_catalogs[$id]['size']]['long'];
			}
			$long += $plugin_catalogs[$id]['packing_long'];

			if ($long > $plugin_catalog_long_max) {
				$plugin_catalog_long_max = $long;
			}
		}
	}

	//厚さの合計を取得
	$plugin_catalog_thickness_total = 0;
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			$plugin_catalog_thickness_total += ($plugin_catalogs[$id]['thickness'] * $count) + ($plugin_catalogs[$id]['packing_thickness'] * $count);
		}
	}

	//重さの合計を取得
	$plugin_catalog_weight_total = 0;
	foreach ($plugin_catalog_counts as $id => $count) {
		if (isset($plugin_catalogs[$id])) {
			$plugin_catalog_weight_total += ($plugin_catalogs[$id]['weight'] * $count) + ($plugin_catalogs[$id]['packing_weight'] * $count);
		}
	}

	//商品データ作成
	$cart = array(
		'catalogs'                => $plugin_catalogs,
		'catalog_counts'          => $plugin_catalog_counts,
		'catalog_tags'            => $plugin_catalog_tags,
		'catalog_files'           => $plugin_catalog_files,
		'catalog_price_subtotals' => $plugin_catalog_price_subtotals,
		'catalog_price_total'     => $plugin_catalog_price_total,
		'catalog_target_max'      => $plugin_catalog_target_max,
		'catalog_short_max'       => $plugin_catalog_short_max,
		'catalog_long_max'        => $plugin_catalog_long_max,
		'catalog_thickness_total' => $plugin_catalog_thickness_total,
		'catalog_weight_total'    => $plugin_catalog_weight_total
	);

	return $cart;
}

/* 対象を取得 */
function freo_page_catalog_get_target()
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
function freo_page_catalog_get_size()
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
function freo_page_catalog_get_prefecture()
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

?>
