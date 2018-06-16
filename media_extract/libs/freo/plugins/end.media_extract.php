<?php

/*********************************************************************

 メディア一括登録プラグイン (2012/10/09)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_media_extract()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['plugin']['media_extract'])) {
		return;
	}

	//入力データ取得
	$media_extract = $_SESSION['plugin']['media_extract'];

	if ($media_extract['exec'] != 'insert') {
		return;
	}

	//アップロード先取得
	$file_dir      = FREO_FILE_DIR . 'medias/' . ($media_extract['path'] ? $media_extract['path'] . '/' : '');
	$thumbnail_dir = FREO_FILE_DIR . 'media_thumbnails/' . ($media_extract['path'] ? $media_extract['path'] . '/' : '');

	//現在日時取得
	$now = time();

	$i = -1;

	foreach ($media_extract['file'] as $file) {
		$i++;

		if (!preg_match('/\.zip$/', $file)) {
			continue;
		}

		if ($freo->config['media']['filename']) {
			for ($j = 0; $j < 100; $j++) {
				$filename = date('YmdHis', $now - $j + $i) . '.zip';

				if (file_exists($file_dir . $filename)) {
					$file = $filename;

					break;
				}
			}
		}

		//ZIPファイルを解凍
		$zip             = new ZipArchive();
		$extracted       = null;
		$extracted_dirs  = array();
		$extracted_files = array();

		if ($zip->open($file_dir . $file)) {
			$status    = $zip->statIndex(0);
			$extracted = $status['name'];

			for ($j = 0; $j < $zip->numFiles; $j++) {
				$status = $zip->statIndex($j);

				if (preg_match('/\/$/', $status['name'])) {
					$extracted_dirs[] = $status['name'];
				} else {
					$extracted_files[] = $status['name'];
				}
			}

			if ($zip->extractTo($file_dir)) {
				$zip->close();
			} else {
				freo_error('圧縮ファイル ' . $file_dir . $file . ' を展開できません。');
			}
		} else {
			freo_error('圧縮ファイル ' . $file_dir . $file . ' を開けません。');
		}

		//ZIPファイルを削除
		unlink($file_dir . $file);

		//ディレクトリのパーミッションを設定
		if (preg_match('/^([^\/]+\/)/', $extracted, $matches)) {
			$directory = $matches[1];

			chmod($file_dir . $directory, FREO_PERMISSION_DIR);
		}

		foreach ($extracted_dirs as $extracted_dir) {
			chmod($file_dir . $extracted_dir, FREO_PERMISSION_DIR);
		}

		foreach ($extracted_files as $extracted_file) {
			//ファイルのパーミッションを設定
			chmod($file_dir . $extracted_file, FREO_PERMISSION_FILE);

			//サムネイル用ディレクトリを作成
			if ($freo->config['media']['thumbnail']) {
				freo_mkdir(preg_replace('/([^\/]+)$/', '', $thumbnail_dir . $extracted_file), FREO_PERMISSION_DIR);
			}

			//サムネイルを作成
			if ($freo->config['media']['thumbnail']) {
				$thumbnail_width  = isset($media_extract['thumbnail_width'])  ? $media_extract['thumbnail_width']  : $freo->config['media']['thumbnail_width'];
				$thumbnail_height = isset($media_extract['thumbnail_height']) ? $media_extract['thumbnail_height'] : $freo->config['media']['thumbnail_height'];

				freo_resize($file_dir . $extracted_file, $thumbnail_dir . $extracted_file, $thumbnail_width, $thumbnail_height);

				if (file_exists($thumbnail_dir . $extracted_file)) {
					chmod($thumbnail_dir . $extracted_file, FREO_PERMISSION_FILE);
				}
			}

			//画像を縮小
			if ($freo->config['media']['original']) {
				freo_resize($file_dir . $extracted_file, $file_dir . $extracted_file, $freo->config['media']['original_width'], $freo->config['media']['original_height']);
			}
		}
	}

	//入力データ破棄
	$_SESSION['plugin']['media_extract'] = array();

	return;
}

?>
