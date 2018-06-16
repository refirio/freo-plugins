<?php

/*********************************************************************

 Twitterフレンド限定公開プラグイン (2014/01/22)

 Copyright(C) 2009-2014 freo.jp

*********************************************************************/

//外部ファイル読み込み
set_include_path(get_include_path() . PATH_SEPARATOR . getcwd() . '/' . FREO_MAIN_DIR . 'PEAR/');
require_once 'HTTP/OAuth/Consumer.php';

/* メイン処理 */
function freo_page_twitter_friends()
{
	global $freo;

	//証明書の確認を無効化
	$http_request = new HTTP_Request2();
	$http_request->setConfig('ssl_verify_peer', false);

	$consumer_request = new HTTP_OAuth_Consumer_Request();
	$consumer_request->accept($http_request);

	//OAuth
	$consumer = new HTTP_OAuth_Consumer(FREO_PLUGIN_TWITTER_FRIENDS_CONSUMER_KEY, FREO_PLUGIN_TWITTER_FRIENDS_CONSUMER_SECRET);
	$consumer->accept($consumer_request);

	//認証
	if (isset($_GET['denied'])) {
		freo_error('認証できませんでした。');
	} elseif (isset($_GET['oauth_verifier'])) {
		$token        = null;
		$token_secret = null;

		try {
			$consumer->setToken($_SESSION['plugin']['twitter_friends']['request_token']);
			$consumer->setTokenSecret($_SESSION['plugin']['twitter_friends']['request_token_secret']);
			$consumer->getAccessToken('https://api.twitter.com/oauth/access_token', $_GET['oauth_verifier']);

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
				$response = $consumer->sendRequest('https://api.twitter.com/1.1/friends/ids.json', array('screen_name' => $freo->config['plugin']['twitter_friends']['screen_name'], 'cursor' => $cursor), 'GET');

				$json = json_decode($response->getBody());

				if (isset($json->errors)) {
					if ($json->errors[0]->message == 'Not authorized') {
						break;
					}

					freo_error($json->errors[0]->message);
				}

				foreach ($json->ids as $id) {
					$friends[$id] = true;
				}

				$cursor = $json->next_cursor_str;

				if ($cursor == 0) {
					break;
				}
			}

			//ユーザー情報を取得
			$response = $consumer->sendRequest('https://api.twitter.com/1.1/account/verify_credentials.json', array(), 'GET');

			$json = json_decode($response->getBody());

			if (isset($json->errors)) {
				freo_error($json->errors[0]->message);
			}

			$id = $json->id_str;

			//結果を保持
			if (isset($friends[$id])) {
				$freo->user['groups'][] = $freo->config['plugin']['twitter_friends']['group'];

				$_SESSION['freo']['user'] = $freo->user;

				$error = false;
			} else {
				$error = true;
			}

			//元の記事へ移動
			if (!empty($_SESSION['plugin']['twitter_friends']['entry_id'])) {
				freo_redirect('view/' . $_SESSION['plugin']['twitter_friends']['entry_id'] . ($error ? '?error=1' : ''));
			} elseif (!empty($_SESSION['plugin']['twitter_friends']['page_id'])) {
				freo_redirect('page/' . $_SESSION['plugin']['twitter_friends']['page_id'] . ($error ? '?error=1' : ''));
			} elseif (!empty($_SESSION['plugin']['twitter_friends']['media_path'])) {
				freo_redirect('file/media/' . $_SESSION['plugin']['twitter_friends']['media_path'] . ($error ? '?error=1' : ''));
			}
		}
	} else {
		try {
			$consumer->getRequestToken('https://api.twitter.com/oauth/request_token', $freo->core['http_file'] . '/twitter_friends');

			$_SESSION['plugin']['twitter_friends']['request_token']        = $consumer->getToken();
			$_SESSION['plugin']['twitter_friends']['request_token_secret'] = $consumer->getTokenSecret();
		} catch (Exception $e) {
			freo_error($e->getMessage());
		}

		$_SESSION['plugin']['twitter_friends']['entry_id']   = $_GET['entry_id'];
		$_SESSION['plugin']['twitter_friends']['page_id']    = $_GET['page_id'];
		$_SESSION['plugin']['twitter_friends']['media_path'] = $_GET['media_path'];

		freo_redirect($consumer->getAuthorizeUrl('https://api.twitter.com/oauth/authenticate'));
	}

	return;
}

?>
