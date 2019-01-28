<?php

/*********************************************************************

 エントリータグ管理プラグイン (2019/01/28)

 Copyright(C) 2009-2019 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_entry.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_entry.php';

/* メイン処理 */
function freo_end_entry_tagmanager()
{
	global $freo;

	//検索条件設定
	$condition = null;

	//制限されたエントリーを一覧に表示しない
	if (!$freo->config['view']['restricted_display']) {
		$entry_filters = freo_filter_entry('nobody', array_keys($freo->refer['entries']));
		$entry_filters = array_keys($entry_filters, true);
		$entry_filters = array_map('intval', $entry_filters);
		if (!empty($entry_filters)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_filters) . ')';
		}

		$entry_securities = freo_security_entry('nobody', array_keys($freo->refer['entries']));
		$entry_securities = array_keys($entry_securities, true);
		$entry_securities = array_map('intval', $entry_securities);
		if (!empty($entry_securities)) {
			$condition .= ' AND id NOT IN(' . implode(',', $entry_securities) . ')';
		}
	}

	//エントリータグ取得
	$stmt = $freo->pdo->prepare('SELECT tag FROM ' . FREO_DATABASE_PREFIX . 'entries WHERE approved = \'yes\' AND (status = \'publish\' OR (status = \'future\' AND datetime <= :now1)) AND display = \'publish\' AND tag IS NOT NULL AND (close IS NULL OR close >= :now2) ' . $condition);
	$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
	$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$entry_tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($entry_tags[$tag])) {
				$entry_tags[$tag]++;
			} else {
				$entry_tags[$tag] = 1;
			}
		}
	}

	ksort($entry_tags, SORT_STRING);

	$data = '';
	foreach ($entry_tags as $tag => $count) {
		$data .= "$tag,$count\n";
	}

	//エントリータグ情報更新
	if (file_put_contents(FREO_FILE_DIR . 'plugins/entry_tagcloud.log', $data) === false) {
		freo_error('エントリータグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/entry_tagcloud.log に書き込めません。');
	}

	return;
}

?>
