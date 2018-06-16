<?php

/*********************************************************************

 検索プラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/associate_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_page.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_page.php';

/* メイン処理 */
function freo_page_search()
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
			$condition .= ' AND (title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
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

	//制限されたエントリーを一覧に表示しない
	if (!$freo->config['view']['restricted_display'] and ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author')) {
		$entry_filters = freo_filter_entry('user', array_keys($freo->refer['entries']));
		$entry_filters = array_keys($entry_filters, true);
		$entry_filters = array_map('intval', $entry_filters);
		if (!empty($entry_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_filters) . ')';
		}

		$entry_securities = freo_security_entry('user', array_keys($freo->refer['entries']), array('password'));
		$entry_securities = array_keys($entry_securities, true);
		$entry_securities = array_map('intval', $entry_securities);
		if (!empty($entry_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_securities) . ')';
		}
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

	$page_condition = null;

	if (isset($_GET['page_name'])) {
		if (isset($_GET['page_id'])) {
			$page_condition .= ' AND id LIKE ' . $freo->pdo->quote($_GET['page_id']);
		}
		if (isset($_GET['page_pid'])) {
			$page_condition .= ' AND pid LIKE ' . $freo->pdo->quote($_GET['page_pid']);
		}
		if ($page_condition) {
			$_GET['target'] = 'page';
		}
	} else {
		$_GET['page_id']  = null;
		$_GET['page_pid'] = null;
	}

	//オプション項目検索
	if (isset($_GET['option'])) {
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'options ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$types     = array();
		$validates = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$types[$data['id']]     = $data['type'];
			$validates[$data['id']] = $data['validate'];
		}

		$options = array();
		$count   = 0;
		foreach ($_GET['option'] as $key => $value) {
			if ($value == '') {
				continue;
			} elseif (isset($value['from']) and isset($value['to']) and $value['from'] == '' and $value['to'] == '') {
				continue;
			} else {
				$count++;
			}

			if ($types[$key] == 'select' or $types[$key] == 'radio' or $types[$key] == 'checkbox') {
				$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE option_id = ' . $freo->pdo->quote($key));
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$flag = false;
					foreach (explode("\n", $data['text']) as $text) {
						foreach ($value as $option) {
							if ($text == $option) {
								$flag = true;
							}
						}
					}
					if ($flag) {
						if ($data['entry_id']) {
							$options[] = $data['entry_id'];
						} elseif ($data['page_id']) {
							$options[] = $data['page_id'];
						}
					}
				}
			} elseif ($types[$key] == 'text' and $validates[$key] == 'numeric') {
				if ($value['from'] != '') {
					$value['from'] = intval(mb_convert_kana($value['from'], 'n'));
				}
				if ($value['to'] != '') {
					$value['to'] = intval(mb_convert_kana($value['to'], 'n'));
				}

				$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE option_id = ' . $freo->pdo->quote($key));
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$flag = false;
					if ($value['from'] != '' and $value['to'] != '') {
						if ($value['from'] <= $data['text'] and $value['to'] >= $data['text']) {
							$flag = true;
						}
					} elseif ($value['from'] != '') {
						if ($value['from'] <= $data['text']) {
							$flag = true;
						}
					} elseif ($value['to'] != '') {
						if ($value['to'] >= $data['text']) {
							$flag = true;
						}
					}
					if ($flag) {
						if ($data['entry_id']) {
							$options[] = $data['entry_id'];
						} elseif ($data['page_id']) {
							$options[] = $data['page_id'];
						}
					}
				}
			} else {
				if ($types[$key] == 'text' or $types[$key] == 'textarea') {
					$value = str_replace('_', '\\_', $value);
					$value = str_replace('%', '\\%', $value);
					$value = '%' . $value . '%';
				}

				$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'option_sets WHERE option_id = ' . $freo->pdo->quote($key) . ' AND text LIKE ' . $freo->pdo->quote($value));
				if (!$stmt) {
					freo_error($freo->pdo->errorInfo());
				}

				while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					if ($data['entry_id']) {
						$options[] = $data['entry_id'];
					} elseif ($data['page_id']) {
						$options[] = $data['page_id'];
					}
				}
			}
		}

		if ($count) {
			$counts  = array_count_values($options);
			$buffers = array();
			foreach ($counts as $key => $value) {
				if ($value >= $count) {
					$buffers[] = 'id = ' . $freo->pdo->quote($key);
				}
			}

			if (empty($buffers)) {
				$condition .= ' AND (1 = 0)';
			} else {
				$condition .= ' AND (' . implode(' OR ', $buffers) . ')';
			}
		}
	}

	//表示順設定
	if ($freo->config['plugin']['search']['order'] == 'datetime_desc') {
		$order = ' ORDER BY datetime DESC';
	} else {
		$order = ' ORDER BY datetime';
	}

	//表示件数設定
	if ($freo->config['plugin']['search']['limit']) {
		$limit = ' LIMIT ' . (intval($freo->config['plugin']['search']['limit']) * ($_GET['page'] - 1)) . ', ' . intval($freo->config['plugin']['search']['limit']);
	} else {
		$limit = null;
	}

	//検索条件設定
	$selects = array();
	if (empty($_GET['target']) or $_GET['target'] == 'entry') {
		$selects[] = 'SELECT id, NULL AS pid, user_id, created, modified, approved, restriction, password, status, display, comment, trackback, NULL, code, title, tag, datetime, close, file, image, memo, text, \'entry\' AS type FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= \'' . date('Y-m-d H:i:s') . '\')) AND (close IS NULL OR close >= \'' . date('Y-m-d H:i:s') . '\') ' . $condition;
	}
	if (empty($_GET['target']) or $_GET['target'] == 'page') {
		$selects[] = 'SELECT id, pid, user_id, created, modified, approved, restriction, password, status, display, comment, trackback, sort, NULL AS code, title, tag, datetime, close, file, image, memo, text, \'page\' AS type FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= \'' . date('Y-m-d H:i:s') . '\')) AND (close IS NULL OR close >= \'' . date('Y-m-d H:i:s') . '\') ' . $condition . $page_condition;
	}
	$select = implode(' UNION ALL ', $selects);

	//記事取得
	$stmt = $freo->pdo->prepare($select . $order . $limit);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$articles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$articles[$data['id']] = $data;
	}

	//記事数・ページ数取得
	$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM(' . $select . ') AS uni');
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$data          = $stmt->fetch(PDO::FETCH_NUM);
	$article_count = $data[0];
	$article_page  = $freo->config['plugin']['search']['limit'] ? ceil($article_count / $freo->config['plugin']['search']['limit']) : 1;

	//ID取得
	$entry_keys = array();
	$page_keys  = array();
	foreach ($articles as $article) {
		if (is_numeric($article['id'])) {
			$entry_keys[] = $article['id'];
		} else {
			$page_keys[] = $article['id'];
		}
	}

	$article_keys = $entry_keys + $page_keys;

	//関連データ取得
	$entry_associates = freo_associate_entry('get', $entry_keys);
	$page_associates  = freo_associate_page('get', $page_keys);

	$article_associates = $entry_associates + $page_associates;

	//フィルター取得
	$entry_filters = freo_filter_entry('user', $entry_keys);
	$page_filters  = freo_filter_page('user', $page_keys);

	$article_filters = $entry_filters + $page_filters;

	foreach ($article_filters as $id => $filter) {
		if (!$filter) {
			continue;
		}

		$articles[$id]['comment']   = 'closed';
		$articles[$id]['trackback'] = 'closed';
		$articles[$id]['file']      = null;
		$articles[$id]['image']     = null;
		$articles[$id]['memo']      = null;

		if (is_numeric($id)) {
			$articles[$id]['title'] = str_replace('[$title]', $articles[$id]['title'], $freo->config['entry']['filter_title']);
			$articles[$id]['text']  = str_replace('[$text]', $articles[$id]['text'], $freo->config['entry']['filter_text']);
		} else {
			$articles[$id]['title'] = str_replace('[$title]', $articles[$id]['title'], $freo->config['page']['filter_title']);
			$articles[$id]['text']  = str_replace('[$text]', $articles[$id]['text'], $freo->config['page']['filter_text']);
		}

		if (is_numeric($id)) {
			if ($freo->config['entry']['filter_option']) {
				$article_associates[$id]['option'] = array();
			}
		} else {
			if ($freo->config['page']['filter_option']) {
				$article_associates[$id]['option'] = array();
			}
		}
	}

	//保護データ取得
	$entry_securities = freo_security_entry('user', $entry_keys);
	$page_securities  = freo_security_page('user', $page_keys);

	$article_securities = $entry_securities + $page_securities;

	foreach ($article_securities as $id => $security) {
		if (!$security) {
			continue;
		}

		$articles[$id]['comment']   = 'closed';
		$articles[$id]['trackback'] = 'closed';
		$articles[$id]['file']      = null;
		$articles[$id]['image']     = null;
		$articles[$id]['memo']      = null;

		if (is_numeric($id)) {
			$articles[$id]['title'] = str_replace('[$title]', $articles[$id]['title'], $freo->config['entry']['restriction_title']);
			$articles[$id]['text']  = str_replace('[$text]', $articles[$id]['text'], $freo->config['entry']['restriction_text']);
		} else {
			$articles[$id]['title'] = str_replace('[$title]', $articles[$id]['title'], $freo->config['page']['restriction_title']);
			$articles[$id]['text']  = str_replace('[$text]', $articles[$id]['text'], $freo->config['page']['restriction_text']);
		}

		if (is_numeric($id)) {
			if ($freo->config['entry']['restriction_option']) {
				$article_associates[$id]['option'] = array();
			}
		} else {
			if ($freo->config['page']['restriction_option']) {
				$article_associates[$id]['option'] = array();
			}
		}
	}

	//タグ取得
	$article_tags = array();
	foreach ($article_keys as $article) {
		if (!$articles[$article]['tag']) {
			continue;
		}

		$article_tags[$article] = explode(',', $articles[$article]['tag']);
	}

	//ファイル取得
	$article_files = array();
	foreach ($article_keys as $article) {
		if (!$articles[$article]['file']) {
			continue;
		}

		if (is_numeric($article)) {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_files/' . $article . '/' . $articles[$article]['file']);
		} else {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_files/' . $article . '/' . $articles[$article]['file']);
		}

		$article_files[$article] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//エントリーサムネイル取得
	$article_thumbnails = array();
	foreach ($article_keys as $article) {
		if (!$articles[$article]['file']) {
			continue;
		}

		if (is_numeric($article)) {
			if (!file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $article . '/' . $articles[$article]['file'])) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_thumbnails/' . $article . '/' . $articles[$article]['file']);
		} else {
			if (!file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $article . '/' . $articles[$article]['file'])) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_thumbnails/' . $article . '/' . $articles[$article]['file']);
		}

		$article_thumbnails[$article] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//エントリーイメージ取得
	$article_images = array();
	foreach ($article_keys as $article) {
		if (!$articles[$article]['image']) {
			continue;
		}

		if (is_numeric($article)) {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_images/' . $article . '/' . $articles[$article]['image']);
		} else {
			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'page_images/' . $article . '/' . $articles[$article]['image']);
		}

		$article_images[$article] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//エントリーテキスト取得
	$article_texts = array();
	foreach ($article_keys as $article) {
		if (!$articles[$article]['text']) {
			continue;
		}

		if (is_numeric($article)) {
			if (isset($article_associates[$article]['option'])) {
				list($articles[$article]['text'], $article_associates[$article]['option']) = freo_option($articles[$article]['text'], $article_associates[$article]['option'], FREO_FILE_DIR . 'entry_options/' . $article . '/');
			}
			list($excerpt, $more) = freo_divide($articles[$article]['text']);
		} else {
			if (isset($article_associates[$article]['option'])) {
				list($articles[$article]['text'], $article_associates[$article]['option']) = freo_option($articles[$article]['text'], $article_associates[$article]['option'], FREO_FILE_DIR . 'page_options/' . $article . '/');
			}
			list($excerpt, $more) = freo_divide($articles[$article]['text']);
		}

		$article_texts[$article] = array(
			'excerpt' => $excerpt,
			'more'    => $more
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'              => freo_token('create'),
		'articles'           => $articles,
		'article_associates' => $article_associates,
		'article_filters'    => $article_filters,
		'article_securities' => $article_securities,
		'article_tags'       => $article_tags,
		'article_files'      => $article_files,
		'article_thumbnails' => $article_thumbnails,
		'article_images'     => $article_images,
		'article_texts'      => $article_texts,
		'article_count'      => $article_count,
		'article_page'       => $article_page
	));

	return;
}

?>
