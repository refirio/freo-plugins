<?php

/*********************************************************************

 パスワード認証プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_page_password()
{
	global $freo;

	//リクエストメソッドに応じた処理を実行
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		//パスワード認証
		if ($_POST['plugin']['password']['password'] == $freo->config['plugin']['password']['password']) {
			$_SESSION['plugin']['password']['approved'] = true;

			if (isset($_POST['plugin']['password']['session'])) {
				$session = md5(uniqid(rand(), true));

				if (touch(FREO_FILE_DIR . 'plugins/password/' . $session)) {
					chmod(FREO_FILE_DIR . 'plugins/password/' . $session, 0606);
				} else {
					freo_error('ログイン情報を保持できません。');
				}

				freo_setcookie('plugin[password][session]', $session, time() + FREO_COOKIE_EXPIRE);
			}

			freo_redirect('default');
		} else {
			$freo->smarty->append('errors', 'パスワードが違います。');
		}
	}

	//認証解除
	if (!empty($_SESSION['plugin']['password']['approved']) and ((isset($_GET['plugin']['password']['session']) and $_GET['plugin']['password']['session'] == 'logout') or (isset($_POST['plugin']['password']['session']) and $_POST['plugin']['password']['session'] == 'logout'))) {
		if (isset($_COOKIE['plugin']['password']['session'])) {
			unlink(FREO_FILE_DIR . 'plugins/password/' . $_COOKIE['plugin']['password']['session']);

			freo_setcookie('plugin[password][session]', null);
		}

		$_SESSION['plugin']['password']['approved'] = null;
	}

	//データ割当
	$freo->smarty->assign(array(
		'token' => freo_token('create')
	));

	return;
}

?>
