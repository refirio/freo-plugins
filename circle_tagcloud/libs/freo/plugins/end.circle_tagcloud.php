<?php

/*********************************************************************

 サークルタグクラウド表示プラグイン | 設定ファイル (2013/01/12)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_circle_tagcloud()
{
	global $freo;

	//エントリータグ取得
	$stmt = $freo->pdo->query('SELECT user_id, tag FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE tag IS NOT NULL');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$circle_tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($circle_tags[$tag])) {
				$circle_tags[$tag]++;
			} else {
				$circle_tags[$tag] = 1;
			}
		}
	}

	ksort($circle_tags, SORT_STRING);

	$data = '';
	foreach ($circle_tags as $tag => $count) {
		$data .= "$tag,$count\n";
	}

	//エントリータグ情報更新
	if (file_put_contents(FREO_FILE_DIR . 'plugins/circle_tagcloud.log', $data) === false) {
		freo_error('サークルタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/circle_tagcloud.log に書き込めません。');
	}

	return;
}

?>
