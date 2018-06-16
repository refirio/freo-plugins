<?php

/*********************************************************************

 メディア表示プラグイン (2013/09/25)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_media.php';

/* メイン処理 */
function freo_page_media_list()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['directory']) or !preg_match('/^[\w\-\/]+$/', $_GET['directory'])) {
		$_GET['directory'] = '';
	}
	if (!isset($_GET['target']) or !preg_match('/^[\w\-\/]+$/', $_GET['target'])) {
		$_GET['target'] = 'file';
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//メディアフィルター取得
	$media_filters = freo_filter_media('user', array($_GET['directory'] . '/'));
	$media_filter  = $media_filters[$_GET['directory'] . '/'];

	if ($media_filter) {
		freo_error($freo->config['media']['filter_message']);
	}

	//ページ保護データ取得
	$media_securities = freo_security_media('user', array($_GET['directory'] . '/'));
	$media_security   = $media_securities[$_GET['directory'] . '/'];

	if ($media_security) {
		freo_error($freo->config['media']['restriction_message']);
	}

	//閲覧制限の有無を確認
	$restrict_flag = false;

	$media_filters = freo_filter_media('nobody', array($_GET['directory'] . '/'));
	$media_filter  = $media_filters[$_GET['directory'] . '/'];

	if ($media_filter) {
		$restrict_flag = true;
	}

	$media_securities = freo_security_media('nobody', array($_GET['directory'] . '/'));
	$media_security   = $media_securities[$_GET['directory'] . '/'];

	if ($media_security) {
		$restrict_flag = true;
	}

	//置換用ディレクトリ名取得
	$media_list_names = array();
	$directory_names  = explode("\n", $freo->config['plugin']['media_list']['directory_names']);

	foreach ($directory_names as $directory_name) {
		if (!$directory_name) {
			continue;
		}

		list($id, $text) = explode(',', $directory_name, 2);

		$media_list_names[$id] = $text;
	}

	//表示対象ディレクトリ取得
	if ($_GET['directory']) {
		$directory           = FREO_FILE_DIR . 'medias/' . $_GET['directory'] . '/';
		$thumbnail_directory = FREO_FILE_DIR . 'media_thumbnails/' . $_GET['directory'] . '/';
		$memo_directory      = FREO_FILE_DIR . 'media_memos/' . $_GET['directory'] . '/';
	} else {
		$directory           = FREO_FILE_DIR . 'medias/';
		$thumbnail_directory = FREO_FILE_DIR . 'media_thumbnails/';
		$memo_directory      = FREO_FILE_DIR . 'media_memos/';
	}

	if ($_GET['target'] == 'directory') {
		//表示範囲取得
		$start = 1 + ($_GET['page'] - 1) * $freo->config['plugin']['media_list']['directory_limit'];
		$end   = $start + $freo->config['plugin']['media_list']['directory_limit'] - 1;
		$count = 0;

		$media_lists = array();
		if ($dir = scandir($directory)) {
			//表示順調整
			if ($freo->config['plugin']['media_list']['directory_order'] == 'time_asc' or $freo->config['plugin']['media_list']['directory_order'] == 'time_desc') {
				$temps = array();
				foreach ($dir as $data) {
					$temps[$data] = filemtime($directory . $data);
				}
				asort($temps);
				
				$dir = array_keys($temps);
			} else {
				natcasesort($dir);
			}

			if ($freo->config['plugin']['media_list']['directory_order'] == 'name_desc' or $freo->config['plugin']['media_list']['directory_order'] == 'time_desc') {
				$dir = array_reverse($dir);
			}

			//ディレクトリ表示
			foreach ($dir as $data) {
				if (!is_dir($directory . $data) or preg_match('/^\./', $data)) {
					continue;
				}

				$count++;
				if ($count < $start) {
					continue;
				}
				if ($count > $end) {
					continue;
				}

				//サムネイルを取得
				$thumbnail = array();
				if ($sub_dir = scandir($directory . $data)) {
					natcasesort($sub_dir);

					foreach ($sub_dir as $sub_data) {
						if (!is_file($directory . $data . '/' . $sub_data) or preg_match('/^\./', $sub_data)) {
							continue;
						}

						//サムネイルを取得
						if (is_file($thumbnail_directory . $data . '/' . $sub_data)) {
							list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file($thumbnail_directory . $data . '/' . $sub_data);

							if ($thumbnail_width > 0 and $thumbnail_height > 0) {
								$thumbnail = array(
									'id'     => $thumbnail_directory . $data . '/' . $sub_data,
									'path'   => $thumbnail_directory . $data . '/',
									'name'   => $sub_data,
									'type'   => 'file',
									'width'  => $thumbnail_width,
									'height' => $thumbnail_height,
									'size'   => $thumbnail_size
								);
							}
						} elseif (is_file($directory . $data . '/' . $sub_data)) {
							list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file($directory . $data . '/' . $sub_data);

							if ($thumbnail_width > 0 and $thumbnail_height > 0) {
								$thumbnail = array(
									'id'     => $directory . $data . '/' . $sub_data,
									'path'   => $directory . $data . '/',
									'name'   => $sub_data,
									'type'   => 'file',
									'width'  => $thumbnail_width,
									'height' => $thumbnail_height,
									'size'   => $thumbnail_size
								);
							}
						}

						break;
					}
				} else {
					freo_error('サブディレクトリを開けません。');
				}

				$media_lists[$directory . $data] = array(
					'id'        => $directory . $data,
					'path'      => $directory,
					'name'      => $data,
					'type'      => 'directory',
					'thumbnail' => $thumbnail
				);
			}
		} else {
			freo_error('メディア格納ディレクトリを開けません。');
		}

		//メディア数・ページ数取得
		$media_list_count = $count;
		$media_list_page  = ceil($media_list_count / $freo->config['plugin']['media_list']['directory_limit']);
	} else {
		//表示範囲取得
		$start = 1 + ($_GET['page'] - 1) * $freo->config['plugin']['media_list']['media_limit'];
		$end   = $start + $freo->config['plugin']['media_list']['media_limit'] - 1;
		$count = 0;

		$media_lists = array();
		if ($dir = scandir($directory)) {
			//表示順調整
			if ($freo->config['plugin']['media_list']['media_order'] == 'time_asc' or $freo->config['plugin']['media_list']['media_order'] == 'time_desc') {
				$temps = array();
				foreach ($dir as $data) {
					$temps[$data] = filemtime($directory . $data);
				}
				asort($temps);
				
				$dir = array_keys($temps);
			} else {
				natcasesort($dir);
			}

			if ($freo->config['plugin']['media_list']['media_order'] == 'name_desc' or $freo->config['plugin']['media_list']['media_order'] == 'time_desc') {
				$dir = array_reverse($dir);
			}

			//メディア表示
			foreach ($dir as $data) {
				if (!is_file($directory . $data) or preg_match('/^\./', $data)) {
					continue;
				}

				$count++;
				if ($count < $start) {
					continue;
				}
				if ($count > $end) {
					continue;
				}

				//サムネイルを取得
				if (is_file($thumbnail_directory . $data)) {
					list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file($thumbnail_directory . $data);

					$thumbnail = array(
						'id'     => $thumbnail_directory . $data,
						'path'   => $thumbnail_directory,
						'name'   => $data,
						'type'   => 'file',
						'width'  => $thumbnail_width,
						'height' => $thumbnail_height,
						'size'   => $thumbnail_size
					);
				} else {
					$thumbnail = array();
				}

				//ファイルの説明を取得
				if (is_file($memo_directory . $data . '.txt')) {
					$memo = file_get_contents($memo_directory . $data . '.txt');

					if ($memo === false) {
						freo_error('ファイル ' . $memo_directory . $data . '.txt' . ' を読み込めません。');
					}
				} else {
					$memo = null;
				}

				//ファイル情報を取得
				list($file_width, $file_height, $file_size) = freo_file($directory . $data);

				$media_lists[$directory . $data] = array(
					'id'        => $directory . $data,
					'path'      => $directory,
					'name'      => $data,
					'type'      => 'file',
					'width'     => $file_width,
					'height'    => $file_height,
					'size'      => $file_size,
					'thumbnail' => $thumbnail,
					'memo'      => $memo
				);
			}
		} else {
			freo_error('メディア格納ディレクトリを開けません。');
		}

		//メディア数・ページ数取得
		$media_list_count = $count;
		$media_list_page  = ceil($media_list_count / $freo->config['plugin']['media_list']['media_limit']);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'media_lists'            => $media_lists,
		'media_list_count'       => $media_list_count,
		'media_list_page'        => $media_list_page,
		'media_list_names'       => $media_list_names,
		'media_list_restriction' => $restrict_flag
	));

	return;
}

?>
