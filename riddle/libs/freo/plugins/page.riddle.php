<?php

/*********************************************************************

 なぞなぞ認証プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_riddle()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_riddle_setup();
			break;
		case 'setup_execute':
			freo_page_riddle_setup_execute();
			break;
		case 'admin':
			freo_page_riddle_admin();
			break;
		case 'admin_form':
			freo_page_riddle_admin_form();
			break;
		case 'admin_post':
			freo_page_riddle_admin_post();
			break;
		case 'admin_delete':
			freo_page_riddle_admin_delete();
			break;
		default:
			freo_page_riddle_default();
	}

	return;
}

/* セットアップ */
function freo_page_riddle_setup()
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
			freo_redirect('riddle/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'riddle',
		'plugin_name' => FREO_PLUGIN_RIDDLE_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_riddle_setup_execute()
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
			'plugin_riddles' => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, created DATETIME NOT NULL, modified DATETIME NOT NULL, question VARCHAR(255) NOT NULL, answer VARCHAR(255) NOT NULL, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_riddles' => '(id INTEGER, created DATETIME NOT NULL, modified DATETIME NOT NULL, question VARCHAR NOT NULL, answer VARCHAR NOT NULL, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_RIDDLE_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('riddle/setup?exec=setup', true);

	return;
}

/* 管理画面 | なぞなぞ管理 */
function freo_page_riddle_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//なぞなぞ取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles ORDER BY id DESC');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_riddles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_riddles[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'plugin_riddles' => $plugin_riddles
	));

	return;
}

/* 管理画面 | なぞなぞ入力 */
function freo_page_riddle_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//問題
			if ($_POST['plugin_riddle']['question'] == '') {
				$freo->smarty->append('errors', '問題が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_riddle']['question'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '問題は80文字以内で入力してください。');
			}

			//回答
			if ($_POST['plugin_riddle']['answer'] == '') {
				$freo->smarty->append('errors', '回答が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_riddle']['answer'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '回答は80文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_riddle = $_POST['plugin_riddle'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('riddle/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_riddle = $data;
			} else {
				freo_error('指定されたなぞなぞが見つかりません。', '404 Not Found');
			}
		} else {
			//新規データ設定
			$plugin_riddle = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_riddle' => $plugin_riddle,
		)
	));

	return;
}

/* 管理画面 | なぞなぞ登録 */
function freo_page_riddle_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('riddle/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('riddle/admin?error=1');
	}

	//入力データ取得
	$riddle = $_SESSION['input']['plugin_riddle'];

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_riddles SET modified = :now, question = :question, answer = :answer WHERE id = :id');
		$stmt->bindValue(':now',      date('Y-m-d H:i:s'));
		$stmt->bindValue(':question', $riddle['question']);
		$stmt->bindValue(':answer',   $riddle['answer']);
		$stmt->bindValue(':id',       $riddle['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_riddles VALUES(NULL, :now1, :now2, :question, :answer)');
		$stmt->bindValue(':now1',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':question',    $riddle['question']);
		$stmt->bindValue(':answer',      $riddle['answer']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('なぞなぞを編集しました。');
	} else {
		freo_log('なぞなぞを新規に登録しました。');
	}

	//なぞなぞ管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('riddle/admin?exec=update&id=' . $riddle['id']);
	} else {
		freo_redirect('riddle/admin?exec=insert');
	}

	return;
}

/* 管理画面 | なぞなぞ削除 */
function freo_page_riddle_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('riddle/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('riddle/admin?error=1');
	}

	//なぞなぞ削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_riddles WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_riddles AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('なぞなぞを削除しました。');

	//なぞなぞ管理へ移動
	freo_redirect('riddle/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* なぞなぞ一覧 */
function freo_page_riddle_default()
{
	global $freo;

	freo_redirect('riddle/admin');

	return;
}

?>
