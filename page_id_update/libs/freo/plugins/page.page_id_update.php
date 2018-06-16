<?php

/*********************************************************************

 ページID変更プラグイン (2012/11/11)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_page_id_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		freo_redirect('admin/page?error=1');
	}

	//入力データ検証
	if ($_POST['plugin_page_id_update']['id'] == '') {
		freo_error('ページIDが入力されていません。');
	} elseif (!preg_match('/^[\w\-\/]+$/', $_POST['plugin_page_id_update']['id'])) {
		freo_error('ページIDは半角英数字で入力してください。');
	} elseif (preg_match('/^\d+$/', $_POST['plugin_page_id_update']['id'])) {
		freo_error('ページIDには半角英字を含んでください。');
	} elseif (mb_strlen($_POST['plugin_page_id_update']['id'], 'UTF-8') > 80) {
		freo_error('ページIDは80文字以内で入力してください。');
	} else {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
		$stmt->bindValue(':id', $_POST['plugin_page_id_update']['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			freo_error('入力されたページIDはすでに使用されています。');
		}
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('admin/page?error=1');
	}

	//親ID取得
	$stmt = $freo->pdo->prepare('SELECT pid FROM ' . FREO_DATABASE_PREFIX . 'pages WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$page = $data;
	} else {
		freo_error('指定されたページが見つかりません。');
	}

	//トラックバック更新
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'trackbacks SET page_id = :new WHERE page_id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//コメント更新
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'comments SET page_id = :new WHERE page_id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//オプションファイル更新
	if (file_exists(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/')) {
		$stmt = $freo->pdo->query('SELECT id FROM ' . FREO_DATABASE_PREFIX . 'options WHERE type = \'file\'');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$options = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$options[] = $data['id'];
		}

		foreach ($options as $option) {
			if (!freo_mkdir(FREO_FILE_DIR . 'page_options/' . $_POST['plugin_page_id_update']['id'] . '/' . $option . '/', FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . FREO_FILE_DIR . 'page_options/' . $_POST['plugin_page_id_update']['id'] . '/' . $option . '/' . ' を作成できません。');
			}

			if ($dir = scandir(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/' . $option . '/')) {
				foreach ($dir as $data) {
					if ($data == '.' or $data == '..') {
						continue;
					}
					if (is_file(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/' . $option . '/' . $data)) {
						rename(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/' . $option . '/' . $data, FREO_FILE_DIR . 'page_options/' . $_POST['plugin_page_id_update']['id'] . '/' . $option . '/' . $data);
					}
				}
			}

			freo_rmdir(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/' . $option . '/', false);
		}

		freo_rmdir(FREO_FILE_DIR . 'page_options/' . $_GET['id'] . '/', false);
	}

	//関連データ更新
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'group_sets SET page_id = :new WHERE page_id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'filter_sets SET page_id = :new WHERE page_id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'option_sets SET page_id = :new WHERE page_id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//イメージ更新
	if (file_exists(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/')) {
		if (!freo_mkdir(FREO_FILE_DIR . 'page_images/' . $_POST['plugin_page_id_update']['id'] . '/', FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . FREO_FILE_DIR . 'page_images/' . $_POST['plugin_page_id_update']['id'] . '/' . ' を作成できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (is_file(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/' . $data)) {
					$from = $data;
					$to   = $data;

					if ($freo->config['page']['filename'] and preg_match('/\.(.*)$/', $from, $matches)) {
						$to = $_POST['plugin_page_id_update']['id'] . '.' . $matches[1];

						if (preg_match('/\/([^\/]*)$/', $to, $matches)) {
							$to = $matches[1];
						}
					}

					rename(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/' . $from, FREO_FILE_DIR . 'page_images/' . $_POST['plugin_page_id_update']['id'] . '/' . $to);

					if ($freo->config['page']['filename'] and $from != $to) {
						$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET image = :image WHERE id = :id');
						$stmt->bindValue(':image', $to);
						$stmt->bindValue(':id',    $_GET['id']);
						$flag = $stmt->execute();
						if (!$flag) {
							freo_error($stmt->errorInfo());
						}
					}
				}
			}
		}

		freo_rmdir(FREO_FILE_DIR . 'page_images/' . $_GET['id'] . '/', false);
	}

	//サムネイル更新
	if (file_exists(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/')) {
		if (!freo_mkdir(FREO_FILE_DIR . 'page_thumbnails/' . $_POST['plugin_page_id_update']['id'] . '/', FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . FREO_FILE_DIR . 'page_thumbnails/' . $_POST['plugin_page_id_update']['id'] . '/' . ' を作成できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (is_file(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/' . $data)) {
					$from = $data;
					$to   = $data;

					if ($freo->config['page']['filename'] and preg_match('/\.(.*)$/', $from, $matches)) {
						$to = $_POST['plugin_page_id_update']['id'] . '.' . $matches[1];

						if (preg_match('/\/([^\/]*)$/', $to, $matches)) {
							$to = $matches[1];
						}
					}

					rename(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/' . $from, FREO_FILE_DIR . 'page_thumbnails/' . $_POST['plugin_page_id_update']['id'] . '/' . $to);
				}
			}
		}

		freo_rmdir(FREO_FILE_DIR . 'page_thumbnails/' . $_GET['id'] . '/', false);
	}

	//ファイル更新
	if (file_exists(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/')) {
		if (!freo_mkdir(FREO_FILE_DIR . 'page_files/' . $_POST['plugin_page_id_update']['id'] . '/', FREO_PERMISSION_DIR)) {
			freo_error('ディレクトリ ' . FREO_FILE_DIR . 'page_files/' . $_POST['plugin_page_id_update']['id'] . '/' . ' を作成できません。');
		}

		if ($dir = scandir(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/')) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (is_file(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/' . $data)) {
					$from = $data;
					$to   = $data;

					if ($freo->config['page']['filename'] and preg_match('/\.(.*)$/', $from, $matches)) {
						$to = $_POST['plugin_page_id_update']['id'] . '.' . $matches[1];

						if (preg_match('/\/([^\/]*)$/', $to, $matches)) {
							$to = $matches[1];
						}
					}

					rename(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/' . $from, FREO_FILE_DIR . 'page_files/' . $_POST['plugin_page_id_update']['id'] . '/' . $to);

					if ($freo->config['page']['filename'] and $from != $to) {
						$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET file = :file WHERE id = :id');
						$stmt->bindValue(':file', $to);
						$stmt->bindValue(':id',   $_GET['id']);
						$flag = $stmt->execute();
						if (!$flag) {
							freo_error($stmt->errorInfo());
						}
					}
				}
			}
		}

		freo_rmdir(FREO_FILE_DIR . 'page_files/' . $_GET['id'] . '/', false);
	}

	//ページ更新
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET pid = :new WHERE pid = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'pages SET id = :new WHERE id = :old');
	$stmt->bindValue(':new', $_POST['plugin_page_id_update']['id']);
	$stmt->bindValue(':old', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('ページIDを変更しました。');

	//ページ管理へ移動
	freo_redirect('admin/page?exec=update&id=' . $_GET['id'] . ($page['pid'] ? '&pid=' . $page['pid'] : ''));

	return;
}

?>
