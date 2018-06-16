<?php

/*********************************************************************

 人気コンテンツプラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_popularity()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_popularity_setup();
			break;
		case 'setup_execute':
			freo_page_popularity_setup_execute();
			break;
		case 'admin':
			freo_page_popularity_admin();
			break;
		case 'admin_form':
			freo_page_popularity_admin_form();
			break;
		case 'admin_post':
			freo_page_popularity_admin_post();
			break;
		case 'admin_delete':
			freo_page_popularity_admin_delete();
			break;
		default:
			freo_page_popularity_default();
	}

	return;
}

/* セットアップ */
function freo_page_popularity_setup()
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
			freo_redirect('popularity/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'popularity',
		'plugin_name' => FREO_PLUGIN_POPULARITY_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_popularity_setup_execute()
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
			'plugin_popularies' => '(parameter VARCHAR(255) NOT NULL, count INT UNSIGNED NOT NULL, status VARCHAR(20) NOT NULL, title VARCHAR(255))'
		);
	} else {
		$queries = array(
			'plugin_popularies' => '(parameter VARCHAR NOT NULL, count INTEGER UNSIGNED NOT NULL, status VARCHAR NOT NULL, title VARCHAR)'
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
	freo_log(FREO_PLUGIN_POPULARITY_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('popularity/setup?exec=setup', true);

	return;
}

/* 管理画面 | カウント一覧 */
function freo_page_popularity_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//カウント取得
	if (isset($_GET['type']) and $_GET['type'] == 'all') {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_popularies ORDER BY count DESC, parameter');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_popularity_raws = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_popularity_raws[$data['parameter']] = $data;
		}
	} else {
		$plugin_popularity_raws = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_popularity_raws' => $plugin_popularity_raws,
	));

	return;
}

/* 管理画面 | データ入力 */
function freo_page_popularity_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['parameter'])) {
		freo_redirect('popularity/admin?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//アクセス数
			if ($_POST['plugin_popularity']['count'] == '') {
				$freo->smarty->append('errors', 'アクセス数が入力されていません。');
			} elseif (!preg_match('/^\d+$/', $_POST['plugin_popularity']['count'])) {
				$freo->smarty->append('errors', 'アクセス数は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_popularity']['count'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', 'アクセス数は10文字以内で入力してください。');
			}

			//状態
			if ($_POST['plugin_popularity']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//タイトル
			if (mb_strlen($_POST['plugin_popularity']['title'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タイトルは80文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_popularity = $_POST['plugin_popularity'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('popularity/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['parameter'] ? '&parameter=' . $_GET['parameter'] : ''));
		}
	} else {
		//編集データ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_popularies WHERE parameter = :parameter');
		$stmt->bindValue(':parameter', str_replace('&amp;', '&', $_GET['parameter']));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_popularity = $data;
		} else {
			freo_error('指定されたデータが見つかりません。', '404 Not Found');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_popularity' => $plugin_popularity
		)
	));

	return;
}

/* 管理画面 | データ登録 */
function freo_page_popularity_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('popularity/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('popularity/admin?error=1');
	}

	//入力データ取得
	$popularity = $_SESSION['input']['plugin_popularity'];

	if ($popularity['title'] == '') {
		$popularity['title'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_popularies SET count = :count, status = :status, title = :title WHERE parameter = :parameter');
	$stmt->bindValue(':count',     $popularity['count'], PDO::PARAM_INT);
	$stmt->bindValue(':status',    $popularity['status']);
	$stmt->bindValue(':title',     $popularity['title']);
	$stmt->bindValue(':parameter', $popularity['parameter']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('人気コンテンツを編集しました。');

	//データ管理へ移動
	freo_redirect('popularity/admin?exec=update&id=' . $popularity['parameter']);

	return;
}

/* 管理画面 | データリセット */
function freo_page_popularity_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('popularity/admin?error=1');
	}

	//データリセット
	$stmt = $freo->pdo->query('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_popularies');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	//ログ記録
	freo_log('人気コンテンツをリセットしました。');

	//データ管理へ移動
	freo_redirect('popularity/admin?exec=delete');

	return;
}

/* カウント一覧 */
function freo_page_popularity_default()
{
	global $freo;

	freo_redirect('popularity/admin');

	return;
}

?>
