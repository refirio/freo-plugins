<?php

/*********************************************************************

 ページタグ管理プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_page_tagmanager()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_page_tagmanager_admin();
			break;
		case 'admin_form':
			freo_page_page_tagmanager_admin_form();
			break;
		case 'admin_post':
			freo_page_page_tagmanager_admin_post();
			break;
		case 'admin_delete':
			freo_page_page_tagmanager_admin_delete();
			break;
		default:
			freo_page_page_tagmanager_default();
	}

	return;
}

/* 管理画面 | ページタグ管理 */
function freo_page_page_tagmanager_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ページタグ取得
	$stmt = $freo->pdo->query('SELECT tag FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE tag IS NOT NULL');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($tags[$tag])) {
				$tags[$tag]++;
			} else {
				$tags[$tag] = 1;
			}
		}
	}

	ksort($tags, SORT_STRING);

	$page_tags = array();
	foreach ($tags as $tag => $count) {
		$page_tags[] = array(
			'tag'   => $tag,
			'count' => $count
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'     => freo_token('create'),
		'page_tags' => $page_tags
	));

	return;
}

/* 管理画面 | ページタグ入力 */
function freo_page_page_tagmanager_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['tag'])) {
		$_GET['tag'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//タグ
			if ($_POST['plugin_page_tagmanager']['new'] == '') {
				$freo->smarty->append('errors', 'タグが入力されていません');
			} elseif (mb_strlen($_POST['plugin_page_tagmanager']['new'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			} elseif ($_POST['plugin_page_tagmanager']['new'] == $_POST['plugin_page_tagmanager']['old']) {
				$freo->smarty->append('errors', 'タグが変更されていません');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_page_tagmanager = $_POST['plugin_page_tagmanager'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('page_tagmanager/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['tag'] ? '&tag=' . $_GET['tag'] : ''));
		}
	} else {
		//編集ページタグ確認
		$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%'));
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$data       = $stmt->fetch(PDO::FETCH_NUM);
		$page_count = $data[0];

		if ($page_count == 0) {
			freo_error('指定されたページタグが見つかりません。', '404 Not Found');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

/* 管理画面 | ページタグ登録 */
function freo_page_page_tagmanager_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('page_tagmanager/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('page_tagmanager/admin?error=1');
	}

	//入力データ取得
	$page_tagmanager = $_SESSION['input']['plugin_page_tagmanager'];

	if ($page_tagmanager['old'] == '') {
		freo_redirect('page_tagmanager/admin?error=1');
	}
	if ($page_tagmanager['new'] == '') {
		freo_redirect('page_tagmanager/admin?error=1');
	}

	//編集ページ取得
	$stmt = $freo->pdo->query('SELECT id, tag FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE tag = ' . $freo->pdo->quote($page_tagmanager['old']) . ' OR tag LIKE ' . $freo->pdo->quote($page_tagmanager['old'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $page_tagmanager['old']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $page_tagmanager['old'] . ',%'));
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//データ登録
	$freo->pdo->beginTransaction();

	foreach ($pages as $page) {
		$new_tags = array();
		foreach (explode(',', $page['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if ($tag == $page_tagmanager['old']) {
				foreach (explode(',', $page_tagmanager['new']) as $new_tag) {
					$new_tags[$new_tag] = $new_tag;
				}
			} else {
				$new_tags[$tag] = $tag;
			}
		}

		$new_tag = implode(',', $new_tags);

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET modified = :now, tag = :tag WHERE id = :id');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$stmt->bindValue(':tag', $new_tag);
		$stmt->bindValue(':id',  $page['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	$freo->pdo->commit();

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('ページタグを編集しました。');

	//ページタグ管理へ移動
	freo_redirect('page_tagmanager/admin?exec=update&tag=' . $page_tagmanager['old']);

	return;
}

/* 管理画面 | ページタグ削除 */
function freo_page_page_tagmanager_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['tag'])) {
		freo_redirect('page_tagmanager/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('page_tagmanager/admin?error=1');
	}

	//編集ページ取得
	$stmt = $freo->pdo->query('SELECT id, tag FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%'));
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//データ登録
	$freo->pdo->beginTransaction();

	foreach ($pages as $page) {
		$new_tags = array();
		foreach (explode(',', $page['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if ($tag != $_GET['tag']) {
				$new_tags[$tag] = $tag;
			}
		}

		$new_tag = implode(',', $new_tags);
		if ($new_tag == '') {
			$new_tag = null;
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET modified = :now, tag = :tag WHERE id = :id');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$stmt->bindValue(':tag', $new_tag);
		$stmt->bindValue(':id',  $page['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	$freo->pdo->commit();

	//ログ記録
	freo_log('ページタグを削除しました。');

	//ページタグ管理へ移動
	freo_redirect('page_tagmanager/admin?exec=delete&tag=' . $_GET['tag']);

	return;
}

/* ページタグ管理 */
function freo_page_page_tagmanager_default()
{
	global $freo;

	freo_redirect('page_tagmanager/admin');

	return;
}

?>
