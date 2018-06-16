<?php

/*********************************************************************

 ページ親ID使用制限プラグイン (2012/09/21)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_page_pid_limit()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] == 'root') {
		return;
	}

	//ユーザーID取得
	$users = array();
	if (!$freo->config['plugin']['page_pid_limit']['root_limit']) {
		$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'users WHERE authority = \'root\'');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$users[] = $data['id'];
		}
	}
	$users[] = $freo->user['id'];

	//ページID取得
	$users = array_map(array($freo->pdo, 'quote'), $users);

	$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE user_id IN(' . implode(',', $users) . ')');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[] = $data['id'];
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_POST['page']['pid']) and $_POST['page']['pid'] != '' and array_search($_POST['page']['pid'], $pages) === false) {
			$freo->smarty->append('errors', '他のユーザーが作成したページのIDは使用できません。');
		}
	} else {
		if (isset($_GET['pid']) and $_GET['pid'] != '' and array_search($_GET['pid'], $pages) === false) {
			freo_error('他のユーザーが作成したページのIDは使用できません。');
		}
	}

	return;
}

?>
