<?php

/*********************************************************************

 エントリー書き出しプラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_end_entry_output()
{
	global $freo;

	//データ割当
	$freo->smarty->assign('freo', array(
		'core'   => isset($freo->core)   ? $freo->core   : array(),
		'agent'  => isset($freo->agent)  ? $freo->agent  : array(),
		'user'   => isset($freo->user)   ? $freo->user   : array(),
		'refer'  => isset($freo->refer)  ? $freo->refer  : array(),
		'config' => isset($freo->config) ? $freo->config : array()
	));

	//書き出し先取得
	$output_files = explode("\n", $freo->config['plugin']['entry_output']['files']);

	foreach ($output_files as $output_file) {
		$output_info = explode(',', $output_file, 2);
		if (isset($output_info[0])) {
			$output_file = $output_info[0];
		} else {
			continue;
		}
		if (isset($output_info[1])) {
			parse_str($output_info[1], $output_queries);
		} else {
			$output_queries = null;
		}

		//検索条件設定
		$condition = null;
		if (isset($output_queries['word'])) {
			$words = explode(' ', str_replace('　', ' ', $output_queries['word']));

			foreach ($words as $word) {
				$condition .= ' AND (title LIKE ' . $freo->pdo->quote('%' . $word . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $word . '%') . ')';
			}
		}
		if (isset($output_queries['user'])) {
			$condition .= ' AND user_id = ' . $freo->pdo->quote($output_queries['user']);
		}
		if (isset($output_queries['tag'])) {
			$condition .= ' AND (tag = ' . $freo->pdo->quote($output_queries['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($output_queries['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $output_queries['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $output_queries['tag'] . ',%') . ')';
		}
		if (isset($output_queries['date'])) {
			if (preg_match('/^\d\d\d\d$/', $output_queries['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y\') = ' . $freo->pdo->quote($output_queries['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y\', datetime) = ' . $freo->pdo->quote($output_queries['date']);
				}
			} elseif (preg_match('/^\d\d\d\d\d\d$/', $output_queries['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m\') = ' . $freo->pdo->quote($output_queries['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y%m\', datetime) = ' . $freo->pdo->quote($output_queries['date']);
				}
			} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $output_queries['date'])) {
				if (FREO_DATABASE_TYPE == 'mysql') {
					$condition .= ' AND DATE_FORMAT(datetime, \'%Y%m%d\') = ' . $freo->pdo->quote($output_queries['date']);
				} else {
					$condition .= ' AND STRFTIME(\'%Y%m%d\', datetime) = ' . $freo->pdo->quote($output_queries['date']);
				}
			}
		}

		//制限されたエントリーを一覧に表示しない
		if (!$freo->config['view']['restricted_display']) {
			$entry_filters = freo_filter_entry('nobody', array_keys($freo->refer['entries']));
			$entry_filters = array_keys($entry_filters, true);
			$entry_filters = array_map('intval', $entry_filters);
			if (!empty($entry_filters)) {
				$condition .= ' AND id NOT IN(' . implode(',', $entry_filters) . ')';
			}

			$entry_securities = freo_security_entry('nobody', array_keys($freo->refer['entries']), array('password'));
			$entry_securities = array_keys($entry_securities, true);
			$entry_securities = array_map('intval', $entry_securities);
			if (!empty($entry_securities)) {
				$condition .= ' AND id NOT IN(' . implode(',', $entry_securities) . ')';
			}
		}

		//エントリー取得
		if (isset($output_queries['category'])) {
			$stmt = $freo->pdo->prepare('SELECT id, user_id, created, modified, approved, restriction, password, status, display, comment, trackback, code, title, tag, datetime, close, file, image, memo, text, category_id, entry_id FROM ' . FREO_DATABASE_PREFIX . 'entries LEFT JOIN ' . FREO_DATABASE_PREFIX . 'category_sets ON id = entry_id WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) AND category_id = :category ' . $condition . ' ORDER BY datetime DESC LIMIT :limit');
			$stmt->bindValue(':now1',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',     date('Y-m-d H:i:s'));
			$stmt->bindValue(':category', $output_queries['category']);
			$stmt->bindValue(':limit',    intval($freo->config['plugin']['entry_output']['count']), PDO::PARAM_INT);
		} else {
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' ORDER BY datetime DESC LIMIT :limit');
			$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
			$stmt->bindValue(':limit', intval($freo->config['plugin']['entry_output']['count']), PDO::PARAM_INT);
		}
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$entries = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$entries[$data['id']] = $data;
		}

		//エントリーID取得
		$entry_keys = array_keys($entries);

		//エントリー関連データ取得
		$entry_associates = freo_associate_entry('get', $entry_keys);

		//エントリーフィルター取得
		$entry_filters = freo_filter_entry('nobody', $entry_keys);

		foreach ($entry_filters as $id => $filter) {
			if (!$filter) {
				continue;
			}

			$entries[$id]['comment']   = 'closed';
			$entries[$id]['trackback'] = 'closed';
			$entries[$id]['title']     = str_replace('[$title]', $entries[$id]['title'], $freo->config['entry']['filter_title']);
			$entries[$id]['file']      = null;
			$entries[$id]['image']     = null;
			$entries[$id]['memo']      = null;
			$entries[$id]['text']      = str_replace('[$text]', $entries[$id]['text'], $freo->config['entry']['filter_text']);

			if ($freo->config['entry']['filter_option']) {
				$entry_associates[$id]['option'] = array();
			}
		}

		//エントリー保護データ取得
		$entry_securities = freo_security_entry('nobody', $entry_keys);

		foreach ($entry_securities as $id => $security) {
			if (!$security) {
				continue;
			}

			$entries[$id]['comment']   = 'closed';
			$entries[$id]['trackback'] = 'closed';
			$entries[$id]['title']     = str_replace('[$title]', $entries[$id]['title'], $freo->config['entry']['restriction_title']);
			$entries[$id]['file']      = null;
			$entries[$id]['image']     = null;
			$entries[$id]['memo']      = null;
			$entries[$id]['text']      = str_replace('[$text]', $entries[$id]['text'], $freo->config['entry']['restriction_text']);

			if ($freo->config['entry']['restriction_option']) {
				$entry_associates[$id]['option'] = array();
			}
		}

		//エントリータグ取得
		$entry_tags = array();
		foreach ($entry_keys as $entry) {
			if (!$entries[$entry]['tag']) {
				continue;
			}

			$entry_tags[$entry] = explode(',', $entries[$entry]['tag']);
		}

		//エントリーファイル取得
		$entry_files = array();
		foreach ($entry_keys as $entry) {
			if (!$entries[$entry]['file']) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_files/' . $entry . '/' . $entries[$entry]['file']);

			$entry_files[$entry] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//エントリーサムネイル取得
		$entry_thumbnails = array();
		foreach ($entry_keys as $entry) {
			if (!$entries[$entry]['file']) {
				continue;
			}
			if (!file_exists(FREO_FILE_DIR . 'entry_thumbnails/' . $entry . '/' . $entries[$entry]['file'])) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_thumbnails/' . $entry . '/' . $entries[$entry]['file']);

			$entry_thumbnails[$entry] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//エントリーイメージ取得
		$entry_images = array();
		foreach ($entry_keys as $entry) {
			if (!$entries[$entry]['image']) {
				continue;
			}

			list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'entry_images/' . $entry . '/' . $entries[$entry]['image']);

			$entry_images[$entry] = array(
				'width'  => $width,
				'height' => $height,
				'size'   => $size
			);
		}

		//エントリーテキスト取得
		$entry_texts = array();
		foreach ($entry_keys as $entry) {
			if (!$entries[$entry]['text']) {
				continue;
			}

			if (isset($entry_associates[$entry]['option'])) {
				list($entries[$entry]['text'], $entry_associates[$entry]['option']) = freo_option($entries[$entry]['text'], $entry_associates[$entry]['option'], FREO_FILE_DIR . 'entry_options/' . $entry . '/');
			}
			list($excerpt, $more) = freo_divide($entries[$entry]['text']);

			$entry_texts[$entry] = array(
				'excerpt' => $excerpt,
				'more'    => $more
			);
		}

		//データ割当
		$freo->smarty->assign(array(
			'entries'          => $entries,
			'entry_associates' => $entry_associates,
			'entry_securities' => $entry_securities,
			'entry_tags'       => $entry_tags,
			'entry_files'      => $entry_files,
			'entry_thumbnails' => $entry_thumbnails,
			'entry_images'     => $entry_images,
			'entry_texts'      => $entry_texts
		));

		//エントリー書き出し
		$data = $freo->smarty->fetch('plugins/entry_output/default.html');
		$data = freo_cleanup($data);

		if (file_put_contents($output_file, $data) === false) {
			freo_error('エントリー書き出しファイル ' . $output_file . ' に書き込めません。');
		}
	}

	return;
}

?>
