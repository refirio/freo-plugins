<?php

/*********************************************************************

 pixiv小説表示プラグイン (2013/05/13)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_display_pixiv_novel()
{
	global $freo;

	if ($freo->config['plugin']['pixiv_novel']['user_id'] > 0) {
		//pixivからデータを取得してキャッシュ
		$cache_file = FREO_FILE_DIR . 'plugins/pixiv_novel.log';
		$cache_time = $freo->config['plugin']['pixiv_novel']['cache_time'];

		if ($cache_time < 600) {
			$cache_time = 600;
		}

		if (filesize($cache_file) < 10 or filemtime($cache_file) < time() - $cache_time) {
			$data = freo_display_pixiv_novel_get_contents('http://spapi.pixiv.net/iphone/member_novel.php?id=' . $freo->config['plugin']['pixiv_novel']['user_id'] . '&p=1');

			if ($data == '') {
				$data = '0,0,,表示するデータが見つかりません,,,,,,,,,,,,,,,,,,,,,,,,,';
			}
			if (file_put_contents($cache_file, $data) === false) {
				freo_error('キャッシュ保存ファイル ' . $cache_file . ' に書き込めません。');
			}
		}

		//データ読み込み
		$pixiv_novels = array();
		$i            = 0;
		if ($fp = fopen($cache_file, 'r')) {
			while ($line = fgetcsv($fp)) {
				list($novel_id, $id, $type, $title, $directory, $user, $file_small, $x1, $x2, $file_big, $x3, $x4, $datetime, $tags, $tools, $rate, $score, $view, $caption, $x5, $x6, $x7, $x8, $x9, $x10, $x11, $x12, $x13, $x14) = $line;

				if (++$i > $freo->config['plugin']['pixiv_novel']['default_limit']) {
					break;
				}

				$pixiv_novels[$novel_id] = array(
					'novel_id'   => $novel_id,
					'id'         => $id,
					'type'       => $type,
					'title'      => $title,
					'directory'  => $directory,
					'user'       => $user,
					'file_small' => $file_small,
					'x1'         => $x1,
					'x2'         => $x2,
					'file_big'   => $file_big,
					'x3'         => $x3,
					'x4'         => $x4,
					'datetime'   => $datetime,
					'tags'       => $tags,
					'tools'      => $tools,
					'rate'       => $rate,
					'score'      => $score,
					'view'       => $view,
					'caption'    => $caption,
					'x5'         => $x5,
					'x6'         => $x6,
					'x7'         => $x7,
					'x8'         => $x8,
					'x9'         => $x9,
					'x10'        => $x10,
					'x11'        => $x11,
					'x12'        => $x12,
					'x13'        => $x13,
					'x14'        => $x14
				);
			}

			fclose($fp);
		} else {
			freo_error('キャッシュ保存ファイル ' . $cache_file . ' を読み込めません。');
		}
	} else {
		$pixiv_novels = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'plugin_pixiv_novels' => $pixiv_novels
	));

	return;
}

/* データ取得 */
function freo_display_pixiv_novel_get_contents($url)
{
	global $freo;

	//データ調整
	$info = parse_url($url);

	$request  = "GET " . $info['path'] . (isset($info['query']) ? '?' . $info['query'] : '') . " HTTP/1.0\r\n";
	$request .= "Host: " . $info['host'] . "\r\n";
	$request .= "User-Agent: freo\r\n";
	$request .= "\r\n";

	//データ送受信
	$data = '';

	if ($sock = fsockopen($info['host'], 80)) {
		fputs($sock, $request);
		while (!feof($sock)) {
			$data .= fgets($sock);
		}
		fclose($sock);
	}

	//BODYを取得
	list($header, $body) = explode("\r\n\r\n", $data, 2);

	return $body;
}

?>
