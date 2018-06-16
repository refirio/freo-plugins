<?php

/*********************************************************************

 メッセージ登録プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_message()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_message_setup();
			break;
		case 'setup_execute':
			freo_page_message_setup_execute();
			break;
		case 'admin':
			freo_page_message_admin();
			break;
		case 'admin_view':
			freo_page_message_admin_view();
			break;
		case 'admin_form':
			freo_page_message_admin_form();
			break;
		case 'admin_post':
			freo_page_message_admin_post();
			break;
		case 'admin_delete':
			freo_page_message_admin_delete();
			break;
		case 'preview':
			freo_page_message_preview();
			break;
		case 'post':
			freo_page_message_post();
			break;
		case 'complete':
			freo_page_message_complete();
			break;
		default:
			freo_page_message_default();
	}

	return;
}

/* セットアップ */
function freo_page_message_setup()
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
			freo_redirect('message/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'message',
		'plugin_name' => FREO_PLUGIN_MESSAGE_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_message_setup_execute()
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
			'plugin_messages' => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR(80) NOT NULL, text TEXT NOT NULL, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_messages' => '(id INTEGER, created DATETIME NOT NULL, modified DATETIME NOT NULL, ip VARCHAR(80) NOT NULL, text TEXT NOT NULL, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_MESSAGE_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('message/setup?exec=setup', true);

	return;
}

/* 管理画面 | メッセージ管理 */
function freo_page_message_admin()
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
	if (isset($_GET['word'])) {
		$condition .= ' AND text LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%');
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if (isset($_GET['date'])) {
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
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//メッセージ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['message']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['message']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_messages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_messages[$data['id']] = $data;
	}

	//メッセージ数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                 = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_message_count = $data[0];
	$plugin_message_page  = ceil($plugin_message_count / $freo->config['plugin']['message']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                => freo_token('create'),
		'plugin_messages'      => $plugin_messages,
		'plugin_message_count' => $plugin_message_count,
		'plugin_message_page'  => $plugin_message_page
	));

	return;
}

/* 管理画面 | メッセージ表示 */
function freo_page_message_admin_view()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('message/admin?error=1');
	}

	//メッセージ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_message = $data;
	} else {
		freo_error('指定されたメッセージが見つかりません。', '404 Not Found');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'plugin_message' => $plugin_message
	));

	return;
}

/* 管理画面 | メッセージ入力 */
function freo_page_message_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('message/admin?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//本文
			if ($_POST['plugin_message']['text'] == '') {
				$freo->smarty->append('errors', '本文が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_message']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_message = $_POST['plugin_message'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('message/admin_post?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id']);
		}
	} else {
		//編集データ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_message = $data;
		} else {
			freo_error('指定されたメッセージが見つかりません。', '404 Not Found');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_message' => $plugin_message,
		)
	));

	return;
}

/* 管理画面 | メッセージ登録 */
function freo_page_message_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('message/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('message/admin?error=1');
	}

	//入力データ取得
	$message = $_SESSION['input']['plugin_message'];

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_messages SET modified = :now, text = :text WHERE id = :id');
	$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':text', $message['text']);
	$stmt->bindValue(':id',   $message['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('メッセージを編集しました。');

	//メッセージ管理へ移動
	freo_redirect('message/admin?exec=update&id=' . $message['id']);

	return;
}

/* 管理画面 | メッセージ削除 */
function freo_page_message_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('message/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('message/admin?error=1');
	}

	//メッセージ削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_messages AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('メッセージを削除しました。');

	//メッセージ管理へ移動
	freo_redirect('message/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* メッセージ確認 */
function freo_page_message_preview()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('message');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('message');
		}

		//登録処理へ移動
		freo_redirect('message/post?freo%5Btoken%5D=' . freo_token('create'));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'plugin_message' => $_SESSION['input']['plugin_message']
	));

	return;
}

/* メッセージ登録 */
function freo_page_message_post()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('message');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('message');
	}

	//入力データ取得
	$message = $_SESSION['input']['plugin_message'];

	//データ登録
	$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_messages VALUES(NULL, :now1, :now2, :ip, :text)');
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$stmt->bindValue(':ip',   $_SERVER['REMOTE_ADDR']);
	$stmt->bindValue(':text', $message['text']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('メッセージを新規に登録しました。');

	//登録完了画面へ移動
	freo_redirect('message/complete');

	return;
}

/* メッセージ登録完了 */
function freo_page_message_complete()
{
	global $freo;

	return;
}

/* メッセージ入力 */
function freo_page_message_default()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//本文
			if ($_POST['plugin_message']['text'] == '') {
				$freo->smarty->append('errors', '本文が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_message']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_message = $_POST['plugin_message'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('message/preview');
			} else {
				//登録処理へ移動
				freo_redirect('message/post?freo%5Btoken%5D=' . freo_token('create'));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_message = $_SESSION['input']['plugin_message'];
		} else {
			//新規データ設定
			$plugin_message = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_message' => $plugin_message
		)
	));

	return;
}

?>
