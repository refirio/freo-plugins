<?php

/*********************************************************************

 ページタグ管理プラグイン (2019/01/29)

 Copyright(C) 2009-2019 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_end_page_tagmanager()
{
	global $freo;

	//検索条件設定
	$condition = null;

	//制限されたページを一覧に表示しない
	if (!$freo->config['view']['restricted_display']) {
		$page_filters = freo_filter_page('nobody', array_keys($freo->refer['pages']));
		$page_filters = array_keys($page_filters, true);
		$page_filters = array_map(array($freo->pdo, 'quote'), $page_filters);
		if (!empty($page_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_filters) . ')';
		}

		$page_securities = freo_security_page('nobody', array_keys($freo->refer['pages']));
		$page_securities = array_keys($page_securities, true);
		$page_securities = array_map(array($freo->pdo, 'quote'), $page_securities);
		if (!empty($page_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $page_securities) . ')';
		}
	}

	//ページタグ取得
	$stmt = $freo->pdo->prepare('SELECT tag FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND tag IS NOT NULL AND (close IS NULL OR close >= :now2) ' . $condition);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$page_tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($page_tags[$tag])) {
				$page_tags[$tag]++;
			} else {
				$page_tags[$tag] = 1;
			}
		}
	}

	ksort($page_tags, SORT_STRING);

	$data = '';
	foreach ($page_tags as $tag => $count) {
		$data .= "$tag,$count\n";
	}

	//ページタグ情報更新
	if (file_put_contents(FREO_FILE_DIR . 'plugins/page_tagcloud.log', $data) === false) {
		freo_error('ページタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/page_tagcloud.log に書き込めません。');
	}

	return;
}

?>
