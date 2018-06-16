<?php

/*********************************************************************

 メール送信プラグイン (2011/02/12)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_contact()
{
	global $freo;

	switch ($_REQUEST['freo']['work']) {
		case 'preview':
			freo_page_contact_preview();
			break;
		case 'send':
			freo_page_contact_send();
			break;
		case 'complete':
			freo_page_contact_complete();
			break;
		default:
			freo_page_contact_default();
	}

	return;
}

/* 入力内容確認 */
function freo_page_contact_preview()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('contact');
	}

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			freo_redirect('contact');
		}

		//登録処理へ移動
		freo_redirect('contact/send?freo%5Btoken%5D=' . freo_token('create'));
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'          => freo_token('create'),
		'plugin_contact' => $_SESSION['input']['plugin_contact']
	));

	return;
}

/* メール送信 */
function freo_page_contact_send()
{
	global $freo;

	//入力データ確認
	if (empty($_SESSION['input'])) {
		freo_redirect('contact');
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('contact');
	}

	//入力データ取得
	$contact = $_SESSION['input']['plugin_contact'];

	//メールヘッダを定義
	$headers = array(
		'From' => '"' . mb_encode_mimeheader(mb_convert_kana($contact['name'], 'KV', 'UTF-8')) . '" <' . $contact['mail'] . '>'
	);
	if ($contact['copy']) {
		$headers['Cc'] = $contact['mail'];
	}

	//メール送信
	$flag = freo_mail($freo->config['plugin']['contact']['address'], $contact['subject'], $contact['text'], $headers);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	//入力データ破棄
	$_SESSION['input'] = array();

	//ログ記録
	freo_log('メールを送信しました。');

	//登録完了画面へ移動
	freo_redirect('contact/complete');

	return;
}

/* メール送信完了 */
function freo_page_contact_complete()
{
	global $freo;

	return;
}

/* メール入力 */
function freo_page_contact_default()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//ワンタイムトークン確認
		if (!freo_token('check')) {
			$freo->smarty->append('errors', '不正なアクセスです。');
		}

		//コピーメール送信データ初期化
		if (!isset($_POST['plugin_contact']['copy'])) {
			$_POST['plugin_contact']['copy'] = null;
		}

		//入力データ検証
		if (!$freo->smarty->get_template_vars('errors')) {
			//件名
			if ($_POST['plugin_contact']['subject'] == '') {
				$freo->smarty->append('errors', '件名が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_contact']['subject'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '件名は80文字以内で入力してください。');
			}

			//名前
			if ($_POST['plugin_contact']['name'] == '') {
				$freo->smarty->append('errors', '名前が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_contact']['name'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', '名前は80文字以内で入力してください。');
			}

			//メールアドレス
			if ($_POST['plugin_contact']['mail'] == '') {
				$freo->smarty->append('errors', 'メールアドレスが入力されていません。');
			} elseif (!strpos($_POST['plugin_contact']['mail'], '@')) {
				$freo->smarty->append('errors', 'メールアドレスの入力内容が正しくありません。');
			} elseif (mb_strlen($_POST['plugin_contact']['mail'], 'UTF-8') > 80) {
				$freo->smarty->append('errors', 'メールアドレスは80文字以内で入力してください。');
			}

			//本文
			if ($_POST['plugin_contact']['text'] == '') {
				$freo->smarty->append('errors', '本文が入力されていません。');
			} elseif (mb_strlen($_POST['plugin_contact']['text'], 'UTF-8') > 5000) {
				$freo->smarty->append('errors', '本文は5000文字以内で入力してください。');
			}
		}

		//エラー確認
		if ($freo->smarty->get_template_vars('errors')) {
			//エラー表示
			$plugin_contact = $_POST['plugin_contact'];
		} else {
			$_SESSION['input'] = $_POST;

			if (isset($_POST['preview'])) {
				//プレビューへ移動
				freo_redirect('contact/preview');
			} else {
				//登録処理へ移動
				freo_redirect('contact/send?freo%5Btoken%5D=' . freo_token('create'));
			}
		}
	} else {
		if (!empty($_GET['session']) and !empty($_SESSION['input'])) {
			//入力データ復元
			$plugin_contact = $_SESSION['input']['plugin_contact'];
		} else {
			//新規データ設定
			$plugin_contact = array();
		}
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create'),
		'input' => array(
			'plugin_contact' => $plugin_contact
		)
	));

	return;
}

?>
