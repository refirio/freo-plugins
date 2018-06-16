<?php

/*********************************************************************

 トラックバックスパム対策プラグイン (2012/10/09)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_trackback_spamfilter()
{
	global $freo;

	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return;
	}

	$errors = array();

	if ($freo->config['plugin']['trackback_spamfilter']['deny_url']) {
		foreach (explode(',', $freo->config['plugin']['trackback_spamfilter']['deny_url']) as $url) {
			if (preg_match('/' . preg_quote($url, '/') . '/', $_POST['url'])) {
				$errors[] = 'Forbidden URL (url)';
			}
		}
	}
	if ($freo->config['plugin']['trackback_spamfilter']['deny_word']) {
		foreach (explode(',', $freo->config['plugin']['trackback_spamfilter']['deny_word']) as $word) {
			if (preg_match('/' . preg_quote($word, '/') . '/', $_POST['excerpt'])) {
				$errors[] = $word . ' is NG Word (excerpt)';
			}
		}
	}
	if ($freo->config['plugin']['trackback_spamfilter']['need_word']) {
		$flag = false;
		foreach (explode(',', $freo->config['plugin']['trackback_spamfilter']['need_word']) as $word) {
			if (preg_match('/' . preg_quote($word, '/') . '/', $_POST['excerpt'])) {
				$flag = true;
			}
		}
		if (!$flag) {
			$errors[] = 'Required Word Not Exist (excerpt)';
		}
	}
	if ($freo->config['plugin']['trackback_spamfilter']['need_multibyte'] and strlen($_POST['excerpt']) == mb_strlen($_POST['excerpt'])) {
		$errors[] = 'No Multibyte Character (excerpt)';
	}
	if ($freo->config['plugin']['trackback_spamfilter']['need_link']) {
		$info = parse_url($_POST['url']);

		$request  = "GET " . $info['path'] . " HTTP/1.0\r\n";
		$request .= "Host: " . $info['host'] . "\r\n";
		$request .= "User-Agent: freo\r\n";
		$request .= "\r\n";

		$data = '';

		if ($sock = fsockopen($info['host'], 80)) {
			fputs($sock, $request);
			while (!feof($sock)) {
				$data .= fgets($sock);
			}
			fclose($sock);
		} else {
			$errors[] = 'Socket error';
		}

		if (!preg_match('/' . preg_quote(FREO_HTTP_URL, '/') . '/', $data)) {
			$errors[] = 'URL of This Site is Not Found in ' . $_POST['url'];
		}
	}

	if ($errors) {
		$freo->smarty->assign('message', $errors[0]);

		freo_output('internals/trackback/error.xml');

		exit;
	}

	return;
}

?>
