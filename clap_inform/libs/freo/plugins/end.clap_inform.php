<?php

/*********************************************************************

 拍手メール通知プラグイン (2011/12/30)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_clap_inform()
{
	global $freo;

	if (!$_POST['plugin_clap']['text']) {
		return;
	}

	//ログイン状態確認
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author') {
		return;
	}

	$subject = '';
	$message = '';

	//登録内容取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_claps ORDER BY created DESC LIMIT 1');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$clap = $data;
	} else {
		freo_error('メッセージが見つかりません。');
	}

	//データ調整
	if ($freo->config['plugin']['clap_inform']['message_length']) {
		$clap['text'] = str_replace("\n", '', strip_tags($clap['text']));
		$clap['text'] = strlen($clap['text']) > $freo->config['plugin']['clap_inform']['message_length'] ? mb_strimwidth($clap['text'], 0, $freo->config['plugin']['clap_inform']['message_length'] - 3, '...', 'UTF-8') : $clap['text'];
	}

	//メール件名定義
	$subject = '拍手メッセージが登録されました';

	//メール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/clap_inform/header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/clap_inform/footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	$message  = $mail_header;
	if ($clap['title']) {
		$message .= 'タイトル  ：' . $clap['title'] . "\n";
	}
	$message .= 'メッセージ：' . $clap['text'] . "\n";
	$message .= 'IPアドレス：' . $clap['ip'] . "\n";
	$message .= $mail_footer;

	//メール送信
	$flag = freo_mail($freo->config['plugin']['clap_inform']['address'], $subject, $message);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	return;
}

?>
