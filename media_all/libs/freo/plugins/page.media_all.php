<?php

/*********************************************************************

 メディア一括表示プラグイン (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_media.php';

/* メイン処理 */
function freo_page_media_all()
{
	global $freo;

	//置換用ディレクトリ名取得
	$media_all_names = array();
	$directory_names = explode("\n", $freo->config['plugin']['media_all']['directory_names']);

	foreach ($directory_names as $directory_name) {
		if (!$directory_name) {
			continue;
		}

		list($id, $text) = explode(',', $directory_name, 2);

		$media_all_names[$id] = $text;
	}

	//メディア一括取得
	$media_alls = freo_page_media_all_get_medias(FREO_FILE_DIR . 'medias/');

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'media_alls'      => $media_alls,
		'media_all_names' => $media_all_names
	));

	return;
}

/* メディア一括取得 */
function freo_page_media_all_get_medias($path)
{
	global $freo;

	$files = array();

	if (!file_exists($path)) {
		return $files;
	}

	//メディア取得
	if ($dir = scandir($path)) {
		natcasesort($dir);

		$tmp_directories = array();
		$tmp_files       = array();

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..' or preg_match('/^\./', $data)) {
				continue;
			}

			if (is_dir($path . $data)) {
				$tmp_directories[] = $data;
			} elseif (is_file($path . $data)) {
				$tmp_files[] = $data;
			}
		}

		$dir = array_merge($tmp_files, $tmp_directories);
	}

	foreach ($dir as $data) {
		if (is_dir($path . $data)) {
			$directory = preg_replace('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '/', '', $path . $data . '/');

			//メディアフィルター取得
			$media_filters = freo_filter_media('user', array($directory));
			$media_filter  = $media_filters[$directory];

			if ($media_filter) {
				continue;
			}

			//ページ保護データ取得
			$media_securities = freo_security_media('user', array($directory));
			$media_security   = $media_securities[$directory];

			if ($media_security) {
				continue;
			}

			//閲覧制限の有無を確認
			$restrict_flag = false;

			$media_filters = freo_filter_media('nobody', array($directory));
			$media_filter  = $media_filters[$directory];

			if ($media_filter) {
				$restrict_flag = true;
			}

			$media_securities = freo_security_media('nobody', array($directory));
			$media_security   = $media_securities[$directory];

			if ($media_security) {
				$restrict_flag = true;
			}

			//メディア一括取得
			$results = freo_page_media_all_get_medias($path . $data . '/');

			if (!empty($results)) {
				$files = array_merge($files, $results);
			}

			$files[$path . $data . '/'] = array(
				'id'          => $path . $data . '/',
				'path'        => $path,
				'directory'   => $directory,
				'name'        => $data,
				'type'        => 'directory',
				'width'       => 0,
				'height'      => 0,
				'size'        => 0,
				'thumbnail'   => array(),
				'memo'        => null,
				'restriction' => $restrict_flag
			);
		} elseif (is_file($path . $data)) {
			$directory = preg_replace('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '/', '', $path);

			//閲覧制限の有無を確認
			$restrict_flag = false;

			$media_filters = freo_filter_media('nobody', array($directory));
			$media_filter  = $media_filters[$directory];

			if ($media_filter) {
				$restrict_flag = true;
			}

			$media_securities = freo_security_media('nobody', array($directory));
			$media_security   = $media_securities[$directory];

			if ($media_security) {
				$restrict_flag = true;
			}

			//サムネイルを取得
			$thumbnail_path = preg_replace('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '/', FREO_FILE_DIR . 'media_thumbnails/', $path);

			if (is_file($thumbnail_path . $data)) {
				list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file($thumbnail_path . $data);

				$thumbnail = array(
					'id'        => $thumbnail_path . $data,
					'path'      => $thumbnail_path,
					'directory' => $directory,
					'name'      => $data,
					'type'      => 'file',
					'width'     => $thumbnail_width,
					'height'    => $thumbnail_height,
					'size'      => $thumbnail_size
				);
			} else {
				$thumbnail = array();
			}

			//ファイルの説明を取得
			$memo_path = preg_replace('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '/', FREO_FILE_DIR . 'media_memos/', $path);

			if (is_file($memo_path . $data . '.txt')) {
				$memo = file_get_contents($memo_path . $data . '.txt');

				if ($memo === false) {
					freo_error('ファイル ' . $memo_path . $data . '.txt' . ' を読み込めません。');
				}
			} else {
				$memo = null;
			}

			//ファイル情報を取得
			list($file_width, $file_height, $file_size) = freo_file($path . $data);

			$files[$path . $data] = array(
				'id'          => $path . $data,
				'path'        => $path,
				'directory'   => $directory,
				'name'        => $data,
				'type'        => 'file',
				'width'       => $file_width,
				'height'      => $file_height,
				'size'        => $file_size,
				'thumbnail'   => $thumbnail,
				'memo'        => $memo,
				'restriction' => $restrict_flag
			);
		}
	}

	return $files;
}

?>
