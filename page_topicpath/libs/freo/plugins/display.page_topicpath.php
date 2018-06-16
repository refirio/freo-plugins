<?php

/*********************************************************************

 パンくずリスト表示プラグイン (2010/10/30)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_page_topicpath()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('page')) {
		return;
	}

	//表示ページ取得
	$page = $freo->smarty->get_template_vars('page');

	$topicpath = array();

	while ($page['pid']) {
		//ページ取得
		$stmt = $freo->pdo->prepare('SELECT id, pid, title FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2)');
		$stmt->bindValue(':id',   $page['pid']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$page = $data;
		} else {
			return;
		}

		//ページフィルター取得
		$page_filters = freo_filter_page('user', array($page['id']));
		$page_filter  = $page_filters[$page['id']];

		if ($page_filter) {
			$page['title'] = str_replace('[$title]', $page['title'], $freo->config['page']['filter_title']);
		}

		//ページ保護データ取得
		$page_securities = freo_security_page('user', array($page['id']));
		$page_security   = $page_securities[$page['id']];

		if ($page_security) {
			$page['title'] = str_replace('[$title]', $page['title'], $freo->config['page']['restriction_title']);
		}

		array_unshift($topicpath, $page);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_topicpaths' => $topicpath
	));

	return;
}

?>
