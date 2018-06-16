<?php

/*********************************************************************

 フォーム管理プラグイン (2015/03/21)

 Copyright(C) 2009-2015 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_form()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'setup':
			freo_page_form_setup();
			break;
		case 'setup_execute':
			freo_page_form_setup_execute();
			break;
		case 'admin':
			freo_page_form_admin();
			break;
		case 'admin_form':
			freo_page_form_admin_form();
			break;
		case 'admin_post':
			freo_page_form_admin_post();
			break;
		case 'admin_delete':
			freo_page_form_admin_delete();
			break;
		case 'admin_view':
			freo_page_form_admin_view();
			break;
		case 'admin_record':
			freo_page_form_admin_record();
			break;
		case 'admin_record_form':
			freo_page_form_admin_record_form();
			break;
		case 'admin_record_download':
			freo_page_form_admin_record_download();
			break;
		case 'admin_record_post':
			freo_page_form_admin_record_post();
			break;
		case 'admin_record_delete':
			freo_page_form_admin_record_delete();
			break;
		case 'send':
			freo_page_form_send();
			break;
		case 'complete':
			freo_page_form_complete();
			break;
		default:
			freo_page_form_default();
	}

	return;
}

/* セットアップ */
function freo_page_form_setup()
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
			freo_redirect('form/setup_execute?freo%5Btoken%5D=' . freo_token('create'), true);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_id'   => 'form',
		'plugin_name' => FREO_PLUGIN_FORM_NAME
	));

	//データ出力
	freo_output('plugins/setup.html');

	return;
}

/* セットアップ | セットアップ実行 */
function freo_page_form_setup_execute()
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
			'plugin_forms' => '(id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR(20) NOT NULL, name VARCHAR(255) NOT NULL, complete VARCHAR(255), count INT UNSIGNED NOT NULL, secure VARCHAR(20) NOT NULL, attachment VARCHAR(20) NOT NULL, mail VARCHAR(20) NOT NULL, mail_to TEXT, mail_cc TEXT, mail_bcc TEXT, mail_text TEXT, reply VARCHAR(20) NOT NULL, reply_subject VARCHAR(255), reply_name VARCHAR(255), reply_from VARCHAR(255), reply_text TEXT, record VARCHAR(20) NOT NULL, record_text TEXT, memo TEXT, PRIMARY KEY(id))',
			'plugin_form_records' => '(id INT UNSIGNED NOT NULL AUTO_INCREMENT, form_id VARCHAR(80) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, subject VARCHAR(255), name VARCHAR(255), mail VARCHAR(255), ip VARCHAR(80) NOT NULL, count INT UNSIGNED NOT NULL, header TEXT, body TEXT NOT NULL, memo TEXT, PRIMARY KEY(id))'
		);
	} else {
		$queries = array(
			'plugin_forms' => '(id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, status VARCHAR NOT NULL, name VARCHAR NOT NULL, complete VARCHAR, count INTEGER UNSIGNED NOT NULL, secure VARCHAR NOT NULL, attachment VARCHAR NOT NULL, mail VARCHAR NOT NULL, mail_to TEXT, mail_cc TEXT, mail_bcc TEXT, mail_text TEXT, reply VARCHAR NOT NULL, reply_subject VARCHAR, reply_name VARCHAR, reply_from VARCHAR, reply_text TEXT, record VARCHAR NOT NULL, record_text TEXT, memo TEXT, PRIMARY KEY(id))',
			'plugin_form_records' => '(id INTEGER, form_id VARCHAR NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, subject VARCHAR, name VARCHAR, mail VARCHAR, ip VARCHAR NOT NULL, count INTEGER UNSIGNED NOT NULL, header TEXT, body TEXT NOT NULL, memo TEXT, PRIMARY KEY(id))'
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
	freo_log(FREO_PLUGIN_FORM_NAME . 'のセットアップを実行しました。');

	//完了画面へ移動
	freo_redirect('form/setup?exec=setup', true);

	return;
}

/* 管理画面 | フォーム管理 */
function freo_page_form_admin()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//フォーム取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms ORDER BY id');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	$plugin_forms = array();
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_forms[$data['id']] = $data;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'        => freo_token('create'),
		'plugin_forms' => $plugin_forms
	));

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

/* 管理画面 | フォーム入力 */
function freo_page_form_admin_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//フォームID
			if ($_POST['plugin_form']['id'] == '') {
				$freo->smarty->append('errors', 'フォームIDが入力されていません。');
			} elseif (!preg_match('/^[\w\-\/]+$/', $_POST['plugin_form']['id'])) {
				$freo->smarty->append('errors', 'フォームIDは半角英数字で入力してください。');
			} elseif (preg_match('/^\d+$/', $_POST['plugin_form']['id'])) {
				$freo->smarty->append('errors', 'フォームIDには半角英字を含んでください。');
			} elseif (mb_strlen($_POST['plugin_form']['id'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'フォームIDは80文字以内で入力してください。');
			} elseif (!$_GET['id']) {
				$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id');
				$stmt->bindValue(':id', $_POST['plugin_form']['id']);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
					$freo->smarty->append('errors', '入力されたフォームIDはすでに使用されています。');
				}
			}

			//状態
			if ($_POST['plugin_form']['status'] == '') {
				$freo->smarty->append('errors', '状態が入力されていません。');
			}

			//フォームの名前
			if ($_POST['plugin_form']['name'] == '') {
				$freo->smarty->append('errors', 'フォームの名前が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_form']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'フォームの名前は80文字以内で入力してください。');
			}

			//送信完了時のリダイレクト先
			if (mb_strlen($_POST['plugin_form']['complete'], 'UTF-8') > 200) {
				$freo->smarty->append('errors', 'フォームの名前は200文字以内で入力してください。');
			}

			//送信数
			if (!preg_match('/^\d+$/', $_POST['plugin_form']['count'])) {
				$freo->smarty->append('errors', '送信数は半角数字で入力してください。');
			} elseif (mb_strlen($_POST['plugin_form']['count'], 'UTF-8') > 10) {
				$freo->smarty->append('errors', '送信数は10文字以内で入力してください。');
			}

			//SSLの使用
			if ($_POST['plugin_form']['secure'] == '') {
				$freo->smarty->append('errors', 'SSLの使用が入力されていません。');
			}

			//添付ファイル
			if ($_POST['plugin_form']['attachment'] == '') {
				$freo->smarty->append('errors', '添付ファイルが入力されていません。');
			}

			//メールの送信
			if ($_POST['plugin_form']['mail'] == '') {
				$freo->smarty->append('errors', 'メールの送信が入力されていません。');
			}

			if ($_POST['plugin_form']['mail'] == 'yes') {
				//送信先
				if ($_POST['plugin_form']['mail_to'] == '') {
					$freo->smarty->append('errors', 'メールの送信先が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['mail_to'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', 'メールの送信先は5000文字以内で入力してください。');
				} else {
					$mail_tos = explode("\n", $_POST['plugin_form']['mail_to']);

					foreach ($mail_tos as $mail_to) {
						if ($mail_to and !strpos($mail_to, '@')) {
							$freo->smarty->append('errors', 'メールの送信先の入力内容が正しくありません。');
						}
					}
				}

				//Cc
				if (mb_strlen($_POST['plugin_form']['mail_cc'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', 'メールのCcは5000文字以内で入力してください。');
				} else {
					$mail_ccs = explode("\n", $_POST['plugin_form']['mail_cc']);

					foreach ($mail_ccs as $mail_cc) {
						if ($mail_cc and !strpos($mail_cc, '@')) {
							$freo->smarty->append('errors', 'メールのCcの入力内容が正しくありません。');
						}
					}
				}

				//Bcc
				if (mb_strlen($_POST['plugin_form']['mail_bcc'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', 'メールのBccは5000文字以内で入力してください。');
				} else {
					$mail_bccs = explode("\n", $_POST['plugin_form']['mail_bcc']);

					foreach ($mail_bccs as $mail_bcc) {
						if ($mail_bcc and !strpos($mail_bcc, '@')) {
							$freo->smarty->append('errors', 'メールのBccの入力内容が正しくありません。');
						}
					}
				}

				//本文
				if ($_POST['plugin_form']['mail_text'] == '') {
					$freo->smarty->append('errors', 'メールの本文が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['mail_text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', 'メールの本文は5000文字以内で入力してください。');
				}
			}

			//自動返信メール
			if ($_POST['plugin_form']['reply'] == '') {
				$freo->smarty->append('errors', '自動返信メールが入力されていません。');
			}

			if ($_POST['plugin_form']['reply'] == 'yes') {
				//件名
				if ($_POST['plugin_form']['reply_subject'] == '') {
					$freo->smarty->append('errors', '自動返信メールの件名が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['reply_subject'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '自動返信メールの件名は80文字以内で入力してください。');
				}

				//送信者名
				if ($_POST['plugin_form']['reply_name'] == '') {
					$freo->smarty->append('errors', '自動返信メールの送信者名が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['reply_name'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '自動返信メールの送信者名は80文字以内で入力してください。');
				}

				//送信元アドレス
				if ($_POST['plugin_form']['reply_from'] == '') {
					$freo->smarty->append('errors', '自動返信メールの送信元アドレスが入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['reply_from'], 'UTF-8') > 80) {
					$freo->smarty->append('errors', '自動返信メールの送信元アドレスは80文字以内で入力してください。');
				} elseif (!strpos($_POST['plugin_form']['reply_from'], '@')) {
					$freo->smarty->append('errors', '自動返信メールの入力内容が正しくありません。');
				}

				//本文
				if ($_POST['plugin_form']['reply_text'] == '') {
					$freo->smarty->append('errors', '自動返信メールの本文が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['reply_text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '自動返信メールの本文は5000文字以内で入力してください。');
				}
			}

			//送信内容の記録
			if ($_POST['plugin_form']['record'] == '') {
				$freo->smarty->append('errors', '送信内容の記録が入力されていません。');
			}

			if ($_POST['plugin_form']['record'] == 'yes') {
				//本文
				if ($_POST['plugin_form']['record_text'] == '') {
					$freo->smarty->append('errors', '送信内容の記録の本文が入力されていません。');
				} elseif (mb_strlen($_POST['plugin_form']['record_text'], 'UTF-8') > 5000) {
					$freo->smarty->append('errors', '送信内容の記録の本文は5000文字以内で入力してください。');
				}
			}

			//フォームの説明
			if (mb_strlen($_POST['plugin_form']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', 'フォームの説明は5000文字以内で入力してください。');
			}

			//登録内容の確認
			if ($_POST['plugin_form']['mail'] == 'no' and $_POST['plugin_form']['reply'] == 'no' and $_POST['plugin_form']['record'] == 'no') {
				$freo->smarty->append('errors', 'メールの送信、メールの自動返信、送信内容の記録のいずれかを有効にしてください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_form = $_POST['plugin_form'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('form/admin_post?freo%5Btoken%5D=' . freo_token('create') . ($_GET['id'] ? '&id=' . $_GET['id'] : ''));
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id');
			$stmt->bindValue(':id', $_GET['id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_form = $data;
			} else {
				freo_error('指定されたフォームが見つかりません。', '404 Not Found');
			}
		} else {
			//新規データ設定
			$plugin_form = array(
				'count'       => 0,
				'secure'      => 'no',
				'attachment'  => 'no',
				'mail'        => 'yes',
				'mail_text'   => "以下のメッセージが送信されました。\n\n[\$message]\n\n- - - - -\n\nこのメールは自動で配信されています。",
				'record'      => 'no',
				'record_text' => "[\$message]",
				'reply'       => 'no'
			);
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_form' => $plugin_form
		)
	));

	return;
}

/* 管理画面 | フォーム登録 */
function freo_page_form_admin_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('form/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('form/admin?error=1');
	}

	//入力データ取得
	$form = $_SESSION['input']['plugin_form'];

	if ($form['complete'] == '') {
		$form['complete'] = null;
	}
	if ($form['mail_to'] == '') {
		$form['mail_to'] = null;
	}
	if ($form['mail_cc'] == '') {
		$form['mail_cc'] = null;
	}
	if ($form['mail_bcc'] == '') {
		$form['mail_bcc'] = null;
	}
	if ($form['mail_text'] == '') {
		$form['mail_text'] = null;
	}
	if ($form['reply_subject'] == '') {
		$form['reply_subject'] = null;
	}
	if ($form['reply_name'] == '') {
		$form['reply_name'] = null;
	}
	if ($form['reply_from'] == '') {
		$form['reply_from'] = null;
	}
	if ($form['reply_text'] == '') {
		$form['reply_text'] = null;
	}
	if ($form['record_text'] == '') {
		$form['record_text'] = null;
	}
	if ($form['memo'] == '') {
		$form['memo'] = null;
	}

	//データ登録
	if (isset($_GET['id'])) {
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_forms SET modified = :now, status = :status, name = :name, complete = :complete, count = :count, secure = :secure, attachment = :attachment, mail = :mail, mail_to = :mail_to, mail_cc = :mail_cc, mail_bcc = :mail_bcc, mail_text = :mail_text, reply = :reply, reply_subject = :reply_subject, reply_name = :reply_name, reply_from = :reply_from, reply_text = :reply_text, record = :record, record_text = :record_text, memo = :memo WHERE id = :id');
		$stmt->bindValue(':now',           date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',        $form['status']);
		$stmt->bindValue(':name',          $form['name']);
		$stmt->bindValue(':complete',      $form['complete']);
		$stmt->bindValue(':count',         $form['count'], PDO::PARAM_INT);
		$stmt->bindValue(':secure',        $form['secure']);
		$stmt->bindValue(':attachment',    $form['attachment']);
		$stmt->bindValue(':mail',          $form['mail']);
		$stmt->bindValue(':mail_to',       $form['mail_to']);
		$stmt->bindValue(':mail_cc',       $form['mail_cc']);
		$stmt->bindValue(':mail_bcc',      $form['mail_bcc']);
		$stmt->bindValue(':mail_text',     $form['mail_text']);
		$stmt->bindValue(':reply',         $form['reply']);
		$stmt->bindValue(':reply_subject', $form['reply_subject']);
		$stmt->bindValue(':reply_name',    $form['reply_name']);
		$stmt->bindValue(':reply_from',    $form['reply_from']);
		$stmt->bindValue(':reply_text',    $form['reply_text']);
		$stmt->bindValue(':record',        $form['record']);
		$stmt->bindValue(':record_text',   $form['record_text']);
		$stmt->bindValue(':memo',          $form['memo']);
		$stmt->bindValue(':id',            $form['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	} else {
		$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_forms VALUES(:id, :now1, :now2, :status, :name, :complete, :count, :secure, :attachment, :mail, :mail_to, :mail_cc, :mail_bcc, :mail_text, :reply, :reply_subject, :reply_name, :reply_from, :reply_text, :record, :record_text, :memo)');
		$stmt->bindValue(':id',            $form['id']);
		$stmt->bindValue(':now1',          date('Y-m-d H:i:s'));
		$stmt->bindValue(':now2',          date('Y-m-d H:i:s'));
		$stmt->bindValue(':status',        $form['status']);
		$stmt->bindValue(':name',          $form['name']);
		$stmt->bindValue(':complete',      $form['complete']);
		$stmt->bindValue(':count',         $form['count'], PDO::PARAM_INT);
		$stmt->bindValue(':secure',        $form['secure']);
		$stmt->bindValue(':attachment',    $form['attachment']);
		$stmt->bindValue(':mail',          $form['mail']);
		$stmt->bindValue(':mail_to',       $form['mail_to']);
		$stmt->bindValue(':mail_cc',       $form['mail_cc']);
		$stmt->bindValue(':mail_bcc',      $form['mail_bcc']);
		$stmt->bindValue(':mail_text',     $form['mail_text']);
		$stmt->bindValue(':reply',         $form['reply']);
		$stmt->bindValue(':reply_subject', $form['reply_subject']);
		$stmt->bindValue(':reply_name',    $form['reply_name']);
		$stmt->bindValue(':reply_from',    $form['reply_from']);
		$stmt->bindValue(':reply_text',    $form['reply_text']);
		$stmt->bindValue(':record',        $form['record']);
		$stmt->bindValue(':record_text',   $form['record_text']);
		$stmt->bindValue(':memo',          $form['memo']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	if (isset($_GET['id'])) {
		freo_log('フォームを編集しました。');
	} else {
		freo_log('フォームを新規に登録しました。');
	}

	//フォーム管理へ移動
	if (isset($_GET['id'])) {
		freo_redirect('form/admin?exec=update&id=' . $form['id']);
	} else {
		freo_redirect('form/admin?exec=insert');
	}

	return;
}

/* 管理画面 | フォーム削除 */
function freo_page_form_admin_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		freo_redirect('form/admin?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('form/admin?error=1');
	}

	//送信内容削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_form_records WHERE form_id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//フォーム削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//ログ記録
	freo_log('フォームを削除しました。');

	//フォーム管理へ移動
	freo_redirect('form/admin?exec=delete&id=' . $_GET['id']);

	return;
}

/* 管理画面 | フォーム確認 */
function freo_page_form_admin_view()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^[\w\-\/]+$/', $_GET['id'])) {
		freo_redirect('form/admin?error=1');
	}

	//フォーム取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_form = $data;
	} else {
		freo_error('指定されたフォームが見つかりません。', '404 Not Found');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'       => freo_token('create'),
		'plugin_form' => $plugin_form
	));

	return;
}

/* 管理画面 | 送信内容管理 */
function freo_page_form_admin_record()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['form_id']) or !preg_match('/^[\w\-\/]+$/', $_GET['form_id'])) {
		freo_redirect('form/admin?error=1');
	}
	if (!isset($_GET['page']) or !preg_match('/^\d+$/', $_GET['page']) or $_GET['page'] < 1) {
		$_GET['page'] = 1;
	}

	//フォーム取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :form_id');
	$stmt->bindValue(':form_id', $_GET['form_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_form = $data;
	} else {
		freo_error('指定されたフォームが見つかりません。', '404 Not Found');
	}

	//検索条件設定
	$condition = null;
	if (isset($_GET['word'])) {
		$condition .= ' AND (subject LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR name LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR mail LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR header LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ' OR body LIKE ' . $freo->pdo->quote('%' . $_GET['word'] . '%') . ')';
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

	//送信内容取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_form_records WHERE form_id = :form_id ' . $condition . ' ORDER BY id DESC LIMIT :start, :limit');
	$stmt->bindValue(':form_id', $_GET['form_id']);
	$stmt->bindValue(':start',   intval($freo->config['plugin']['form']['admin_limit']) * ($_GET['page'] - 1), PDO::PARAM_INT);
	$stmt->bindValue(':limit',   intval($freo->config['plugin']['form']['admin_limit']), PDO::PARAM_INT);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$plugin_form_records             = array();
	$plugin_form_record_show_subject = false;
	$plugin_form_record_show_name    = false;
	$plugin_form_record_show_mail    = false;
	$plugin_form_record_show_memo    = false;
	while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_form_records[$data['id']] = $data;

		if ($data['subject'] != null) {
			$plugin_form_record_show_subject = true;
		}
		if ($data['name'] != null) {
			$plugin_form_record_show_name = true;
		}
		if ($data['mail'] != null) {
			$plugin_form_record_show_mail = true;
		}
		if ($data['memo'] != null) {
			$plugin_form_record_show_memo = true;
		}
	}

	//送信内容数・ページ数取得
	$stmt = $freo->pdo->prepare('SELECT COUNT(*) FROM ' . FREO_DATABASE_PREFIX . 'plugin_form_records WHERE form_id = :form_id ' . $condition);
	$stmt->bindValue(':form_id', $_GET['form_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	$data                     = $stmt->fetch(PDO::FETCH_NUM);
	$plugin_form_record_count = $data[0];
	$plugin_form_record_page  = ceil($plugin_form_record_count / $freo->config['plugin']['form']['admin_limit']);

	//データ割当
	$freo->smarty->assign(array(
		'token'                           => freo_token('create'),
		'plugin_form'                     => $plugin_form,
		'plugin_form_records'             => $plugin_form_records,
		'plugin_form_record_show_subject' => $plugin_form_record_show_subject,
		'plugin_form_record_show_name'    => $plugin_form_record_show_name,
		'plugin_form_record_show_mail'    => $plugin_form_record_show_mail,
		'plugin_form_record_show_memo'    => $plugin_form_record_show_memo,
		'plugin_form_record_count'        => $plugin_form_record_count,
		'plugin_form_record_page'         => $plugin_form_record_page
	));

	return;
}

/* 管理画面 | 送信内容入力 */
function freo_page_form_admin_record_form()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('form/admin_record?error=1');
	}
	if (!isset($_GET['form_id']) or !preg_match('/^[\w\-\/]+$/', $_GET['form_id'])) {
		freo_redirect('form/admin_record?error=1');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//メモ
			if (mb_strlen($_POST['plugin_form_record']['memo'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_form_record = $_POST['plugin_form_record'];
		} else {
			$_SESSION['input'] = $_POST;

			//登録処理へ移動
			freo_redirect('form/admin_record_post?freo%5Btoken%5D=' . freo_token('create') . '&id=' . $_GET['id'] . '&form_id=' . $_GET['form_id']);
		}
	} else {
		if ($_GET['id']) {
			//編集データ取得
			$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_form_records WHERE id = :id AND form_id = :form_id');
			$stmt->bindValue(':id',      $_GET['id'], PDO::PARAM_INT);
			$stmt->bindValue(':form_id', $_GET['form_id']);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$plugin_form_record = $data;
			} else {
				freo_error('指定された送信内容が見つかりません。', '404 Not Found');
			}
		} else {
			//新規データ設定
			$plugin_form_record = array();
		}
	}

	//添付ファイル
	$plugin_form_files = array();
	$file_dir          = FREO_FILE_DIR . 'plugins/form_files/' . $plugin_form_record['id'] . '/';

	if (file_exists($file_dir)) {
		if ($dir = scandir($file_dir)) {
			foreach ($dir as $data) {
				if ($data == '.' or $data == '..') {
					continue;
				}
				if (is_file($file_dir . $data)) {
					$plugin_form_files[] = array(
						'name'     => $data,
						'filesize' => filesize($file_dir . $data)
					);
				}
			}
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'             => freo_token('create'),
		'plugin_form_files' => $plugin_form_files,
		'input' => array(
			'plugin_form_record' => $plugin_form_record
		)
	));

	return;
}

/* 管理画面 | 添付ファイルダウンロード */
function freo_page_form_admin_record_download()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('form/admin_record?error=1');
	}
	if (!isset($_GET['file']) or !preg_match('/^[\w\-\/\.]+$/', $_GET['file'])) {
		freo_redirect('form/admin_record?error=1');
	}

	//データ出力
	header('Content-Type: ' . freo_mime($_GET['file']));
	header('Content-Disposition: attachment; filename="' . $_GET['file'] . '"'); 
	header('Content-Length: '. filesize(FREO_FILE_DIR . 'plugins/form_files/' . $_GET['id'] . '/' . $_GET['file']));

	readfile(FREO_FILE_DIR . 'plugins/form_files/' . $_GET['id'] . '/' . $_GET['file']);

	return;
}

/* 管理画面 | 送信内容編集 */
function freo_page_form_admin_record_post()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('form/admin_record?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('form/admin_record?error=1');
	}

	//入力データ取得
	$form_record = $_SESSION['input']['plugin_form_record'];

	if ($form_record['memo'] == '') {
		$form_record['memo'] = null;
	}

	//データ登録
	$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_form_records SET modified = :now, memo = :memo WHERE id = :id AND form_id = :form_id');
	$stmt->bindValue(':now',     date('Y-m-d H:i:s'));
	$stmt->bindValue(':memo',    $form_record['memo']);
	$stmt->bindValue(':id',      $form_record['id'], PDO::PARAM_INT);
	$stmt->bindValue(':form_id', $form_record['form_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('送信内容を編集しました。');

	//送信内容管理へ移動
	freo_redirect('form/admin_record?exec=update&form_id=' . $form_record['form_id'] . '&id=' . $form_record['id']);

	return;
}

/* 管理画面 | 送信内容削除 */
function freo_page_form_admin_record_delete()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] != 'root' and $freo->user['authority'] != 'author') {
		freo_redirect('login', true);
	}

	//パラメータ検証
	if (!isset($_GET['id']) or !preg_match('/^\d+$/', $_GET['id']) or $_GET['id'] < 1) {
		freo_redirect('form/admin_record?error=1');
	}
	if (!isset($_GET['form_id']) or !preg_match('/^[\w\-\/]+$/', $_GET['form_id'])) {
		freo_redirect('form/admin_record?error=1');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('form/admin_record?error=1');
	}

	//添付ファイル削除
	freo_rmdir(FREO_FILE_DIR . 'plugins/form_files/' . $_GET['id'] . '/');

	//送信内容削除
	$stmt = $freo->pdo->prepare('DELETE FROM ' . FREO_DATABASE_PREFIX . 'plugin_form_records WHERE id = :id AND form_id = :form_id');
	$stmt->bindValue(':id',      $_GET['id'], PDO::PARAM_INT);
	$stmt->bindValue(':form_id', $_GET['form_id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	//連番リセット
	if (FREO_DATABASE_TYPE == 'mysql') {
		$stmt = $freo->pdo->query('ALTER TABLE ' . FREO_DATABASE_PREFIX . 'plugin_form_records AUTO_INCREMENT = 0');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}
	}

	//ログ記録
	freo_log('送信内容を削除しました。');

	//送信内容管理へ移動
	freo_redirect('form/admin_record?exec=delete&form_id=' . $_GET['form_id'] . '&id=' . $_GET['id']);

	return;
}

/* メール送信 */
function freo_page_form_send()
{
	global $freo;

	//パラメータ検証
	if (!isset($_GET['id']) and isset($freo->parameters[2])) {
		$_GET['id'] = $freo->parameters[2];
	}
	if (!isset($_GET['id']) or !preg_match('/^[\w\-]+$/', $_GET['id'])) {
		$_GET['id'] = null;
	}

	//フォーム取得
	$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id AND status = \'publish\'');
	$stmt->bindValue(':id', $_GET['id']);
	$flag = $stmt->execute();
	if (!$flag) {
		freo_error($stmt->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$plugin_form = $data;
	} else {
		freo_error('指定されたフォームが見つかりません。', '404 Not Found');
	}

	if (isset($_POST['plugin_form']['id'])) {
		//連続送信を拒否
		if (isset($_SESSION['plugin']['form']['time']) and $_SESSION['plugin']['form']['time'] > time() - $freo->config['plugin']['form']['duplicate_time']) {
			$freo->smarty->append('errors', array('id' => null, 'message' => $freo->config['plugin']['form']['duplicate_time'] . '秒以内の連続送信は禁止です。時間をおいてから送信してください。'));
		}

		//価格取得
		if (!empty($_POST['plugin_form']['__price'])) {
			$total = 0;
			foreach ($_POST['plugin_form']['__price'] as $id => $price) {
				$integer = 0;

				if (!isset($_POST['plugin_form'][$id])) {
					continue;
				}

				if (is_array($price)) {
					if (is_array($_POST['plugin_form'][$id])) {
						foreach ($_POST['plugin_form'][$id] as $value) {
							if (isset($price[$value])) {
								$integer = intval($price[$value]);

								if (!empty($_POST['plugin_form']['count'][$id][$value])) {
									$integer *= intval($_POST['plugin_form']['count'][$id][$value]);
								}

								$total += $integer;
							}
						}
					} else {
						if (isset($price[$_POST['plugin_form'][$id]])) {
							$integer = intval($price[$_POST['plugin_form'][$id]]);

							if (!empty($_POST['plugin_form']['count'][$id])) {
								$_POST['plugin_form'][$id] .= '（×' . $_POST['plugin_form']['count'][$id] . '）';

								$integer *= intval($_POST['plugin_form']['count'][$id]);
							}

							$total += $integer;
						}
					}
				} else {
					if ($_POST['plugin_form'][$id] != '') {
						$integer = intval($price);

						if (!empty($_POST['plugin_form']['count'][$id])) {
							$_POST['plugin_form'][$id] .= '（×' . $_POST['plugin_form']['count'][$id] . '）';

							$integer *= intval($_POST['plugin_form']['count'][$id]);
						}

						$total += $integer;
					}
				}
			}
			if (!empty($_POST['plugin_form']['set'])) {
				$total *= intval($_POST['plugin_form']['set']);
			}
			if (!empty($_POST['plugin_form']['price'])) {
				$_POST['plugin_form']['price'] += $total;
			} else {
				$_POST['plugin_form']['price'] = $total;
			}
		}

		//複数データ連結
		foreach ($_POST['plugin_form']['__label'] as $id => $label) {
			if (isset($_POST['plugin_form'][$id]) and is_array($_POST['plugin_form'][$id])) {
				if (isset($_POST['plugin_form']['__implode'][$id])) {
					$glue = $_POST['plugin_form']['__implode'][$id];
				} else {
					$glue = '';
				}

				$tmp = '';
				foreach ($_POST['plugin_form'][$id] as $data) {
					if (!empty($_POST['plugin_form']['count'][$id][$data])) {
						$data .= '（×' . $_POST['plugin_form']['count'][$id][$data] . '）';
					}

					if ($tmp == '') {
						$tmp = $data;
					} else {
						$tmp .= $glue . $data;
					}
				}

				$_POST['plugin_form'][$id] = $tmp;
			}
		}

		if ($plugin_form['attachment'] == 'yes' and !empty($_FILES['plugin_form']['tmp_name'])) {
			//アップロードデータファイル名重複チェック
			$names = array();
			$i     = 1;
			foreach (array_keys($_FILES['plugin_form']['tmp_name']) as $key) {
				if (empty($_FILES['plugin_form']['name'][$key])) {
					continue;
				}

				while (in_array($_FILES['plugin_form']['name'][$key], $names)) {
					$info = pathinfo($_FILES['plugin_form']['name'][$key]);

					$filename = preg_replace('/' . preg_quote('.' . $info['extension'], '/') . '$/', '', $_FILES['plugin_form']['name'][$key]);

					$_FILES['plugin_form']['name'][$key] = $filename . '-' . ++$i . '.' . $info['extension'];
				}

				$names[] = $_FILES['plugin_form']['name'][$key];
			}

			//アップロードデータ取得
			foreach (array_keys($_FILES['plugin_form']['tmp_name']) as $key) {
				if (is_uploaded_file($_FILES['plugin_form']['tmp_name'][$key])) {
					$_POST['plugin_form'][$key] = $_FILES['plugin_form']['name'][$key];
				} else {
					$_POST['plugin_form'][$key] = null;
				}
			}
		}

		//入力データ検証
		foreach ($_POST['plugin_form']['__label'] as $id => $label) {
			//必須チェック
			if (isset($_POST['plugin_form']['__require'][$id])) {
				if ($_POST['plugin_form']['__require'][$id] and (!isset($_POST['plugin_form'][$id]) or $_POST['plugin_form'][$id] == '')) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'が入力されていません。'));

					continue;
				}
			}

			//長さチェック
			if (isset($_POST['plugin_form']['__type'][$id])) {
				if ($_POST['plugin_form']['__type'][$id] and mb_strlen($_POST['plugin_form'][$id], 'UTF-8') > $freo->config['plugin']['form']['line_maxlength']) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'は' . $freo->config['plugin']['form']['line_maxlength'] . '文字以内で入力してください。'));

					continue;
				} elseif (mb_strlen($_POST['plugin_form'][$id], 'UTF-8') > $freo->config['plugin']['form']['text_maxlength']) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'は' . $freo->config['plugin']['form']['text_maxlength'] . '文字以内で入力してください。'));

					continue;
				}
			}

			//書式チェック
			if (isset($_POST['plugin_form']['__type'][$id]) and $_POST['plugin_form'][$id] != '') {
				if ($_POST['plugin_form']['__type'][$id] == 'numeric' and !preg_match('/^\d+$/', $_POST['plugin_form'][$id])) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'は半角数字で入力してください。'));

					continue;
				} elseif ($_POST['plugin_form']['__type'][$id] == 'alphabet' and !preg_match('/^[\w\-]+$/', $_POST['plugin_form'][$id])) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'は半角英数字で入力してください。'));

					continue;
				} elseif ($_POST['plugin_form']['__type'][$id] == 'mail' and !strpos($_POST['plugin_form'][$id], '@')) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $label . 'の入力内容が正しくありません。'));

					continue;
				}
			}

			//確認入力チェック
			if (isset($_POST['plugin_form']['confirm'][$id])) {
				if ($_POST['plugin_form'][$id] != $_POST['plugin_form']['confirm'][$id]) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $_POST['plugin_form']['__label'][$id] . 'の確認入力内容と一致しません。'));

					continue;
				}
			}
		}

		//必須ワードチェック
		if (isset($_POST['plugin_form']['__need'])) {
			foreach ($_POST['plugin_form']['__need'] as $id => $label) {
				if ($_POST['plugin_form'][$id] != $_POST['plugin_form']['__need'][$id]) {
					$freo->smarty->append('errors', array('id' => $id, 'message' => $_POST['plugin_form']['__label'][$id] . 'に必須ワードが入力されていません。'));

					continue;
				}
			}
		}

		//文字列追加
		if (!empty($_POST['plugin_form']['__begin'])) {
			foreach ($_POST['plugin_form']['__begin'] as $id => $begin) {
				if ($_POST['plugin_form'][$id] != '') {
					$_POST['plugin_form'][$id] = $begin . $_POST['plugin_form'][$id];
				}
			}
		}
		if (!empty($_POST['plugin_form']['__end'])) {
			foreach ($_POST['plugin_form']['__end'] as $id => $end) {
				if ($_POST['plugin_form'][$id] != '') {
					$_POST['plugin_form'][$id] = $_POST['plugin_form'][$id] . $end;
				}
			}
		}

		if ($plugin_form['attachment'] == 'yes' and !empty($_FILES['plugin_form']['name'])) {
			//アップロードデータ保存
			if (!$freo->smarty->get_template_vars('errors')) {
				$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/form_files/' . session_id() . '/';

				freo_rmdir($temporary_dir);

				if (!freo_mkdir($temporary_dir)) {
					freo_error('ディレクトリ ' . $temporary_dir . ' を作成できません。');
				}

				foreach ($_FILES['plugin_form']['tmp_name'] as $key => $value) {
					if (is_uploaded_file($_FILES['plugin_form']['tmp_name'][$key])) {
						if ($_FILES['plugin_form']['size'][$key] > $freo->config['plugin']['form']['attachment_maxsize'] * 1024) {
							$freo->smarty->append('errors', array('id' => $id, 'message' => $_FILES['plugin_form']['name'][$key] . ' のファイルサイズが大きすぎます。（1つ' . $freo->config['plugin']['form']['attachment_maxsize'] . 'KBまで。）'));

							continue;
						}

						if (!$freo->config['plugin']['form']['attachment_multibyte'] and !preg_match('/^[\w\.\~\-\&\#\+\=\;\@\%]+$/', $_FILES['plugin_form']['name'][$key])) {
							$freo->smarty->append('errors', array('id' => $id, 'message' => '添付ファイルの名前は半角英数字で入力してください。'));

							continue;
						}

						if (!preg_match('/\.(' . implode('|', explode(',', $freo->config['plugin']['form']['attachment_extension'])) . ')$/i', $_FILES['plugin_form']['name'][$key])) {
							$freo->smarty->append('errors', array('id' => $id, 'message' => '添付できるファイルの拡張子は ' . implode('、', explode(',', $freo->config['plugin']['form']['attachment_extension'])) . ' のみです。'));

							continue;
						}

						if (!freo_mkdir($temporary_dir . $key . '/')) {
							freo_error('ディレクトリ ' . $temporary_dir . $key . '/' . ' を作成できません。');
						}

						if (move_uploaded_file($_FILES['plugin_form']['tmp_name'][$key], $temporary_dir . $key . '/' . $_FILES['plugin_form']['name'][$key])) {
							chmod($temporary_dir . $key . '/' . $_FILES['plugin_form']['name'][$key], FREO_PERMISSION_FILE);
						} else {
							freo_error($_FILES['plugin_form']['name'][$key] . 'をアップロードできません。');
						}
					}
				}
			}

			//古いフォルダを削除
			$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/form_files/';

			if ($dir = scandir($temporary_dir)) {
				foreach ($dir as $data) {
					if (!is_dir($temporary_dir . $data) or $data == '.' or $data == '..') {
						continue;
					}
					if (filemtime($temporary_dir . $data) > time() - 60 * 10) {
						continue;
					}

					freo_rmdir($temporary_dir . $data . '/');
				}
			} else {
				freo_error('ディレクトリ ' . $config_dir . ' を開けません。');
			}
		}

		//代替文字設定
		if (!isset($_POST['plugin_form']['subject']) or $_POST['plugin_form']['subject'] == '') {
			$_POST['plugin_form']['subject'] = $freo->config['plugin']['form']['default_subject'];
		}
		if (!isset($_POST['plugin_form']['name']) or $_POST['plugin_form']['name'] == '') {
			$_POST['plugin_form']['name'] = $freo->config['plugin']['form']['default_name'];
		}
		if (!isset($_POST['plugin_form']['mail']) or $_POST['plugin_form']['mail'] == '') {
			$_POST['plugin_form']['mail'] = $freo->config['plugin']['form']['default_mail'];
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'       => freo_token('create'),
			'plugin_form' => $_POST['plugin_form']
		));

		//データ出力
		if ($freo->smarty->get_template_vars('errors')) {
			freo_output('plugins/form/error.html');
		} else {
			$_SESSION['input'] = $_POST;

			freo_output('plugins/form/preview.html');
		}
	} else {
		//入力データ確認
		if (empty($_SESSION['input'])) {
			freo_redirect('form');
		}

		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('contact');
		}

		//トランザクション開始
		$freo->pdo->beginTransaction();

		//入力データ取得
		$form = $_SESSION['input']['plugin_form'];

		//送信数カウント
		$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'plugin_forms SET count = count + 1 WHERE id = :id AND status = \'publish\'');
		$stmt->bindValue(':id', $form['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		//送信数取得
		$stmt = $freo->pdo->prepare('SELECT count FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id AND status = \'publish\'');
		$stmt->bindValue(':id', $form['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		$data                 = $stmt->fetch(PDO::FETCH_ASSOC);
		$plugin_catalog_count = $data['count'];

		$_SESSION['plugin']['form']['count'] = $plugin_catalog_count;

		//送信内容定義
		$message = '';
		foreach ($form['__label'] as $id => $label) {
			$message .= "■" . $label . "\n";
			$message .= (isset($form[$id]) ? $form[$id] : '') . "\n";
			$message .= "\n";
		}
		$message = trim($message);

		//メールヘッダ定義
		if ($plugin_form['mail'] == 'yes') {
			$headers = array(
				'From' => '"' . mb_encode_mimeheader(mb_convert_kana($form['name'], 'KV', 'UTF-8')) . '" <' . $form['mail'] . '>'
			);

			if ($plugin_form['mail_cc']) {
				$headers['Cc'] = str_replace("\n", ',', $plugin_form['mail_cc']);
			}
			if ($plugin_form['mail_bcc']) {
				$headers['Bcc'] = str_replace("\n", ',', $plugin_form['mail_bcc']);
			}
		} else {
			$headers = array();
		}

		//添付ファイル定義
		if ($plugin_form['attachment'] == 'yes') {
			$temporary_dir = FREO_FILE_DIR . 'temporaries/plugins/form_files/' . session_id() . '/';
		} else {
			$temporary_dir = null;
		}

		if ($plugin_form['attachment'] == 'yes') {
			$files = array();
			if ($plugin_form['attachment'] == 'yes') {
				foreach ($form['__label'] as $id => $label) {
					if (isset($form[$id]) and file_exists($temporary_dir . $id . '/' . $form[$id])) {
						$files[] = $temporary_dir . $id . '/' . $form[$id];
					}
				}
			}
		} else {
			$files = array();
		}

		//送信内容記録
		if ($plugin_form['record'] == 'yes') {
			//メール本文定義
			$body = str_replace('[$message]', $message, $plugin_form['record_text']);
			$body = str_replace('[$datetime]', date('Y-m-d H:i:s'), $body);
			$body = str_replace('[$ip]', $_SERVER['REMOTE_ADDR'], $body);
			$body = str_replace('[$count]', $plugin_catalog_count, $body);

			foreach ($form['__label'] as $id => $label) {
				$body = str_replace('[$input.' . $id . ']', (isset($form[$id]) ? $form[$id] : ''), $body);
			}

			//メールヘッダ定義
			$header = '';
			foreach ($headers as $key => $value) {
				$header .= $key . ': ' . $value . "\n";
			}

			//代替文字確認
			if ($form['subject'] == $freo->config['plugin']['form']['default_subject']) {
				$subject = null;
			} else {
				$subject = mb_strcut($form['subject'], 0, 255);
			}
			if ($form['name'] == $freo->config['plugin']['form']['default_name']) {
				$name = null;
			} else {
				$name = mb_strcut($form['name'], 0, 255);
			}
			if ($form['mail'] == $freo->config['plugin']['form']['default_mail']) {
				$mail = null;
			} else {
				$mail = mb_strcut($form['mail'], 0, 255);
			}

			//送信内容登録
			$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'plugin_form_records VALUES(NULL, :form_id, :now1, :now2, :subject, :name, :mail, :ip, :count, :header, :body, NULL)');
			$stmt->bindValue(':form_id', $form['id']);
			$stmt->bindValue(':now1',    date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',    date('Y-m-d H:i:s'));
			$stmt->bindValue(':subject', $subject);
			$stmt->bindValue(':name',    $name);
			$stmt->bindValue(':mail',    $mail);
			$stmt->bindValue(':ip',      $_SERVER['REMOTE_ADDR']);
			$stmt->bindValue(':count',   $_SESSION['plugin']['form']['count'], PDO::PARAM_INT);
			$stmt->bindValue(':header',  $header);
			$stmt->bindValue(':body',    $body);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($plugin_form['attachment'] == 'yes' and !empty($files)) {
				//添付ファイル保存
				$file_dir = FREO_FILE_DIR . 'plugins/form_files/' . $freo->pdo->lastInsertId() . '/';

				if (!freo_mkdir($file_dir)) {
					freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
				}

				foreach ($form['__label'] as $id => $label) {
					if (isset($form[$id]) and file_exists($temporary_dir . $id . '/' . $form[$id])) {
						copy($temporary_dir . $id . '/' . $form[$id], $file_dir . $form[$id]);
					}
				}
			}
		}

		//メール送信
		if ($plugin_form['mail'] == 'yes') {
			//メール本文定義
			$body = str_replace('[$message]', $message, $plugin_form['mail_text']);
			$body = str_replace('[$datetime]', date('Y-m-d H:i:s'), $body);
			$body = str_replace('[$ip]', $_SERVER['REMOTE_ADDR'], $body);
			$body = str_replace('[$count]', $plugin_catalog_count, $body);

			foreach ($form['__label'] as $id => $label) {
				$body = str_replace('[$input.' . $id . ']', (isset($form[$id]) ? $form[$id] : ''), $body);
			}

			//メール送信
			$flag = freo_mail(str_replace("\n", ',', $plugin_form['mail_to']), $form['subject'], $body, $headers, $files);
			if (!$flag) {
				freo_error('メールを送信できません。');
			}
		}

		//メール自動返信
		if ($plugin_form['reply'] == 'yes') {
			//メール本文定義
			$body = str_replace('[$message]', $message, $plugin_form['reply_text']);
			$body = str_replace('[$count]', $plugin_catalog_count, $body);

			foreach ($form['__label'] as $id => $label) {
				$body = str_replace('[$input.' . $id . ']', (isset($form[$id]) ? $form[$id] : ''), $body);
			}

			//メールヘッダ定義
			$headers = array(
				'From' => '"' . mb_encode_mimeheader(mb_convert_kana($plugin_form['reply_name'], 'KV', 'UTF-8')) . '" <' . $plugin_form['reply_from'] . '>'
			);

			//メール送信
			$flag = freo_mail($form['mail'], $plugin_form['reply_subject'], $body, $headers);
			if (!$flag) {
				freo_error('自動返信メールを送信できません。');
			}
		}

		//トランザクション終了
		$freo->pdo->commit();

		//アップロードデータ削除
		if ($plugin_form['attachment'] == 'yes') {
			freo_rmdir($temporary_dir);
		}

		//入力データ破棄
		$_SESSION['input'] = array();

		//送信日時
		$_SESSION['plugin']['form']['time'] = time();

		//ログ記録
		freo_log('メールを送信しました。');

		//送信完了画面へ移動
		if ($plugin_form['complete']) {
			$url = $plugin_form['complete'];
		} elseif ($plugin_form['secure'] == 'yes') {
			$url = $freo->core['https_file'] . '/form/complete';
		} else {
			$url = $freo->core['http_file'] . '/form/complete';
		}

		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"ja\" lang=\"ja\" dir=\"ltr\">\n";
		echo "<head>\n";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo "<title>送信完了</title>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "if (window == window.parent) {\n";
		echo "window.location.href = '" . $url . "';\n";
		echo "} else {\n";
		echo "window.parent.location.href = '" . $url . "';\n";
		echo "}\n";
		echo "</script>\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<p><a href=\"" . $url . "\">送信完了</a></p>\n";
		echo "</body>\n";
		echo "</html>\n";
	}

	return;
}

/* メール送信完了 */
function freo_page_form_complete()
{
	global $freo;

	//送信数取得
	$plugin_catalog_count = $_SESSION['plugin']['form']['count'];

	//データ割当
	$freo->smarty->assign(array(
		'token'             => freo_token('create'),
		'plugin_form_count' => $plugin_catalog_count
	));

	return;
}

/* フォーム表示 */
function freo_page_form_default()
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
		//フォーム取得
		$stmt = $freo->pdo->prepare('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE id = :id AND status = \'publish\'');
		$stmt->bindValue(':id', $_GET['id']);
		$flag = $stmt->execute();
		if (!$flag) {
			freo_error($stmt->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_form = $data;
		} else {
			freo_error('指定されたフォームが見つかりません。', '404 Not Found');
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'       => freo_token('create'),
			'plugin_form' => $plugin_form
		));

		if ($freo->smarty->template_exists('plugins/form/form/' . $_GET['id'] . '.html')) {
			//データ出力
			freo_output('plugins/form/form/' . $_GET['id'] . '.html');
		} else {
			//データ出力
			freo_output('plugins/form/form.html');
		}
	} else {
		//フォーム取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_forms WHERE status = \'publish\' ORDER BY id');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		$plugin_forms = array();
		while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$plugin_forms[$data['id']] = $data;
		}

		//データ割当
		$freo->smarty->assign(array(
			'token'        => freo_token('create'),
			'plugin_forms' => $plugin_forms
		));

		//データ出力
		freo_output('plugins/form/default.html');
	}

	return;
}

?>
