<?php

/*********************************************************************

 拍手送信プラグイン (2013/04/14)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_clap()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_clap_setup();
			break;
		case 'setup_execute':
			freo_page_clap_setup_execute();
			break;
		case 'admin':
			freo_page_clap_admin();
			break;
		case 'admin_text':
			freo_page_clap_admin_text();
			break;
		case 'admin_delete':
			freo_page_clap_admin_delete();
			break;
		case 'admin_thank':
			freo_page_clap_admin_thank();
			break;
		case 'admin_thank_form':
			freo_page_clap_admin_thank_form();
			break;
		case 'admin_thank_post':
			freo_page_clap_admin_thank_post();
			break;
		case 'admin_thank_update':
			freo_page_clap_admin_thank_update();
			break;
		case 'admin_thank_delete':
			freo_page_clap_admin_thank_delete();
			break;
		default:
			freo_page_clap_default();
	}

	return;
}

/* セットアップ */
function freo_page_clap_setup()
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
			freo_redirect('clap/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'clap',
		'plugin_name' => FREO_PLUGIN_CLAP_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_clap_setup_execute()
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
			'plugin_claps'       => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, created DATETIME NOT NULL, modified DATETIME NOT NULL, session VARCHAR(40) NOT NULL, title VARCHAR(255), ip VARCHAR(80) NOT NULL, text TEXT, PRIMARY KEY(id))',
			'plugin_clap_thanks' => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, target VARCHAR(20), status VARCHAR(20) NOT NULL, sort INT UNSIGNED NOT NULL, text TEXT NOT NULL, memo TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_claps'       => '(id INTEGER, created DATETIME NOT NULL, modified DATETIME NOT NULL, session VARCHAR NOT NULL, title VARCHAR, ip VARCHAR NOT NULL, text TEXT, PRIMARY KEY(id))',
			'plugin_clap_thanks' => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, target VARCHAR, status VARCHAR NOT NULL, sort INTEGER UNSIGNED NOT NULL, text TEXT NOT NULL, memo TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_CLAP_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('clap/setup?exec=setup', true);

	return;
}

/* 管理画面 | 拍手管理 */
function freo_page_clap_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['day']) or !preg_match('/^\d\d\d\d\d\d\d\d$/', $_GET['day'])) {
		$_GET['day'] = date('Ymd');
	}

	//拍手年月日取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(created, \'%Y-%m-%d\') AS day, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps GROUP BY day ORDER BY day DESC');
	} else {
		$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%Y-%m-%d\', created) AS day, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps GROUP BY day ORDER BY day DESC');
	}
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_clap_days = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_clap_days[] = $data;
	}

	//拍手時間取得
	if (isset($_GET['hour'])) {
		$plugin_clap_hours  = array();
		$plugin_clap_counts = array();
	} else {
		if (isset($_GET['title']) and $_GET['title'] != '') {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(created, \'%H\') AS hour, COUNT(*) AS count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND title = :title GROUP BY hour, session ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%H\', created) AS hour, COUNT(*) AS count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND title = :title GROUP BY hour, session ORDER BY id');
			}
			$stmt->bindValue(':day',   $_GET['day']);
			$stmt->bindValue(':title', $_GET['title']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} else {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT DATE_FORMAT(created, \'%H\') AS hour, COUNT(*) AS count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day GROUP BY hour, session ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT STRFTIME(\'%H\', created) AS hour, COUNT(*) AS count, session FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day GROUP BY hour, session ORDER BY id');
			}
			$stmt->bindValue(':day', $_GET['day']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		$plugin_clap_hours  = array();
		$plugin_clap_counts = array('count' => 0, 'session' => array());
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			if (isset($plugin_clap_hours[$data['hour']])) {
				$plugin_clap_hours[$data['hour']]['count'] += $data['count'];
				$plugin_clap_hours[$data['hour']]['session'][$data['session']] = true;
			} else {
				$plugin_clap_hours[$data['hour']] = array(
					'hour'    => $data['hour'],
					'count'   => $data['count'],
					'session' => array($data['session'] => true)
				);
			}

			$plugin_clap_counts['count'] += $data['count'];
			$plugin_clap_counts['session'][$data['session']] = true;
		}
	}

	//拍手タイトル取得
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->prepare('SELECT title, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND title IS NOT NULL GROUP BY title ORDER BY title');
	} else {
		$stmt = $freo->pdo->prepare('SELECT title, COUNT(*) AS count FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND title IS NOT NULL GROUP BY title ORDER BY title');
	}
	$stmt->bindValue(':day', $_GET['day']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_clap_titles = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_clap_titles[] = $data;
	}

	//拍手メッセージ取得
	if (isset($_GET['hour'])) {
		$plugin_clap_texts = array();
	} else {
		if (isset($_GET['title']) and $_GET['title'] != '') {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND title = :title AND text IS NOT NULL ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND title = :title AND text IS NOT NULL ORDER BY id');
			}
			$stmt->bindValue(':day',   $_GET['day']);
			$stmt->bindValue(':title', $_GET['title']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} else {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND text IS NOT NULL ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND text IS NOT NULL ORDER BY id');
			}
			$stmt->bindValue(':day', $_GET['day']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		$plugin_clap_texts = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_clap_texts[$data['id']] = $data;
		}
	}

	//拍手取得
	if (isset($_GET['hour'])) {
		if (isset($_GET['title']) and $_GET['title'] != '') {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND DATE_FORMAT(created, \'%H\') = :hour AND title = :title ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND STRFTIME(\'%H\', created) = :hour AND title = :title ORDER BY id');
			}
			$stmt->bindValue(':day',   $_GET['day']);
			$stmt->bindValue(':hour',  $_GET['hour']);
			$stmt->bindValue(':title', $_GET['title']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} else {
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE DATE_FORMAT(created, \'%Y%m%d\') = :day AND DATE_FORMAT(created, \'%H\') = :hour ORDER BY id');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE STRFTIME(\'%Y%m%d\', created) = :day AND STRFTIME(\'%H\', created) = :hour ORDER BY id');
			}
			$stmt->bindValue(':day',  $_GET['day']);
			$stmt->bindValue(':hour', $_GET['hour']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		$plugin_claps = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_claps[$data['id']] = $data;
		}
	} else {
		$plugin_claps = array();
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'              => freo_token('create'),
		'plugin_claps'       => $plugin_claps,
		'plugin_clap_days'   => $plugin_clap_days,
		'plugin_clap_hours'  => $plugin_clap_hours,
		'plugin_clap_counts' => $plugin_clap_counts,
		'plugin_clap_titles' => $plugin_clap_titles,
		'plugin_clap_texts'  => $plugin_clap_texts
	));

	return;
}

/* 管理画面 | メッセージ管理 */
function freo_page_clap_admin_text()
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

	//コメント取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE text IS NOT NULL ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':start', intval($freo->config['plugin']['clap']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit', intval($freo->config['plugin']['clap']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_clap_texts = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_clap_texts[$data['id']] = $data;
	}

	//コメント数・ページ数取得
	$stmt = $freo->pdo->query('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE text IS NOT NULL');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$data                   = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_clap_text_count = $data[0];
	$plugin_clap_text_page  = ceil($plugin_clap_text_count / $freo->config['plugin']['clap']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                  => freo_token('create'),
		'plugin_clap_texts'      => $plugin_clap_texts,
		'plugin_clap_text_count' => $plugin_clap_text_count,
		'plugin_clap_text_page'  => $plugin_clap_text_page
	));

	return;
}

/* 管理画面 | 拍手削除 */
function freo_page_clap_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('clap/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('clap/admin?error=1');
	}

	//拍手削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_claps AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('拍手を削除しました。');

	//お礼管理へ移動
	freo_redirect('clap/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | お礼管理 */
function freo_page_clap_admin_thank()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//お礼取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks ORDER BY sort, id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_clap_thanks = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_clap_thanks[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'              => freo_token('create'),
		'plugin_clap_thanks' => $plugin_clap_thanks
	));

	return;
}

/* 管理画面 | お礼入力 */
function freo_page_clap_admin_thank_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = 0;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//並び順取得
		if ($_POST['plugin_clap_thank']['sort'] != '') {
			$_POST['plugin_clap_thank']['sort'] = mb_convert_kana($_POST['plugin_clap_thank']['sort'], 'n', 'UTF-8');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//お礼ID
			if ($_POST['plugin_clap_thank']['id'] == '') {
				$freo->smarty->append('errors', 'お礼IDが入力されていません。');
			} elseif (!preg_match('/^[\w\-\/]+$/', $_POST['plugin_clap_thank']['id'])) {
				$freo->smarty->append('errors', 'お礼IDは半角英数字で入力してください。');
			} elseif (preg_match('/^\d+$/', $_POST['plugin_clap_thank']['id'])) {
				$freo->smarty->append('errors', 'お礼IDには半角英字を含んでください。');
			} elseif (mb_strlen($_POST['plugin_clap_thank']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'お礼IDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_clap_thank']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力されたメニューIDはすでに使用されています。');
				}
			}

			//状態
			if ($_POST['plugin_clap_thank']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//並び順
			if ($_POST['plugin_clap_thank']['sort'] == '') {
				$freo->smarty->append('errors', '並び順が入力されていません。');
			} elseif (!preg_match('/^[\d]+$/', $_POST['plugin_clap_thank']['sort'])) {
				$freo->smarty->append('errors', '並び順は半角英数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_clap_thank']['sort'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '並び順は10文字以内で入力してください。');
			}

			//本文
			if ($_POST['plugin_clap_thank']['text'] == '') {
				$freo->smarty->append('errors', '本文が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_clap_thank']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}

			//説明
			if (mb_strlen($_POST['plugin_clap_thank']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '説明は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_clap_thank = $_POST['plugin_clap_thank'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('clap/admin_thank_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_clap_thank = $data;
			} else {
				freo_error('指定されたお礼が見つかりません。', '404 Not Found');
			}
		} else {
			//並び順初期値取得
			$stmt = $freo->pdo->query('SELECT MAX(sort) FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks');
			if (!$stmt) {
				freo_error($freo->pdo->errorInfo());
			}
			$data = $stmt->fetch(PDO::FETCH_NUM);
			$sort = $data[0] + 1;

			//新規データ設定
			$plugin_clap_thank = array(
				'sort' => $sort
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_clap_thank' => $plugin_clap_thank
		)
	));

	return;
}

/* 管理画面 | お礼登録 */
function freo_page_clap_admin_thank_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('clap/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('clap/admin?error=1');
	}

	//入力データ取得
	$clap_thank = $_SESSION['input']['plugin_clap_thank'];

	if ($clap_thank['target'] == '') {
		$clap_thank['target'] = null;
	}
	if ($clap_thank['memo'] == '') {
		$clap_thank['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks SET modified = :now, target = :target, status = :status, sort = :sort, text = :text, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':target', $clap_thank['target']);
		$stmt->bindValue(':status', $clap_thank['status']);
		$stmt->bindValue(':sort',   $clap_thank['sort']);
		$stmt->bindValue(':text',   $clap_thank['text']);
		$stmt->bindValue(':memo',   $clap_thank['memo']);
		$stmt->bindValue(':id',     $clap_thank['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks VALUES(:id, :now1, :now2, :target, :status, :sort, :text, :memo)');
		$stmt->bindValue(':id',     $clap_thank['id']);
		$stmt->bindValue(':now1',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',   date('Y-m-d H:i:s'));
		$stmt->bindValue(':target', $clap_thank['target']);
		$stmt->bindValue(':status', $clap_thank['status']);
		$stmt->bindValue(':sort',   $clap_thank['sort']);
		$stmt->bindValue(':text',   $clap_thank['text']);
		$stmt->bindValue(':memo',   $clap_thank['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('お礼を編集しました。');
	} else {
		freo_log('お礼を新規に登録しました。');
	}

	//お礼管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('clap/admin_thank?exec=update&id=' . $clap_thank['id']);
	} else {
		freo_redirect('clap/admin_thank?exec=insert');
	}

	return;
}

/* 管理画面 | お礼一括編集 */
function freo_page_clap_admin_thank_update()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('clap/admin_thank?error=1');
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

			$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks SET sort = :sort WHERE id = :id');
			$stmt->bindValue(':sort', $sort, PDO::PARAM_INT);
			$stmt->bindValue(':id',   $id);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}
	}

	//ログ記録
	freo_log('お礼を並び替えました。');

	//お礼管理へ移動
	freo_redirect('clap/admin_thank?exec=sort');

	return;
}

/* 管理画面 | お礼削除 */
function freo_page_clap_admin_thank_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		freo_redirect('clap/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('clap/admin?error=1');
	}

	//お礼削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('お礼を削除しました。');

	//お礼管理へ移動
	freo_redirect('clap/admin_thank?exec=delete&id=' . $_GET['id']);

	return;
}

/* お礼表示 */
function freo_page_clap_default()
{
	global $freo;

	//投稿データ取得
	if (!isset($_POST['plugin_clap']['id']) and isset($_GET['plugin_clap']['id'])) {
		$_POST['plugin_clap']['id'] = $_GET['plugin_clap']['id'];
	}
	if (!isset($_POST['plugin_clap']['title']) and isset($_GET['plugin_clap']['title'])) {
		$_POST['plugin_clap']['title'] = $_GET['plugin_clap']['title'];
	}
	if (isset($_POST['plugin_clap']['option'])) {
		foreach ($_POST['plugin_clap']['option'] as $key => $value) {
			if ($value == '') {
				continue;
			}
			if (isset($_POST['plugin_clap']['label'][$key])) {
				$label = $_POST['plugin_clap']['label'][$key];
			} else {
				$label = $key . '=';
			}

			if (isset($_POST['plugin_clap']['text']) and $_POST['plugin_clap']['text'] != '') {
				$_POST['plugin_clap']['text'] .= "\n";
			}
			$_POST['plugin_clap']['text'] .= $label . $value;
		}
	}

	if (!isset($_POST['plugin_clap']['title']) or $_POST['plugin_clap']['title'] == '') {
		$_POST['plugin_clap']['title'] = null;
	}
	if (!isset($_POST['plugin_clap']['text']) or $_POST['plugin_clap']['text'] == '') {
		$_POST['plugin_clap']['text'] = null;
	}

	//ワンタイムトークン確認
	if (!freo_token('check') and $_POST['plugin_clap']['text'] != '') {
		$freo->smarty->append('errors', '不正なアクセスです。');
	}

	//入力データ検証
	if (isset($_POST['plugin_clap']['text']) and mb_strlen($_POST['plugin_clap']['text'], 'UTF-8') > 5000) {
		$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
	}

	$plugin_clap_thank = array();

	if (!$freo->smarty->get_template_vars('errors')) {
		//データ登録
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_claps VALUES(NULL, :now1, :now2, :session, :title, :ip, :text)');
		$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
		$stmt->bindValue(':session', session_id());
		$stmt->bindValue(':title',   $_POST['plugin_clap']['title']);
		$stmt->bindValue(':ip',      $_SERVER['REMOTE_ADDR']);
		$stmt->bindValue(':text',    $_POST['plugin_clap']['text']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//送信回数設定
		if (!isset($_SESSION['clap']['count'])) {
			$_SESSION['clap']['count'] = 1;
		}

		if (isset($_POST['plugin_clap']['id']) and $_POST['plugin_clap']['id'] != '') {
			//お礼取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE id = :id');
			$stmt->bindValue(':id', $_POST['plugin_clap']['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} elseif ($freo->config['plugin']['clap']['thank_order'] == 'random') {
			//お礼取得
			if (FREO_DATABASE_TYPE == 'mysql') {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE target IS NULL OR target = :agent ORDER BY RAND() LIMIT 1');
			} else {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE target IS NULL OR target = :agent ORDER BY RANDOM() LIMIT 1');
			}
			$stmt->bindValue(':agent', $freo->agent['type']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		} else {
			//お礼数取得
			$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE target IS NULL OR target = :agent');
			$stmt->bindValue(':agent', $freo->agent['type']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			$data                  = $stmt->fetch(PDO::FETCH_NUM);
			$plugin_bookmark_count = $data[0];

			//お礼取得
			if ($_SESSION['clap']['count'] >= $plugin_bookmark_count) {
				$page = $plugin_bookmark_count;
			} else {
				$page = $_SESSION['clap']['count'];
			}

			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_clap_thanks WHERE target IS NULL OR target = :agent ORDER BY sort, id LIMIT :start, 1');
			$stmt->bindValue(':agent', $freo->agent['type']);
			$stmt->bindValue(':start', intval($page - 1), PDO::PARAM_INT);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_clap_thank = $data;
		}

		//送信回数カウント
		$_SESSION['clap']['count']++;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'             => freo_token('create'),
		'plugin_clap_thank' => $plugin_clap_thank
	));

	if ($freo->smarty->get_template_vars('errors')) {
		//データ出力
		freo_output('plugins/clap/error.html');
	}

	return;
}

?>
