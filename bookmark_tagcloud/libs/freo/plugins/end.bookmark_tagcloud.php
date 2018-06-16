<?php

/*********************************************************************

 ブックマークタグクラウド表示プラグイン | 設定ファイル (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_bookmark_tagcloud()
{
	global $freo;

	//エントリータグ取得
	$stmt = $freo->pdo->query('SELECT id, tag FROM ' . FREO_DATABASE_PREFIX . 'plugin_bookmarks WHERE tag IS NOT NULL');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$bookmark_tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($bookmark_tags[$tag])) {
				$bookmark_tags[$tag]++;
			} else {
				$bookmark_tags[$tag] = 1;
			}
		}
	}

	ksort($bookmark_tags, SORT_STRING);

	$data = '';
	foreach ($bookmark_tags as $tag => $count) {
		$data .= "$tag,$count\n";
	}

	//エントリータグ情報更新
	if (file_put_contents(FREO_FILE_DIR . 'plugins/bookmark_tagcloud.log', $data) === false) {
		freo_error('ブックマークタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/bookmark_tagcloud.log に書き込めません。');
	}

	return;
}

?>
