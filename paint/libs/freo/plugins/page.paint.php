<?php

/*********************************************************************

 イラスト投稿プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_paint()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_paint_setup();
			break;
		case 'setup_execute':
			freo_page_paint_setup_execute();
			break;
		case 'admin':
			freo_page_paint_admin();
			break;
		case 'admin_ready':
			freo_page_paint_admin_ready();
			break;
		case 'admin_canvas':
			freo_page_paint_admin_canvas();
			break;
		case 'admin_form':
			freo_page_paint_admin_form();
			break;
		case 'admin_post':
			freo_page_paint_admin_post();
			break;
		case 'admin_delete':
			freo_page_paint_admin_delete();
			break;
		case 'save':
			freo_page_paint_save();
			break;
		default:
			freo_page_paint_default();
	}

	return;
}

/* セットアップ */
function freo_page_paint_setup()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//エラー確認
		if (!$freo->smarty->get_template_vars('errors')) {
			freo_redirect('paint/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'paint',
		'plugin_name' => FREO_PLUGIN_PAINT_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_paint_setup_execute()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('setup?error=1', true);
	}

	//データベーステーブル存在検証
	if (FREO_DATABASE_TYPE == 'mysql') {
		$query = 'SHOW TABLES';
	} else {
		$query = 'SELECT name FROM sqlite_master WHERE type = \'table\'';
	}
	$stmt = $freo->pdo->query($query);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$table = array();
	while ($data = $stmt->fetch(PDO::FETCH_NUM)) {
		$table[$data[0]] = true;
	}

	//データベーステーブル定義
	if (FREO_DATABASE_TYPE == 'mysql') {
		$queries = array(
			'plugin_paints' => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, created DATETIME NOT NULL, modified DATETIME NOT NULL, tool VARCHAR(20) NOT NULL, pch VARCHAR(20) NOT NULL, format VARCHAR(20) NOT NULL, title VARCHAR(255), tag VARCHAR(255), text TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_paints' => '(id INTEGER, created DATETIME NOT NULL, modified DATETIME NOT NULL, tool VARCHAR NOT NULL, pch VARCHAR NOT NULL, format VARCHAR NOT NULL, title VARCHAR, tag VARCHAR, text TEXT, PRIMARY KEY(id))'
		);
	}

	//データベーステーブル作成
	foreach ($queries as $name => $query) {
		if (empty($table[FREO_DATABASE_PREFIX . $name])) {
			$stmt = $freo->pdo->query('CREATE TABLE ' . FREO_DATABASE_PREFIX . $name . $query);
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log(FREO_PLUGIN_PAINT_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('paint/setup?exec=setup', true);

	return;
}

/* 管理画面 | イラスト管理 */
function freo_page_paint_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$condition .= ' AND title LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%');
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//イラスト取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['paint']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['paint']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_paints = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_paints[$data['id']] = $data;
	}

	//イラストID取得
	$plugin_paint_keys = array_keys($plugin_paints);

	//イラストタグ取得
	$plugin_paint_tags = array();
	foreach ($plugin_paint_keys as $plugin_paint) {
		if (!$plugin_paints[$plugin_paint]['tag']) {
			continue;
		}

		$plugin_paint_tags[$plugin_paint] = explode(',', $plugin_paints[$plugin_paint]['tag']);
	}

	//イラストファイル取得
	$plugin_paint_files = array();
	foreach ($plugin_paint_keys as $plugin_paint) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_PAINT_IMAGE_DIR . $plugin_paint . '.' . $plugin_paints[$plugin_paint]['format']);

		$plugin_paint_files[$plugin_paint] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//イラスト数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data               = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_paint_count = $data[0];
	$plugin_paint_page  = ceil($plugin_paint_count / $freo->config['plugin']['paint']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'              => freo_token('create'),
		'plugin_paints'      => $plugin_paints,
		'plugin_paint_tags'  => $plugin_paint_tags,
		'plugin_paint_files' => $plugin_paint_files,
		'plugin_paint_count' => $plugin_paint_count,
		'plugin_paint_page'  => $plugin_paint_page
	));

	return;
}

/* 管理画面 | キャンバス設定 */
function freo_page_paint_admin_ready()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//データ割当
	$freo->smarty->assign('plugin_paint_tools', array(
		'spainter' => FREO_PLUGIN_PAINT_SPAINTER_FILE,
		'paintbbs' => FREO_PLUGIN_PAINT_PAINTBBS_FILE
	));

	return;
}

/* 管理画面 | キャンバス表示 */
function freo_page_paint_admin_canvas()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	if ($_GET['id']) {
		//編集データ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$tool = $data['tool'];
			$pch  = $data['pch'];
		} else {
			freo_error('指定されたイラストが見つかりません。', '404 Not Found');
		}
	} else {
		$tool = $_POST['tool'];
		$pch  = null;
	}

	//アプレット設定
	if ($tool == 'paintbbs') {
		$code     = 'pbbs.PaintBBS.class';
		$archive  = FREO_PLUGIN_PAINT_PAINTBBS_FILE;
		$tools    = '';
		$layer    = '';
		$resource = '';
		$reszip   = '';
		$ttzip    = '';
	} elseif ($tool == 'shipainterpro') {
		$code     = 'c.ShiPainter.class';
		$archive  = FREO_PLUGIN_PAINT_SPAINTER_FILE . ',' . FREO_PLUGIN_PAINT_RESOURCE_DIR . 'pro.zip';
		$tools    = 'pro';
		$layer    = 3;
		$resource = FREO_PLUGIN_PAINT_RESOURCE_DIR;
		$reszip   = FREO_PLUGIN_PAINT_RESOURCE_DIR . 'res_pro.zip';
		$ttzip    = FREO_PLUGIN_PAINT_RESOURCE_DIR . 'tt.zip';
	} else {
		$code     = 'c.ShiPainter.class';
		$archive  = FREO_PLUGIN_PAINT_SPAINTER_FILE . ',' . FREO_PLUGIN_PAINT_RESOURCE_DIR . 'normal.zip';
		$tools    = 'normal';
		$layer    = 3;
		$resource = FREO_PLUGIN_PAINT_RESOURCE_DIR;
		$reszip   = FREO_PLUGIN_PAINT_RESOURCE_DIR . 'res_normal.zip';
		$ttzip    = FREO_PLUGIN_PAINT_RESOURCE_DIR . 'tt.zip';
	}

	//データ割当
	$freo->smarty->assign('plugin_paint_applet', array(
		'tool'     => $tool,
		'pch'      => $pch,
		'code'     => $code,
		'archive'  => $archive,
		'tools'    => $tools,
		'layer'    => $layer,
		'resource' => $resource,
		'reszip'   => $reszip,
		'ttzip'    => $ttzip
	));

	return;
}

/* 管理画面 | イラスト情報入力 */
function freo_page_paint_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		$_GET['id'] = 0;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//タイトル
			if ($_POST['plugin_paint']['title'] == '') {
				$freo->smarty->append('errors', 'タイトルが入力されていません。');
			} elseif (mb_strlen($_POST['plugin_paint']['title'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タイトルは80文字以内で入力してください。');
			}

			//タグ
			if (mb_strlen($_POST['plugin_paint']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//本文
			if (mb_strlen($_POST['plugin_paint']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_paint = $_POST['plugin_paint'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('paint/admin_post?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id']);
		}
	} else {
		//編集データ取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_paint = $data;
		} else {
			freo_error('指定されたイラストが見つかりません。', '404 Not Found');
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_paint' => $plugin_paint
		)
	));

	return;
}

/* 管理画面 | イラスト情報登録 */
function freo_page_paint_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('paint/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('paint/admin?error=1');
	}

	//入力データ取得
	$paint = $_SESSION['input']['plugin_paint'];

	if ($paint['title'] == '') {
		$paint['title'] = null;
	}
	if ($paint['tag'] == '') {
		$paint['tag'] = null;
	}
	if ($paint['text'] == '') {
		$paint['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_paints SET modified = :now, title = :title, tag = :tag, text = :text WHERE id = :id');
	$stmt->bindValue(':now',   date('Y-m-d H:i:s'));
	$stmt->bindValue(':title', $paint['title']);
	$stmt->bindValue(':tag',   $paint['tag']);
	$stmt->bindValue(':text',  $paint['text']);
	$stmt->bindValue(':id',    $paint['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('イラスト情報を編集しました。');

	//イラスト情報管理へ移動
	freo_redirect('paint/admin?exec=update&id=' . $paint['id']);

	return;
}

/* 管理画面 | イラスト削除 */
function freo_page_paint_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('paint/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('paint/admin?error=1');
	}

	//削除データ取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_paint = $data;
	} else {
		freo_redirect('paint/admin?error=1');
	}

	//画像データ削除
	if (!unlink(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_PAINT_IMAGE_DIR . $_GET['id'] . '.' . $data['format'])) {
		freo_redirect('paint/admin?error=1');
	}

	//PCHデータ削除
	if (!unlink(FREO_FILE_DIR . 'plugins/paint/' . $_GET['id'] . '.' . $data['pch'])) {
		freo_redirect('paint/admin?error=1');
	}

	//イラスト削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_paints AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('イラストを削除しました。');

	//イラスト管理へ移動
	freo_redirect('paint/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* イラスト投稿 */
function freo_page_paint_save()
{
	global $freo, $HTTP_RAW_POST_DATA;

	//投稿データ取得
	ini_set('always_populate_raw_post_data', '1');

	$buffer = $HTTP_RAW_POST_DATA;
	if (!$buffer) {
		if ($stdin = fopen('php://input', 'rb')) {
			$buffer = fread($stdin, $_SERVER['CONTENT_LENGTH']);
			fclose($stdin);
		} else {
			exit("error\n標準入力を開けません。");
		}
	}
	if (!$buffer) {
		exit("error\n標準入力からデータを取得できません。");
	}

	//拡張ヘッダーの長さ取得
	$header_length = substr($buffer, 1, 8);

	if ($header_length == 0) {
		exit("error\n拡張ヘッダーを取得できません。");
	} else {
		//拡張ヘッダー取得
		$header_data = substr($buffer, 1 + 8, $header_length);

		//拡張ヘッダー解析
		parse_str($header_data, $header);
	}

	//投稿キーチェック
	if ($header['key'] != FREO_PLUGIN_PAINT_KEY) {
		exit("error\n投稿キーが違います。");
	}

	//現在時刻取得
	$time = time();

	//画像データの長さ取得
	$image_length = substr($buffer, 1 + 8 + $header_length, 8);

	//データ保存
	$image_dir = FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_PAINT_IMAGE_DIR;
	$pch_dir   = FREO_FILE_DIR . 'plugins/paint/';
	$pch       = null;
	$ext       = null;

	if ($image_length == 0) {
		exit("error\n画像データを取得できません。");
	} else {
		//画像データ取得
		$image_data = substr($buffer, 1 + 8 + $header_length + 8 + 2, $image_length);

		//画像拡張子取得
		if (substr($image_data, 1, 5) == "PNG\r\n") {
			$ext = 'png';
		} else {
			$ext = 'jpg';
		}

		//画像データ保存
		if (!freo_mkdir($image_dir)) {
			freo_error('ディレクトリ ' . $image_dir . ' を作成できません。');
		}

		if ($fp = fopen($image_dir . $time . '.' . $ext, 'wb')) {
			fwrite($fp, $image_data);
			fclose($fp);
		} else {
			exit("error\n画像データを保存できません。");
		}

		chmod($image_dir . $time . '.' . $ext, 0604);
	}

	//PCHデータの長さ取得
	$pch_length = substr($buffer, 1 + 8 + $header_length + 8 + 2 + $image_length, 8);

	if ($pch_length == 0) {
		exit("error\nPCHデータを取得できません。");
	} else {
		//PCHデータ取得
		$pch_data = substr($buffer, 1 + 8 + $header_length + 8 + 2 + $image_length + 8, $pch_length);

		//PCH拡張子取得
		if (substr($buffer, 0, 1) == 'S') {
			$pch = 'spch';
		} else {
			$pch = 'pch';
		}

		//PCHデータ保存
		if ($fp = fopen($pch_dir . $time . '.' . $pch, 'wb')) {
			fwrite($fp, $pch_data);
			fclose($fp);
		} else {
			exit("error\nPCHデータを保存できません。");
		}

		chmod($pch_dir . $time . '.' . $pch, 0604);
	}

	//旧データ削除
	if ($header['id']) {
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints WHERE id = :id');
		$stmt->bindValue(':id', $header['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_paint = $data;
		} else {
			freo_error('指定されたイラストが見つかりません。');
		}

		if (!unlink($image_dir . $header['id'] . '.' . $plugin_paint['format'])) {
			exit("error\n画像データを削除できません。");
		}
		if (!unlink($pch_dir . $header['id'] . '.' . $plugin_paint['pch'])) {
			exit("error\nPCHデータを削除できません。");
		}
	}

	//データ登録
	if ($header['id']) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_paints SET modified = :now, tool = :tool, pch = :pch, format = :format WHERE id = :id');
		$stmt->bindValue(':now',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':tool',   $header['tool']);
		$stmt->bindValue(':pch',    $pch);
		$stmt->bindValue(':format', $ext);
		$stmt->bindValue(':id',     $header['id'], PDO::PARAM_INT);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$id = $header['id'];
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_paints VALUES(NULL, :now1, :now2, :tool, :pch, :format, NULL, NULL, NULL)');
		$stmt->bindValue(':now1',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':tool',   $header['tool']);
		$stmt->bindValue(':pch',    $pch);
		$stmt->bindValue(':format', $ext);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$id = $freo->pdo->lastInsertId();
	}

	//ファイル名変更
	if (!rename($image_dir . $time . '.' . $ext, $image_dir . $id . '.' . $ext)) {
		exit("error\nファイル " . $image_dir . $time . '.' . $ext . ' を移動できません。');
	}
	if (!rename($pch_dir . $time . '.' . $pch, $pch_dir . $id . '.' . $pch)) {
		exit("error\nファイル " . $pch_dir . $time . '.' . $pch . ' を移動できません。');
	}

	//処理完了
	exit('ok');
}

/* イラスト一覧 */
function freo_page_paint_default()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$condition .= ' AND title LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR text LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%');
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if (isset($_GET['date'])) {
		if (preg_match('/^\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		} elseif (preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['date'])) {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$condition .= ' AND DATE_FORMAT(created, \'%Y%m%d\') = ' . $freo->pdo->quote($_GET['date']);
			} else {
				$condition .= ' AND STRFTIME(\'%Y%m%d\', created) = ' . $freo->pdo->quote($_GET['date']);
			}
		}
	}
	if ($condition) {
		$condition = ' WHERE id IS NOT NULL ' . $condition;
	}

	//イラスト取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['paint']['default_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['paint']['default_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_paints = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_paints[$data['id']] = $data;
	}

	//イラストID取得
	$plugin_paint_keys = array_keys($plugin_paints);

	//イラストタグ取得
	$plugin_paint_tags = array();
	foreach ($plugin_paint_keys as $plugin_paint) {
		if (!$plugin_paints[$plugin_paint]['tag']) {
			continue;
		}

		$plugin_paint_tags[$plugin_paint] = explode(',', $plugin_paints[$plugin_paint]['tag']);
	}

	//イラストファイル取得
	$plugin_paint_files = array();
	foreach ($plugin_paint_keys as $plugin_paint) {
		list($width, $height, $size) = freo_file(FREO_FILE_DIR . 'medias/' . FREO_PLUGIN_PAINT_IMAGE_DIR . $plugin_paint . '.' . $plugin_paints[$plugin_paint]['format']);

		$plugin_paint_files[$plugin_paint] = array(
			'width'  => $width,
			'height' => $height,
			'size'   => $size
		);
	}

	//イラスト数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_paints ' . $condition);
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data               = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_paint_count = $data[0];
	$plugin_paint_page  = ceil($plugin_paint_count / $freo->config['plugin']['paint']['default_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'              => freo_token('create'),
		'plugin_paints'      => $plugin_paints,
		'plugin_paint_tags'  => $plugin_paint_tags,
		'plugin_paint_files' => $plugin_paint_files,
		'plugin_paint_count' => $plugin_paint_count,
		'plugin_paint_page'  => $plugin_paint_page
	));

	return;
}

?>
