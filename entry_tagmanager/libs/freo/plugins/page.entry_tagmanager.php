<?php

/*********************************************************************

 エントリータグ管理プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_entry_tagmanager()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_entry_tagmanager_admin();
			break;
		case 'admin_form':
			freo_page_entry_tagmanager_admin_form();
			break;
		case 'admin_post':
			freo_page_entry_tagmanager_admin_post();
			break;
		case 'admin_delete':
			freo_page_entry_tagmanager_admin_delete();
			break;
		default:
			freo_page_entry_tagmanager_default();
	}

	return;
}

/* 管理画面 | エントリータグ管理 */
function freo_page_entry_tagmanager_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//エントリータグ取得
	$stmt = $freo->pdo->query('SELECT tag FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE tag IS NOT NULL');
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

	$entry_tags = array();
	foreach ($tags as $tag => $count) {
		$entry_tags[] = array(
			'tag'   => $tag,
			'count' => $count
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'      => freo_token('create'),
		'entry_tags' => $entry_tags
	));

	return;
}

/* 管理画面 | エントリータグ入力 */
function freo_page_entry_tagmanager_admin_form()
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
			if ($_POST['plugin_entry_tagmanager']['new'] == '') {
				$freo->smarty->append('errors', 'タグが入力されていません');
			} elseif (mb_strlen($_POST['plugin_entry_tagmanager']['new'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			} elseif ($_POST['plugin_entry_tagmanager']['new'] == $_POST['plugin_entry_tagmanager']['old']) {
				$freo->smarty->append('errors', 'タグが変更されていません');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_entry_tagmanager = $_POST['plugin_entry_tagmanager'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('entry_tagmanager/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['tag'] ? '&tag=' . $_GET['tag'] : ''));
		}
	} else {
		//編集エントリータグ確認
		$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%'));
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$data        = $stmt->fetch(PDO::FETCH_NUM);
		$entry_count = $data[0];

		if ($entry_count == 0) {
			freo_error('指定されたエントリータグが見つかりません。', '404 Not Found');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

/* 管理画面 | エントリータグ登録 */
function freo_page_entry_tagmanager_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('entry_tagmanager/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('entry_tagmanager/admin?error=1');
	}

	//入力データ取得
	$entry_tagmanager = $_SESSION['input']['plugin_entry_tagmanager'];

	if ($entry_tagmanager['old'] == '') {
		freo_redirect('entry_tagmanager/admin?error=1');
	}
	if ($entry_tagmanager['new'] == '') {
		freo_redirect('entry_tagmanager/admin?error=1');
	}

	//編集エントリー取得
	$stmt = $freo->pdo->query('SELECT id, tag FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE tag = ' . $freo->pdo->quote($entry_tagmanager['old']) . ' OR tag LIKE ' . $freo->pdo->quote($entry_tagmanager['old'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $entry_tagmanager['old']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $entry_tagmanager['old'] . ',%'));
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$entries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$entries[$data['id']] = $data;
	}

	//データ登録
	$freo->pdo->beginTransaction();

	foreach ($entries as $entry) {
		$new_tags = array();
		foreach (explode(',', $entry['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if ($tag == $entry_tagmanager['old']) {
				foreach (explode(',', $entry_tagmanager['new']) as $new_tag) {
					$new_tags[$new_tag] = $new_tag;
				}
			} else {
				$new_tags[$tag] = $tag;
			}
		}

		$new_tag = implode(',', $new_tags);

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET modified = :now, tag = :tag WHERE id = :id');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$stmt->bindValue(':tag', $new_tag);
		$stmt->bindValue(':id',  $entry['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	$freo->pdo->commit();

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('エントリータグを編集しました。');

	//エントリータグ管理へ移動
	freo_redirect('entry_tagmanager/admin?exec=update&tag=' . $entry_tagmanager['old']);

	return;
}

/* 管理画面 | エントリータグ削除 */
function freo_page_entry_tagmanager_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['tag'])) {
		freo_redirect('entry_tagmanager/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('entry_tagmanager/admin?error=1');
	}

	//編集エントリー取得
	$stmt = $freo->pdo->query('SELECT id, tag FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%'));
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$entries = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$entries[$data['id']] = $data;
	}

	//データ登録
	$freo->pdo->beginTransaction();

	foreach ($entries as $entry) {
		$new_tags = array();
		foreach (explode(',', $entry['tag']) as $tag) {
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

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET modified = :now, tag = :tag WHERE id = :id');
		$stmt->bindValue(':now', date('Y-m-d H:i:s'));
		$stmt->bindValue(':tag', $new_tag);
		$stmt->bindValue(':id',  $entry['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	$freo->pdo->commit();

	//ログ記録
	freo_log('エントリータグを削除しました。');

	//エントリータグ管理へ移動
	freo_redirect('entry_tagmanager/admin?exec=delete&tag=' . $_GET['tag']);

	return;
}

/* エントリータグ管理 */
function freo_page_entry_tagmanager_default()
{
	global $freo;

	freo_redirect('entry_tagmanager/admin');

	return;
}

?>
