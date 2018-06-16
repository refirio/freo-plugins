<?php

/*********************************************************************

 フィルター認証確認プラグイン (2013/01/10)

 Copyright(C) 2009-2013 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_filter_confirm()
{
	global $freo;

	//フィルターチェック
	if ($freo->user['authority'] == 'root' or $freo->user['authority'] == 'author' or (!$freo->config['entry']['filter'] and !$freo->config['page']['filter'] and !$freo->config['media']['filter'])) {
		freo_redirect('default');
	}

	//入力データ確認
	if (empty($_GET['filter_id']) or (empty($_GET['entry_id']) and empty($_GET['page_id']) and empty($_GET['media_path']))) {
		freo_redirect('filter/default?error=1', true);
	}

	//ワンタイムトークン確認
	if (!freo_token('check')) {
		freo_redirect('filter/default?error=1', true);
	}

	//データ登録
	freo_setcookie('filter[' . $_GET['filter_id'] . ']', true, time() + FREO_COOKIE_EXPIRE);

	$_SESSION['filter'][$_GET['filter_id']] = true;

	//ログ記録
	freo_log('フィルターを設定しました。');

	//元の記事へ移動
	if (!empty($_GET['entry_id'])) {
		freo_redirect('view/' . $_GET['entry_id']);
	} elseif (!empty($_GET['page_id'])) {
		freo_redirect('page/' . $_GET['page_id']);
	} elseif (!empty($_GET['media_path'])) {
		freo_redirect('file/media/' . $_GET['media_path']);
	}

	return;
}

?>
