<?php

/*********************************************************************

 メール通知プラグイン (2011/12/30)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_end_inform()
{
	global $freo;

	//ログイン状態確認
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author') {
		return;
	}

	$subject = '';
	$message = '';

	//コメント登録通知
	if ($_REQUEST['freo']['mode'] == 'comment' and $freo->config['plugin']['inform']['comment']) {
		//登録内容取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'comments ORDER BY created DESC LIMIT 1');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$comment = $data;
		} else {
			freo_error('コメントが見つかりません。');
		}

		//データ調整
		if ($freo->config['plugin']['inform']['message_length']) {
			$comment['text'] = str_replace("\n", '', strip_tags($comment['text']));
			$comment['text'] = strlen($comment['text']) > $freo->config['plugin']['inform']['message_length'] ? mb_strimwidth($comment['text'], 0, $freo->config['plugin']['inform']['message_length'] - 3, '...', 'UTF-8') : $comment['text'];
		}

		//メール件名定義
		$subject = 'コメントが登録されました';

		//メール本文定義
		$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/comment_header.txt');
		$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/comment_footer.txt');

		eval('$mail_header = "' . $mail_header . '";');
		eval('$mail_footer = "' . $mail_footer . '";');

		$message  = $mail_header;
		if ($comment['user_id']) {
			$message .= 'ユーザーID：' . $comment['user_id'] . "\n";
		} else {
			$message .= '名前      ：' . $comment['name'] . "\n";
		}
		$message .= '本文      ：' . $comment['text'] . "\n";
		$message .= 'IPアドレス：' . $comment['ip'] . "\n";
		if ($comment['entry_id']) {
			$message .= '記事を表示：' . $freo->core['http_file'] . '/view/' . $comment['entry_id'] . "\n";
		} else {
			$message .= '記事を表示：' . $freo->core['http_file'] . '/page/' . $comment['page_id'] . "\n";
		}
		$message .= $mail_footer;
	}

	//トラックバック登録通知
	if ($_REQUEST['freo']['mode'] == 'trackback' and $freo->config['plugin']['inform']['trackback']) {
		//登録内容取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'trackbacks ORDER BY created DESC LIMIT 1');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$trackback = $data;
		} else {
			freo_error('トラックバックが見つかりません。');
		}

		//データ調整
		if ($freo->config['plugin']['inform']['message_length']) {
			$trackback['text'] = str_replace("\n", '', strip_tags($trackback['text']));
			$trackback['text'] = strlen($trackback['text']) > $freo->config['plugin']['inform']['message_length'] ? mb_strimwidth($trackback['text'], 0, $freo->config['plugin']['inform']['message_length'] - 3, '...', 'UTF-8') : $trackback['text'];
		}

		//メール件名定義
		$subject = 'トラックバックが登録されました';

		//メール本文定義
		$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/trackback_header.txt');
		$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/trackback_footer.txt');

		eval('$mail_header = "' . $mail_header . '";');
		eval('$mail_footer = "' . $mail_footer . '";');

		$message  = $mail_header;
		$message .= '名前      ：' . $trackback['name'] . "\n";
		$message .= 'タイトル  ：' . $trackback['title'] . "\n";
		$message .= '本文      ：' . $trackback['text'] . "\n";
		$message .= 'IPアドレス：' . $trackback['ip'] . "\n";
		if ($trackback['entry_id']) {
			$message .= '記事を表示：' . $freo->core['http_file'] . '/view/' . $trackback['entry_id'] . "\n";
		} else {
			$message .= '記事を表示：' . $freo->core['http_file'] . '/page/' . $trackback['page_id'] . "\n";
		}
		$message .= $mail_footer;
	}

	//ユーザー登録通知
	if ($_REQUEST['freo']['mode'] == 'regist' and $freo->config['plugin']['inform']['regist']) {
		//登録内容取得
		$stmt = $freo->pdo->query('SELECT * FROM ' . FREO_DATABASE_PREFIX . 'users ORDER BY created DESC LIMIT 1');
		if (!$stmt) {
			freo_error($freo->pdo->errorInfo());
		}

		if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$user = $data;
		} else {
			freo_error('ユーザーが見つかりません。');
		}

		//データ調整
		if ($freo->config['plugin']['inform']['message_length']) {
			$user['text'] = str_replace("\n", '', strip_tags($user['text']));
			$user['text'] = strlen($user['text']) > $freo->config['plugin']['inform']['message_length'] ? mb_strimwidth($user['text'], 0, $freo->config['plugin']['inform']['message_length'] - 3, '...', 'UTF-8') : $user['text'];
		}

		//メール件名定義
		$subject = 'ユーザーが登録されました';

		//メール本文定義
		$mail_header = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/regist_header.txt');
		$mail_footer = file_get_contents(FREO_MAIL_DIR . 'plugins/inform/regist_footer.txt');

		eval('$mail_header = "' . $mail_header . '";');
		eval('$mail_footer = "' . $mail_footer . '";');

		$message  = $mail_header;
		$message .= 'ユーザーID：' . $user['id'] . "\n";
		$message .= '名前      ：' . $user['name'] . "\n";
		$message .= '紹介文    ：' . $user['text'] . "\n";
		$message .= 'IPアドレス：' . $user['ip'] . "\n";
		$message .= $mail_footer;
	}

	//メール送信
	$flag = freo_mail($freo->config['plugin']['inform']['address'], $subject, $message);
	if (!$flag) {
		freo_error('メールを送信できません。');
	}

	return;
}

?>
