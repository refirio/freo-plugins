<?php

/*********************************************************************

 Twitterフレンド限定公開プラグイン (2012/11/01)

 Copyright(C) 2009-2012 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_page_twitter_friends()
{
	global $freo;

	if (empty($_SESSION['plugin']['page_twitter_friends']['authenticated'])) {
		//外部ファイル読み込み
		set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/' . FREO_MAIN_DIR . 'PEAR/');
		require_once 'HTTP/OAuth/Consumer.php';

		//OAuth
		$consumer = new HTTP_OAuth_Consumer(FREO_PLUGIN_PAGE_TWITTER_FRIENDS_CONSUMER_KEY, FREO_PLUGIN_PAGE_TWITTER_FRIENDS_CONSUMER_SECRET);

		//認証
		if ($_REQUEST['freo']['work'] == 'auth') {
			if (isset($_GET['denied'])) {
				freo_redirect('page_twitter_friends');
			} elseif (isset($_GET['oauth_verifier'])) {
				$token        = null;
				$token_secret = null;

				try {
					$consumer->setToken($_SESSION['plugin']['page_twitter_friends']['request_token']);
					$consumer->setTokenSecret($_SESSION['plugin']['page_twitter_friends']['request_token_secret']);
					$consumer->getAccessToken('http://api.twitter.com/oauth/access_token', $_GET['oauth_verifier']);

					$token        = $consumer->getToken();
					$token_secret = $consumer->getTokenSecret();
				} catch (Exception $e) {
					freo_error($e->getMessage());
				}

				if ($token and $token_secret) {
					//フレンドのIDを取得
					$friends = array();
					$cursor  = -1;

					while (1) {
						$response = $consumer->sendRequest('http://api.twitter.com/1/friends/ids.xml', array('screen_name' => $freo->config['plugin']['page_twitter_friends']['screen_name'], 'cursor' => $cursor), 'GET');

						$xml = simplexml_load_string($response->getBody());

						if ($xml->error) {
							if ($xml->error == 'Not authorized') {
								break;
							}

							freo_error($xml->error);
						}

						foreach ($xml->ids->id as $id) {
							$friends[(string)$id] = true;
						}

						$cursor = $xml->next_cursor;

						if ($cursor == 0) {
							break;
						}
					}

					//ユーザー情報を取得
					$response = $consumer->sendRequest('http://api.twitter.com/1/account/verify_credentials.xml', array(), 'GET');

					$xml = simplexml_load_string($response->getBody());

					if ($xml->error) {
						freo_error($xml->error);
					}

					$id = (string)$xml->id;

					//結果を保持
					if (isset($friends[$id])) {
						$_SESSION['plugin']['page_twitter_friends']['authenticated'] = true;
					} else {
						freo_redirect('page_twitter_friends?error=1');
					}

					//リダイレクト
					if (empty($_SESSION['plugin']['page_twitter_friends']['page'])) {
						freo_redirect();
					} else {
						freo_redirect($_SESSION['plugin']['page_twitter_friends']['page']);
					}
				}
			} else {
				try {
					$consumer->getRequestToken('http://api.twitter.com/oauth/request_token', $freo->core['http_file'] . '/page_twitter_friends/auth');

					$_SESSION['plugin']['page_twitter_friends']['request_token']        = $consumer->getToken();
					$_SESSION['plugin']['page_twitter_friends']['request_token_secret'] = $consumer->getTokenSecret();
				} catch (Exception $e) {
					freo_error($e->getMessage());
				}

				freo_redirect($consumer->getAuthorizeUrl('http://api.twitter.com/oauth/authenticate'));
			}

			exit;
		}
	} else {
		freo_redirect('page');
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
