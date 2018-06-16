<?php

/*********************************************************************

 人気コンテンツプラグイン (2011/04/08)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_popularity()
{
	global $freo;

	//ヘッダ出力確認
	if (!headers_sent()) {
		return;
	}

	//ログイン状態確認
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author') {
		return;
	}

	//パラメーター取得
	$parameters = array();
	$i          = 0;
	while (isset($freo->parameters[$i])) {
		if ($freo->parameters[$i] == 'default') {
			break;
		}

		$parameters[] = $freo->parameters[$i];

		$i++;
	}

	$query_string = preg_replace('/&?' . $freo->core['session_name'] . '=' . $freo->core['session_id'] . '/', '', $_SERVER['QUERY_STRING']);
	$query_string = preg_replace('/&?\w+%5B\w+%5D=[^&]*/', '', $query_string);
	$parameter    = '/' . implode('/', $parameters) . ($query_string ? '?' . $query_string : '');

	//カウントアップ
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_popularies SET count = count + 1 WHERE parameter = :parameter');
	$stmt->bindValue(':parameter', $parameter);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (!$stmt->rowCount()) {
		$title = null;

		if ($_REQUEST['freo']['mode'] == 'view') {
			$stmt = $freo->pdo->prepare('SELECT title FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
			$stmt->bindValue(':id',  $_GET['id'], PDO::PARAM_INT);
			$stmt->bindValue(':now', date('Y-m-d H:i:s'));
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$title = $data['title'];
			}
		} elseif ($_REQUEST['freo']['mode'] == 'page') {
			$stmt = $freo->pdo->prepare('SELECT title FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now))');
			$stmt->bindValue(':id',  $_GET['id']);
			$stmt->bindValue(':now', date('Y-m-d H:i:s'));
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$title = $data['title'];
			}
		} elseif ($_REQUEST['freo']['mode'] == 'default' and $_REQUEST['freo']['work'] == 'default') {
			$title = 'トップページ';
		}

		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_popularies VALUES(:parameter, :count, \'publish\', :title)');
		$stmt->bindValue(':parameter', $parameter);
		$stmt->bindValue(':count',     1, PDO::PARAM_INT);
		$stmt->bindValue(':title',     $title);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	return;
}

?>
