<?php

/*********************************************************************

 カウンタプラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_count()
{
	global $freo;

	//今日のカウント取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE DATE_FORMAT(date, \'%Y%m%d\') = :today');
	} else {
		$stmt = $freo->pdo->prepare('SELECT count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE STRFTIME(\'%Y%m%d\', date) = :today');
	}
	$stmt->bindValue(':today', date('Ymd'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_count_today = $data;
	} else {
		$plugin_count_today = array('count' => 0, 'session' => 0);
	}

	//昨日のカウント取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE DATE_FORMAT(date, \'%Y%m%d\') = :yesterday');
	} else {
		$stmt = $freo->pdo->prepare('SELECT count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts WHERE STRFTIME(\'%Y%m%d\', date) = :yesterday');
	}
	$stmt->bindValue(':yesterday', date('Ymd', strtotime(date('Y-m-d H:i:s')) - (60 * 60 * 24)));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_count_yesterday = $data;
	} else {
		$plugin_count_yesterday = array('count' => 0, 'session' => 0);
	}

	//総カウント取得
	$stmt = $freo->pdo->prepare('SELECT SUM(count) AS count, SUM(session) AS session FROM ' . FREO_DATABASE_PREFIX . 'plugin_counts');
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_count_total = $data;
	} else {
		$plugin_count_total = array('count' => 0, 'session' => 0);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_count_today'     => $plugin_count_today,
		'plugin_count_yesterday' => $plugin_count_yesterday,
		'plugin_count_total'     => $plugin_count_total
	));

	return;
}

?>
