<?php

/*********************************************************************

 メディア文章表示プラグイン (2012/12/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/security_media.php';
require_once FREO_MAIN_DIR . 'freo/internals/filter_media.php';

/* メイン処理 */
function freo_page_media_document()
{
	global $freo;

	if (isset($_GET['file']) and preg_match('/^[\w\-\/\.]+$/', $_GET['file']) and !preg_match('/\.\./', $_GET['file'])) {
		//メディアフィルター取得
		$media_filters = freo_filter_media('user', array($_GET['file']));
		$media_filter  = $media_filters[$_GET['file']];

		if ($media_filter) {
			freo_error($freo->config['media']['filter_message']);
		}

		//ページ保護データ取得
		$media_securities = freo_security_media('user', array($_GET['file']));
		$media_security   = $media_securities[$_GET['file']];

		if ($media_security) {
			freo_error($freo->config['media']['restriction_message']);
		}

		if (preg_match('/' . FREO_ASCII_FILE . '/i', $_GET['file'])) {
			//ファイル情報取得
			list($file_width, $file_height, $file_size) = freo_file(FREO_FILE_DIR . 'medias/' . $_GET['file']);

			//メディア情報を取得
			$information = array();
			$text        = null;
			$flag        = false;

			if ($fp = fopen(FREO_FILE_DIR . 'medias/' . $_GET['file'], 'r')) {
				while ($line = fgets($fp)) {
					$line = freo_trim($line);

					if (preg_match('/' . FREO_PLUGIN_MEDIA_DOCUMENT_INFORMATION_START . '/', $line)) {
						$flag = true;
					} elseif (!$flag) {
						$text .= $line . "\n";
					} elseif (preg_match('/' . FREO_PLUGIN_MEDIA_DOCUMENT_INFORMATION_END . '/', $line)) {
						$flag = false;
					}

					if ($flag and preg_match('/([^:]+):(.+)/', $line, $matches)) {
						$information[$matches[1]] = $matches[2];
					}
				}

				fclose($fp);
			}

			$text = freo_trim($text);

			//表示形式調整
			if (!preg_match('/\.(html|htm||php)$/i', $_GET['file'])) {
				if (empty($information['html']) or $information['html'] != 'enabled') {
					$text = htmlspecialchars($text, ENT_QUOTES);
				}

				$text = '<p>' . nl2br($text) . '</p>';
			}

			//ファイルの説明を読み込み
			if (is_file(FREO_FILE_DIR . 'media_memos/' . $_GET['file'] . '.txt')) {
				$memo = file_get_contents(FREO_FILE_DIR . 'media_memos/' . $_GET['file'] . '.txt');

				if ($memo === false) {
					freo_error('ファイル ' . FREO_FILE_DIR . 'media_memos/' . $_GET['file'] . '.txt' . ' を読み込めません。');
				}
			} else {
				$memo = null;
			}

			//データ割当
			$freo->smarty->assign(array(
				'token'                      => freo_token('create'),
				'media_document_name'        => basename($_GET['file']),
				'media_document_memo'        => $memo,
				'media_document_width'       => $file_width,
				'media_document_height'      => $file_height,
				'media_document_size'        => $file_size,
				'media_document_text'        => $text,
				'media_document_information' => $information
			));

			//データ出力
			freo_output('plugins/media_document/file.html');
		} else {
			//リダイレクト
			freo_redirect($freo->core['http_url'] . FREO_FILE_DIR . 'medias/' . $_GET['file']);
		}
	} else {
		//置換用ディレクトリ名取得
		$media_document_names = array();
		$directory_names      = explode("\n", $freo->config['plugin']['media_document']['directory_names']);

		foreach ($directory_names as $directory_name) {
			if (!$directory_name) {
				continue;
			}

			list($id, $text) = explode(',', $directory_name, 2);

			$media_document_names[$id] = $text;
		}

		//メディア一括取得
		$media_documents = freo_page_media_document_get_medias(FREO_FILE_DIR . 'medias/');

		//データ割当
		$freo->smarty->assign(array(
			'token'                => freo_token('create'),
			'media_documents'      => $media_documents,
			'media_document_names' => $media_document_names
		));
	}

	return;
}

/* メディア一括取得 */
function freo_page_media_document_get_medias($path)
{
	global $freo;

	$files = array();

	if (!file_exists($path)) {
		return $files;
	}

	//メディア取得
	if ($dir = scandir($path)) {
		natcasesort($dir);

		if ($freo->config['plugin']['media_document']['order'] == 'name_desc') {
			$dir = array_reverse($dir);
		}

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
			$results = freo_page_media_document_get_medias($path . $data . '/');

			if (!empty($results)) {
				$files = array_merge($files, $results);
			}

			if (preg_match('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '(.+)$/', $path . $data, $matches)) {
				$directory = $matches[1];
			} else {
				$directory = null;
			}

			$files[$path . $data . '/'] = array(
				'id'          => $path . $data . '/',
				'path'        => $path,
				'directory'   => $directory,
				'name'        => $data,
				'type'        => 'directory',
				'memo'        => null,
				'directory'   => $directory,
				'width'       => 0,
				'height'      => 0,
				'size'        => 0,
				'information' => array(),
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

			//メディア情報を取得
			$information = array();
			$flag        = false;

			if ($fp = fopen($path . $data, 'r')) {
				while ($line = fgets($fp)) {
					$line = freo_trim($line);

					if (preg_match('/' . FREO_PLUGIN_MEDIA_DOCUMENT_INFORMATION_START . '/', $line)) {
						$flag = true;
					} elseif (preg_match('/' . FREO_PLUGIN_MEDIA_DOCUMENT_INFORMATION_END . '/', $line)) {
						$flag = false;
					}

					if ($flag and preg_match('/([^:]+):(.+)/', $line, $matches)) {
						$information[$matches[1]] = $matches[2];
					}
				}

				fclose($fp);
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

			if (preg_match('/^' . preg_quote(FREO_FILE_DIR . 'medias/', '/') . '(.+)$/', $path . $data, $matches)) {
				$file = $matches[1];
			} else {
				$file = null;
			}

			$files[$path . $data] = array(
				'id'          => $path . $data,
				'path'        => $path,
				'directory'   => $directory,
				'name'        => $data,
				'type'        => 'file',
				'memo'        => $memo,
				'file'        => $file,
				'width'       => $file_width,
				'height'      => $file_height,
				'size'        => $file_size,
				'information' => $information,
				'restriction' => $restrict_flag
			);
		}
	}

	return $files;
}

?>
