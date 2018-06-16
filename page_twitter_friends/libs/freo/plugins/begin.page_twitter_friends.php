<?php

/*********************************************************************

 Twitterフレンド限定公開プラグイン (2012/01/03)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_page_twitter_friends()
{
	global $freo;

	$parameters = implode('/', $freo->parameters);

	if ($parameters == 'page' or $parameters == 'file/page') {
		return;
	} elseif (preg_match('/^page\//', $parameters) and !preg_match('/^page\/' . preg_quote($freo->config['plugin']['page_twitter_friends']['page'], '/') . '/', $parameters)) {
		return;
	} elseif (preg_match('/^file\/page\//', $parameters) and !preg_match('/^file\/page\/' . preg_quote($freo->config['plugin']['page_twitter_friends']['page'], '/') . '/', $parameters)) {
		return;
	}

	if ($freo->user['id']) {
		return;
	}

	if (empty($_SESSION['plugin']['page_twitter_friends']['authenticated'])) {
		$_SESSION['plugin']['page_twitter_friends']['page'] = implode('/', $freo->parameters);
	} else {
		return;
	}

	freo_redirect('page_twitter_friends');

	return;
}

?>
