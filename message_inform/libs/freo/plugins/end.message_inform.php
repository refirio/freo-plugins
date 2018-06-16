<?php

/*********************************************************************

 メッセージメール通知プラグイン (2011/12/30)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_message_inform()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author') {
		return;
	}

	$subject = '';
	$message = '';

	//登録内容取得
	$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'plugin_messages ORDER BY created DESC LIMIT 1');
	if (!$stmt) {
		freo_error($freo->pdo->errorInfo());
	}

	if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$message = $data;
	} else {
		freo_error('メッセージが見つかりません。');
	}

	//データ調整
	if ($freo->config['plugin']['message_inform']['message_length']) {
		$message['text'] = str_replace("\n", '', strip_tags($message['text']));
		$message['text'] = strlen($message['text']) > $freo->config['plugin']['message_inform']['message_length'] ? mb_strimwidth($message['text'], 0, $freo->config['plugin']['message_inform']['message_length'] - 3, '...', 'UTF-8') : $message['text'];
	}

	//メール件名定義
	$mail_subject = 'メッセージが登録されました';

	//メール本文定義
	$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/message_inform/header.txt');
	$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/message_inform/footer.txt');

	eval('$mail_header = "' . $mail_header . '";');
	eval('$mail_footer = "' . $mail_footer . '";');

	$mail_message  = $mail_header;
	$mail_message .= 'メッセージ：' . $message['text'] . "\n";
	$mail_message .= 'IPアドレス：' . $message['ip'] . "\n";
	$mail_message .= $mail_footer;

	//メール送信
	$flag = freo_mail($freo->config['plugin']['message_inform']['address'], $mail_subject, $mail_message);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	return;
}

?>
