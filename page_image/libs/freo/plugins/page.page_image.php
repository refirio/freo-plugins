<?php

/*********************************************************************

 ページイメージ表示プラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_page_page_image()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$words = explode(' ', str_replace('　', ' ', $_GET['word']));

		foreach ($words as $word) {
			$condition .= ' AND (title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
		}
	}
	if (isset($_GET['user'])) {
		$condition .= ' AND user_id = ' . $freo->pdo->quote($_GET['user']);
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND (tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%') . ')';
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if (!$freo->config['plugin']['page_image']['display'] and $condition == null) {
		$condition .= ' AND display = \'publish\'';
	}

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

	//表示件数設定
	if ($freo->config['plugin']['page_image']['limit']) {
		$limit = 'LIMIT ' . (intval($freo->config['plugin']['page_image']['default_limit']) * ($_GET['page'] - 1)) . ', ' . intval($freo->config['plugin']['page_image']['default_limit']);
	} else {
		$limit = null;
	}

	//表示順設定
	if ($freo->config['plugin']['page_image']['order'] == 'datetime_desc') {
		$order = 'ORDER BY datetime DESC';
	} else {
		$order = 'ORDER BY datetime';
	}

	//ページ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND (close IS NULL OR close >= :now2) AND image IS NOT NULL ' . $condition . ' ' . $order . ' ' . $limit);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$pages = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$pages[$data['id']] = $data;
	}

	//ページID取得
	$page_keys = array_keys($pages);

	//ページ関連データ取得
	$page_associates = freo_associate_page('get', $page_keys);

	//ページフィルター取得
	$page_filters = freo_filter_page('user', $page_keys);

	foreach ($page_filters as $id => $filter) {
		if (!$filter) {
			continue;
		}

		$pages[$id]['comment']   = 'closed';
		$pages[$id]['trackback'] = 'closed';
		$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['filter_title']);
		$pages[$id]['file']      = null;
		$pages[$id]['image']     = null;
		$pages[$id]['memo']      = null;
		$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['filter_text']);

		if ($freo->config['page']['filter_option']) {
			$page_associates[$id]['option'] = array();
		}
	}

	//ページ保護データ取得
	$page_securities = freo_security_page('user', $page_keys);

	foreach ($page_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$pages[$id]['comment']   = 'closed';
		$pages[$id]['trackback'] = 'closed';
		$pages[$id]['title']     = str_replace('[$title]', $pages[$id]['title'], $freo->config['page']['restriction_title']);
		$pages[$id]['file']      = null;
		$pages[$id]['image']     = null;
		$pages[$id]['memo']      = null;
		$pages[$id]['text']      = str_replace('[$text]', $pages[$id]['text'], $freo->config['page']['restriction_text']);

		if ($freo->config['page']['restriction_option']) {
			$page_associates[$id]['option'] = array();
		}
	}

	//ページタグ取得
	$page_tags = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['tag']) {
			continue;
		}

		$page_tags[$page] = explode(',', $pages[$page]['tag']);
	}

	//ページファイル取得
	$page_files = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['file']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page . '/' . $pages[$page]['file']);

		$page_files[$page] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//ページサムネイル取得
	$page_thumbnails = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['file']) {
			continue;
		}
		if (!file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file'])) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$page]['file']);

		$page_thumbnails[$page] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//ページイメージ取得
	$page_images = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['image']) {
			continue;
		}

		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page . '/' . $pages[$page]['image']);

		$page_images[$page] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//ページテキスト取得
	$page_texts = array();
	foreach ($page_keys as $page) {
		if (!$pages[$page]['text']) {
			continue;
		}

		if (isset($page_associates[$page]['option'])) {
			list($pages[$page]['text'], $page_associates[$page]['option']) = freo_option($pages[$page]['text'], $page_associates[$page]['option'], FREO_FILE_DIR . 'page_options/' . $page . '/');
		}
		list($excerpt, $more) = freo_divide($pages[$page]['text']);

		$page_texts[$page] = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	}

	//ページ数取得
	$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND (close IS NULL OR close >= :now2) AND image IS NOT NULL ' . $condition);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$data       = $stmt->fetch(PDO::FETCH_NUM);
	$page_count = $data[0];
	$page_page  = $freo->config['plugin']['page_image']['limit'] ? ceil($page_count / $freo->config['plugin']['page_image']['default_limit']) : 1;

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'pages'           => $pages,
		'page_associates' => $page_associates,
		'page_filters'    => $page_filters,
		'page_securities' => $page_securities,
		'page_tags'       => $page_tags,
		'page_files'      => $page_files,
		'page_thumbnails' => $page_thumbnails,
		'page_images'     => $page_images,
		'page_texts'      => $page_texts,
		'page_count'      => $page_count,
		'page_page'       => $page_page
	));

	return;
}

?>
