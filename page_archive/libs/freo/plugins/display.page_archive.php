<?php

/*********************************************************************

 ページアーカイブ表示プラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_page_archive()
{
	global $freo;

	//検索条件設定
	$condition = null;

	//制限されたページを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$page_filters = freo_filter_page('user', array_keys($freo->refer['pages']));
		$page_filters = array_keys($page_filters, true);
		$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
		if (!empty($page_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_filters) . ')';
		}

		$page_securities = freo_security_page('user', array_keys($freo->refer['pages']), array('password'));
		$page_securities = array_keys($page_securities, true);
		$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
		if (!empty($page_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_securities) . ')';
		}
	}

	//ページ取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(datetime, \'%Y-%m\') AS month, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY month ORDER BY month DESC');
	} else {
		$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%Y-%m\', datetime) AS month, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY month ORDER BY month DESC');
	}
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$archives = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		if (preg_match('/^(\d\d\d\d)\-(\d\d)$/', $data['month'], $matches)) {
			$archives[] = array(
				'year'  => $matches[1],
				'month' => $matches[2],
				'count' => $data['count']
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_archives' => $archives
	));

	return;
}

?>
