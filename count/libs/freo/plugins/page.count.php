<?php

/*********************************************************************

 カウンタプラグイン (2012/08/10)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_count()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_count_setup();
			break;
		case 'setup_execute':
			freo_page_count_setup_execute();
			break;
		case 'admin':
			freo_page_count_admin();
			break;
		default:
			freo_page_count_default();
	}

	return;
}

/* セットアップ */
function freo_page_count_setup()
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
			freo_redirect('count/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'count',
		'plugin_name' => FREO_PLUGIN_COUNT_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_count_setup_execute()
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
			'plugin_counts' => '(date DATE NOT NULL, count INT UNSIGNED NOT NULL, session INT UNSIGNED NOT NULL)'
		);
	} else {
		$queries = array(
			'plugin_counts' => '(date DATE NOT NULL, count INTEGER UNSIGNED NOT NULL, session INTEGER UNSIGNED NOT NULL)'
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
	freo_log(FREO_PLUGIN_COUNT_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('count/setup?exec=setup', true);

	return;
}

/* 管理画面 | カウント一覧 */
function freo_page_count_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['date']) or !preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
		$_GET['date'] = date('Ym');
	}

	//日別カウント取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(date, \'%d\') AS day, count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE DATE_FORMAT(date, \'%Y%m\') = :now ORDER BY day DESC');
	} else {
		$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%d\', date) AS day, count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE STRFTIME(\'%Y%m\', date) = :now ORDER BY day DESC');
	}
	$stmt->bindValue(':now', $_GET['date']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_count_days = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_count_days[$data['day']] = $data;
	}

	//月別カウント取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('SELECT DATE_FORMAT(date, \'%Y%m\') AS month, SUM(count) AS count, SUM(session) AS session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts GROUP by month ORDER BY month DESC');
	} else {
		$stmt = $freo->pdo->query('SELECT STRFTIME(\'%Y%m\', date) AS month, SUM(count) AS count, SUM(session) AS session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts GROUP by month ORDER BY month DESC');
	}
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_count_months = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_count_months[$data['month']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'               => freo_token('create'),
		'plugin_count_days'   => $plugin_count_days,
		'plugin_count_months' => $plugin_count_months
	));

	return;
}

/* カウント一覧 */
function freo_page_count_default()
{
	global $freo;

	freo_redirect('count/admin');

	return;
}

?>
