<?php

/*********************************************************************

 ページタグクラウド表示プラグイン (2013/01/07)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_display_page_tagcloud()
{
	global $freo;

	$tags = array();

	if ($freo->user['id'] or $freo->user['groups'] or isset($_SESSION['security']['page']) or isset($_SESSION['filter'])) {
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

			$page_securities = freo_security_page('user', array_keys($freo->refer['pages']));
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
	} else {
		//ページタグ情報取得
		if ($fp = fopen(FREO_FILE_DIR . 'plugins/page_tagcloud.log', 'r')) {
			while ($line = fgets($fp)) {
				list($tag, $count) = explode(',', trim($line));

				$tags[$tag] = $count;
			}
			fclose($fp);
		} else {
			freo_error('ページタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/page_tagcloud.log を読み込めません。');
		}
	}

	//最大値と最小値を取得
	$max_count = empty($tags) ? 0 : max(array_values($tags));
	$min_count = empty($tags) ? 0 : min(array_values($tags));

	//値の範囲を取得
	$spread = $max_count - $min_count;
	if ($spread == 0) {
	    $spread = 1;
	}

	//サイズの比率を取得
	$step = ($freo->config['plugin']['page_tagcloud']['max_size'] - $freo->config['plugin']['page_tagcloud']['min_size']) / $spread;

	//タグクラウド取得
	$tagclouds = array();

	foreach ($tags as $tag => $count) {
		$tagclouds[] = array(
			'tag'   => $tag,
			'count' => $count,
			'size'  => intval($freo->config['plugin']['page_tagcloud']['min_size'] + (($count - $min_count) * $step))
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_page_tagclouds' => $tagclouds
	));

	return;
}

?>
