<?php

/*********************************************************************

 拍手コメントスパム対策プラグイン (2012/10/09)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_clap_spamfilter()
{
	global $freo;

	if ($_SERVER['REQUEST_METHOD'] != 'POST') {
		return;
	}

	if (!isset($_POST['plugin_clap']['text']) or $_POST['plugin_clap']['text'] == '') {
		return;
	}

	if ($freo->config['plugin']['clap_spamfilter']['deny_host']) {
		$remote_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

		foreach (explode(',', $freo->config['plugin']['clap_spamfilter']['deny_host']) as $host) {
			if (preg_match('/' . preg_quote($host, '/') . '/', $remote_host)) {
				$freo->smarty->append('errors', 'ホスト「' . $remote_host . '」からのアクセスは禁止されています。');
			}
		}
	}
	if ($freo->config['plugin']['clap_spamfilter']['deny_word']) {
		foreach (explode(',', $freo->config['plugin']['clap_spamfilter']['deny_word']) as $word) {
			if (preg_match('/' . preg_quote($word, '/') . '/', $_POST['plugin_clap']['text'])) {
				$freo->smarty->append('errors', '「' . $word . '」は投稿禁止ワードに設定されています。');
			}
		}
	}
	if ($freo->config['plugin']['clap_spamfilter']['need_word']) {
		$flag = false;
		foreach (explode(',', $freo->config['plugin']['clap_spamfilter']['need_word']) as $word) {
			if (preg_match('/' . preg_quote($word, '/') . '/', $_POST['plugin_clap']['text'])) {
				$flag = true;
			}
		}
		if (!$flag) {
			$freo->smarty->append('errors', '投稿必須ワードが含まれていません。');
		}
	}
	if ($freo->config['plugin']['clap_spamfilter']['need_multibyte'] and strlen($_POST['plugin_clap']['text']) == mb_strlen($_POST['plugin_clap']['text'])) {
		$freo->smarty->append('errors', '半角英数字のみの拍手コメントは投稿できません。');
	}
	if (substr_count($_POST['plugin_clap']['text'], 'http://') >= $freo->config['plugin']['clap_spamfilter']['max_url']) {
		$freo->smarty->append('errors', '本文にURLを' . $freo->config['plugin']['clap_spamfilter']['max_url'] . '個以上書くことはできません。');
	}

	return;
}

?>
