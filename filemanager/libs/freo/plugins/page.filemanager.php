<?php

/*********************************************************************

 ファイル管理プラグイン (2012/12/03)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_filemanager()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'admin':
			freo_page_filemanager_admin();
			break;
		case 'admin_form':
			freo_page_filemanager_admin_form();
			break;
		case 'admin_post':
			freo_page_filemanager_admin_post();
			break;
		case 'admin_move':
			freo_page_filemanager_admin_move();
			break;
		case 'admin_delete':
			freo_page_filemanager_admin_delete();
			break;
		default:
			freo_page_filemanager_default();
	}

	return;
}

/* 管理画面 | ファイル管理 */
function freo_page_filemanager_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ調整
	if (isset($_GET['path'])) {
		$_GET['path'] = str_replace('&amp;', '&', $_GET['path']);
		$_GET['path'] = str_replace('&#039;', '\'', $_GET['path']);
	}

	//パラメータ検証
	if (!isset($_GET['path']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['path']) or preg_match('/\.\.\//', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//管理対象除外ディレクトリ確認
	$excepted_dirs = explode(',', FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR);

	foreach ($excepted_dirs as $excepted_dir) {
		if (preg_match('/^' . preg_quote($excepted_dir, '/') . '/', $_GET['path'])) {
			freo_error('このディレクトリは管理対象外です。');
		}
	}

	//親ディレクトリ取得
	$path = $_GET['path'];

	if (preg_match('/(.+)\/$/', $path, $matches)) {
		$path = $matches[1];
	}
	$pos = strrpos($path, '/');

	if ($pos > 0) {
		$parent = substr($path, 0, $pos) . '/';
	} else {
		$parent = '';
	}

	//ファイル取得
	$directories = array();
	$files       = array();

	if ($dir = scandir(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'])) {
		natcasesort($dir);

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}

			if (is_dir(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $data)) {
				$directories[] = array(
					'name'     => $data,
					'datetime' => date('Y-m-d H:i:s', filemtime(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $data)),
				);
			} else {
				list($width, $height, $size) = freo_file(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $data);

				$files[] = array(
					'name'     => $data,
					'datetime' => date('Y-m-d H:i:s', filemtime(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $data)),
					'width'    => $width,
					'height'   => $height,
					'size'     => $size
				);
			}
		}
	} else {
		freo_error('ファイル管理ディレクトリ ' . FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . ' を開けません。');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'parent'      => $parent,
		'directories' => $directories,
		'files'       => $files
	));

	return;
}

/* 管理画面 | ファイル入力 */
function freo_page_filemanager_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ調整
	if (isset($_GET['name'])) {
		$_GET['name'] = str_replace('&amp;', '&', $_GET['name']);
		$_GET['name'] = str_replace('&#039;', '\'', $_GET['name']);
	}
	if (isset($_GET['path'])) {
		$_GET['path'] = str_replace('&amp;', '&', $_GET['path']);
		$_GET['path'] = str_replace('&#039;', '\'', $_GET['path']);
	}

	//パラメータ検証
	if (!isset($_GET['path']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['path']) or preg_match('/\.\.\//', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//管理対象除外ディレクトリ確認
	$excepted_dirs = explode(',', FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR);

	foreach ($excepted_dirs as $excepted_dir) {
		if (preg_match('/^' . preg_quote($excepted_dir, '/') . '/', $_GET['path'] . (isset($_GET['name']) ? $_GET['name'] : '') . '/')) {
			freo_error('このディレクトリは管理対象外です。');
		}
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		if ($_POST['plugin_filemanager']['exec'] == 'insert_directory' or $_POST['plugin_filemanager']['exec'] == 'rename_directory') {
			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				if ($_POST['plugin_filemanager']['directory'] == '') {
					$freo->smarty->append('errors', 'ディレクトリ名が入力されていません。');
				} elseif (!preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_POST['plugin_filemanager']['directory'])) {
					$freo->smarty->append('errors', 'ディレクトリ名は半角英数字で入力してください。');
				} elseif (preg_match('/\.\.\//', $_POST['plugin_filemanager']['directory'])) {
					$freo->smarty->append('errors', 'ディレクトリ名の入力内容が不正です。');
				} elseif (mb_strlen($_POST['plugin_filemanager']['directory'], 'UTF-8') > 255) {
					$freo->smarty->append('errors', 'ディレクトリ名は255文字以内で入力してください。');
				} elseif (file_exists(FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['directory'])) {
					$freo->smarty->append('errors', $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['directory'] . '/はすでに存在します。');
				}
			}
		} elseif ($_POST['plugin_filemanager']['exec'] == 'insert_file' or $_POST['plugin_filemanager']['exec'] == 'rename_file') {
			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				if ($_POST['plugin_filemanager']['file'] == '') {
					$freo->smarty->append('errors', 'ファイル名が入力されていません。');
				} elseif (!preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_POST['plugin_filemanager']['file'])) {
					$freo->smarty->append('errors', 'ファイル名は半角英数字で入力してください。');
				} elseif (preg_match('/\.\.\//', $_POST['plugin_filemanager']['file'])) {
					$freo->smarty->append('errors', 'ファイル名の入力内容が不正です。');
				} elseif (mb_strlen($_POST['plugin_filemanager']['file'], 'UTF-8') > 255) {
					$freo->smarty->append('errors', 'ファイル名は255文字以内で入力してください。');
				} elseif (file_exists(FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'])) {
					$freo->smarty->append('errors', $_POST['plugin_filemanager']['file'] . 'はすでに存在します。');
				}
			}
		} elseif ($_POST['plugin_filemanager']['exec'] == 'edit_file') {
		} else {
			//アップロード項目数取得
			$file_count = count($_FILES['plugin_filemanager']['tmp_name']['file']);

			//アップロードデータ初期化
			for ($i = 0; $i < $file_count; $i++) {
				if (!isset($_FILES['plugin_filemanager']['tmp_name']['file'][$i])) {
					$_FILES['plugin_filemanager']['tmp_name']['file'][$i] = null;
				}
			}

			//アップロードデータ取得
			for ($i = 0; $i < $file_count; $i++) {
				if (is_uploaded_file($_FILES['plugin_filemanager']['tmp_name']['file'][$i])) {
					$_POST['plugin_filemanager']['file'][$i] = $_FILES['plugin_filemanager']['name']['file'][$i];
				} else {
					$_POST['plugin_filemanager']['file'][$i] = null;
				}
			}

			//入力データ検証
			if (!$freo->smarty->get_template_vars('errors')) {
				if ($_POST['plugin_filemanager']['path'] != '') {
					if (!preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_POST['plugin_filemanager']['path'])) {
						$freo->smarty->append('errors', 'アップロード先は半角英数字で入力してください。');
					} elseif (preg_match('/\.\.\//', $_POST['plugin_filemanager']['path'])) {
						$freo->smarty->append('errors', 'アップロード先の入力内容が不正です。');
					}
				}

				$file_count = count($_POST['plugin_filemanager']['file']);

				if ($_POST['plugin_filemanager']['file'][0] == '') {
					$freo->smarty->append('errors', 'ファイルが入力されていません。');
				}

				$filenames = array();
				for ($i = 0; $i < $file_count; $i++) {
					if ($_POST['plugin_filemanager']['file'][$i] == '') {
						continue;
					}

					if (isset($filenames[$_POST['plugin_filemanager']['file'][$i]])) {
						$freo->smarty->append('errors', 'ファイル名はすべて異なるものを入力してください。');
					} elseif (!preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_POST['plugin_filemanager']['file'][$i])) {
						$freo->smarty->append('errors', 'ファイル名は半角英数字で入力してください。');
					} elseif (preg_match('/\.\.\//', $_POST['plugin_filemanager']['file'][$i])) {
						$freo->smarty->append('errors', 'ファイル名の入力内容が不正です。');
					} elseif (mb_strlen($_POST['plugin_filemanager']['file'][$i], 'UTF-8') > 255) {
						$freo->smarty->append('errors', 'ファイル名は255文字以内で入力してください。');
					} elseif (empty($_POST['plugin_filemanager']['file_org']) and file_exists(FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'][$i])) {
						$freo->smarty->append('errors', $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'][$i] . 'はすでにアップロードされています。');
					}

					$filenames[$_POST['plugin_filemanager']['file'][$i]] = true;
				}
			}

			//ファイルアップロード
			for ($i = 0; $i < $file_count; $i++) {
				$file_flag = false;

				if (!$freo->smarty->get_template_vars('errors')) {
					if (is_uploaded_file($_FILES['plugin_filemanager']['tmp_name']['file'][$i])) {
						$temporary_dir = FREO_FILE_DIR . 'temporaries/';

						if (move_uploaded_file($_FILES['plugin_filemanager']['tmp_name']['file'][$i], $temporary_dir . $_FILES['plugin_filemanager']['name']['file'][$i])) {
							chmod($temporary_dir . $_FILES['plugin_filemanager']['name']['file'][$i], FREO_PERMISSION_FILE);

							if (preg_match('/' . FREO_ASCII_FILE . '/i', $_FILES['plugin_filemanager']['name']['file'][$i])) {
								$data = file_get_contents($temporary_dir . $_FILES['plugin_filemanager']['name']['file'][$i]);

								if ($data) {
									if (file_put_contents($temporary_dir . $_FILES['plugin_filemanager']['name']['file'][$i], freo_unify($data)) === false) {
										$freo->smarty->append('errors', 'ファイルの改行コードを変更できません。');
									}
								}
							}

							$file_flag = true;
						} else {
							$freo->smarty->append('errors', 'ファイルをアップロードできません。');
						}
					}
				}
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_filemanager = $_POST['plugin_filemanager'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('filemanager/admin_post?freo%5Btoken%5D=' . freo_token('create'));
		}
	} else {
		//新規データ設定
		$plugin_filemanager = array();

		//テキストファイルを読み込み
		if (isset($_GET['name']) and preg_match('/' . FREO_ASCII_FILE . '/i', $_GET['name'])) {
			$plugin_filemanager['exec'] = 'edit_file';
			$plugin_filemanager['text'] = file_get_contents(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name']);

			if ($plugin_filemanager['text'] === false) {
				freo_error('ファイル ' . FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'] . ' 読み込めません。');
			}
		}
	}

	//ディレクトリ取得
	$dirs = freo_page_filemanager_get_dir(FREO_PLUGIN_FILEMANAGER_DIR);

	$directories = array();
	foreach ($dirs as $dir) {
		$directories[] = preg_replace('/^' . preg_quote(FREO_PLUGIN_FILEMANAGER_DIR, '/') . '/', '', $dir);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'directories' => $directories,
		'input' => array(
			'plugin_filemanager' => $plugin_filemanager
		)
	));

	return;
}

/* 管理画面 | ファイル登録 */
function freo_page_filemanager_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('filemanager/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('filemanager/admin?error=1');
	}

	//入力データ取得
	$plugin_filemanager = $_SESSION['input']['plugin_filemanager'];

	//アップロード先取得
	$file_dir = FREO_PLUGIN_FILEMANAGER_DIR . $plugin_filemanager['path'];

	if ($plugin_filemanager['exec'] == 'rename_directory') {
		//ディレクトリ名変更
		if (!rename($file_dir . $plugin_filemanager['directory_org'], $file_dir . $plugin_filemanager['directory'])) {
			freo_error('ディレクトリ ' . $file_dir . $plugin_filemanager['directory_org'] . ' の名前を変更できません。');
		}
	} elseif ($plugin_filemanager['exec'] == 'insert_directory') {
		//ディレクトリ作成
		if (!freo_mkdir($file_dir . $plugin_filemanager['directory'], FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . $file_dir . $plugin_filemanager['directory'] . ' を作成できません。');
		}
	} elseif ($plugin_filemanager['exec'] == 'rename_file') {
		//ファイル名変更
		if (!rename($file_dir . $plugin_filemanager['file_org'], $file_dir . $plugin_filemanager['file'])) {
			freo_error('ファイル ' . $file_dir . $plugin_filemanager['file_org'] . ' の名前を変更できません。');
		}
	} elseif ($plugin_filemanager['exec'] == 'insert_file') {
		//ファイル作成
		if (file_put_contents($file_dir . $plugin_filemanager['file'], '') === false) {
			freo_error('ファイル ' . $file_dir . $plugin_filemanager['file'] . ' を作成できません。');
		}

		chmod($file_dir . $plugin_filemanager['file'], FREO_PERMISSION_FILE);
	} elseif ($plugin_filemanager['exec'] == 'edit_file') {
		//ファイル編集
		if (file_put_contents($file_dir . $plugin_filemanager['file'], $plugin_filemanager['text']) === false) {
			freo_error('ファイル ' . $file_dir . $plugin_filemanager['file'] . ' に書き込めません。');
		}
	} else {
		//ファイル削除
		if (isset($plugin_filemanager['file_org']) and !unlink(FREO_PLUGIN_FILEMANAGER_DIR . $plugin_filemanager['path'] . $plugin_filemanager['file_org'])) {
			freo_error('ファイル ' . $file_dir . $plugin_filemanager['file_org'] . ' を削除できません。');
		}

		//ファイル保存
		if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
		}

		//アップロード項目数取得
		$file_count = count($plugin_filemanager['file']);

		//現在日時取得
		$now = time();

		for ($i = 0; $i < $file_count; $i++) {
			if ($plugin_filemanager['file'][$i] == '') {
				continue;
			}

			$org_plugin_filemanager = $plugin_filemanager['file'][$i];

			if (rename(FREO_FILE_DIR . 'temporaries/' . $plugin_filemanager['file'][$i], $file_dir . $plugin_filemanager['file'][$i])) {
				chmod($file_dir . $plugin_filemanager['file'][$i], FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ./' . $plugin_filemanager['file'][$i] . ' を移動できません。');
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($plugin_filemanager['directory_org'])) {
		freo_log('ディレクトリ名を変更しました。');
	} elseif (isset($plugin_filemanager['directory'])) {
		freo_log('ディレクトリを新規に作成しました。');
	} elseif (isset($plugin_filemanager['file_org'])) {
		freo_log('ファイル名を変更しました。');
	} elseif (isset($media['text'])) {
		freo_log('ファイルを編集しました。');
	} else {
		freo_log('ファイルを新規に登録しました。');
	}

	//エントリー管理へ移動
	if (isset($plugin_filemanager['directory_org'])) {
		$exec = 'rename_directory';
	} elseif (isset($plugin_filemanager['directory'])) {
		$exec = 'insert_directory';
	} elseif (isset($plugin_filemanager['file_org'])) {
		$exec = 'rename_file';
	} elseif (isset($plugin_filemanager['text'])) {
		$exec = 'edit_file';
	} else {
		$exec = 'insert';
	}

	freo_redirect('filemanager/admin?exec=' . $exec . (isset($plugin_filemanager['path']) ? '&path=' . str_replace('%2F', '/', rawurlencode($plugin_filemanager['path'])) : ''));

	return;
}

/* 管理画面 | ファイル移動 */
function freo_page_filemanager_admin_move()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ調整
	if (isset($_GET['name'])) {
		$_GET['name'] = str_replace('&amp;', '&', $_GET['name']);
		$_GET['name'] = str_replace('&#039;', '\'', $_GET['name']);
	}
	if (isset($_GET['path'])) {
		$_GET['path'] = str_replace('&amp;', '&', $_GET['path']);
		$_GET['path'] = str_replace('&#039;', '\'', $_GET['path']);
	}

	//パラメータ検証
	if (!isset($_GET['name']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['name'])) {
		$_GET['name'] = null;
	}
	if (!isset($_GET['path']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//管理対象除外ディレクトリ確認
	$excepted_dirs = explode(',', FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR);

	foreach ($excepted_dirs as $excepted_dir) {
		if (preg_match('/^' . preg_quote($excepted_dir, '/') . '/', $_GET['path'] . (isset($_GET['name']) ? $_GET['name'] : '') . '/')) {
			freo_error('このディレクトリは管理対象外です。');
		}
		if (preg_match('/^' . preg_quote($excepted_dir, '/') . '/', $_POST['plugin_filemanager']['path'] . '/')) {
			freo_error('指定された移動先は管理対象外です。');
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('filemanager/admin?error=1');
	}

	if (empty($_GET['directory'])) {
		//ファイル確認
		if (file_exists(FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'])) {
			freo_error('ファイル ' . FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'] . ' はすでに存在します。');
		}

		//ファイル移動
		if (!rename(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'], FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_POST['plugin_filemanager']['file'])) {
			freo_redirect('filemanager/admin?error=1');
		}
	} else {
		//ディレクトリ確認
		if (file_exists(FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_GET['name'] . '/')) {
			freo_error('ディレクトリ ' . FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'] . '/ はすでに存在します。');
		}

		//ディレクトリ移動
		if (!rename(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'] . '/', FREO_PLUGIN_FILEMANAGER_DIR . $_POST['plugin_filemanager']['path'] . $_GET['name'] . '/')) {
			freo_redirect('filemanager/admin?error=1');
		}
	}

	//ログ記録
	if (empty($_GET['directory'])) {
		freo_log('ファイルを移動しました。');
	} else {
		freo_log('ディレクトリを移動しました。');
	}

	//エントリー管理へ移動
	if (empty($_GET['directory'])) {
		$exec = 'move_file';
	} else {
		$exec = 'move_directory';
	}

	freo_redirect('filemanager/admin?exec=' . $exec . '&name=' . str_replace('%2F', '/', rawurlencode($_GET['name'])) . '&path=' . str_replace('%2F', '/', rawurlencode($_GET['path'])));

	return;
}

/* 管理画面 | ファイル削除 */
function freo_page_filemanager_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ調整
	if (isset($_GET['name'])) {
		$_GET['name'] = str_replace('&amp;', '&', $_GET['name']);
		$_GET['name'] = str_replace('&#039;', '\'', $_GET['name']);
	}
	if (isset($_GET['path'])) {
		$_GET['path'] = str_replace('&amp;', '&', $_GET['path']);
		$_GET['path'] = str_replace('&#039;', '\'', $_GET['path']);
	}

	//パラメータ検証
	if (!isset($_GET['name']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['name'])) {
		$_GET['name'] = null;
	}
	if (!isset($_GET['path']) or !preg_match('/' . FREO_PLUGIN_FILEMANAGER_PATH_CHARACTER . '/', $_GET['path'])) {
		$_GET['path'] = null;
	}

	//管理対象除外ディレクトリ確認
	$excepted_dirs = explode(',', FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR);

	foreach ($excepted_dirs as $excepted_dir) {
		if (preg_match('/^' . preg_quote($excepted_dir, '/') . '/', $_GET['path'] . (isset($_GET['name']) ? $_GET['name'] : '') . '/')) {
			freo_error('このディレクトリは管理対象外です。');
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('filemanager/admin?error=1');
	}

	if (empty($_GET['directory'])) {
		//ファイル削除
		if (!unlink(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'])) {
			freo_redirect('filemanager/admin?error=1');
		}
	} else {
		//ディレクトリ削除
		if (!freo_rmdir(FREO_PLUGIN_FILEMANAGER_DIR . $_GET['path'] . $_GET['name'])) {
			freo_redirect('filemanager/admin?error=1');
		}
	}

	//ログ記録
	if (empty($_GET['directory'])) {
		freo_log('ファイルを削除しました。');
	} else {
		freo_log('ディレクトリを削除しました。');
	}

	//ファイル管理へ移動
	if (empty($_GET['directory'])) {
		$exec = 'delete_file';
	} else {
		$exec = 'delete_directory';
	}

	freo_redirect('filemanager/admin?exec=' . $exec . '&name=' . str_replace('%2F', '/', rawurlencode($_GET['name'])) . '&path=' . str_replace('%2F', '/', rawurlencode($_GET['path'])));

	return;
}

/* ファイル管理 */
function freo_page_filemanager_default()
{
	global $freo;

	freo_redirect('filemanager/admin');

	return;
}

/* ディレクトリ取得 */
function freo_page_filemanager_get_dir($path)
{
	global $freo;

	$files = array();

	if (!file_exists($path)) {
		return $files;
	}

	$excepted_dirs = explode(',', FREO_PLUGIN_FILEMANAGER_EXCEPTED_DIR);

	foreach ($excepted_dirs as $excepted_dir) {
		if ($path == FREO_PLUGIN_FILEMANAGER_DIR . $excepted_dir) {
			return $files;
		}
	}

	if ($dir = scandir($path)) {
		$tmp_directories = array();

		foreach ($dir as $data) {
			if ($data == '.' or $data == '..') {
				continue;
			}

			if (is_dir($path . $data)) {
				$tmp_directories[] = $data;
			}
		}

		$dir = $tmp_directories;
	}

	foreach ($dir as $data) {
		$files = array_merge($files, array($path . $data . '/'), freo_page_filemanager_get_dir($path . $data . '/'));
	}

	return $files;
}

?>
