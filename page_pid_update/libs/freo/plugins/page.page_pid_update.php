<?php

/*********************************************************************

 ページ親ID一括変更プラグイン (2012/08/08)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_page_pid_update()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_page_pid_update_admin();
			break;
		case 'admin_post':
			freo_page_page_pid_update_admin_post();
			break;
		default:
			freo_page_page_pid_update_default();
	}

	return;
}

/* 管理画面 | ページ親ID一括変更 */
function freo_page_page_pid_update_admin()
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

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//親ID
			if ($_POST['plugin_page_pid_update']['new'] == '' or $_POST['plugin_page_pid_update']['old'] == '') {
				$freo->smarty->append('errors', '親IDが入力されていません');
			} elseif (!preg_match('/^[\w\-\/]+$/', $_POST['plugin_page_pid_update']['new'])) {
				$freo->smarty->append('errors', '親IDは半角英数字で入力してください。');
			} elseif (preg_match('/^\d+$/', $_POST['plugin_page_pid_update']['new'])) {
				$freo->smarty->append('errors', '親IDには半角英字を含んでください。');
			} elseif (mb_strlen($_POST['plugin_page_pid_update']['new'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '親IDは80文字以内で入力してください。');
			} elseif ($_POST['plugin_page_pid_update']['new'] == $_POST['plugin_page_pid_update']['old']) {
				$freo->smarty->append('errors', '親IDが変更されていません');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_page_pid_update = $_POST['plugin_page_pid_update'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('page_pid_update/admin_post?freo%5Btoken%5D=' . freo_token('create'));
		}
	}

	//ページID取得
	$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$page_ids = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$page_ids[] = $data;
	}

	//ページ親ID取得
	$stmt = $freo->pdo->query('SELECT pid FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid IS NOT NULL GROUP BY pid ORDER BY pid');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$page_pids = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$page_pids[] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'     => freo_token('create'),
		'page_ids'  => $page_ids,
		'page_pids' => $page_pids
	));

	return;
}

/* 管理画面 | ページ親ID登録 */
function freo_page_page_pid_update_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('page_pid_update/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('page_pid_update/admin?error=1');
	}

	//入力データ取得
	$page_pid_update = $_SESSION['input']['plugin_page_pid_update'];

	if ($page_pid_update['old'] == '') {
		freo_redirect('page_pid_update/admin?error=1');
	}
	if ($page_pid_update['new'] == '') {
		freo_redirect('page_pid_update/admin?error=1');
	}

	//編集ページ取得
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET modified = :now, pid = :new WHERE pid = :old');
	$stmt->bindValue(':now', date('Y-m-d H:i:s'));
	$stmt->bindValue(':new', $page_pid_update['new']);
	$stmt->bindValue(':old', $page_pid_update['old']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('親IDを一括編集しました。');

	//ページ親ID一括変更へ移動
	freo_redirect('page_pid_update/admin?exec=update');

	return;
}

/* ページ親ID一括変更 */
function freo_page_page_pid_update_default()
{
	global $freo;

	freo_redirect('page_pid_update/admin');

	return;
}

?>
