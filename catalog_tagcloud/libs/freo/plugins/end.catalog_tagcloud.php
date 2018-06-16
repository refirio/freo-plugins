<?php

/*********************************************************************

 ショッピングカートタグクラウド表示プラグイン | 設定ファイル (2013/04/09)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_catalog_tagcloud()
{
	global $freo;

	//ショッピングカートタグ取得
	$stmt = $freo->pdo->query('SELECT tag FROM ' . FREO_DATABASE_PREFIX . 'plugin_catalogs WHERE status = \'publish\' AND tag IS NOT NULL');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$catalog_tags = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		foreach (explode(',', $data['tag']) as $tag) {
			if ($tag == '') {
				continue;
			}

			if (isset($catalog_tags[$tag])) {
				$catalog_tags[$tag]++;
			} else {
				$catalog_tags[$tag] = 1;
			}
		}
	}

	ksort($catalog_tags, SORT_STRING);

	$data = '';
	foreach ($catalog_tags as $tag => $count) {
		$data .= "$tag,$count\n";
	}

	//エントリータグ情報更新
	if (file_put_contents(FREO_FILE_DIR . 'plugins/catalog_tagcloud.log', $data) === false) {
		freo_error('ショッピングカートタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/catalog_tagcloud.log に書き込めません。');
	}

	return;
}

?>
