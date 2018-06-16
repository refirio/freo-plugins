<?php

/*********************************************************************

 サークル管理プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';

/* メイン処理 */
function freo_page_circle()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_circle_setup();
			break;
		case 'setup_execute':
			freo_page_circle_setup_execute();
			break;
		case 'view':
			freo_page_circle_view();
			break;
		case 'form':
			freo_page_circle_form();
			break;
		case 'post':
			freo_page_circle_post();
			break;
		case 'admin':
			freo_page_circle_admin();
			break;
		case 'admin_form':
			freo_page_circle_admin_form();
			break;
		case 'admin_post':
			freo_page_circle_admin_post();
			break;
		case 'admin_category':
			freo_page_circle_admin_category();
			break;
		case 'admin_category_form':
			freo_page_circle_admin_category_form();
			break;
		case 'admin_category_post':
			freo_page_circle_admin_category_post();
			break;
		case 'admin_category_update':
			freo_page_circle_admin_category_update();
			break;
		case 'admin_category_delete':
			freo_page_circle_admin_category_delete();
			break;
		default:
			freo_page_circle_default();
	}

	return;
}

/* セットアップ */
function freo_page_circle_setup()
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
			freo_redirect('circle/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'circle',
		'plugin_name' => FREO_PLUGIN_CIRCLE_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_circle_setup_execute()
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
			'plugin_circles'           => '(user_id VARCHAR(80) NOT NULL, category_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, name VARCHAR(255) NOT NULL, kana VARCHAR(255) NOT NULL, url VARCHAR(255), image VARCHAR(80), file VARCHAR(80), space VARCHAR(255), coupling VARCHAR(255), tag VARCHAR(255), text TEXT, owner_name VARCHAR(255) NOT NULL, owner_kana VARCHAR(255) NOT NULL, plan_name VARCHAR(255), plan_url VARCHAR(255), plan_text TEXT, admin_text TEXT, PRIMARY KEY(user_id))',
			'plugin_circle_categories' => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_circles'           => '(user_id VARCHAR NOT NULL, category_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, name VARCHAR NOT NULL, kana VARCHAR NOT NULL, url VARCHAR, image VARCHAR, file VARCHAR, space VARCHAR, coupling VARCHAR, tag VARCHAR, text TEXT, owner_name VARCHAR NOT NULL, owner_kana VARCHAR NOT NULL, plan_name VARCHAR, plan_url VARCHAR, plan_text TEXT, admin_text TEXT, PRIMARY KEY(user_id))',
			'plugin_circle_categories' => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_CIRCLE_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('circle/setup?exec=setup', true);

	return;
}

/* サークル入力 */
function freo_page_circle_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//アップロードデータ初期化
		if (!isset($_FILES['plugin_circle']['tmp_name']['image'])) {
			$_FILES['plugin_circle']['tmp_name']['image'] = null;
		}
		if (!isset($_FILES['plugin_circle']['tmp_name']['file'])) {
			$_FILES['plugin_circle']['tmp_name']['file'] = null;
		}

		//アップロードデータ取得
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'])) {
			$_POST['plugin_circle']['image'] = $_FILES['plugin_circle']['name']['image'];
		} elseif (!isset($_POST['page']['file'])) {
			$_POST['plugin_circle']['image'] = null;
		}
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'])) {
			$_POST['plugin_circle']['file'] = $_FILES['plugin_circle']['name']['file'];
		} elseif (!isset($_POST['page']['file'])) {
			$_POST['plugin_circle']['file'] = null;
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//サークル名
			if ($_POST['plugin_circle']['name'] == '') {
				$freo->smarty->append('errors', 'サークル名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル名は80文字以内で入力してください。');
			}

			//サークル名（フリガナ）
			if ($_POST['plugin_circle']['kana'] == '') {
				$freo->smarty->append('errors', 'サークル名（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル名（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_circle']['kana'])) {
				$freo->smarty->append('errors', 'サークル名（フリガナ）は全角カタカナで入力してください。');
			}

			//サークルURL
			if (mb_strlen($_POST['plugin_circle']['url'], 'UTF-8') > 200) {
				$freo->smarty->append('errors', 'サークルURLは200文字以内で入力してください。');
			}

			//サークルカット
			if ($_POST['plugin_circle']['image'] != '') {
				if (!preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $_POST['plugin_circle']['image'])) {
					$freo->smarty->append('errors', 'サークルカットのファイル名は半角英数字で入力してください。');
				} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_circle']['image'])) {
					$freo->smarty->append('errors', 'アップロードできるサークルカットはGIF、JPEG、PNGのみです。');
				} elseif (mb_strlen($_POST['plugin_circle']['image'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', 'サークルカットのファイル名は80文字以内で入力してください。');
				}
			}

			//添付ファイル
			if ($_POST['plugin_circle']['file'] != '') {
				if (!preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $_POST['plugin_circle']['file'])) {
					$freo->smarty->append('errors', '添付ファイルのファイル名は半角英数字で入力してください。');
				} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_circle']['file'])) {
					$freo->smarty->append('errors', 'アップロードできる添付ファイルはGIF、JPEG、PNGのみです。');
				} elseif (mb_strlen($_POST['plugin_circle']['file'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '添付ファイルのファイル名は80文字以内で入力してください。');
				}
			}

			//サークルスペース
			if (mb_strlen($_POST['plugin_circle']['space'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークルスペースは80文字以内で入力してください。');
			}

			//カップリング
			if (mb_strlen($_POST['plugin_circle']['coupling'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カップリングは80文字以内で入力してください。');
			}

			//タグ
			if (mb_strlen($_POST['plugin_circle']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//メモ
			if (mb_strlen($_POST['plugin_circle']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '紹介文は5000文字以内で入力してください。');
			}

			//サークル代表者名
			if ($_POST['plugin_circle']['owner_name'] == '') {
				$freo->smarty->append('errors', 'サークル代表者名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['owner_name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル代表者名は80文字以内で入力してください。');
			}

			//サークル代表者名（フリガナ）
			if ($_POST['plugin_circle']['owner_kana'] == '') {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['owner_kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_circle']['owner_kana'])) {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）は全角カタカナで入力してください。');
			}

			//企画名
			if (mb_strlen($_POST['plugin_circle']['plan_name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '企画名は80文字以内で入力してください。');
			}

			//企画URL
			if (mb_strlen($_POST['plugin_circle']['plan_url'], 'UTF-8') > 200) {
				$freo->smarty->append('errors', '企画URLは200文字以内で入力してください。');
			}

			//企画詳細
			if (mb_strlen($_POST['plugin_circle']['plan_text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '企画URLは5000文字以内で入力してください。');
			}
		}

		//ファイルアップロード
		$image_flag = false;
		$file_flag  = false;

		if (!$freo->smarty->get_template_vars('errors')) {
			if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_images/';

				if (move_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'], $temporary_dir . $_FILES['plugin_circle']['name']['image'])) {
					chmod($temporary_dir . $_FILES['plugin_circle']['name']['image'], FREO_PERMISSION_FILE);

					$image_flag = true;
				} else {
					$freo->smarty->append('errors', 'サークルカットをアップロードできません。');
				}
			}
			if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_files/';

				if (move_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'], $temporary_dir . $_FILES['plugin_circle']['name']['file'])) {
					chmod($temporary_dir . $_FILES['plugin_circle']['name']['file'], FREO_PERMISSION_FILE);

					$file_flag = true;
				} else {
					$freo->smarty->append('errors', '添付ファイルをアップロードできません。');
				}
			}
		}

		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image']) and !$image_flag) {
			$_POST['plugin_circle']['image'] = null;
		}
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file']) and !$file_flag) {
			$_POST['plugin_circle']['file'] = null;
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_circle = $_POST['plugin_circle'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('circle/post?freo%5Btoken%5D=' . freo_token('create'));
		}
	} else {
		if ($freo->user['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_circle = $data;
			} else {
				$plugin_circle = array();
			}
		} else {
			freo_error('ユーザー情報を取得できません。');
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circle_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_circle_categories' => $plugin_circle_categories,
		'input' => array(
			'plugin_circle' => $plugin_circle
		)
	));

	return;
}

/* サークル登録 */
function freo_page_circle_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('circle/form?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('circle/form?error=1');
	}

	//入力データ取得
	$circle = $_SESSION['input']['plugin_circle'];

	if ($circle['category_id'] == '') {
		$circle['category_id'] = null;
	}
	if ($circle['url'] == '') {
		$circle['url'] = null;
	}
	if ($circle['space'] == '') {
		$circle['space'] = null;
	}
	if ($circle['coupling'] == '') {
		$circle['coupling'] = null;
	}
	if ($circle['tag'] == '') {
		$circle['tag'] = null;
	}
	if ($circle['text'] == '') {
		$circle['text'] = null;
	}
	if ($circle['plan_name'] == '') {
		$circle['plan_name'] = null;
	}
	if ($circle['plan_url'] == '') {
		$circle['plan_url'] = null;
	}
	if ($circle['plan_text'] == '') {
		$circle['plan_text'] = null;
	}

	//データ確認
	$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :user_id');
	$stmt->bindValue(':user_id', $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$flag = true;
	} else {
		$flag = false;
	}

	if ($flag == false) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_circles VALUES(:user_id, NULL, :now1, :now2, \'name\', \'kana\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, \'owner_name\', \'owner_kana\', NULL, NULL, NULL, NULL)');
		$stmt->bindValue(':user_id', $freo->user['id']);
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET category_id = :category_id, modified = :now, name = :name, kana = :kana, url = :url, space = :space, coupling = :coupling, tag = :tag, text = :text, owner_name = :owner_name, owner_kana = :owner_kana, plan_name = :plan_name, plan_url = :plan_url, plan_text = :plan_text WHERE user_id = :user_id');
	$stmt->bindValue(':category_id', $circle['category_id']);
	$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
	$stmt->bindValue(':name',        $circle['name']);
	$stmt->bindValue(':kana',        $circle['kana']);
	$stmt->bindValue(':url',         $circle['url']);
	$stmt->bindValue(':space',       $circle['space']);
	$stmt->bindValue(':coupling',    $circle['coupling']);
	$stmt->bindValue(':tag',         $circle['tag']);
	$stmt->bindValue(':text',        $circle['text']);
	$stmt->bindValue(':owner_name',  $circle['owner_name']);
	$stmt->bindValue(':owner_kana',  $circle['owner_kana']);
	$stmt->bindValue(':plan_name',   $circle['plan_name']);
	$stmt->bindValue(':plan_url',    $circle['plan_url']);
	$stmt->bindValue(':plan_text',   $circle['plan_text']);
	$stmt->bindValue(':user_id',     $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//サークルカット保存
	$image_dir     = FREO_FILE_DIR . 'plugins/circle_images/' . $freo->user['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_images/';

	if (($circle['image'] and file_exists($temporary_dir . $circle['image'])) or isset($circle['image_remove'])) {
		if (isset($circle['image_remove'])) {
			$image = null;
		} else {
			$image = $circle['image'];
		}

		freo_rmdir($image_dir);

		if ($image) {
			if (!freo_mkdir($image_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $image_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $image, $image_dir . $image)) {
				chmod($image_dir . $image, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $image . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET image = :image WHERE user_id = :user_id');
		$stmt->bindValue(':image',   $image);
		$stmt->bindValue(':user_id', $freo->user['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//添付ファイル保存
	$file_dir      = FREO_FILE_DIR . 'plugins/circle_files/' . $freo->user['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_files/';

	if (($circle['file'] and file_exists($temporary_dir . $circle['file'])) or isset($circle['file_remove'])) {
		if (isset($circle['file_remove'])) {
			$file = null;
		} else {
			$file = $circle['file'];
		}

		freo_rmdir($file_dir);

		if ($file) {
			if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $file, $file_dir . $file)) {
				chmod($file_dir . $file, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $file . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET file = :file WHERE user_id = :user_id');
		$stmt->bindValue(':file',    $file);
		$stmt->bindValue(':user_id', $freo->user['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('サークルを編集しました。');

	//サークル管理へ移動
	freo_redirect('circle/form?exec=update');

	return;
}

/* 管理画面 | サークル管理 */
function freo_page_circle_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ユーザー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$users = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$users[$data['id']] = $data;
	}

	//ユーザーID取得
	$user_keys = array_keys($users);

	//ユーザー関連データ取得
	$user_associates = freo_associate_user('get', $user_keys);

	//サークル取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles ORDER BY user_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circles[$data['user_id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'users'           => $users,
		'user_associates' => $user_associates,
		'plugin_circles'  => $plugin_circles
	));

	return;
}

/* 管理画面 | サークル入力 */
function freo_page_circle_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//アップロードデータ初期化
		if (!isset($_FILES['plugin_circle']['tmp_name']['image'])) {
			$_FILES['plugin_circle']['tmp_name']['image'] = null;
		}
		if (!isset($_FILES['plugin_circle']['tmp_name']['file'])) {
			$_FILES['plugin_circle']['tmp_name']['file'] = null;
		}

		//アップロードデータ取得
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'])) {
			$_POST['plugin_circle']['image'] = $_FILES['plugin_circle']['name']['image'];
		} elseif (!isset($_POST['page']['file'])) {
			$_POST['plugin_circle']['image'] = null;
		}
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'])) {
			$_POST['plugin_circle']['file'] = $_FILES['plugin_circle']['name']['file'];
		} elseif (!isset($_POST['page']['file'])) {
			$_POST['plugin_circle']['file'] = null;
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//サークル名
			if ($_POST['plugin_circle']['name'] == '') {
				$freo->smarty->append('errors', 'サークル名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル名は80文字以内で入力してください。');
			}

			//サークル名（フリガナ）
			if ($_POST['plugin_circle']['kana'] == '') {
				$freo->smarty->append('errors', 'サークル名（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル名（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_circle']['kana'])) {
				$freo->smarty->append('errors', 'サークル名（フリガナ）は全角カタカナで入力してください。');
			}

			//サークルURL
			if (mb_strlen($_POST['plugin_circle']['url'], 'UTF-8') > 200) {
				$freo->smarty->append('errors', 'サークルURLは200文字以内で入力してください。');
			}

			//サークルカット
			if ($_POST['plugin_circle']['image'] != '') {
				if (!preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $_POST['plugin_circle']['image'])) {
					$freo->smarty->append('errors', 'サークルカットのファイル名は半角英数字で入力してください。');
				} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_circle']['image'])) {
					$freo->smarty->append('errors', 'アップロードできるサークルカットはGIF、JPEG、PNGのみです。');
				} elseif (mb_strlen($_POST['plugin_circle']['image'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', 'サークルカットのファイル名は80文字以内で入力してください。');
				}
			}

			//添付ファイル
			if ($_POST['plugin_circle']['file'] != '') {
				if (!preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $_POST['plugin_circle']['file'])) {
					$freo->smarty->append('errors', '添付ファイルのファイル名は半角英数字で入力してください。');
				} elseif (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_circle']['file'])) {
					$freo->smarty->append('errors', 'アップロードできる添付ファイルはGIF、JPEG、PNGのみです。');
				} elseif (mb_strlen($_POST['plugin_circle']['file'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '添付ファイルのファイル名は80文字以内で入力してください。');
				}
			}

			//サークルスペース
			if (mb_strlen($_POST['plugin_circle']['space'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークルスペースは80文字以内で入力してください。');
			}

			//カップリング
			if (mb_strlen($_POST['plugin_circle']['coupling'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カップリングは80文字以内で入力してください。');
			}

			//タグ
			if (mb_strlen($_POST['plugin_circle']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//メモ
			if (mb_strlen($_POST['plugin_circle']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '紹介文は5000文字以内で入力してください。');
			}

			//サークル代表者名
			if ($_POST['plugin_circle']['owner_name'] == '') {
				$freo->smarty->append('errors', 'サークル代表者名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['owner_name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル代表者名は80文字以内で入力してください。');
			}

			//サークル代表者名（フリガナ）
			if ($_POST['plugin_circle']['owner_kana'] == '') {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle']['owner_kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）は80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_circle']['owner_kana'])) {
				$freo->smarty->append('errors', 'サークル代表者名（フリガナ）は全角カタカナで入力してください。');
			}

			//企画名
			if (mb_strlen($_POST['plugin_circle']['plan_name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '企画名は80文字以内で入力してください。');
			}

			//企画URL
			if (mb_strlen($_POST['plugin_circle']['plan_url'], 'UTF-8') > 200) {
				$freo->smarty->append('errors', '企画URLは200文字以内で入力してください。');
			}

			//企画詳細
			if (mb_strlen($_POST['plugin_circle']['plan_text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '企画URLは5000文字以内で入力してください。');
			}

			//管理者用メモ
			if (mb_strlen($_POST['plugin_circle']['admin_text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '管理者用メモは5000文字以内で入力してください。');
			}
		}

		//ファイルアップロード
		$image_flag = false;
		$file_flag  = false;

		if (!$freo->smarty->get_template_vars('errors')) {
			if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_images/';

				if (move_uploaded_file($_FILES['plugin_circle']['tmp_name']['image'], $temporary_dir . $_FILES['plugin_circle']['name']['image'])) {
					chmod($temporary_dir . $_FILES['plugin_circle']['name']['image'], FREO_PERMISSION_FILE);

					$image_flag = true;
				} else {
					$freo->smarty->append('errors', 'サークルカットをアップロードできません。');
				}
			}
			if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'])) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_files/';

				if (move_uploaded_file($_FILES['plugin_circle']['tmp_name']['file'], $temporary_dir . $_FILES['plugin_circle']['name']['file'])) {
					chmod($temporary_dir . $_FILES['plugin_circle']['name']['file'], FREO_PERMISSION_FILE);

					$file_flag = true;
				} else {
					$freo->smarty->append('errors', '添付ファイルをアップロードできません。');
				}
			}
		}

		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['image']) and !$image_flag) {
			$_POST['plugin_circle']['image'] = null;
		}
		if (is_uploaded_file($_FILES['plugin_circle']['tmp_name']['file']) and !$file_flag) {
			$_POST['plugin_circle']['file'] = null;
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_circle = $_POST['plugin_circle'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('circle/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_circle = $data;
			} else {
				$plugin_circle = array();
			}
		} else {
			freo_error('ユーザー情報を取得できません。');
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circle_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_circle_categories' => $plugin_circle_categories,
		'input' => array(
			'plugin_circle' => $plugin_circle
		)
	));

	return;
}

/* 管理画面 | サークル登録 */
function freo_page_circle_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('circle/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('circle/admin?error=1');
	}

	//入力データ取得
	$circle = $_SESSION['input']['plugin_circle'];

	if ($circle['category_id'] == '') {
		$circle['category_id'] = null;
	}
	if ($circle['url'] == '') {
		$circle['url'] = null;
	}
	if ($circle['space'] == '') {
		$circle['space'] = null;
	}
	if ($circle['coupling'] == '') {
		$circle['coupling'] = null;
	}
	if ($circle['tag'] == '') {
		$circle['tag'] = null;
	}
	if ($circle['text'] == '') {
		$circle['text'] = null;
	}
	if ($circle['plan_name'] == '') {
		$circle['plan_name'] = null;
	}
	if ($circle['plan_url'] == '') {
		$circle['plan_url'] = null;
	}
	if ($circle['plan_text'] == '') {
		$circle['plan_text'] = null;
	}
	if ($circle['admin_text'] == '') {
		$circle['admin_text'] = null;
	}

	//データ確認
	$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$flag = true;
	} else {
		$flag = false;
	}

	if ($flag == false) {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_circles VALUES(:id, NULL, :now1, :now2, \'name\', \'kana\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, \'owner_name\', \'owner_kana\', NULL, NULL, NULL, NULL)');
		$stmt->bindValue(':id',   $_GET['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET category_id = :category_id, modified = :now, name = :name, kana = :kana, url = :url, space = :space, coupling = :coupling, tag = :tag, text = :text, owner_name = :owner_name, owner_kana = :owner_kana, plan_name = :plan_name, plan_url = :plan_url, plan_text = :plan_text, admin_text = :admin_text WHERE user_id = :id');
	$stmt->bindValue(':category_id', $circle['category_id']);
	$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
	$stmt->bindValue(':name',        $circle['name']);
	$stmt->bindValue(':kana',        $circle['kana']);
	$stmt->bindValue(':url',         $circle['url']);
	$stmt->bindValue(':space',       $circle['space']);
	$stmt->bindValue(':coupling',    $circle['coupling']);
	$stmt->bindValue(':tag',         $circle['tag']);
	$stmt->bindValue(':text',        $circle['text']);
	$stmt->bindValue(':owner_name',  $circle['owner_name']);
	$stmt->bindValue(':owner_kana',  $circle['owner_kana']);
	$stmt->bindValue(':plan_name',   $circle['plan_name']);
	$stmt->bindValue(':plan_url',    $circle['plan_url']);
	$stmt->bindValue(':plan_text',   $circle['plan_text']);
	$stmt->bindValue(':admin_text',  $circle['admin_text']);
	$stmt->bindValue(':id',          $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//サークルカット保存
	$image_dir     = FREO_FILE_DIR . 'plugins/circle_images/' . $_GET['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_images/';

	if (($circle['image'] and file_exists($temporary_dir . $circle['image'])) or isset($circle['image_remove'])) {
		if (isset($circle['image_remove'])) {
			$image = null;
		} else {
			$image = $circle['image'];
		}

		freo_rmdir($image_dir);

		if ($image) {
			if (!freo_mkdir($image_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $image_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $image, $image_dir . $image)) {
				chmod($image_dir . $image, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $image . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET image = :image WHERE user_id = :id');
		$stmt->bindValue(':image', $image);
		$stmt->bindValue(':id',    $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//添付ファイル保存
	$file_dir      = FREO_FILE_DIR . 'plugins/circle_files/' . $_GET['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/circle_files/';

	if (($circle['file'] and file_exists($temporary_dir . $circle['file'])) or isset($circle['file_remove'])) {
		if (isset($circle['file_remove'])) {
			$file = null;
		} else {
			$file = $circle['file'];
		}

		freo_rmdir($file_dir);

		if ($file) {
			if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
				freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
			}

			if (rename($temporary_dir . $file, $file_dir . $file)) {
				chmod($file_dir . $file, FREO_PERMISSION_FILE);
			} else {
				freo_error('ファイル ' . $temporary_dir . $file . ' を移動できません。');
			}
		}

		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circles SET file = :file WHERE user_id = :id');
		$stmt->bindValue(':file', $file);
		$stmt->bindValue(':id',   $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('サークルを編集しました。');

	//サークル管理へ移動
	freo_redirect('circle/admin?exec=update&id=' . $_GET['id']);

	return;
}

/* 管理画面 | カテゴリー管理 */
function freo_page_circle_admin_category()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circle_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_circle_categories' => $plugin_circle_categories
	));

	return;
}

/* 管理画面 | カテゴリー入力 */
function freo_page_circle_admin_category_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//並び順取得
		if ($_POST['plugin_circle_category']['sort'] != '') {
			$_POST['plugin_circle_category']['sort'] = mb_convert_kana($_POST['plugin_circle_category']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//カテゴリーID
			if ($_POST['plugin_circle_category']['id'] == '') {
				$freo->smarty->append('errors', 'カテゴリーIDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_circle_category']['id'])) {
				$freo->smarty->append('errors', 'カテゴリーIDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_circle_category']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリーIDは80文字以内で入力してください。');
			}

			//並び順
			if ($_POST['plugin_circle_category']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_circle_category']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_circle_category']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//カテゴリー名
			if ($_POST['plugin_circle_category']['name'] == '') {
				$freo->smarty->append('errors', 'カテゴリー名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_circle_category']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリー名は80文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_circle_category']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_circle_category = $_POST['plugin_circle_category'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('circle/admin_category_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_circle_category = $_SESSION['input']['plugin_circle_category'];
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_circle_category = $data;
			} else {
				freo_error('指定されたカテゴリーが見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_circle_category = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_circle_category' => $plugin_circle_category
		)
	));

	return;
}

/* 管理画面 | カテゴリー登録 */
function freo_page_circle_admin_category_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('circle/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('circle/admin_category?error=1');
	}

	//入力データ取得
	$plugin_circle_category = $_SESSION['input']['plugin_circle_category'];

	if ($plugin_circle_category['memo'] == '') {
		$plugin_circle_category['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories SET modified = :now, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_circle_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_circle_category['name']);
		$stmt->bindValue(':memo', $plugin_circle_category['memo']);
		$stmt->bindValue(':id',   $plugin_circle_category['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories VALUES(:id, :now1, :now2, :sort, :name, :memo)');
		$stmt->bindValue(':id',   $plugin_circle_category['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_circle_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_circle_category['name']);
		$stmt->bindValue(':memo', $plugin_circle_category['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('カテゴリーを編集しました。');
	} else {
		freo_log('カテゴリーを新規に登録しました。');
	}

	//カテゴリー管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('circle/admin_category?exec=update&id=' . $plugin_circle_category['id']);
	} else {
		freo_redirect('circle/admin_category?exec=insert');
	}

	return;
}

/* 管理画面 | カテゴリー一括編集 */
function freo_page_circle_admin_category_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('circle/admin_category?error=1');
	}

	//データ登録
	if (isset($_POST['sort'])) {
		foreach ($_POST['sort'] as $id => $sort) {
			if (!preg_match('/^[\w\-\/]+$/', $id)) {
				continue;
			}
			if (!preg_match('/^\d+$/', $sort)) {
				continue;
			}

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('カテゴリーを並び替えました。');

	//カテゴリー管理へ移動
	freo_redirect('circle/admin_category?exec=sort');

	return;
}

/* 管理画面 | カテゴリー削除 */
function freo_page_circle_admin_category_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('circle/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('circle/admin_category?error=1');
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('カテゴリーを削除しました。');

	//カテゴリー管理へ移動
	freo_redirect('circle/admin_category?exec=delete&id=' . $_GET['id']);

	return;
}

/* サークル表示 */
function freo_page_circle_view()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[2])) {
		$_GET['id'] = $freo->parameters[2];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_error('表示したいユーザーを指定してください。');
	}

	//サークル取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles WHERE user_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle = $data;
	} else {
		freo_error('指定されたサークルが見つかりません。', '404 Not Found');
	}

	//エントリータグ取得
	if ($plugin_circle['tag']) {
		$plugin_circle_tags = explode(',', $plugin_circle['tag']);
	} else {
		$plugin_circle_tags = array();
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circle_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_circle'            => $plugin_circle,
		'plugin_circle_tags'       => $plugin_circle_tags,
		'plugin_circle_categories' => $plugin_circle_categories
	));

	return;
}

/* サークル一覧 */
function freo_page_circle_default()
{
	global $freo;

	//検索条件設定
	$condition = null;
	if (isset($_GET['category'])) {
		$condition .= ' AND category_id = ' . $freo->pdo->quote($_GET['category']);
	}
	if (isset($_GET['tag'])) {
		$condition .= ' AND tag = ' . $freo->pdo->quote($_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote($_GET['tag'] . ',%') . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag']) . ' OR tag LIKE ' . $freo->pdo->quote('%,' . $_GET['tag'] . ',%');
	}
	if ($condition) {
		$condition = ' WHERE user_id IS NOT NULL ' . $condition;
	}

	//サークル取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circles ' . $condition . ' ORDER BY kana, user_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circles[$data['user_id']] = $data;
	}

	//サークルID取得
	$plugin_circle_keys = array_keys($plugin_circles);

	//サークルタグ取得
	$plugin_circle_tags = array();
	foreach ($plugin_circle_keys as $plugin_circle) {
		if (!$plugin_circles[$plugin_circle]['tag']) {
			continue;
		}

		$plugin_circle_tags[$plugin_circle] = explode(',', $plugin_circles[$plugin_circle]['tag']);
	}

	//ユーザー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE authority = \'guest\' ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$users = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$users[$data['id']] = $data;
	}

	//ユーザーID取得
	$user_keys = array_keys($users);

	//ユーザー関連データ取得
	$user_associates = freo_associate_user('get', $user_keys);

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_circle_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_circle_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_circle_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_circles'           => $plugin_circles,
		'plugin_circle_tags'       => $plugin_circle_tags,
		'plugin_circle_categories' => $plugin_circle_categories,
		'users'                    => $users,
		'user_associates'          => $user_associates
	));

	return;
}

?>
