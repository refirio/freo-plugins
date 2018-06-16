<?php

/*********************************************************************

 ページイメージ分類別表示プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_page_page_image_categorized()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[1])) {
		$parameters = array();
		$i          = 0;
		while (isset($freo->parameters[++$i])) {
			if (!$freo->parameters[$i]) {
				continue;
			}

			$parameters[] = $freo->parameters[$i];
		}
		$_GET['id'] = implode('/', $parameters);
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//検索条件設定
	$condition = null;
	if (!$freo->config['plugin']['page_image_categorized']['display']) {
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

	if (empty($_GET['view'])) {
		//ページ取得
		if ($_GET['id']) {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY sort, id');
			$stmt->bindValue(':id',   $_GET['id']);
			$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		} else {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid IS NULL AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY sort, id');
			$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		}
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$categories = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$categories[$data['id']] = $data;
		}

		//表示順設定
		if ($freo->config['plugin']['page_image_categorized']['order'] == 'datetime_desc') {
			$order = 'ORDER BY datetime DESC';
		} elseif ($freo->config['plugin']['page_image_categorized']['order'] == 'datetime') {
			$order = 'ORDER BY datetime';
		} else {
			$order = 'ORDER BY sort, id';
		}

		//ページID取得
		$category_keys = array_keys($categories);

		$pages           = array();
		$page_keys       = array();
		$page_associates = array();
		$page_filters    = array();
		$page_securities = array();
		$page_tags       = array();
		$page_files      = array();
		$page_thumbnails = array();
		$page_images     = array();
		$page_texts      = array();
		foreach ($category_keys as $category) {
			//ページ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :category AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND image IS NOT NULL ' . $condition . ' ' . $order . ' LIMIT :limit');
			$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':category', $category);
			$stmt->bindValue(':limit',    intval($freo->config['plugin']['page_image_categorized']['default_limit']), PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$pages[$category][$data['id']] = $data;
			}

			if (empty($pages[$category])) {
				continue;
			}

			//ページID取得
			$page_keys[$category] = array_keys($pages[$category]);

			//ページ関連データ取得
			$page_associates[$category] = freo_associate_page('get', $page_keys[$category]);

			//ページフィルター取得
			$page_filters[$category] = freo_filter_page('user', $page_keys[$category]);

			foreach ($page_filters[$category] as $id => $filter) {
				if (!$filter) {
					continue;
				}

				$pages[$category][$id]['comment']   = 'closed';
				$pages[$category][$id]['trackback'] = 'closed';
				$pages[$category][$id]['title']     = str_replace('[$title]', $pages[$category][$id]['title'], $freo->config['page']['filter_title']);
				$pages[$category][$id]['file']      = null;
				$pages[$category][$id]['image']     = null;
				$pages[$category][$id]['memo']      = null;
				$pages[$category][$id]['text']      = str_replace('[$text]', $pages[$category][$id]['text'], $freo->config['page']['filter_text']);

				if ($freo->config['page']['filter_option']) {
					$page_associates[$category][$id]['option'] = array();
				}
			}

			//ページ保護データ取得
			$page_securities[$category] = freo_security_page('user', $page_keys[$category]);

			foreach ($page_securities[$category] as $id => $security) {
				if (!$security) {
					continue;
				}

				$pages[$category][$id]['comment']   = 'closed';
				$pages[$category][$id]['trackback'] = 'closed';
				$pages[$category][$id]['title']     = str_replace('[$title]', $pages[$category][$id]['title'], $freo->config['page']['restriction_title']);
				$pages[$category][$id]['file']      = null;
				$pages[$category][$id]['image']     = null;
				$pages[$category][$id]['memo']      = null;
				$pages[$category][$id]['text']      = str_replace('[$text]', $pages[$category][$id]['text'], $freo->config['page']['restriction_text']);

				if ($freo->config['page']['restriction_option']) {
					$page_associates[$category][$id]['option'] = array();
				}
			}

			//ページタグ取得
			foreach ($page_keys[$category] as $page) {
				if (!$pages[$category][$page]['tag']) {
					continue;
				}

				$page_tags[$category][$page] = explode(',', $pages[$category][$page]['tag']);
			}

			//ページファイル取得
			foreach ($page_keys[$category] as $page) {
				if (!$pages[$category][$page]['file']) {
					continue;
				}

				list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $page . '/' . $pages[$category][$page]['file']);

				$page_files[$category][$page] = array(
					'width'  => $width,
					'height' => $height,
					'size'   => $size
				);
			}

			//ページサムネイル取得
			foreach ($page_keys[$category] as $page) {
				if (!$pages[$category][$page]['file']) {
					continue;
				}
				if (!file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$category][$page]['file'])) {
					continue;
				}

				list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $page . '/' . $pages[$category][$page]['file']);

				$page_thumbnails[$category][$page] = array(
					'width'  => $width,
					'height' => $height,
					'size'   => $size
				);
			}

			//ページイメージ取得
			foreach ($page_keys[$category] as $page) {
				if (!$pages[$category][$page]['image']) {
					continue;
				}

				list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $page . '/' . $pages[$category][$page]['image']);

				$page_images[$category][$page] = array(
					'width'  => $width,
					'height' => $height,
					'size'   => $size
				);
			}

			//ページテキスト取得
			foreach ($page_keys[$category] as $page) {
				if (!$pages[$category][$page]['text']) {
					continue;
				}

				if (isset($page_associates[$category][$page]['option'])) {
					list($pages[$category][$page]['text'], $page_associates[$category][$page]['option']) = freo_option($pages[$category][$page]['text'], $page_associates[$category][$page]['option'], FREO_FILE_DIR . 'page_options/' . $page . '/');
				}
				list($excerpt, $more) = freo_divide($pages[$category][$page]['text']);

				$page_texts[$category][$page] = array(
					'excerpt' => $excerpt,
					'more'    => $more
				);
			}
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'           => freo_token('create'),
			'categories'      => $categories,
			'pages'           => $pages,
			'page_associates' => $page_associates,
			'page_filters'    => $page_filters,
			'page_securities' => $page_securities,
			'page_tags'       => $page_tags,
			'page_files'      => $page_files,
			'page_thumbnails' => $page_thumbnails,
			'page_images'     => $page_images,
			'page_texts'      => $page_texts
		));
	} else {
		//ページ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) ' . $condition . ' AND (close IS NULL OR close >= :now2)');
		$stmt->bindValue(':id',   $_GET['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$category = $data;
		} else {
			freo_error('表示したいページを指定してください。', '404 Not Found');
		}

		//表示順設定
		if ($freo->config['plugin']['page_image_categorized']['order'] == 'datetime_desc') {
			$order = 'ORDER BY datetime DESC';
		} elseif ($freo->config['plugin']['page_image_categorized']['order'] == 'datetime') {
			$order = 'ORDER BY datetime';
		} else {
			$order = 'ORDER BY sort, id';
		}

		//ページ取得
		$pages           = array();
		$page_keys       = array();
		$page_associates = array();
		$page_filters    = array();
		$page_securities = array();
		$page_tags       = array();
		$page_files      = array();
		$page_thumbnails = array();
		$page_images     = array();
		$page_texts      = array();

		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE pid = :category AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND image IS NOT NULL ' . $condition . ' ' . $order);
		$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
		$stmt->bindValue(':category', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

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
		foreach ($page_keys as $page) {
			if (!$pages[$page]['tag']) {
				continue;
			}

			$page_tags[$page] = explode(',', $pages[$page]['tag']);
		}

		//ページファイル取得
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

		//データ割当
		$freo->smarty->assign(array(
			'token'           => freo_token('create'),
			'category'        => $category,
			'pages'           => $pages,
			'page_associates' => $page_associates,
			'page_filters'    => $page_filters,
			'page_securities' => $page_securities,
			'page_tags'       => $page_tags,
			'page_files'      => $page_files,
			'page_thumbnails' => $page_thumbnails,
			'page_images'     => $page_images,
			'page_texts'      => $page_texts
		));
	}

	return;
}

?>
