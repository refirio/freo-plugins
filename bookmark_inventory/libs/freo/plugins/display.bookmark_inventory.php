<?php

/*********************************************************************

 ブックマーク棚卸プラグイン | 設定ファイル (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_bookmark_inventory()
{
	global $freo;

	//検索条件設定
	if (FREO_DATABASE_TYPE == 'mysql') {
		$condition = 'WHERE DATE_FORMAT(created, \'%m%d\') = ' . $freo->pdo->quote(date('md')) . ' AND DATE_FORMAT(created, \'%Y%m%d\') <> ' . $freo->pdo->quote(date('Ymd'));
	} else {
		$condition = 'WHERE STRFTIME(\'%m%d\', created) = ' . $freo->pdo->quote(date('md')) . ' AND STRFTIME(\'%Y%m%d\', created) <> ' . $freo->pdo->quote(date('Ymd'));
	}

	//ブックマーク取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['bookmark']['default_limit']) * 0, PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['bookmark']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_bookmarks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_bookmarks[$data['id']] = $data;
	}

	//ブックマークID取得
	$plugin_bookmark_keys = array_keys($plugin_bookmarks);

	//ブックマークタグ取得
	$plugin_bookmark_tags = array();
	foreach ($plugin_bookmark_keys as $plugin_bookmark) {
		if (!$plugin_bookmarks[$plugin_bookmark]['tag']) {
			continue;
		}

		$plugin_bookmark_tags[$plugin_bookmark] = explode(',', $plugin_bookmarks[$plugin_bookmark]['tag']);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_bookmark_inventories'    => $plugin_bookmarks,
		'plugin_bookmark_inventory_tags' => $plugin_bookmark_tags
	));

	return;
}

?>
