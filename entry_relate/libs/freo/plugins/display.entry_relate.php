<?php

/*********************************************************************

 関連エントリー表示プラグイン (2013/01/03)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_display_entry_relate()
{
	global $freo;

	if (!$freo->smarty->get_template_vars('entry')) {
		return;
	}

	$entry = $freo->smarty->get_template_vars('entry');

	//カテゴリー情報取得
	$stmt = $freo->pdo->prepare('SELECT category_id FROM ' . FREO_DATABASE_PREFIX . 'category_sets WHERE entry_id = :id');
	$stmt->bindValue(':id', $entry['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$categories[] = $data[0];
	}

	//検索条件設定
	$conditions = array();
	if ($freo->config['plugin']['entry_relate']['target'] != 'category' and $entry['tag']) {
		$tags = explode(',', $entry['tag']);

		foreach ($tags as $tag) {
			$conditions[] = '(tag = ' . $freo->pdo->quote($tag) . ' OR tag LIKE ' . $freo->pdo->quote($tag . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $tag) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $tag . ',%') . ')';
		}
	}
	if ($freo->config['plugin']['entry_relate']['target'] != 'tag' and !empty($categories)) {
		foreach ($categories as $category) {
			$conditions[] = '(category_id = ' . $freo->pdo->quote($category) . ')';
		}
	}
	if (empty($conditions)) {
		$condition = null;
	} else {
		$condition = ' AND (' . implode(' OR ', $conditions) . ')';
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

	$entries          = array();
	$entry_filters    = array();
	$entry_securities = array();
	if (!empty($conditions)) {
		//エントリー取得
		$stmt = $freo->pdo->prepare('SELECT id, user_id, created, modified, approved, restriction, password, status, display, comment, trackback, code, title, tag, datetime, close, file, image, memo, text, category_id, entry_id FROM ' . FREO_DATABASE_PREFIX . 'entries LEFT JOIN ' . FREO_DATABASE_PREFIX . 'category_sets ON id = entry_id WHERE id <> :id AND approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND (close IS NULL OR close >= :now2) ' . $condition . ' GROUP BY id ORDER BY datetime DESC LIMIT :limit');
		$stmt->bindValue(':id',    $entry['id'], PDO::PARAM_INT);
		$stmt->bindValue(':now1',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':limit', intval($freo->config['plugin']['entry_relate']['default_limit']), PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$entries[$data['id']] = $data;
		}

		//エントリーID取得
		$entry_keys = array_keys($entries);

		//エントリーフィルター取得
		$entry_filters = freo_filter_entry('user', $entry_keys);

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
		$entry_securities = freo_security_entry('user', $entry_keys);

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
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_entry_relates'           => $entries,
		'plugin_entry_relate_filters'    => $entry_filters,
		'plugin_entry_relate_securities' => $entry_securities
	));

	return;
}

?>
