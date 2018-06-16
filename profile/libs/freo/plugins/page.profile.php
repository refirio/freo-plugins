<?php

/*********************************************************************

 プロフィール拡張プラグイン (2013/09/24)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_user.php';
require_once FREO_MAIN_DIR . 'freo/internals/validate_user.php';

/* メイン処理 */
function freo_page_profile()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_profile_setup();
			break;
		case 'setup_execute':
			freo_page_profile_setup_execute();
			break;
		case 'form':
			freo_page_profile_form();
			break;
		case 'preview':
			freo_page_profile_preview();
			break;
		case 'post':
			freo_page_profile_post();
			break;
		case 'admin':
			freo_page_profile_admin();
			break;
		case 'admin_form':
			freo_page_profile_admin_form();
			break;
		case 'admin_preview':
			freo_page_profile_admin_preview();
			break;
		case 'admin_post':
			freo_page_profile_admin_post();
			break;
		case 'admin_update':
			freo_page_profile_admin_update();
			break;
		case 'admin_category':
			freo_page_profile_admin_category();
			break;
		case 'admin_category_form':
			freo_page_profile_admin_category_form();
			break;
		case 'admin_category_post':
			freo_page_profile_admin_category_post();
			break;
		case 'admin_category_update':
			freo_page_profile_admin_category_update();
			break;
		case 'admin_category_delete':
			freo_page_profile_admin_category_delete();
			break;
		default:
			freo_page_profile_default();
	}

	return;
}

/* セットアップ */
function freo_page_profile_setup()
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
			freo_redirect('profile/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'profile',
		'plugin_name' => FREO_PLUGIN_PROFILE_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_profile_setup_execute()
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
			'plugin_profiles'           => '(user_id VARCHAR(80) NOT NULL, category_id VARCHAR(80), created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, kana VARCHAR(255) NOT NULL, tag VARCHAR(255), option01 TEXT, option02 TEXT, option03 TEXT, option04 TEXT, option05 TEXT, option06 TEXT, option07 TEXT, option08 TEXT, option09 TEXT, option10 TEXT, admin_text TEXT, PRIMARY KEY(user_id))',
			'plugin_profile_categories' => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, memo TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_profiles'           => '(user_id VARCHAR NOT NULL, category_id VARCHAR, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INT UNSIGNED NOT NULL, kana VARCHAR NOT NULL, tag VARCHAR, option01 TEXT, option02 TEXT, option03 TEXT, option04 TEXT, option05 TEXT, option06 TEXT, option07 TEXT, option08 TEXT, option09 TEXT, option10 TEXT, admin_text  TEXT, PRIMARY KEY(user_id))',
			'plugin_profile_categories' => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, sort INTEGER UNSIGNED NOT NULL, name VARCHAR NOT NULL, memo TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_PROFILE_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('profile/setup?exec=setup', true);

	return;
}

/* プロフィール入力 */
function freo_page_profile_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//ファイル番号定義
	$numbers = array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//ユーザーID取得
		$_POST['user']['id'] = $freo->user['id'];

		//アップロードデータ初期化
		foreach ($numbers as $number) {
			if (!isset($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
				$_FILES['plugin_profile']['tmp_name']['file' . $number] = null;
			}
		}

		//アップロードデータ取得
		foreach ($numbers as $number) {
			if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
				$_POST['plugin_profile']['file' . $number] = $_FILES['plugin_profile']['name']['file' . $number];
			}
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_user('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}

			//フリガナ
			if ($_POST['plugin_profile']['kana'] == '') {
				$freo->smarty->append('errors', 'フリガナが入力されていません。');
			} elseif (mb_strlen($_POST['plugin_profile']['kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'フリガナは80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_profile']['kana'])) {
				$freo->smarty->append('errors', 'フリガナは全角カタカナで入力してください。');
			}

			//タグ
			if (mb_strlen($_POST['plugin_profile']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//イメージ
			if ($_POST['plugin_profile']['file'] != '') {
				if (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_profile']['file'])) {
					$freo->smarty->append('errors', 'アップロードできるイメージはGIF、JPEG、PNGのみです。');
				}
			}
		}

		//ファイルアップロード
		foreach ($numbers as $number) {
			$file_flag  = false;

			if (!$freo->smarty->get_template_vars('errors')) {
				if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
					$temporary_dir  = FREO_FILE_DIR . 'temporaries/plugins/profile_files/';
					$temporary_file = $_FILES['plugin_profile']['name']['file' . $number];

					if (move_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number], $temporary_dir . $temporary_file)) {
						if (preg_match('/\.(.*)$/', $temporary_file, $matches)) {
							$_POST['plugin_profile']['file' . $number] = 'file' . $number . '.' . $matches[1];

							if (rename($temporary_dir . $temporary_file, $temporary_dir . $_POST['plugin_profile']['file' . $number])) {
								$temporary_file = $_POST['plugin_profile']['file' . $number];
							} else {
								$freo->smarty->append('errors', 'ファイル ' . $temporary_dir . $temporary_file . ' の名前を変更できません。');
							}
						} else {
							$freo->smarty->append('errors', 'ファイル ' . $temporary_file . ' の拡張子を取得できません。');
						}

						chmod($temporary_dir . $temporary_file, FREO_PERMISSION_FILE);

						$file_flag = true;
					} else {
						$freo->smarty->append('errors', 'ファイルをアップロードできません。');
					}
				}
			}

			if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number]) and !$file_flag) {
				$_POST['plugin_profile']['file' . $number] = null;
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$user           = $_POST['user'];
			$plugin_profile = $_POST['plugin_profile'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('profile/preview', true);
			} else {
				//登録処理へ移動
				freo_redirect('profile/post?freo%5Btoken%5D=' . freo_token('create'), true);
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$user           = $_SESSION['input']['user'];
			$plugin_profile = $_SESSION['input']['plugin_profile'];
		} elseif ($freo->user['id']) {
			//ユーザー取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
			$stmt->bindValue(':id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$user = $data;
			} else {
				freo_error('指定されたユーザーが見つかりません。');
			}

			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :user_id');
			$stmt->bindValue(':user_id', $freo->user['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_profile = $data;
			} else {
				$plugin_profile = array();
			}

			//ファイル取得
			$file_dir = FREO_FILE_DIR . 'plugins/profile_files/' . $freo->user['id'] . '/';

			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$plugin_profile[$matches[1]] = $data;
						}
					}
				} else {
					freo_error('ファイル保存ディレクトリを開けません。');
				}
			}
		} else {
			freo_error('ユーザー情報を取得できません。');
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profile_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profile_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                     => freo_token('create'),
		'plugin_profile_categories' => $plugin_profile_categories,
		'input' => array(
			'user'           => $user,
			'plugin_profile' => $plugin_profile
		)
	));

	return;
}

/* プロフィール入力内容確認 */
function freo_page_profile_preview()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('profile/form?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('profile/preview', true);
		}

		//登録処理へ移動
		freo_redirect('profile/post?freo%5Btoken%5D=' . freo_token('create'), true);
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'user'           => $_SESSION['input']['user'],
		'plugin_profile' => $_SESSION['input']['plugin_profile']
	));

	return;
}

/* プロフィール登録 */
function freo_page_profile_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'guest') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('profile/form?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/form?error=1');
	}

	//入力データ取得
	$user = $_SESSION['input']['user'];

	if ($user['url'] == '') {
		$user['url'] = null;
	}
	if ($user['text'] == '') {
		$user['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, name = :name, mail = :mail, url = :url, text = :text WHERE id = :id');
	$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':name', $user['name']);
	$stmt->bindValue(':mail', $user['mail']);
	$stmt->bindValue(':url',  $user['url']);
	$stmt->bindValue(':text', $user['text']);
	$stmt->bindValue(':id',   $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ファイル番号定義
	$numbers = array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//入力データ取得
	$profile = $_SESSION['input']['plugin_profile'];

	if ($profile['category_id'] == '') {
		$profile['category_id'] = null;
	}
	if ($profile['tag'] == '') {
		$profile['tag'] = null;
	}
	if ($profile['option01'] == '') {
		$profile['option01'] = null;
	}
	if ($profile['option02'] == '') {
		$profile['option02'] = null;
	}
	if ($profile['option03'] == '') {
		$profile['option03'] = null;
	}
	if ($profile['option04'] == '') {
		$profile['option04'] = null;
	}
	if ($profile['option05'] == '') {
		$profile['option05'] = null;
	}
	if ($profile['option06'] == '') {
		$profile['option06'] = null;
	}
	if ($profile['option07'] == '') {
		$profile['option07'] = null;
	}
	if ($profile['option08'] == '') {
		$profile['option08'] = null;
	}
	if ($profile['option09'] == '') {
		$profile['option09'] = null;
	}
	if ($profile['option10'] == '') {
		$profile['option10'] = null;
	}

	//データ確認
	$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :user_id');
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
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_profiles VALUES(:user_id, NULL, :now1, :now2, 1, \'kana\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');
		$stmt->bindValue(':user_id', $freo->user['id']);
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_profiles SET category_id = :category_id, modified = :now, kana = :kana, tag = :tag, option01 = :option01, option02 = :option02, option03 = :option03, option04 = :option04, option05 = :option05, option06 = :option06, option07 = :option07, option08 = :option08, option09 = :option09, option10 = :option10 WHERE user_id = :user_id');
	$stmt->bindValue(':category_id', $profile['category_id']);
	$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
	$stmt->bindValue(':kana',        $profile['kana']);
	$stmt->bindValue(':tag',         $profile['tag']);
	$stmt->bindValue(':option01',    $profile['option01']);
	$stmt->bindValue(':option02',    $profile['option02']);
	$stmt->bindValue(':option03',    $profile['option03']);
	$stmt->bindValue(':option04',    $profile['option04']);
	$stmt->bindValue(':option05',    $profile['option05']);
	$stmt->bindValue(':option06',    $profile['option06']);
	$stmt->bindValue(':option07',    $profile['option07']);
	$stmt->bindValue(':option08',    $profile['option08']);
	$stmt->bindValue(':option09',    $profile['option09']);
	$stmt->bindValue(':option10',    $profile['option10']);
	$stmt->bindValue(':user_id',     $freo->user['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ファイル保存
	$file_dir      = FREO_FILE_DIR . 'plugins/profile_files/' . $freo->user['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/profile_files/';

	foreach ($numbers as $number) {
		if ((!empty($profile['file' . $number]) and file_exists($temporary_dir . $profile['file' . $number])) or isset($profile['file' . $number . '_remove'])) {
			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$filename = $matches[1];

							if ($filename == 'file' . $number) {
								unlink($file_dir . $data);
							}
						}
					}
				} else {
					freo_error('ファイル保存ディレクトリを開けません。');
				}
			}

			if ($profile['file' . $number] and !isset($profile['file' . $number . '_remove'])) {
				if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
					freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
				}

				if (rename($temporary_dir . $profile['file' . $number], $file_dir . $profile['file' . $number])) {
					chmod($file_dir . $profile['file' . $number], FREO_PERMISSION_FILE);
				} else {
					freo_error('ファイル ' . $temporary_dir . $profile['file' . $number] . ' を移動できません。');
				}
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('プロフィールを編集しました。');

	//プロフィール管理へ移動
	freo_redirect('profile/form?exec=update');

	return;
}

/* 管理画面 | プロフィール管理 */
function freo_page_profile_admin()
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

	//プロフィール取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles ORDER BY user_id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profiles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profiles[$data['user_id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'           => freo_token('create'),
		'users'           => $users,
		'user_associates' => $user_associates,
		'plugin_profiles' => $plugin_profiles
	));

	return;
}

/* 管理画面 | プロフィール入力 */
function freo_page_profile_admin_form()
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

	//ファイル番号定義
	$numbers = array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//並び順取得
		if ($_POST['plugin_profile']['sort'] != '') {
			$_POST['plugin_profile']['sort'] = mb_convert_kana($_POST['plugin_profile']['sort'], 'n', 'UTF-8');
		}

		//アップロードデータ初期化
		foreach ($numbers as $number) {
			if (!isset($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
				$_FILES['plugin_profile']['tmp_name']['file' . $number] = null;
			}
		}

		//アップロードデータ取得
		foreach ($numbers as $number) {
			if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
				$_POST['plugin_profile']['file' . $number] = $_FILES['plugin_profile']['name']['file' . $number];
			}
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			$errors = freo_validate_user('update', $_POST);

			if ($errors) {
				foreach ($errors as $error) {
					$freo->smarty->append('errors', $error);
				}
			}

			//並び順
			if ($_POST['plugin_profile']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_profile']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_profile']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//フリガナ
			if ($_POST['plugin_profile']['kana'] == '') {
				$freo->smarty->append('errors', 'フリガナが入力されていません。');
			} elseif (mb_strlen($_POST['plugin_profile']['kana'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'フリガナは80文字以内で入力してください。');
			} elseif (!preg_match('/^[ァ-ヶー]+$/u', $_POST['plugin_profile']['kana'])) {
				$freo->smarty->append('errors', 'フリガナは全角カタカナで入力してください。');
			}

			//タグ
			if (mb_strlen($_POST['plugin_profile']['tag'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'タグは80文字以内で入力してください。');
			}

			//イメージ
			if ($_POST['plugin_profile']['file'] != '') {
				if (!preg_match('/\.(gif|jpeg|jpg|jpe|png)$/i', $_POST['plugin_profile']['file'])) {
					$freo->smarty->append('errors', 'アップロードできるイメージはGIF、JPEG、PNGのみです。');
				}
			}
		}

		//ファイルアップロード
		foreach ($numbers as $number) {
			$file_flag  = false;

			if (!$freo->smarty->get_template_vars('errors')) {
				if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number])) {
					$temporary_dir  = FREO_FILE_DIR . 'temporaries/plugins/profile_files/';
					$temporary_file = $_FILES['plugin_profile']['name']['file' . $number];

					if (move_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number], $temporary_dir . $temporary_file)) {
						if (preg_match('/\.(.*)$/', $temporary_file, $matches)) {
							$_POST['plugin_profile']['file' . $number] = 'file' . $number . '.' . $matches[1];

							if (rename($temporary_dir . $temporary_file, $temporary_dir . $_POST['plugin_profile']['file' . $number])) {
								$temporary_file = $_POST['plugin_profile']['file' . $number];
							} else {
								$freo->smarty->append('errors', 'ファイル ' . $temporary_dir . $temporary_file . ' の名前を変更できません。');
							}
						} else {
							$freo->smarty->append('errors', 'ファイル ' . $temporary_file . ' の拡張子を取得できません。');
						}

						chmod($temporary_dir . $temporary_file, FREO_PERMISSION_FILE);

						$file_flag = true;
					} else {
						$freo->smarty->append('errors', 'ファイルをアップロードできません。');
					}
				}
			}

			if (is_uploaded_file($_FILES['plugin_profile']['tmp_name']['file' . $number]) and !$file_flag) {
				$_POST['plugin_profile']['file' . $number] = null;
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$user           = $_POST['user'];
			$plugin_profile = $_POST['plugin_profile'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('profile/admin_preview' . ($_GET['id'] ? '?id=' . $_GET['id'] : ''));
			} else {
				//登録処理へ移動
				freo_redirect('profile/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$user           = $_SESSION['input']['user'];
			$plugin_profile = $_SESSION['input']['plugin_profile'];
		} elseif ($_GET['id']) {
			//ユーザー取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$user = $data;
			} else {
				freo_error('指定されたユーザーが見つかりません。');
			}

			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_profile = $data;
			} else {
				$plugin_profile = array(
					'sort' => 1
				);
			}

			//ファイル取得
			$file_dir = FREO_FILE_DIR . 'plugins/profile_files/' . $_GET['id'] . '/';

			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$plugin_profile[$matches[1]] = $data;
						}
					}
				} else {
					freo_error('ファイル保存ディレクトリを開けません。');
				}
			}
		} else {
			freo_error('ユーザー情報を取得できません。');
		}
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profile_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profile_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                     => freo_token('create'),
		'plugin_profile_categories' => $plugin_profile_categories,
		'input' => array(
			'user'           => $user,
			'plugin_profile' => $plugin_profile
		)
	));

	return;
}

/* 管理画面 | プロフィール入力内容確認 */
function freo_page_profile_admin_preview()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('profile/admin_form?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('profile/admin_preview', true);
		}

		//登録処理へ移動
		freo_redirect('profile/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'user'           => $_SESSION['input']['user'],
		'plugin_profile' => $_SESSION['input']['plugin_profile']
	));

	return;
}

/* 管理画面 | プロフィール登録 */
function freo_page_profile_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('profile/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/admin?error=1');
	}

	//入力データ取得
	$user = $_SESSION['input']['user'];

	if ($user['url'] == '') {
		$user['url'] = null;
	}
	if ($user['text'] == '') {
		$user['text'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'users SET modified = :now, name = :name, mail = :mail, url = :url, text = :text WHERE id = :id');
	$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
	$stmt->bindValue(':name', $user['name']);
	$stmt->bindValue(':mail', $user['mail']);
	$stmt->bindValue(':url',  $user['url']);
	$stmt->bindValue(':text', $user['text']);
	$stmt->bindValue(':id',   $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ファイル番号定義
	$numbers = array('', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10');

	//入力データ取得
	$profile = $_SESSION['input']['plugin_profile'];

	if ($profile['category_id'] == '') {
		$profile['category_id'] = null;
	}
	if ($profile['tag'] == '') {
		$profile['tag'] = null;
	}
	if ($profile['admin_text'] == '') {
		$profile['admin_text'] = null;
	}
	if ($profile['option01'] == '') {
		$profile['option01'] = null;
	}
	if ($profile['option02'] == '') {
		$profile['option02'] = null;
	}
	if ($profile['option03'] == '') {
		$profile['option03'] = null;
	}
	if ($profile['option04'] == '') {
		$profile['option04'] = null;
	}
	if ($profile['option05'] == '') {
		$profile['option05'] = null;
	}
	if ($profile['option06'] == '') {
		$profile['option06'] = null;
	}
	if ($profile['option07'] == '') {
		$profile['option07'] = null;
	}
	if ($profile['option08'] == '') {
		$profile['option08'] = null;
	}
	if ($profile['option09'] == '') {
		$profile['option09'] = null;
	}
	if ($profile['option10'] == '') {
		$profile['option10'] = null;
	}

	//データ確認
	$stmt = $freo->pdo->prepare('SELECT user_id FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :id');
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
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_profiles VALUES(:id, NULL, :now1, :now2, 1, \'kana\', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL)');
		$stmt->bindValue(':id',   $_GET['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_profiles SET category_id = :category_id, modified = :now, sort = :sort, kana = :kana, tag = :tag, option01 = :option01, option02 = :option02, option03 = :option03, option04 = :option04, option05 = :option05, option06 = :option06, option07 = :option07, option08 = :option08, option09 = :option09, option10 = :option10, admin_text = :admin_text WHERE user_id = :id');
	$stmt->bindValue(':category_id', $profile['category_id']);
	$stmt->bindValue(':now',         date('Y-m-d H:i:s'));
	$stmt->bindValue(':sort',        $profile['sort'], PDO::PARAM_INT);
	$stmt->bindValue(':kana',        $profile['kana']);
	$stmt->bindValue(':tag',         $profile['tag']);
	$stmt->bindValue(':option01',    $profile['option01']);
	$stmt->bindValue(':option02',    $profile['option02']);
	$stmt->bindValue(':option03',    $profile['option03']);
	$stmt->bindValue(':option04',    $profile['option04']);
	$stmt->bindValue(':option05',    $profile['option05']);
	$stmt->bindValue(':option06',    $profile['option06']);
	$stmt->bindValue(':option07',    $profile['option07']);
	$stmt->bindValue(':option08',    $profile['option08']);
	$stmt->bindValue(':option09',    $profile['option09']);
	$stmt->bindValue(':option10',    $profile['option10']);
	$stmt->bindValue(':admin_text',  $profile['admin_text']);
	$stmt->bindValue(':id',          $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ファイル保存
	$file_dir      = FREO_FILE_DIR . 'plugins/profile_files/' . $_GET['id'] . '/';
	$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/profile_files/';

	foreach ($numbers as $number) {
		if ((!empty($profile['file' . $number]) and file_exists($temporary_dir . $profile['file' . $number])) or isset($profile['file' . $number . '_remove'])) {
			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$filename = $matches[1];

							if ($filename == 'file' . $number) {
								unlink($file_dir . $data);
							}
						}
					}
				} else {
					freo_error('ファイル保存ディレクトリを開けません。');
				}
			}

			if ($profile['file' . $number] and !isset($profile['file' . $number . '_remove'])) {
				if (!freo_mkdir($file_dir, FREO_PERMISSION_DIR)) {
					freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
				}

				if (rename($temporary_dir . $profile['file' . $number], $file_dir . $profile['file' . $number])) {
					chmod($file_dir . $profile['file' . $number], FREO_PERMISSION_FILE);
				} else {
					freo_error('ファイル ' . $temporary_dir . $profile['file' . $number] . ' を移動できません。');
				}
			}
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('プロフィールを編集しました。');

	//プロフィール管理へ移動
	freo_redirect('profile/admin?exec=update&id=' . $_GET['id']);

	return;
}

/* 管理画面 | プロフィール一括編集 */
function freo_page_profile_admin_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/admin?error=1');
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

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_profiles SET sort = :sort WHERE user_id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('プロフィールを並び替えました。');

	//プロフィール管理へ移動
	freo_redirect('profile/admin?exec=sort');

	return;
}

/* 管理画面 | カテゴリー管理 */
function freo_page_profile_admin_category()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//カテゴリー取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_profile_categories = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_profile_categories[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                    => freo_token('create'),
		'plugin_profile_categories' => $plugin_profile_categories
	));

	return;
}

/* 管理画面 | カテゴリー入力 */
function freo_page_profile_admin_category_form()
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
		if ($_POST['plugin_profile_category']['sort'] != '') {
			$_POST['plugin_profile_category']['sort'] = mb_convert_kana($_POST['plugin_profile_category']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//カテゴリーID
			if ($_POST['plugin_profile_category']['id'] == '') {
				$freo->smarty->append('errors', 'カテゴリーIDが入力されていません。');
			} elseif (!preg_match('/^[\w\-]+$/', $_POST['plugin_profile_category']['id'])) {
				$freo->smarty->append('errors', 'カテゴリーIDは半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_profile_category']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリーIDは80文字以内で入力してください。');
			}

			//並び順
			if ($_POST['plugin_profile_category']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_profile_category']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_profile_category']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//カテゴリー名
			if ($_POST['plugin_profile_category']['name'] == '') {
				$freo->smarty->append('errors', 'カテゴリー名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_profile_category']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'カテゴリー名は80文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_profile_category']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_profile_category = $_POST['plugin_profile_category'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('profile/admin_category_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_profile_category = $_SESSION['input']['plugin_profile_category'];
		} elseif ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_profile_category = $data;
			} else {
				freo_error('指定されたカテゴリーが見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_profile_category = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_profile_category' => $plugin_profile_category
		)
	));

	return;
}

/* 管理画面 | カテゴリー登録 */
function freo_page_profile_admin_category_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('profile/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/admin_category?error=1');
	}

	//入力データ取得
	$plugin_profile_category = $_SESSION['input']['plugin_profile_category'];

	if ($plugin_profile_category['memo'] == '') {
		$plugin_profile_category['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories SET modified = :now, sort = :sort, name = :name, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_profile_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_profile_category['name']);
		$stmt->bindValue(':memo', $plugin_profile_category['memo']);
		$stmt->bindValue(':id',   $plugin_profile_category['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories VALUES(:id, :now1, :now2, :sort, :name, :memo)');
		$stmt->bindValue(':id',   $plugin_profile_category['id']);
		$stmt->bindValue(':now1', date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2', date('Y-m-d H:i:s'));
		$stmt->bindValue(':sort', $plugin_profile_category['sort'], PDO::PARAM_INT);
		$stmt->bindValue(':name', $plugin_profile_category['name']);
		$stmt->bindValue(':memo', $plugin_profile_category['memo']);
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
		freo_redirect('profile/admin_category?exec=update&id=' . $plugin_profile_category['id']);
	} else {
		freo_redirect('profile/admin_category?exec=insert');
	}

	return;
}

/* 管理画面 | カテゴリー一括編集 */
function freo_page_profile_admin_category_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/admin_category?error=1');
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

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories SET sort = :sort WHERE id = :id');
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
	freo_redirect('profile/admin_category?exec=sort');

	return;
}

/* 管理画面 | カテゴリー削除 */
function freo_page_profile_admin_category_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('profile/admin_category?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('profile/admin_category?error=1');
	}

	//カテゴリー削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('カテゴリーを削除しました。');

	//カテゴリー管理へ移動
	freo_redirect('profile/admin_category?exec=delete&id=' . $_GET['id']);

	return;
}

/* プロフィール表示 */
function freo_page_profile_default()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[1])) {
		$_GET['id'] = $freo->parameters[1];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	if ($_GET['id']) {
		//ユーザー取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users WHERE id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$user = $data;
		} else {
			freo_error('指定されたユーザーが見つかりません。', '404 Not Found');
		}

		//プロフィール取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles WHERE user_id = :id');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_profile = $data;
		} else {
			$plugin_profile = array();
		}

		//関連データ取得
		$user_associates = freo_associate_user('get', array($_GET['id']));
		$user_associate  = $user_associates[$_GET['id']];

		//ファイル取得
		$file_dir = FREO_FILE_DIR . 'plugins/profile_files/' . $_GET['id'] . '/';

		if (file_exists($file_dir)) {
			if ($dir = scandir($file_dir)) {
				foreach ($dir as $data) {
					if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
						$plugin_profile[$matches[1]] = $data;
					}
				}
			} else {
				freo_error('ファイル保存ディレクトリを開けません。');
			}
		}

		//プロフィールタグ取得
		if (!empty($plugin_profile['tag'])) {
			$plugin_profile_tags = explode(',', $plugin_profile['tag']);
		} else {
			$plugin_profile_tags = array();
		}

		//カテゴリー取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_profile_categories = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_profile_categories[$data['id']] = $data;
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'                     => freo_token('create'),
			'user'                      => $user,
			'user_associate'            => $user_associate,
			'plugin_profile'            => $plugin_profile,
			'plugin_profile_tags'       => $plugin_profile_tags,
			'plugin_profile_categories' => $plugin_profile_categories
		));

		//データ出力
		freo_output('internals/profile/default.html');
	} else {
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

		//プロフィール取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profiles ' . $condition . ' ORDER BY sort, kana, user_id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_profiles = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_profiles[$data['user_id']] = $data;
		}

		//プロフィールID取得
		$plugin_profile_keys = array_keys($plugin_profiles);

		//ファイル取得
		foreach ($plugin_profile_keys as $plugin_profile) {
			$file_dir = FREO_FILE_DIR . 'plugins/profile_files/' . $plugin_profile . '/';

			if (file_exists($file_dir)) {
				if ($dir = scandir($file_dir)) {
					foreach ($dir as $data) {
						if (is_file($file_dir . $data) and preg_match("/(\w+)\.\w+$/", $data, $matches)) {
							$plugin_profiles[$plugin_profile][$matches[1]] = $data;
						}
					}
				}
			}
		}

		//プロフィールタグ取得
		$plugin_profile_tags = array();
		foreach ($plugin_profile_keys as $plugin_profile) {
			if (!$plugin_profiles[$plugin_profile]['tag']) {
				continue;
			}

			$plugin_profile_tags[$plugin_profile] = explode(',', $plugin_profiles[$plugin_profile]['tag']);
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

		//カテゴリー取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_profile_categories ORDER BY sort, id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_profile_categories = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_profile_categories[$data['id']] = $data;
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'                     => freo_token('create'),
			'plugin_profiles'           => $plugin_profiles,
			'plugin_profile_tags'       => $plugin_profile_tags,
			'plugin_profile_categories' => $plugin_profile_categories,
			'users'                     => $users,
			'user_associates'           => $user_associates
		));
	}

	return;
}

?>
