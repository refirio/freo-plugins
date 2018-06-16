<?php

/*********************************************************************

 カウンタプラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_count()
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

	//ページビューカウントアップ
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_counts SET count = count + 1 WHERE date = :date');
	$stmt->bindValue(':date', date('Y-m-d'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if (!$stmt->rowCount()) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_counts VALUES(:date, :count, :session)');
		$stmt->bindValue(':date',    date('Y-m-d'));
		$stmt->bindValue(':count',   1, PDO::PARAM_INT);
		$stmt->bindValue(':session', 0, PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//ユニークアクセスカウントアップ
	if (!empty($_SESSION['plugin']['count']['session'])) {
		return;
	}

	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_counts SET session = session + 1 WHERE date = :date');
	$stmt->bindValue(':date', date('Y-m-d'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//セッション保持
	$_SESSION['plugin']['count']['session'] = true;

	return;
}

?>
