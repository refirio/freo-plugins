<?php

/*********************************************************************

 ブックマーク登録プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_bookmark()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_bookmark_setup();
			break;
		case 'setup_execute':
			freo_page_bookmark_setup_execute();
			break;
		case 'admin':
			freo_page_bookmark_admin();
			break;
		case 'admin_form':
			freo_page_bookmark_admin_form();
			break;
		case 'admin_post':
			freo_page_bookmark_admin_post();
			break;
		case 'admin_delete':
			freo_page_bookmark_admin_delete();
			break;
		default:
			freo_page_bookmark_default();
	}

	return;
}

/* セットアップ */
function freo_page_bookmark_setup()
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
			freo_redirect('bookmark/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'bookmark',
		'plugin_name' => FREO_PLUGIN_BOOKMARK_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_bookmark_setup_execute()
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
			'plugin_bookmarks' => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, created DATETIME NOT NULL, modified DATETIME NOT NULL, title VARCHAR(255) NOT NULL, url TEXT NOT NULL, tag VARCHAR(255), text TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_bookmarks' => '(id INTEGER, created DATETIME NOT NULL, modified DATETIME NOT NULL, title VARCHAR NOT NULL, url TEXT NOT NULL, tag VARCHAR, text TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_BOOKMARK_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('bookmark/setup?exec=setup', true);

	return;
}

/* 管理画面 | ブックマーク管理 */
function freo_page_bookmark_admin()
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

	//ブックマーク取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['bookmark']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['bookmark']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_bookmarks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_bookmarks[$data['id']] = $data;
	}

	//ブックマーク数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                  = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_bookmark_count = $data[0];
	$plugin_bookmark_page  = ceil($plugin_bookmark_count / $freo->config['plugin']['bookmark']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                 => freo_token('create'),
		'plugin_bookmarks'      => $plugin_bookmarks,
		'plugin_bookmark_count' => $plugin_bookmark_count,
		'plugin_bookmark_page'  => $plugin_bookmark_page
	));

	return;
}

/* 管理画面 | ブックマーク入力 */
function freo_page_bookmark_admin_form()
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
			//タイトル
			if ($_POST['plugin_bookmark']['title'] == '') {
				$freo->smarty->append('errors', 'タイトルが入力されていません。');
			} elseif (mb_strlen($_POST['plugin_bookmark']['title'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タイトルは80文字以内で入力してください。');
			}

			//URL
			if ($_POST['plugin_bookmark']['url'] == '') {
				$freo->smarty->append('errors', 'URLが入力されていません。');
			} elseif (mb_strlen($_POST['plugin_bookmark']['url'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', 'URLは5000文字以内で入力してください。');
			} else {
				if ($_GET['id']) {
					$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks WHERE id <> :id AND url = :url');
					$stmt->bindValue(':id',  $_GET['id']);
					$stmt->bindValue(':url', $_POST['plugin_bookmark']['url']);
				} else {
					$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks WHERE url = :url');
					$stmt->bindValue(':url', $_POST['plugin_bookmark']['url']);
				}
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力されたURLはすでに登録されています。');
				}
			}

			//タグ
			if (mb_strlen($_POST['plugin_bookmark']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//本文
			if (mb_strlen($_POST['plugin_bookmark']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_bookmark = $_POST['plugin_bookmark'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('bookmark/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_bookmark = $data;
			} else {
				freo_error('指定されたブックマークが見つかりません。', '404 Not Found');
			}
		} else {
			//新規データ設定
			$plugin_bookmark = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_bookmark' => $plugin_bookmark
		)
	));

	return;
}

/* 管理画面 | ブックマーク登録 */
function freo_page_bookmark_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('bookmark/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('bookmark/admin?error=1');
	}

	//入力データ取得
	$bookmark = $_SESSION['input']['plugin_bookmark'];

	if ($bookmark['tag'] == '') {
		$bookmark['tag'] = null;
	}
	if ($bookmark['text'] == '') {
		$bookmark['text'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks SET modified = :now, title = :title, url = :url, tag = :tag, text = :text WHERE id = :id');
		$stmt->bindValue(':now',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':title', $bookmark['title']);
		$stmt->bindValue(':url',   $bookmark['url']);
		$stmt->bindValue(':tag',   $bookmark['tag']);
		$stmt->bindValue(':text',  $bookmark['text']);
		$stmt->bindValue(':id',    $bookmark['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks VALUES(NULL, :now1, :now2, :title, :url, :tag, :text)');
		$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':title', $bookmark['title']);
		$stmt->bindValue(':url',   $bookmark['url']);
		$stmt->bindValue(':tag',   $bookmark['tag']);
		$stmt->bindValue(':text',  $bookmark['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('ブックマークを編集しました。');
	} else {
		freo_log('ブックマークを新規に登録しました。');
	}

	//ブックマーク管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('bookmark/admin?exec=update&id=' . $bookmark['id']);
	} else {
		freo_redirect('bookmark/admin?exec=insert');
	}

	return;
}

/* 管理画面 | ブックマーク削除 */
function freo_page_bookmark_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('bookmark/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('bookmark/admin?error=1');
	}

	//ブックマーク削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('ブックマークを削除しました。');

	//ブックマーク管理へ移動
	freo_redirect('bookmark/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* ブックマーク一覧 */
function freo_page_bookmark_default()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$condition .= ' AND title LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%');
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

	//ブックマーク取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['bookmark']['default_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['bookmark']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_bookmarks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_bookmarks[$data['id']] = $data;
	}

	//ブックマークID取得
	$plugin_bookmark_keys = array_keys($plugin_bookmarks);

	//ブックマークタグ取得
	$plugin_bookmark_tags = array();
	foreach ($plugin_bookmark_keys as $plugin_bookmark) {
		if (!$plugin_bookmarks[$plugin_bookmark]['tag']) {
			continue;
		}

		$plugin_bookmark_tags[$plugin_bookmark] = explode(',', $plugin_bookmarks[$plugin_bookmark]['tag']);
	}

	//ブックマーク数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                  = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_bookmark_count = $data[0];
	$plugin_bookmark_page  = ceil($plugin_bookmark_count / $freo->config['plugin']['bookmark']['default_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                 => freo_token('create'),
		'plugin_bookmarks'      => $plugin_bookmarks,
		'plugin_bookmark_tags'  => $plugin_bookmark_tags,
		'plugin_bookmark_count' => $plugin_bookmark_count,
		'plugin_bookmark_page'  => $plugin_bookmark_page
	));

	return;
}

?>
