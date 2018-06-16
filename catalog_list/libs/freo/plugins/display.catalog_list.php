<?php

/*********************************************************************

 商品一覧表示プラグイン (2013/04/29)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_catalog_list()
{
	global $freo;

	//検索条件設定
	$condition = null;

	if ($freo->config['plugin']['catalog_list']['condition'] == 'prerelease') {
		$condition .= ' AND datetime >= ' . $freo->pdo->quote(date('Y-m-d H:i:s'));
	} elseif ($freo->config['plugin']['catalog_list']['condition'] == 'new') {
		$condition .= ' AND datetime >= ' . $freo->pdo->quote(date('Y-m-d H:i:s', time() - (60 * 60 * 24 * $freo->config['plugin']['catalog']['new_days'])));
	} elseif ($freo->config['plugin']['catalog_list']['condition'] == 'soldout') {
		$condition .= ' AND (stock IS NOT NULL AND stock = 0)';
	} elseif ($freo->config['plugin']['catalog_list']['condition'] == 'end') {
		$condition .= ' AND (close IS NOT NULL AND close < ' . $freo->pdo->quote(date('Y-m-d H:i:s')) . ')';
	}

	if (!$freo->config['plugin']['catalog']['soldout_display']) {
		$condition .= ' AND (stock IS NULL OR stock != 0)';
	}
	if (!$freo->config['plugin']['catalog']['close_display']) {
		$condition .= ' AND (close IS NULL OR close >= ' . $freo->pdo->quote(date('Y-m-d H:i:s')) . ')';
	}

	//対象の初期値
	if (!isset($_SESSION['plugin']['catalog']['target']) and $freo->config['plugin']['catalog']['target_default']) {
		$_SESSION['plugin']['catalog']['target'] = $freo->config['plugin']['catalog']['target_default'];
	}

	//対象確認
	if (isset($_SESSION['plugin']['catalog']['target'])) {
		$plugin_catalog_targets = freo_page_catalog_list_get_target();

		$targets = array();
		foreach ($plugin_catalog_targets as $plugin_catalog_target) {
			if ($plugin_catalog_targets[$_SESSION['plugin']['catalog']['target']]['value'] >= $plugin_catalog_target['value']) {
				$targets[] = 'target = ' . $freo->pdo->quote($plugin_catalog_target['id']);
			}
		}

		$condition .= ' AND (target IS NULL OR ' . implode(' OR ', $targets) . ')';
	} else {
		$condition .= ' AND target IS NULL';
	}

	//並び順設定
	if ($freo->config['plugin']['catalog_list']['order'] == 'price') {
		$order = 'price';
	} elseif ($freo->config['plugin']['catalog_list']['order'] == 'price_desc') {
		$order = 'price DESC';
	} elseif ($freo->config['plugin']['catalog_list']['order'] == 'datetime') {
		$order = 'datetime';
	} elseif ($freo->config['plugin']['catalog_list']['order'] == 'datetime_desc') {
		$order = 'datetime DESC';
	} else {
		$order = 'sort, id';
	}

	//商品取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE status = \'publish\' AND display = \'publish\' ' . $condition . ' ORDER BY ' . $order . ' LIMIT :limit');
	$stmt->bindValue(':limit', intval($freo->config['plugin']['catalog_list']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_catalogs = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalogs[$data['id']] = $data;
	}

	//商品ID取得
	$plugin_catalog_keys = array_keys($plugin_catalogs);

	//商品タグ取得
	$plugin_catalog_tags = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		if (!$plugin_catalogs[$plugin_catalog]['tag']) {
			continue;
		}

		$plugin_catalog_tags[$plugin_catalog] = explode(',', $plugin_catalogs[$plugin_catalog]['tag']);
	}

	//商品ファイル取得
	$plugin_catalog_files = array();
	foreach ($plugin_catalog_keys as $plugin_catalog) {
		$file_dir = FREO_FILE_DIR . 'plugins/catalog_files/' . $plugin_catalog . '/';

		if (file_exists($file_dir)) {
			if ($dir = scandir($file_dir)) {
				foreach ($dir as $data) {
					if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
						$plugin_catalog_files[$plugin_catalog][$matches[1]] = $data;
					}
				}
			} else {
				freo_error('商品ファイル保存ディレクトリを開けません。');
			}
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalog_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_catalog_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_catalog_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                           => freo_token('create'),
		'plugin_catalog_lists'            => $plugin_catalogs,
		'plugin_catalog_list_tags'        => $plugin_catalog_tags,
		'plugin_catalog_list_files'       => $plugin_catalog_files,
		'plugin_catalog_list_categories'  => $plugin_catalog_categories,
		'plugin_catalog_list_targets'     => freo_page_catalog_list_get_target(),
		'plugin_catalog_list_sizes'       => freo_page_catalog_list_get_size(),
		'plugin_catalog_list_prefectures' => freo_page_catalog_list_get_prefecture()
	));

	return;
}

/* 対象を取得 */
function freo_page_catalog_list_get_target()
{
	global $freo;

	$targets = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/targets.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $value) = explode(',', chop($line), 3);

			$targets[$id] = array(
				'id'    => $id,
				'name'  => $name,
				'value' => $value
			);
		}
		fclose($fp);
	} else {
		freo_error('対象定義ファイルを読み込めません。');
	}

	return $targets;
}

/* サイズを取得 */
function freo_page_catalog_list_get_size()
{
	global $freo;

	$sizes = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/sizes.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name, $short, $long) = explode(',', chop($line), 4);

			$sizes[$id] = array(
				'id'    => $id,
				'name'  => $name,
				'short' => $short,
				'long'  => $long
			);
		}
		fclose($fp);
	} else {
		freo_error('サイズ定義ファイルを読み込めません。');
	}

	return $sizes;
}

/* 都道府県を取得 */
function freo_page_catalog_list_get_prefecture()
{
	global $freo;

	$prefectures = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/catalog_defines/prefectures.csv', 'r')) {
		while ($line = fgets($fp)) {
			list($id, $name) = explode(',', chop($line), 2);

			$prefectures[$id] = array(
				'id'   => $id,
				'name' => $name
			);
		}
		fclose($fp);
	} else {
		freo_error('都道府県定義ファイルを読み込めません。');
	}

	return $prefectures;
}

?>
