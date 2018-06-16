<?php

/*********************************************************************

 エントリーメール投稿プラグイン (2011/05/13)

 Copyright(C) 2009-2011 freo.jp

*********************************************************************/

//外部ファイル読み込み
require_once FREO_MAIN_DIR . 'freo/internals/associate_entry.php';

/* メイン処理 */
function freo_page_entry_receive()
{
	global $freo;

	$mails = array();
	$mail  = 0;
	$trash = 0;

	//POPサーバーに接続
	$sock = fsockopen($freo->config['plugin']['entry_receive']['pop_server'], 110, $errno, $errstr, 30);
	if ($sock) {
		$buffer = fgets($sock, 512);
		if (substr($buffer, 0, 3) != '+OK') {
			freo_error($buffer);
		}

		//ログイン
		$buffer .= freo_page_entry_receive_sendcmd($sock, 'USER ' . $freo->config['plugin']['entry_receive']['pop_user']);
		$buffer .= freo_page_entry_receive_sendcmd($sock, 'PASS ' . $freo->config['plugin']['entry_receive']['pop_pwd']);

		if (preg_match('/\-ERR/', $buffer)) {
			freo_error('POPサーバーにログインできません。');
		}

		//メール件数取得
		if (preg_match('/^\+OK\s+(\d+)\s+\d+/', freo_page_entry_receive_sendcmd($sock, 'STAT'), $matches)) {
			$mail = $matches[1];
		} else {
			$mail = 0;
		}

		for ($i = 1; $i <= $mail; $i++) {
			//メールサイズ取得
			if (preg_match('/^\+OK\s+\d+\s+(\d+)/', freo_page_entry_receive_sendcmd($sock, 'LIST ' . $i), $matches)) {
				$size = $matches[1];
			} else {
				continue;
			}

			//メールサイズチェック
			if ($size > $freo->config['plugin']['entry_receive']['max_size'] * 1024) {
				$trash++;
			} else {
				//メール取得
				$buffer = freo_page_entry_receive_sendcmd($sock, 'RETR ' . $i);

				$mails[$i] = '';
				while (!preg_match("/^\.\r\n/", $buffer)) {
					$buffer = fgets($sock, 512);
					$mails[$i] .= $buffer;
				}
			}

			//メール削除
			freo_page_entry_receive_sendcmd($sock, 'DELE ' . $i);
		}

		//ログアウト
		freo_page_entry_receive_sendcmd($sock, 'QUIT');

		//接続終了
		fclose($sock);
	} else {
		freo_error('POPサーバーに接続できません。');
	}

	//投稿者情報
	$user_addresses = explode("\n", $freo->config['plugin']['entry_receive']['user_address']);

	$addresses = array();
	foreach ($user_addresses as $user_address) {
		list($user, $address) = explode(',', $user_address, 2);

		$addresses[$address] = $user;
	}

	//添付ファイル保存ディレクトリ
	$file_dir      = FREO_FILE_DIR . 'medias/' . ($freo->config['plugin']['entry_receive']['directory'] ? $freo->config['plugin']['entry_receive']['directory'] . '/' : '');
	$thumbnail_dir = FREO_FILE_DIR . 'media_thumbnails/' . ($freo->config['plugin']['entry_receive']['directory'] ? $freo->config['plugin']['entry_receive']['directory'] . '/' : '');

	//メール解析
	for ($i = 1; $i <= ($mail - $trash); $i++) {
		//ヘッダーと本文に分割
		list($mail_header, $mail_body) = explode("\r\n\r\n", $mails[$i], 2);

		//本文の不要なホワイトスペースを削除
		$mail_body = preg_replace("/\r\n[\t ]+/", ' ', $mail_body);

		//ヘッダー情報取得
		$headers = freo_page_entry_receive_header($mail_header);

		//メールアドレスチェック
		if (!in_array($headers['address'], array_keys($addresses))) {
			$trash++;

			continue;
		}

		if (preg_match("/Content-type:\s*multipart\//i", $mail_header) and preg_match("/boundary=\"*([^\"\r\n]+)\"*/i", $mail_header, $matches)) {
			//マルチパートならばバウンダリで分割して取得
			$parts = preg_split("/--$matches[1]-?-?\r\n/", $mail_body);
		} else {
			//シングルパートならば全体を取得
			$parts = array($mails[$i]);
		}

		$texts = array();
		$files = array();

		//各パート解析
		foreach ($parts as $part) {
			if (!preg_match("/\r\n\r\n/", $part)) {
				continue;
			}

			//マイムヘッダーと本文に分割
			list($mime_header, $body) = explode("\r\n\r\n", $part, 2);
			$body = preg_replace("/\r\n[\t ]+/", ' ', $body);

			//本文デコード
			if (preg_match("/Content-Type:\s*text\/plain/i", $mime_header)) {
				if (preg_match("/Content-Transfer-Encoding:\s*base64/i", $body)) {
					$body = base64_decode($body);
				}
				if (preg_match("/Content-Transfer-Encoding:\s*quoted-printable/i", $body)) {
					$body = quoted_printable_decode($body);
				}
				$body = mb_convert_encoding($body, 'UTF-8', 'auto');

				$texts[] = $body;
			}

			//添付ファイル名取得
			$mime_header = preg_replace("/filename\*\d\*=/", 'filename=', $mime_header);
			$mime_header = preg_replace("/'ja'/", '?B?', $mime_header);

			if (preg_match("/name=\"?([^\"\r\n]+)\"?/i", $mime_header, $matches)) {
				$filename = trim($matches[1]);

				if (preg_match('/(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)/i', $filename, $matches)) {
					$filename = $matches[1] . base64_decode($matches[2]) . $matches[3];
					$filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
					$filename = trim($filename);
				} elseif (preg_match('/(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)/i', $filename, $matches)) {
					$filename = $matches[1] . quoted_printable_decode($matches[2]) . $matches[3];
					$filename = mb_convert_encoding($filename, 'UTF-8', 'auto');
					$filename = trim($filename);
				}

				//添付ファイル保存
				if (preg_match("/Content-Transfer-Encoding:\s*base64/i", $mime_header)) {
					if (preg_match('/(.*)\.(.*)$/', $filename, $matches)) {
						$name = $matches[1];
						$ext  = $matches[2];
						$no   = '';
					} else {
						continue;
					}

					if ($freo->config['plugin']['entry_receive']['filename'] == 'datetime') {
						$name = date('YmdHis', strtotime($headers['date']));
					}

					while (file_exists($file_dir . $name . $no . '.' . $ext)) {
						if ($no == '') {
							$no = 2;
						} else {
							$no++;
						}
					}

					$file_name = urlencode($name . $no . '.' . $ext);

					if (!freo_mkdir($file_dir)) {
						freo_error('ディレクトリ ' . $file_dir . ' を作成できません。');
					}

					if ($fp = fopen($file_dir . $file_name, 'w')) {
						fwrite($fp, base64_decode($body));
						fclose($fp);

						chmod($file_dir . $file_name, 0606);
					} else {
						freo_error('添付ファイルを保存できません。');
					}

					if ($freo->config['media']['thumbnail']) {
						if (!freo_mkdir($thumbnail_dir)) {
							freo_error('ディレクトリ ' . $thumbnail_dir . ' を作成できません。');
						}

						freo_resize($file_dir . $file_name, $thumbnail_dir . $file_name, $freo->config['media']['thumbnail_width'], $freo->config['media']['thumbnail_height']);
					}
					if ($freo->config['media']['original']) {
						freo_resize($file_dir . $file_name, $file_dir . $file_name, $freo->config['media']['original_width'], $freo->config['media']['original_height']);
					}

					$files[] = $file_name;
				}
			}
		}

		//本文作成
		$text = '';
		foreach ($files as $file) {
			if ($text != '') {
				$text .= ' ';
			}

			list($file_width, $file_height, $file_size) = freo_file($file_dir . $file);

			if (file_exists($thumbnail_dir . $file)) {
				list($thumbnail_width, $thumbnail_height, $thumbnail_size) = freo_file($thumbnail_dir . $file);
			} else {
				$thumbnail_width  = 0;
				$thumbnail_height = 0;
				$thumbnail_size   = 0;
			}

			if ($thumbnail_width and $thumbnail_height) {
				$text .= '<a href="' . $freo->core['http_url'] . $file_dir . $file . '" class="thickbox"><img src="' . $freo->core['http_url'] . $thumbnail_dir . $file . '" alt="' . $file . '" width="' . $thumbnail_width . '" height="' . $thumbnail_height . '" /></a>';
			} elseif ($file_width and $file_height) {
				$text .= '<img src="' . $freo->core['http_url'] . $file_dir . $file . '" alt="' . $file . '" width="' . $file_width . '" height="' . $file_height . '" />';
			} else {
				$text .= '<a href="' . $freo->core['http_url'] . $file_dir . $file . '">' . $file . '</a>';
			}
		}

		if (!empty($texts)) {
			$text .= "\r\n\r\n";
			$text .= implode('', $texts);
			$text  = freo_unify($text);
		}
		$text = trim($text);
		$text = preg_replace("/\n*\.\n*$/", '', $text);

		//本文マークアップ
		if ($text != '') {
			$text = str_replace("\n", '<br />', $text);
			$text = preg_replace('/(<br \/>){2,}/', "</p>\n<p>", $text);
			$text = '<p>' . $text . '</p>';
		}

		//入力データ検証
		if ($headers['subject'] == '') {
			//$subject = 'No Subject';
			$subject = null;
		} else {
			$subject = $headers['subject'];
		}

		$subject = mb_strlen($subject, 'UTF-8') > 80 ? mb_substr($subject, 0, 80, 'UTF-8') : $subject;
		$text    = mb_strlen($text, 'UTF-8') > 50000 ? mb_substr($text, 0, 50000, 'UTF-8') : $text;

		$insert_flag = true;

		//本文追記
		if ($subject == null) {
			//最新データ取得
			$stmt = $freo->pdo->prepare('SELECT id, text FROM ' . FREO_DATABASE_PREFIX . 'entries ORDER BY id DESC');
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
				$entry = $data;
			} else {
				$entry = array();
			}

			if (!empty($entry)) {
				//本文作成
				$entry['text'] .= "\n<p><em>" . date('H:i', strtotime($headers['date'])) . "</em></p>\n" . $text;

				//データ登録
				$stmt = $freo->pdo->prepare('UPDATE ' . FREO_DATABASE_PREFIX . 'entries SET modified = :now, text = :text WHERE id = :id');
				$stmt->bindValue(':now',  date('Y-m-d H:i:s'));
				$stmt->bindValue(':text', $entry['text']);
				$stmt->bindValue(':id',   $entry['id'], PDO::PARAM_INT);
				$flag = $stmt->execute();
				if (!$flag) {
					freo_error($stmt->errorInfo());
				}

				$insert_flag = false;
			}
		}

		//新規登録
		if ($insert_flag) {
			//データ登録
			$stmt = $freo->pdo->prepare('INSERT INTO ' . FREO_DATABASE_PREFIX . 'entries VALUES(NULL, :user_id, :now1, :now2, :approved, NULL, NULL, :status, :display, :commentus, :trackback, NULL, :subject, :tag, :datetime, NULL, NULL, NULL, NULL, :text)');
			$stmt->bindValue(':user_id',   $addresses[$headers['address']]);
			$stmt->bindValue(':now1',      date('Y-m-d H:i:s'));
			$stmt->bindValue(':now2',      date('Y-m-d H:i:s'));
			$stmt->bindValue(':approved',  'yes');
			$stmt->bindValue(':status',    $freo->config['entry']['status']);
			$stmt->bindValue(':display',   $freo->config['entry']['display']);
			$stmt->bindValue(':commentus', $freo->config['comment']['accept_entry']);
			$stmt->bindValue(':trackback', $freo->config['trackback']['accept_entry']);
			$stmt->bindValue(':subject',   $subject);
			$stmt->bindValue(':tag',       $freo->config['plugin']['entry_receive']['tag']);
			$stmt->bindValue(':datetime',  $headers['date']);
			$stmt->bindValue(':text',      $text);
			$flag = $stmt->execute();
			if (!$flag) {
				freo_error($stmt->errorInfo());
			}

			//関連データ更新
			$categories = explode(',', $freo->config['plugin']['entry_receive']['category']);

			$category_set = array();
			foreach ($categories as $category) {
				$category_set[$category] = 1;
			}

			freo_associate_entry('post', array(
				'id'       => $freo->pdo->lastInsertId(),
				'category' => $category_set
			));
		}
	}

	//ログ記録
	if ($mail - $trash > 0) {
		freo_log(($mail - $trash) . '件のメール投稿を受け付けました。');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token'                      => freo_token('create'),
		'plugin_entry_receive_mail'  => $mail,
		'plugin_entry_receive_trash' => $trash
	));

	return;
}

/* コマンド送信 */
function freo_page_entry_receive_sendcmd($sock, $cmd)
{
	fputs($sock, "$cmd\r\n");

	$buffer = fgets($sock, 512);

	return $buffer;
}

/* ヘッダー情報取得 */
function freo_page_entry_receive_header($header)
{
	$headers = array();

	//メール送信日時取得
	if (preg_match('/\nDate:[ \t]*([^\r\n]+)/', $header, $matches)) {
		$headers['date'] = date('Y-m-d H:i:s', strtotime($matches[1]));
	} else {
		$headers['date'] = date('Y-m-d H:i:s');
	}

	//送信元メールアドレス取得
	if (preg_match('/\nFrom:[ \t]*([^\r\n]+)/', $header, $matches)) {
		$from = $matches[1];
	} elseif (preg_match('/\nReply-To:[ \t]*([^\r\n]+)/', $header, $matches)) {
		$from = $matches[1];
	} elseif (preg_match('/\nReturn-Path:[ \t]*([^\r\n]+)/', $header, $matches)) {
		$from = $matches[1];
	} else {
		$from = null;
	}

	$from = str_replace('"', '', $from);

	if (preg_match('/([\w\.\+\-]+@[\w\.\+\-]+)/', $from, $matches)) {
		$headers['address'] = $matches[1];
	} else {
		$headers['address'] = null;
	}

	//メール送信者名取得
	if (preg_match('/(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)/i', $from, $matches)) {
		$name = $matches[1] . base64_decode($matches[2]) . $matches[3];
	} elseif (preg_match('/(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)/i', $from, $matches)) {
		$name = $matches[1] . quoted_printable_decode($matches[2]) . $matches[3];
	} else {
		$name = null;
	}

	if ($name) {
		$headers['name'] = mb_convert_encoding($name, 'UTF-8', 'auto');
		$headers['name'] = trim(strip_tags($name));
	} else {
		$headers['name'] = null;
	}

	//メール件名取得
	if (preg_match('/\nSubject:[ \t]*([^\r\n]+)/', $header, $matches)) {
		$subject = $matches[1];
	} else {
		$subject = null;
	}

	if (preg_match('/(.*)=\?iso-2022-jp\?B\?([^?]+)\?=(.*)/i', $subject, $matches)) {
		$subject = $matches[1] . base64_decode($matches[2]) . $matches[3];
	} elseif (preg_match('/(.*)=\?iso-2022-jp\?Q\?([^?]+)\?=(.*)/i', $subject, $matches)) {
		$subject = $matches[1] . quoted_printable_decode($matches[2]) . $matches[3];
	} else {
		$subject = null;
	}

	if ($subject) {
		$headers['subject'] = trim(mb_convert_encoding($subject, 'UTF-8', 'auto'));
	} else {
		$headers['subject'] = null;
	}

	return $headers;
}

?>
