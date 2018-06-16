<?php

/*********************************************************************

 ブックマークタグクラウド表示プラグイン | 設定ファイル (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_bookmark_tagcloud()
{
	global $freo;

	//エントリータグ情報取得
	$tags = array();
	if ($fp = fopen(FREO_FILE_DIR . 'plugins/bookmark_tagcloud.log', 'r')) {
		while ($line = fgets($fp)) {
			list($tag, $count) = explode(',', trim($line));

			$tags[$tag] = $count;
		}
	} else {
		freo_error('ブックマークタグ情報保存ファイル ' . FREO_FILE_DIR . 'plugins/bookmark_tagcloud.log を読み込めません。');
	}

	//最大値と最小値を取得
	$max_count = empty($tags) ? 0 : max(array_values($tags));
	$min_count = empty($tags) ? 0 : min(array_values($tags));

	//値の範囲を取得
	$spread = $max_count - $min_count;
	if ($spread == 0) {
	    $spread = 1;
	}

	//サイズの比率を取得
	$step = ($freo->config['plugin']['bookmark_tagcloud']['max_size'] - $freo->config['plugin']['bookmark_tagcloud']['min_size']) / $spread;

	//タグクラウド取得
	$tagclouds = array();

	foreach ($tags as $tag => $count) {
		$tagclouds[] = array(
			'tag'   => $tag,
			'count' => $count,
			'size'  => intval($freo->config['plugin']['bookmark_tagcloud']['min_size'] + (($count - $min_count) * $step))
		);
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_bookmark_tagclouds' => $tagclouds
	));

	return;
}

?>
