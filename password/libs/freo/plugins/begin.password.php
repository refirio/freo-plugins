<?php

/*********************************************************************

 直リンク防止プラグイン (2010/09/01)

 Copyright(C) 2009-2010 freo.jp

*********************************************************************/

/* メイン処理 */
function freo_begin_password()
{
	global $freo;

	if ($_REQUEST['freo']['mode'] == 'password') {
		return;
	}
	if (empty($_SESSION['plugin']['password']['approved'])) {
		//ログイン状態を復元
		if (isset($_COOKIE['plugin']['password']['session'])) {
			$session_dir = FREO_FILE_DIR . 'plugins/password/';

			if ($dir = scandir($session_dir)) {
				foreach ($dir as $entry) {
					if (is_file($session_dir . $entry) and time() - filemtime($session_dir . $entry) > FREO_COOKIE_EXPIRE) {
						unlink($session_dir . $entry);
					}
				}
			} else {
				freo_error('ログイン情報格納ディレクトリを開けません。');
			}

			if (file_exists($session_dir . $_COOKIE['plugin']['password']['session'])) {
				$_SESSION['plugin']['password']['approved'] = true;

				$session = md5(uniqid(rand(), true));

				if (rename($session_dir . $_COOKIE['plugin']['password']['session'], $session_dir . $session) and touch($session_dir . $session)) {
					freo_setcookie('plugin[password][session]', $session, time() + FREO_COOKIE_EXPIRE);
				} else {
					freo_error('ログイン情報を保持できません。');
				}
			}
		}
	}
	if (!empty($_SESSION['plugin']['password']['approved'])) {
		return;
	}

	freo_redirect('password');

	return;
}

?>
