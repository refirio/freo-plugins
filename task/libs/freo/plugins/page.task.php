<?php

/*********************************************************************

 タスク登録プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_task()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_task_setup();
			break;
		case 'setup_execute':
			freo_page_task_setup_execute();
			break;
		case 'admin':
			freo_page_task_admin();
			break;
		case 'admin_view':
			freo_page_task_admin_view();
			break;
		case 'admin_archive':
			freo_page_task_admin_archive();
			break;
		case 'admin_form':
			freo_page_task_admin_form();
			break;
		case 'admin_post':
			freo_page_task_admin_post();
			break;
		case 'admin_delete':
			freo_page_task_admin_delete();
			break;
		case 'admin_category':
			freo_page_task_admin_category();
			break;
		case 'admin_category_form':
			freo_page_task_admin_category_form();
			break;
		case 'admin_category_post':
			freo_page_task_admin_category_post();
			break;
		case 'admin_category_update':
			freo_page_task_admin_category_update();
			break;
		case 'admin_category_delete':
			freo_page_task_admin_category_delete();
			break;
		default:
			freo_page_task_default();
	}

	return;
}

/* セットアップ */
function freo_page_task_setup()
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
			freo_redirect('task/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'task',
		'plugin_name' => FREO_PLUGIN_TASK_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_task_setup_execute()
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
			'plugin_tasks'           => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, category_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, title VARCHAR(255) NOT NULL, text TEXT, PRIMARY KEY(id))',
			'plugin_task_categories' => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_tasks'           => '(id INTEGER, category_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, title VARCHAR NOT NULL, text TEXT, PRIMARY KEY(id))',
			'plugin_task_categories' => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_TASK_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('task/setup?exec=setup', true);

	return;
}

/* 管理画面 | タスク管理 */
function freo_page_task_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//タスク取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks WHERE status = \'publish\' ORDER BY id DESC');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_tasks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_tasks[$data['category_id']][$data['id']] = $data;
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_task_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_task_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_tasks'           => $plugin_tasks,
		'plugin_task_categories' => $plugin_task_categories
	));

	return;
}

/* 管理画面 | タスク表示 */
function freo_page_task_admin_view()
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

	//編集データ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_task = $data;
	} else {
		freo_error('指定されたタスクが見つかりません。', '404 Not Found');
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_task_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_task_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_task'            => $plugin_task,
		'plugin_task_categories' => $plugin_task_categories
	));

	return;
}

/* 管理画面 | 完了済みタスク表示 */
function freo_page_task_admin_archive()
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
		$condition .= ' AND title LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%');
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
	$condition = ' WHERE id IS NOT NULL AND status = \'complete\' ' . $condition;

	//メッセージ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['task']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['task']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_tasks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_tasks[$data['id']] = $data;
	}

	//メッセージ数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data              = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_task_count = $data[0];
	$plugin_task_page  = ceil($plugin_task_count / $freo->config['plugin']['task']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'             => freo_token('create'),
		'plugin_tasks'      => $plugin_tasks,
		'plugin_task_count' => $plugin_task_count,
		'plugin_task_page'  => $plugin_task_page
	));

	return;
}

/* 管理画面 | タスク入力 */
function freo_page_task_admin_form()
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
			//カテゴリー
			if ($_POST['plugin_task']['category_id'] == '') {
				$freo->smarty->append('errors', 'カテゴリーが入力されていません。');
			}

			//状態
			if ($_POST['plugin_task']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//内容
			if ($_POST['plugin_task']['title'] == '') {
				$freo->smarty->append('errors', '内容が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_task']['title'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '内容は80文字以内で入力してください。');
			}

			//詳細
			if (mb_strlen($_POST['plugin_task']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '詳細は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_task = $_POST['plugin_task'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('task/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_task = $data;
			} else {
				freo_error('指定されたタスクが見つかりません。', '404 Not Found');
			}
		} else {
			//新規データ設定
			$plugin_task = array();
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_task_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_task_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_task_categories' => $plugin_task_categories,
		'input' => array(
			'plugin_task' => $plugin_task,
		)
	));

	return;
}

/* 管理画面 | タスク登録 */
function freo_page_task_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('task/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('task/admin?error=1');
	}

	//入力データ取得
	$task = $_SESSION['input']['plugin_task'];

	if ($task['text'] == '') {
		$task['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_tasks SET category_id = :category_id, modified = :now, status = :status, title = :title, text = :text WHERE id = :id');
		$stmt->bindValue(':category_id', $task['category_id']);
		$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',      $task['status']);
		$stmt->bindValue(':title',       $task['title']);
		$stmt->bindValue(':text',        $task['text']);
		$stmt->bindValue(':id',          $task['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_tasks VALUES(NULL, :category_id, :now1, :now2, :status, :title, :text)');
		$stmt->bindValue(':category_id', $task['category_id']);
		$stmt->bindValue(':now1',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',        date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',      $task['status']);
		$stmt->bindValue(':title',       $task['title']);
		$stmt->bindValue(':text',        $task['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('タスクを編集しました。');
	} else {
		freo_log('タスクを新規に登録しました。');
	}

	//タスク管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('task/admin?exec=update&id=' . $task['id']);
	} else {
		freo_redirect('task/admin?exec=insert');
	}

	return;
}

/* 管理画面 | タスク削除 */
function freo_page_task_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('task/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('task/admin?error=1');
	}

	//タスク削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_tasks WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_tasks AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('タスクを削除しました。');

	//タスク管理へ移動
	freo_redirect('task/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | カテゴリー管理 */
function freo_page_task_admin_category()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_task_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_task_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_task_categories' => $plugin_task_categories
	));

	return;
}

/* 管理画面 | カテゴリー入力 */
function freo_page_task_admin_category_form()
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
		if ($_POST['plugin_task_category']['sort'] != '') {
			$_POST['plugin_task_category']['sort'] = mb_convert_kana($_POST['plugin_task_category']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//カテゴリーID
			if ($_POST['plugin_task_category']['id'] == '') {
				$freo->smarty->append('errors', 'カテゴリーIDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_task_category']['id'])) {
				$freo->smarty->append('errors', 'カテゴリーIDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_task_category']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリーIDは80文字以内で入力してください。');
			}

			//並び順
			if ($_POST['plugin_task_category']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_task_category']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_task_category']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//カテゴリー名
			if ($_POST['plugin_task_category']['name'] == '') {
				$freo->smarty->append('errors', 'カテゴリー名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_task_category']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリー名は80文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_task_category']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_task_category = $_POST['plugin_task_category'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('task/admin_category_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_task_category = $_SESSION['input']['plugin_task_category'];
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_task_category = $data;
			} else {
				freo_error('指定されたカテゴリーが見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_task_category = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_task_category' => $plugin_task_category
		)
	));

	return;
}

/* 管理画面 | カテゴリー登録 */
function freo_page_task_admin_category_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('task/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('task/admin_category?error=1');
	}

	//入力データ取得
	$plugin_task_category = $_SESSION['input']['plugin_task_category'];

	if ($plugin_task_category['memo'] == '') {
		$plugin_task_category['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_task_categories SET modified = :now, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_task_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_task_category['name']);
		$stmt->bindValue(':memo', $plugin_task_category['memo']);
		$stmt->bindValue(':id',   $plugin_task_category['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_task_categories VALUES(:id, :now1, :now2, :sort, :name, :memo)');
		$stmt->bindValue(':id',   $plugin_task_category['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_task_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_task_category['name']);
		$stmt->bindValue(':memo', $plugin_task_category['memo']);
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
		freo_redirect('task/admin_category?exec=update&id=' . $plugin_task_category['id']);
	} else {
		freo_redirect('task/admin_category?exec=insert');
	}

	return;
}

/* 管理画面 | カテゴリー一括編集 */
function freo_page_task_admin_category_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('task/admin_category?error=1');
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

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_task_categories SET sort = :sort WHERE id = :id');
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
	freo_redirect('task/admin_category?exec=sort');

	return;
}

/* 管理画面 | カテゴリー削除 */
function freo_page_task_admin_category_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('task/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('task/admin_category?error=1');
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_task_categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('カテゴリーを削除しました。');

	//カテゴリー管理へ移動
	freo_redirect('task/admin_category?exec=delete&id=' . $_GET['id']);

	return;
}

/* タスク一覧 */
function freo_page_task_default()
{
	global $freo;

	freo_redirect('task/admin');

	return;
}

?>
